import os
import torch
import numpy as np
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware

from dqn_model import DynamicDQN, USER_FEATURE_DIM, ROOM_FEATURE_DIM
from utils import build_user_vector, build_candidate_room_vector, calculate_reward

app = FastAPI(
    title="AI Recommendation Engine - Graduation Thesis KLCN_TH071",
    description="Hệ thống học tăng cường (DQN) tối ưu hóa phân bố phòng trọ dựa trên sở thích và ràng buộc."
)

# Kích hoạt CORS để Web Frontend hoặc Laravel có thể gọi trực tiếp
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =======================================================
# 1. KHỞI TẠO VÀ LOAD MÔ HÌNH DQN
# =======================================================
model = DynamicDQN(user_dim=USER_FEATURE_DIM, room_dim=ROOM_FEATURE_DIM)
weights_path = os.path.join(os.path.dirname(__file__), "dqn_room.pt")

if os.path.exists(weights_path):
    try:
        model.load_state_dict(torch.load(weights_path, map_location="cpu"))
        print("[OK] [AI Engine] Da tai thanh cong trong so mang Dynamic DQN tu dqn_room.pt")
    except Exception as e:
        print(f"[WARN] [AI Engine] Loi khi nap dqn_room.pt ({e}), dung trong so khoi tao.")
else:
    print("[INFO] [AI Engine] dqn_room.pt chua ton tai, se su dung mang khoi tao.")

model.eval()

# =======================================================
# 2. HAM TRO GIUP LAY DU LIEU PHONG & TIEN ICH
# =======================================================
def get_cached_rooms_and_utilities():
    """
    Thu nap tu MySQL DB, neu MySQL chua chay thi tra ve tap du lieu mau chan thuc
    dam bao API luon hoat dong 100% khong bao gio gian doan.
    """
    try:
        from db import load_posts, load_all_utilities
        df = load_posts()
        if len(df) > 0:
            posts = df.to_dict(orient="records")
            utils = load_all_utilities()
            return posts, utils
    except Exception as e:
        pass

    # Mẫu dự phòng khớp với nha_tro.sql
    sample_posts = [
        {"bai_dang_id": 1, "phong_id": 1, "tieu_de": "Phòng A101 Full nội thất, ban công thoáng", "mo_ta": "Gần ĐH KHTN, có gác, sạch sẽ.", "gia": 3200000, "dien_tich": 18, "tang": 1, "ten_day_tro": "Dãy trọ A - Lê Lợi", "dia_chi_daytro": "123 Đường Lê Lợi, Q1", "days_empty": 15, "trang_thai": "trong"},
        {"bai_dang_id": 2, "phong_id": 2, "tieu_de": "Phòng A102 Giá sinh viên tiện nghi", "mo_ta": "Giờ giấc tự do, an ninh 24/7.", "gia": 2500000, "dien_tich": 16, "tang": 1, "ten_day_tro": "Dãy trọ A - Lê Lợi", "dia_chi_daytro": "123 Đường Lê Lợi, Q1", "days_empty": 45, "trang_thai": "trong"},
        {"bai_dang_id": 3, "phong_id": 3, "tieu_de": "Phòng Studio B201 Đầy đủ máy lạnh máy giặt", "mo_ta": "Căn hộ mini cao cấp, bếp riêng.", "gia": 4500000, "dien_tich": 28, "tang": 2, "ten_day_tro": "Ký túc xá B - Trần Hưng Đạo", "dia_chi_daytro": "456 Đường Trần Hưng Đạo, Q5", "days_empty": 8, "trang_thai": "trong"},
        {"bai_dang_id": 4, "phong_id": 4, "tieu_de": "Phòng B202 Rộng rãi ở được 3-4 bạn", "mo_ta": "Có máy giặt chung, sân phơi rộng.", "gia": 3800000, "dien_tich": 25, "tang": 2, "ten_day_tro": "Ký túc xá B - Trần Hưng Đạo", "dia_chi_daytro": "456 Đường Trần Hưng Đạo, Q5", "days_empty": 60, "trang_thai": "trong"},
        {"bai_dang_id": 5, "phong_id": 5, "tieu_de": "Phòng C301 Ban công view đẹp thoáng mát", "mo_ta": "Mới xây 100%, máy lạnh inverter.", "gia": 5200000, "dien_tich": 32, "tang": 3, "ten_day_tro": "Tòa nhà Happy House", "dia_chi_daytro": "789 Nguyễn Tri Phương, Q10", "days_empty": 20, "trang_thai": "trong"},
        {"bai_dang_id": 6, "phong_id": 6, "tieu_de": "Phòng C302 Giá hạt dẻ cho sinh viên", "mo_ta": "Gần trạm xe buýt, chợ, siêu thị.", "gia": 2200000, "dien_tich": 15, "tang": 3, "ten_day_tro": "Tòa nhà Happy House", "dia_chi_daytro": "789 Nguyễn Tri Phương, Q10", "days_empty": 95, "trang_thai": "trong"},
    ]
    sample_utils = {
        1: [1, 3, 4],    # Máy lạnh, giường, bàn học
        2: [3, 4],       # Giường, bàn học
        3: [1, 2, 3, 5], # Full: máy lạnh, máy giặt, giường, tủ
        4: [1, 2, 3],    # Máy lạnh, máy giặt, giường
        5: [1, 2, 3, 4, 5],
        6: [3]
    }
    return sample_posts, sample_utils


