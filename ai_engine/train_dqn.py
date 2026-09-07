import random
import numpy as np
import torch
import torch.nn as nn
import torch.optim as optim
from collections import deque

from dqn_model import DynamicDQN, USER_FEATURE_DIM, ROOM_FEATURE_DIM
from utils import RoomEnv, build_user_vector, build_candidate_room_vector, calculate_reward

# Cố định seed cho tính lặp lại khoa học trong báo cáo
random.seed(42)
np.random.seed(42)
torch.manual_seed(42)

def generate_synthetic_rooms(num_rooms=30):
    """
    Sinh tập phòng trọ mô phỏng phong phú dựa trên phân bố thực tế tại TP.HCM
    (Sử dụng khi train độc lập hoặc kết hợp dữ liệu DB)
    """
    rooms = []
    utilities = {}
    for i in range(1, num_rooms + 1):
        price = random.choice([
            1_800_000, 2_200_000, 2_500_000, 3_000_000, 3_500_000, 
            4_000_000, 4_500_000, 5_500_000, 7_000_000, 9_000_000
        ])
        area = random.choice([15, 18, 20, 22, 25, 28, 32, 35, 45, 55])
        days_empty = random.choice([2, 5, 12, 25, 45, 75, 110, 150, 180])
        floor = random.choice([1, 2, 3, 4, 5])
        status = "trong" if random.random() > 0.1 else "dang_thue"

        rooms.append({
            "id": i,
            "phong_id": i,
            "gia_thue": float(price),
            "gia": float(price),
            "dien_tich": float(area),
            "days_empty": float(days_empty),
            "tang": floor,
            "trang_thai": status,
            "ten_day_tro": f"Dãy trọ Nhà Xanh #{((i-1)//5)+1}"
        })

        # Ngẫu nhiên tiện ích có sẵn
        # 1: Máy lạnh, 2: Máy giặt, 3: Giường, 4: Bàn học, 5: Tủ quần áo
        utils_count = random.randint(1, 4)
        utilities[i] = random.sample([1, 2, 3, 4, 5], utils_count)

    return rooms, utilities


def generate_random_tenant_persona():
    """
    Sinh các nhóm chân dung khách thuê tiêu biểu:
    - Nhóm 1: Sinh viên tìm phòng giá rẻ
    - Nhóm 2: Nhân viên văn phòng cần tiện nghi
    - Nhóm 3: Gia đình / Nhóm bạn ở chung
    """
    group = random.choices(["student", "worker", "family"], weights=[0.45, 0.40, 0.15])[0]
    
    if group == "student":
        max_price = random.randint(18, 35) * 100_000  # 1.8tr - 3.5tr
        area = random.randint(15, 22)
        utilities = random.sample([1, 3, 4], k=random.randint(1, 3))
    elif group == "worker":
        max_price = random.randint(35, 65) * 100_000  # 3.5tr - 6.5tr
        area = random.randint(22, 35)
        utilities = random.sample([1, 2, 3, 5], k=random.randint(2, 4))
    else:
        max_price = random.randint(60, 110) * 100_000  # 6tr - 11tr
        area = random.randint(35, 60)
        utilities = random.sample([1, 2, 3, 4, 5], k=random.randint(3, 5))

    return {
        "max_price": max_price,
        "area": area,
        "utilities": utilities,
        "budget_flexibility": random.uniform(0.05, 0.20)
    }


