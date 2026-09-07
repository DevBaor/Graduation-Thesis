import numpy as np
import torch

# Hằng số chuẩn hóa
MAX_PRICE_NORM = 15_000_000.0  # 15 triệu VNĐ
MAX_AREA_NORM = 80.0           # 80 m2
MAX_DAYS_EMPTY_NORM = 180.0    # 180 ngày trống
NUM_COMMON_UTILS = 5          # 1: Máy lạnh, 2: Máy giặt, 3: Giường, 4: Bàn học, 5: Tủ quần áo

def build_user_vector(user):
    """
    Chuyển đổi yêu cầu khách thuê thành vector 8 chiều:
    [
        max_price_norm (0..1),
        desired_area_norm (0..1),
        util_1 (0|1), util_2 (0|1), util_3 (0|1), util_4 (0|1), util_5 (0|1),
        budget_flexibility (0..1)
    ]
    """
    max_price = float(user.get("max_price", 3_000_000))
    area = float(user.get("area", 20))
    user_utils = set(user.get("utilities", []))
    flexibility = float(user.get("budget_flexibility", 0.1))  # Mức độ sẵn sàng vượt ngân sách

    vec = [
        min(max_price / MAX_PRICE_NORM, 1.5),
        min(area / MAX_AREA_NORM, 1.5),
        1.0 if 1 in user_utils else 0.0,
        1.0 if 2 in user_utils else 0.0,
        1.0 if 3 in user_utils else 0.0,
        1.0 if 4 in user_utils else 0.0,
        1.0 if 5 in user_utils else 0.0,
        flexibility
    ]
    return np.array(vec, dtype=np.float32)


def build_candidate_room_vector(user, room, utilities_map, favorites_list):
    """
    Chuyển đổi 1 phòng ứng viên thành vector 12 chiều tương tác với user:
    [
        price_norm,
        area_norm,
        days_empty_norm,
        floor_norm,
        utility_match_ratio,
        is_favorite,
        price_diff_ratio,      # (room_price - max_price) / max_price
        area_diff_ratio,       # (room_area - desired_area) / desired_area
        has_ac,
        has_wm,
        has_bed,
        has_desk
    ]
    """
    r_price = float(room.get("gia_thue", room.get("gia", 0)))
    r_area = float(room.get("dien_tich", 0))
    r_days = float(room.get("days_empty", 0))
    r_floor = float(room.get("tang", 1))
    room_id = room.get("id", room.get("phong_id", 0))

    u_price = float(user.get("max_price", 3_000_000))
    u_area = float(user.get("area", 20))
    u_utils = set(user.get("utilities", []))

    room_utils = set(utilities_map.get(room_id, []))

    # Tỷ lệ đáp ứng tiện ích
    overlap = len(room_utils & u_utils)
    util_match_ratio = (overlap / max(1, len(u_utils))) if u_utils else 1.0

    # Yêu thích
    is_fav = 1.0 if room_id in favorites_list else 0.0

    # Chênh lệch tương đối
    price_diff_ratio = (r_price - u_price) / max(u_price, 1_000_000)
    area_diff_ratio = (r_area - u_area) / max(u_area, 10.0)

    vec = [
        min(r_price / MAX_PRICE_NORM, 1.5),
        min(r_area / MAX_AREA_NORM, 1.5),
        min(r_days / MAX_DAYS_EMPTY_NORM, 1.0),
        min(r_floor / 10.0, 1.0),
        float(util_match_ratio),
        is_fav,
        float(np.clip(price_diff_ratio, -1.0, 2.0)),
        float(np.clip(area_diff_ratio, -1.0, 2.0)),
        1.0 if 1 in room_utils else 0.0,
        1.0 if 2 in room_utils else 0.0,
        1.0 if 3 in room_utils else 0.0,
        1.0 if 4 in room_utils else 0.0
    ]
    return np.array(vec, dtype=np.float32)