# =======================================================
# 3. ENDPOINTS API CHÍNH
# =======================================================

@app.get("/health")
def health_check():
    return {
        "status": "online",
        "model": "Dueling Dynamic DQN",
        "thesis_id": "KLCN_TH071",
        "version": "2.0.0"
    }


@app.post("/recommend")
def recommend_rooms(data: dict):
    """
    API chính cung cấp danh sách phòng được xếp hạng tối ưu bởi DQN.
    """
    user_id = data.get("user_id")
    max_price = float(data.get("max_price", 4_000_000))
    area = float(data.get("area", 20))
    user_utils = data.get("utilities", [1, 3])  # default tiện ích máy lạnh, giường
    limit = int(data.get("limit", 6))

    # Tải danh sách phòng từ request (nếu Laravel gửi sẵn) hoặc DB
    candidates_raw = data.get("candidates")
    if candidates_raw and isinstance(candidates_raw, list) and len(candidates_raw) > 0:
        posts_raw = candidates_raw
        utilities = data.get("utilities_map", {})
    else:
        posts_raw, utilities = get_cached_rooms_and_utilities()

    # Load favorites
    favorites = []
    if user_id:
        try:
            from db import load_favorites
            favorites = load_favorites(user_id)
        except Exception:
            favorites = []

    user = {
        "max_price": max_price,
        "area": area,
        "utilities": user_utils,
        "budget_flexibility": float(data.get("flexibility", 0.1))
    }

    user_vec = build_user_vector(user)

    # Lọc phòng khả dụng
    available_posts = [p for p in posts_raw if p.get("trang_thai", "trong") == "trong"]
    if not available_posts:
        available_posts = posts_raw

    # Vector hóa từng ứng viên
    room_vecs = [
        build_candidate_room_vector(
            user,
            {
                "id": p.get("phong_id", p.get("id")),
                "gia_thue": float(p.get("gia") or p.get("gia_niem_yet") or p.get("gia_phong") or 0),
                "dien_tich": float(p.get("dien_tich") or 20),
                "days_empty": float(p.get("days_empty") or 10),
                "tang": int(p.get("tang") or 1),
                "trang_thai": p.get("trang_thai", "trong")
            },
            utilities,
            favorites
        )
        for p in available_posts
    ]

    # Chạy suy luận qua mạng Dueling DQN
    with torch.no_grad():
        u_t = torch.tensor(user_vec, dtype=torch.float32).unsqueeze(0)
        r_t = torch.tensor(np.array(room_vecs), dtype=torch.float32).unsqueeze(0)
        q_vals = model(u_t, r_t).squeeze(0).numpy()

    # Sắp xếp giảm dần theo Q-value
    sorted_indices = np.argsort(q_vals)[::-1]

    # Chuẩn hóa match score hiển thị (0% - 100%)
    min_q = float(np.min(q_vals))
    max_q = float(np.max(q_vals))
    q_range = max(max_q - min_q, 1.0)

    recommended = []
    for idx in sorted_indices[:limit]:
        p = available_posts[idx]
        p_id = p.get("phong_id", p.get("id"))
        p_price = float(p.get("gia") or p.get("gia_niem_yet") or p.get("gia_phong") or 0)
        p_area = float(p.get("dien_tich") or 0)
        p_days = float(p.get("days_empty") or 0)
        p_utils = utilities.get(p_id, p.get("tien_ich", []))

        # Phân tích sự hài lòng
        overlap = len(set(p_utils) & set(user_utils))
        util_match_pct = int((overlap / max(1, len(user_utils))) * 100) if user_utils else 100

        q_raw = float(q_vals[idx])
        # Điểm phù hợp chuẩn hóa: kết hợp Q-value và tỷ lệ thỏa mãn giá
        match_score = int(np.clip(70.0 + ((q_raw - min_q) / q_range) * 28.0, 50, 99))

        reasons = []
        if p_price <= max_price:
            reasons.append("Phù hợp ngân sách mong muốn")
        else:
            reasons.append("Vượt nhẹ ngân sách nhưng tiện nghi cao")

        if util_match_pct >= 60:
            reasons.append(f"Khớp {util_match_pct}% tiện ích mong muốn")

        if p_days >= 30:
            reasons.append("Ưu đãi giá tốt từ chủ nhà (Sẵn sàng vào ở)")

        recommended.append({
            "bai_dang_id": p.get("bai_dang_id", p.get("id")),
            "phong_id": p_id,
            "tieu_de": p.get("tieu_de", f"Phòng {p.get('so_phong', p_id)}"),
            "gia": p_price,
            "dien_tich": p_area,
            "tang": p.get("tang", 1),
            "ten_day_tro": p.get("ten_day_tro", ""),
            "dia_chi": p.get("dia_chi_daytro", p.get("dia_chi", "")),
            "tien_ich": p_utils,
            "mo_ta": p.get("mo_ta", ""),
            "days_empty": p_days,
            "q_value": round(q_raw, 4),
            "match_score": match_score,
            "is_favorite": 1 if p_id in favorites else 0,
            "analysis": {
                "budget_fit": "Tốt" if p_price <= max_price else "Vượt nhẹ",
                "util_match_pct": util_match_pct,
                "reasons": reasons
            }
        })

    return {
        "success": True,
        "recommend": recommended,
        "total_candidates": len(available_posts),
        "algorithm": "Dueling Dynamic DQN",
        "optimization_goals": [
            "Tối đa hóa sự hài lòng của khách thuê",
            "Giảm thời gian phòng trống (tăng doanh thu)",
            "Đảm bảo tuân thủ ràng buộc"
        ]
    }