def train_dqn_model(episodes=600, batch_size=32, lr=0.001):
    print("=" * 60)
    print("BAT DAU HUAN LUYEN MO HINH DUELING DQN (RL)")
    print("De tai: Toi uu hoa phan bo phong tro (KLCN_TH071)")
    print("=" * 60)

    # Thu ket noi DB, neu khong co MySQL thi dung tap mo phong chuan
    try:
        from db import load_posts, load_all_utilities
        df = load_posts()
        if len(df) > 0:
            posts = df.to_dict(orient="records")
            utilities = load_all_utilities()
            rooms = []
            for p in posts:
                rooms.append({
                    "id": p["phong_id"],
                    "phong_id": p["phong_id"],
                    "gia_thue": float(p.get("gia") or p.get("gia_phong") or 0),
                    "gia": float(p.get("gia") or p.get("gia_phong") or 0),
                    "dien_tich": float(p.get("dien_tich") or 20),
                    "days_empty": float(p.get("days_empty") or 10),
                    "tang": int(p.get("tang") or 1),
                    "trang_thai": p.get("trang_thai", "trong")
                })
            print(f"[OK] Da tai thanh cong {len(rooms)} phong tu Database MySQL.")
        else:
            raise Exception("DB rong")
    except Exception as e:
        print(f"[INFO] Su dung tap du lieu 35 phong tro mau da dang ({e}).")
        rooms, utilities = generate_synthetic_rooms(num_rooms=35)

    policy_net = DynamicDQN(user_dim=USER_FEATURE_DIM, room_dim=ROOM_FEATURE_DIM)
    target_net = DynamicDQN(user_dim=USER_FEATURE_DIM, room_dim=ROOM_FEATURE_DIM)
    target_net.load_state_dict(policy_net.state_dict())
    target_net.eval()

    optimizer = optim.Adam(policy_net.parameters(), lr=lr, weight_decay=1e-5)
    loss_fn = nn.MSELoss()

    # Experience Replay: Lưu (user_vec, room_vec, reward, next_user_vec, next_room_vec, done)
    memory = deque(maxlen=8000)

    gamma = 0.95
    eps_start = 1.0
    eps_end = 0.05
    eps_decay = 250

    history_rewards = []
    history_losses = []

    for ep in range(1, episodes + 1):
        # Mỗi episode sinh 1 nhu cầu khách thuê mới (tính tổng quát hóa cực cao)
        user = generate_random_tenant_persona()
        favorites = random.sample([r["id"] for r in rooms], k=random.randint(0, 3))

        env = RoomEnv(rooms, utilities, favorites)
        user_vec, room_vecs = env.reset(user)

        # Epsilon-greedy exploration
        eps = eps_end + (eps_start - eps_end) * np.exp(-1.0 * ep / eps_decay)

        num_candidates = len(rooms)
        if random.random() < eps:
            action_idx = random.randrange(num_candidates)
        else:
            with torch.no_grad():
                u_t = torch.tensor(user_vec, dtype=torch.float32).unsqueeze(0)
                r_t = torch.tensor(room_vecs, dtype=torch.float32).unsqueeze(0)
                q_vals = policy_net(u_t, r_t)  # shape (1, num_candidates)
                action_idx = int(torch.argmax(q_vals, dim=1).item())

        # Bước chuyển trạng thái và nhận phần thưởng đa mục tiêu
        (next_u_vec, next_r_vecs), reward, done, _ = env.step(action_idx)

        # Lưu trải nghiệm của hành động vừa thực hiện
        chosen_room_vec = room_vecs[action_idx]
        memory.append((user_vec, chosen_room_vec, reward, done))

        history_rewards.append(reward)

        # Huấn luyện theo batch khi replay memory đủ lớn
        if len(memory) >= batch_size:
            batch = random.sample(memory, batch_size)
            b_u, b_r, b_rew, b_done = zip(*batch)

            b_u_t = torch.tensor(np.array(b_u), dtype=torch.float32)
            b_r_t = torch.tensor(np.array(b_r), dtype=torch.float32)
            b_rew_t = torch.tensor(np.array(b_rew), dtype=torch.float32).unsqueeze(1)

            # Dự đoán Q-value hiện tại cho cặp (user, room)
            current_q = policy_net(b_u_t, b_r_t)

            # Target Q-value: Do bài toán đề xuất theo từng phiên (Done = True), Target Q = Reward
            target_q = b_rew_t

            loss = loss_fn(current_q, target_q)
            optimizer.zero_grad()
            loss.backward()
            nn.utils.clip_grad_norm_(policy_net.parameters(), max_norm=1.0)
            optimizer.step()

            history_losses.append(loss.item())

        # Cập nhật target network định kỳ
        if ep % 30 == 0:
            target_net.load_state_dict(policy_net.state_dict())

        # In tiến trình
        if ep % 50 == 0 or ep == 1:
            avg_rew = np.mean(history_rewards[-50:])
            avg_loss = np.mean(history_losses[-50:]) if history_losses else 0
            print(f"Episode {ep:03d}/{episodes} | Epsilon: {eps:.3f} | Avg Reward (50 ep): {avg_rew:+.3f} | Loss: {avg_loss:.4f}")

    # Luu trong so mo hinh
    torch.save(policy_net.state_dict(), "dqn_room.pt")
    print("=" * 60)
    print(f"[SUCCESS] Huan luyen thanh cong {episodes} episodes!")
    print("Da luu trong so mang Dueling DQN toi uu vao: dqn_room.pt")
    print(f"Reward trung binh cuoi ky: {np.mean(history_rewards[-50:]):+.3f}")
    print("=" * 60)

    return policy_net

if __name__ == "__main__":
    train_dqn_model(episodes=500)
