import torch
import torch.nn as nn
import torch.nn.functional as F

USER_FEATURE_DIM = 8   # [max_price_norm, area_norm, util_1..util_5, budget_flexibility]
ROOM_FEATURE_DIM = 12  # [price_norm, area_norm, days_empty_norm, floor_norm, util_overlap_ratio, is_fav, price_diff, area_diff, util_1..util_4]

class DynamicDQN(nn.Module):
    """
    Dueling Deep Q-Network với kiến trúc Candidate-Scoring động cho bài toán
    phân bổ và đề xuất phòng trọ (Đề tài tốt nghiệp KLCN_TH071).
    
    Ưu điểm vượt trội so với kiến trúc cũ:
    1. Không bị phụ thuộc vào số lượng phòng cố định trong database.
    2. Có khả năng đánh giá đồng thời K phòng bất kỳ (K >= 1).
    3. Tách biệt State Value V(s_user) và Action Advantage A(s_user, a_room)
       giúp mô hình học nhanh và ổn định hơn rất nhiều.
    """
    def __init__(self, user_dim=USER_FEATURE_DIM, room_dim=ROOM_FEATURE_DIM, hidden_dim=64):
        super(DynamicDQN, self).__init__()
        
        self.user_dim = user_dim
        self.room_dim = room_dim

        # 1. User feature representation
        self.user_fc1 = nn.Linear(user_dim, hidden_dim)
        self.user_fc2 = nn.Linear(hidden_dim, hidden_dim)

        # 2. Room candidate feature representation
        self.room_fc1 = nn.Linear(room_dim, hidden_dim)
        self.room_fc2 = nn.Linear(hidden_dim, hidden_dim)

        # 3. Value stream V(s_user): Đánh giá tiềm năng chung của nhu cầu khách thuê
        self.val_fc1 = nn.Linear(hidden_dim, hidden_dim)
        self.val_out = nn.Linear(hidden_dim, 1)

        # 4. Advantage stream A(s_user, a_room): Đánh giá độ vượt trội khi ghép phòng này
        self.adv_fc1 = nn.Linear(hidden_dim * 2, hidden_dim)
        self.adv_fc2 = nn.Linear(hidden_dim, hidden_dim)
        self.adv_out = nn.Linear(hidden_dim, 1)

    def forward(self, user_feat, room_feat):
        """
        Input:
            user_feat: Tensor shape (batch_size, user_dim) hoặc (user_dim)
            room_feat: Tensor shape (batch_size, num_candidates, room_dim)
                       hoặc (batch_size, room_dim) khi train batch cặp (s, a)
        Output:
            q_values: Tensor shape (batch_size, num_candidates) hoặc (batch_size, 1)
        """
        # Chuẩn hóa số chiều cho user
        if user_feat.dim() == 1:
            user_feat = user_feat.unsqueeze(0)
            
        batch_size = user_feat.size(0)

        # Tính user embedding
        u = F.relu(self.user_fc1(user_feat))
        u = F.relu(self.user_fc2(u))  # (batch_size, hidden_dim)

        # Trường hợp 1: room_feat là cặp 1-1 (train replay buffer theo từng bước)
        if room_feat.dim() == 2 and room_feat.size(0) == batch_size and room_feat.size(1) == self.room_dim:
            r = F.relu(self.room_fc1(room_feat))
            r = F.relu(self.room_fc2(r))  # (batch_size, hidden_dim)

            v = self.val_out(F.relu(self.val_fc1(u)))  # (batch_size, 1)

            combined = torch.cat([u, r], dim=1)  # (batch_size, hidden_dim * 2)
            adv = F.relu(self.adv_fc1(combined))
            adv = F.relu(self.adv_fc2(adv))
            adv = self.adv_out(adv)  # (batch_size, 1)

            q_values = v + adv
            return q_values

        # Trường hợp 2: room_feat là tập K ứng viên cho mỗi user (suy luận hoặc chọn action)
        if room_feat.dim() == 2:
            # (num_candidates, room_dim) cho 1 user đơn
            room_feat = room_feat.unsqueeze(0)  # (1, num_candidates, room_dim)

        num_candidates = room_feat.size(1)

        # Mở rộng user embedding cho mọi phòng ứng viên
        u_expanded = u.unsqueeze(1).expand(-1, num_candidates, -1)  # (batch, num_candidates, hidden_dim)

        r_flat = room_feat.reshape(-1, self.room_dim)
        r_emb_flat = F.relu(self.room_fc1(r_flat))
        r_emb_flat = F.relu(self.room_fc2(r_emb_flat))
        r = r_emb_flat.reshape(batch_size, num_candidates, -1)  # (batch, num_candidates, hidden_dim)

        # Value stream
        v = self.val_out(F.relu(self.val_fc1(u)))  # (batch_size, 1)

        # Advantage stream
        combined = torch.cat([u_expanded, r], dim=-1)  # (batch, num_candidates, hidden_dim * 2)
        adv = F.relu(self.adv_fc1(combined))
        adv = F.relu(self.adv_fc2(adv))
        adv = self.adv_out(adv).squeeze(-1)  # (batch, num_candidates)

        # Dueling formula: Q = V + (A - mean(A))
        # Giúp triệt tiêu độ lệch vô hướng và giữ tính hội tụ ổn định
        q_values = v + (adv - adv.mean(dim=1, keepdim=True))

        return q_values


# Alias để tương thích ngược với code cũ
DQN = DynamicDQN