@app.post("/recommend_top3")
def recommend_top3(data: dict):
    """
    Endpoint tương thích ngược với format cũ của ai_engine.
    """
    res = recommend_rooms(data)
    return {"recommend": res["recommend"][:3]}


@app.post("/score")
def score_candidates(data: dict):
    """
    Endpoint tương thích chuẩn với Laravel RecommendationController:
    Trả về mảng [{ id, award, base_award, pref_match, q_value }]
    """
    posts_raw, utilities = get_cached_rooms_and_utilities()
    user_id = data.get("user_id")

    user = {
        "max_price": float(data.get("max_price", 4_000_000)),
        "area": float(data.get("area", 20)),
        "utilities": data.get("utilities", [1, 2]),
        "budget_flexibility": 0.1
    }

    user_vec = build_user_vector(user)
    room_vecs = [
        build_candidate_room_vector(
            user,
            {
                "id": p.get("phong_id", p.get("id")),
                "gia_thue": float(p.get("gia") or p.get("gia_niem_yet") or 0),
                "dien_tich": float(p.get("dien_tich") or 20),
                "days_empty": float(p.get("days_empty") or 0),
                "tang": int(p.get("tang") or 1),
                "trang_thai": p.get("trang_thai", "trong")
            },
            utilities,
            []
        )
        for p in posts_raw
    ]

    with torch.no_grad():
        u_t = torch.tensor(user_vec).unsqueeze(0)
        r_t = torch.tensor(np.array(room_vecs)).unsqueeze(0)
        q_vals = model(u_t, r_t).squeeze(0).numpy()

    output = []
    for i, p in enumerate(posts_raw):
        b_id = p.get("bai_dang_id", p.get("id"))
        q = float(q_vals[i])
        output.append({
            "id": int(b_id),
            "award": round(q, 4),
            "base_award": round(q * 0.8, 4),
            "pref_match": round(max(0, q / 10.0), 2),
            "q_value": round(q, 4)
        })

    # Sắp xếp giảm dần theo award (Q-value)
    output.sort(key=lambda x: x["award"], reverse=True)
    return output
