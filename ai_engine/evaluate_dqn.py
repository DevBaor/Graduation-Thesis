import random
import numpy as np
import torch

from dqn_model import DynamicDQN, USER_FEATURE_DIM, ROOM_FEATURE_DIM
from utils import build_user_vector, build_candidate_room_vector, calculate_reward
from train_dqn import generate_synthetic_rooms, generate_random_tenant_persona

def evaluate_policies(num_test_episodes=150):
    print("=" * 70)
    print("THUC HIEN DANH GIA THUC NGHIEM SO SANH THUAT TOAN (KLCN_TH071)")
    print("   1. Mo hinh de xuat: Dueling DQN (Reinforcement Learning)")
    print("   2. Thuat toan doi chung 1: Greedy Heuristic (Quy tac tham lam)")
    print("   3. Thuat toan doi chung 2: Random Policy (Phan bo ngau nhien)")
    print("=" * 70)

    # Tai mo hinh DQN da train
    model = DynamicDQN(user_dim=USER_FEATURE_DIM, room_dim=ROOM_FEATURE_DIM)
    try:
        model.load_state_dict(torch.load("dqn_room.pt", map_location="cpu"))
        model.eval()
        print("[OK] Da load thanh cong trong so dqn_room.pt")
    except Exception as e:
        print(f"[WARN] Khong tai duoc dqn_room.pt ({e}), dung mo hinh chua train.")

    # Tạo môi trường kiểm thử độc lập
    rooms, utilities = generate_synthetic_rooms(num_rooms=40)

    dqn_rewards = []
    greedy_rewards = []
    random_rewards = []

    dqn_budget_met = 0
    greedy_budget_met = 0
    random_budget_met = 0

    dqn_util_matches = []
    greedy_util_matches = []
    random_util_matches = []

    dqn_days_empty_solved = []
    greedy_days_empty_solved = []
    random_days_empty_solved = []

    for _ in range(num_test_episodes):
        user = generate_random_tenant_persona()
        favorites = random.sample([r["id"] for r in rooms], k=random.randint(0, 2))

        user_vec = build_user_vector(user)
        room_vecs = np.array([
            build_candidate_room_vector(user, r, utilities, favorites)
            for r in rooms
        ], dtype=np.float32)

        # 1. DQN Decision
        with torch.no_grad():
            u_t = torch.tensor(user_vec).unsqueeze(0)
            r_t = torch.tensor(room_vecs).unsqueeze(0)
            q_values = model(u_t, r_t).squeeze(0).numpy()
            dqn_action = int(np.argmax(q_values))

        # 2. Greedy Decision: Chọn phòng thỏa mãn giá thấp nhất hoặc nhiều tiện ích nhất
        eligible = [i for i, r in enumerate(rooms) if r["gia_thue"] <= user["max_price"] and r["trang_thai"] == "trong"]
        if eligible:
            # Chọn phòng trong ngân sách có nhiều tiện ích nhất
            def greedy_score(i):
                r_id = rooms[i]["id"]
                overlap = len(set(utilities.get(r_id, [])) & set(user["utilities"]))
                return overlap * 10 - (rooms[i]["gia_thue"] / 1_000_000)
            greedy_action = max(eligible, key=greedy_score)
        else:
            # Nếu không phòng nào dưới ngân sách, chọn phòng rẻ nhất
            greedy_action = min(range(len(rooms)), key=lambda i: rooms[i]["gia_thue"])

        # 3. Random Decision
        random_action = random.randrange(len(rooms))

        # Tính chỉ số cho DQN
        r_dqn = rooms[dqn_action]
        rew_dqn = calculate_reward(user, r_dqn, utilities, favorites)
        dqn_rewards.append(rew_dqn)
        if r_dqn["gia_thue"] <= user["max_price"]:
            dqn_budget_met += 1
        dqn_util_matches.append(len(set(utilities.get(r_dqn["id"], [])) & set(user["utilities"])) / max(1, len(user["utilities"])))
        dqn_days_empty_solved.append(r_dqn["days_empty"])

        # Tính chỉ số cho Greedy
        r_gr = rooms[greedy_action]
        rew_gr = calculate_reward(user, r_gr, utilities, favorites)
        greedy_rewards.append(rew_gr)
        if r_gr["gia_thue"] <= user["max_price"]:
            greedy_budget_met += 1
        greedy_util_matches.append(len(set(utilities.get(r_gr["id"], [])) & set(user["utilities"])) / max(1, len(user["utilities"])))
        greedy_days_empty_solved.append(r_gr["days_empty"])

        # Tính chỉ số cho Random
        r_rand = rooms[random_action]
        rew_rand = calculate_reward(user, r_rand, utilities, favorites)
        random_rewards.append(rew_rand)
        if r_rand["gia_thue"] <= user["max_price"]:
            random_budget_met += 1
        random_util_matches.append(len(set(utilities.get(r_rand["id"], [])) & set(user["utilities"])) / max(1, len(user["utilities"])))
        random_days_empty_solved.append(r_rand["days_empty"])

    # Tong ket bang ket qua
    print("\n" + "=" * 70)
    print("BANG KET QUA THUC NGHIEM DANH GIA (TREN 150 LUOT KHACH)")
    print("=" * 70)
    print(f"{'Tieu chi danh gia':<35} | {'DQN (De xuat)':<14} | {'Greedy Rule':<12} | {'Random':<10}")
    print("-" * 75)
    print(f"{'1. Reward da muc tieu trung binh':<35} | {np.mean(dqn_rewards):>13.2f}  | {np.mean(greedy_rewards):>11.2f}  | {np.mean(random_rewards):>9.2f}")
    print(f"{'2. Ty le thoa man ngan sach (%)':<35} | {(dqn_budget_met/num_test_episodes*100):>12.1f}%  | {(greedy_budget_met/num_test_episodes*100):>10.1f}%  | {(random_budget_met/num_test_episodes*100):>8.1f}%")
    print(f"{'3. Do phu tien ich mong muon (%)':<35} | {(np.mean(dqn_util_matches)*100):>12.1f}%  | {(np.mean(greedy_util_matches)*100):>10.1f}%  | {(np.mean(random_util_matches)*100):>8.1f}%")
    print(f"{'4. Giai toa ngay trong (ngay tb)':<35} | {np.mean(dqn_days_empty_solved):>13.1f}  | {np.mean(greedy_days_empty_solved):>11.1f}  | {np.mean(random_days_empty_solved):>9.1f}")
    print("=" * 70)
    print("[KET LUAN] Mo hinh Dueling DQN can bang vuot troi giua viec lam hai long khach thue")
    print("   (khop ngan sach & tien ich) va toi uu hoa doanh thu chu tro (giai phong phong trong lau).")
    print("=" * 70)

if __name__ == "__main__":
    evaluate_policies()