def calculate_reward(user, room, utilities_map, favorites_list):
    """
    Hàm phần thưởng Đa Mục Tiêu chuẩn theo nội dung luận văn KLCN_TH071:
    1. Tối đa hóa sự hài lòng khách thuê:
       - Giá phòng nằm trong ngân sách -> thưởng dương. Vượt ngân sách -> phạt theo % vượt.
       - Diện tích phòng thỏa mãn nhu cầu -> thưởng.
       - Tỷ lệ tiện ích mong muốn được đáp ứng -> thưởng cao (trọng số lớn).
       - Nằm trong danh sách yêu thích cá nhân -> thưởng thêm.
    2. Tối ưu hóa doanh thu cho chủ trọ:
       - Giảm thiểu ngày trống (ưu tiên đề xuất phòng trống lâu ngày nếu phù hợp).
    3. Tuân thủ ràng buộc cứng:
       - Trạng thái phòng phải là trống.
    """
    r_price = float(room.get("gia_thue", room.get("gia", 0)))
    r_area = float(room.get("dien_tich", 0))
    r_days = float(room.get("days_empty", 0))
    room_id = room.get("id", room.get("phong_id", 0))
    status = room.get("trang_thai", "trong")

    u_price = float(user.get("max_price", 3_000_000))
    u_area = float(user.get("area", 20))
    u_utils = set(user.get("utilities", []))
    room_utils = set(utilities_map.get(room_id, []))

    # Ràng buộc cứng: Phòng không trống -> phạt nặng
    if status != "trong" and status != "":
        return -15.0

    reward = 0.0

    # 1. R_price: Ngân sách
    if r_price <= u_price:
        # Giá thấp hơn hoặc bằng ngân sách: Thưởng +3, thưởng thêm nếu tiết kiệm chi phí
        savings_ratio = (u_price - r_price) / max(u_price, 1)
        reward += 3.0 + min(savings_ratio * 2.0, 1.5)
    else:
        # Vượt ngân sách: phạt phi tuyến
        over_ratio = (r_price - u_price) / max(u_price, 1)
        reward -= 4.0 + (over_ratio * 10.0)

    # 2. R_area: Diện tích
    area_diff = r_area - u_area
    if area_diff >= 0:
        reward += 2.0 + min((area_diff / max(u_area, 1)) * 1.5, 1.5)
    elif abs(area_diff) <= 3.0:
        reward += 1.0  # Hụt nhẹ chấp nhận được
    else:
        reward -= 2.5 * (abs(area_diff) / max(u_area, 1))

    # 3. R_utils: Tiện ích
    if u_utils:
        match_count = len(room_utils & u_utils)
        match_ratio = match_count / len(u_utils)
        reward += match_ratio * 6.0  # Trọng số tiện ích rất quan trọng đối với khách thuê
    else:
        reward += 3.0

    # 4. R_fav: Yêu thích cá nhân
    if room_id in favorites_list:
        reward += 4.0

    # 5. R_vacancy: Giảm ngày trống (tăng doanh thu chủ trọ)
    # Khuyến khích giải tỏa phòng trống lâu ngày nhưng chỉ khi phòng đó đã có reward khách >= 0
    days_norm = min(r_days / MAX_DAYS_EMPTY_NORM, 1.0)
    if reward > 0:
        reward += days_norm * 3.5
    else:
        # Nếu phòng không phù hợp với khách, không ép đề xuất chỉ vì trống lâu
        reward += days_norm * 0.5

    return float(reward)


class RoomEnv:
    """
    Môi trường Reinforcement Learning mô phỏng khách thuê và các phòng trọ ứng viên.
    """
    def __init__(self, rooms, utilities, favorites=None):
        self.rooms = rooms
        self.utilities = utilities
        self.favorites = favorites or []
        self.current_user = None

    def reset(self, user):
        self.current_user = user
        return self.get_state_vectors()

    def get_state_vectors(self):
        """
        Trả về (user_vec, room_vecs)
        user_vec: (8,)
        room_vecs: (num_rooms, 12)
        """
        user_vec = build_user_vector(self.current_user)
        room_vecs = [
            build_candidate_room_vector(self.current_user, r, self.utilities, self.favorites)
            for r in self.rooms
        ]
        return user_vec, np.array(room_vecs, dtype=np.float32)

    def step(self, action_idx):
        """
        Thực hiện action (chọn phòng action_idx)
        """
        selected_room = self.rooms[action_idx]
        reward = calculate_reward(self.current_user, selected_room, self.utilities, self.favorites)
        done = True
        info = {
            "selected_room_id": selected_room.get("id", selected_room.get("phong_id")),
            "reward": reward
        }
        next_user_vec, next_room_vecs = self.get_state_vectors()
        return (next_user_vec, next_room_vecs), reward, done, info
