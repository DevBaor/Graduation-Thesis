# 🏠 HỆ THỐNG QUẢN LÝ NHÀ TRỌ THÔNG MINH (KLCN_TH071)
### Ứng dụng Học Tăng Cường Dueling Deep Q-Network Tối Ưu Phân Bố Phòng Trọ & Tự Động Hóa Thanh Toán VietQR

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-9.0%2B-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?logo=python&logoColor=white)](https://www.python.org/)
[![PyTorch](https://img.shields.io/badge/PyTorch-2.0%2B-EE4C2C?logo=pytorch&logoColor=white)](https://pytorch.org/)
[![Flutter](https://img.shields.io/badge/Flutter-3.0%2B-02569B?logo=flutter&logoColor=white)](https://flutter.dev/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![VietQR](https://img.shields.io/badge/VietQR-AutoPayment-005BAA)](https://vietqr.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **Đồ Án Khóa Luận Tốt Nghiệp**  
> **Đề tài**: *Xây dựng hệ thống quản lý hệ thống nhà trọ ứng dụng tối ưu hóa phân bố phòng trọ cho khách thuê dựa trên sở thích và ràng buộc.*  
> **Giảng viên hướng dẫn**: ThS. Bùi Công Danh  
> **Sinh viên thực hiện**: Duy Bảo (@DevBaor)

---

## 📌 1. Giới Thiệu Tổng Quan

Dự án là một **hệ sinh thái toàn diện** phục vụ công tác quản lý, vận hành nhà trọ và kết nối khách thuê theo hướng hiện đại, thông minh. Hệ thống giải quyết trọn vẹn 3 bài toán cốt lõi trong thực tế:

1. **Tối ưu hóa phân bố phòng trọ bằng AI**: Sử dụng thuật toán Học tăng cường sâu **Dueling Deep Q-Network (DQN)** để ghép đôi thông minh giữa nhu cầu khách thuê (giá cả, diện tích, tiện ích, vị trí) và phòng trọ của chủ nhà, giải quyết bài toán đa mục tiêu: vừa thỏa mãn tối đa khách thuê, vừa giảm tỷ lệ phòng trống tồn đọng lâu ngày cho chủ trọ.
2. **Hệ thống thanh toán tự động VietQR**: Tích hợp công nghệ sinh mã QR động chuẩn Napas247 và Webhook tự động gạch nợ thời gian thực, xóa bỏ hoàn toàn thao tác đối soát thủ công chuyển khoản ngân hàng.
3. **Cổng thông tin & Ứng dụng di động đa phân quyền**: Cung cấp đầy đủ giao diện chuyên biệt cho **Chủ trọ**, **Khách thuê** và **Quản trị viên (Admin)** trên cả nền tảng Web Portal và Mobile App (Flutter).

---

## 🏗️ 2. Kiến Trúc Hệ Thống (System Architecture)

```
                            ┌─────────────────────────────────────────┐
                            │            CLIENT INTERFACES            │
                            ├────────────────────┬────────────────────┤
                            │  Flutter Mobile    │  Laravel Blade     │
                            │  App (iOS/Android) │  Web Portal        │
                            └─────────┬──────────┴──────────┬─────────┘
                                      │                     │
                                      ▼                     ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           BACKEND & CORE SERVICES                               │
├─────────────────────────────────────────┬───────────────────────────────────────┤
│  nhatro-main (Port 8001)                │  NhaTro1 / Web Portal (Port 8000)     │
│  - RESTful API Backend                  │  - Quản trị Chủ trọ (Quản lý phòng)   │
│  - Xử lý nghiệp vụ, xác thực JWT        │  - Cổng Khách thuê (Hợp đồng, sự cố)  │
│  - Quản lý hóa đơn, phòng, dịch vụ      │  - Tự động hóa thanh toán VietQR      │
└────────────────────┬────────────────────┴───────────────────┬───────────────────┘
                     │                                        │
                     │ HTTP Rest                              │
                     ▼                                        ▼
┌─────────────────────────────────────────┐      ┌────────────────────────────────┐
│   ai_engine (PyTorch - Port 8002)       │      │   DATABASE (MySQL 8.0)         │
│   - Candidate-scoring Dueling DQN       │      │   - schema: nha_tro.sql        │
│   - Dynamic Action Space (s_user, a_rm) │      │   - Lưu trữ phòng, hợp đồng,   │
│   - Multi-Objective Reward Engine       │      │     hóa đơn, lịch sử giao dịch │
│   - FastAPI Microservice endpoints      │      └────────────────────────────────┘
└─────────────────────────────────────────┘
```

---

## 🌟 3. Các Phân Hệ & Điểm Nổi Bật

### 🤖 Phân Hệ AI Gợi Ý Phòng Trọ (Dueling DQN)
- **Đột phá kiến trúc**: Khắc phục nhược điểm của DQN truyền thống (vốn yêu cầu số lượng phòng cố định). Thuật toán áp dụng kiến trúc **Candidate-Scoring Network**, nhận đầu vào là cặp vector kết hợp $(s_{user}, a_{room})$ và dự đoán điểm Q-Value thông qua 2 nhánh riêng biệt: *Value Stream V(s)* và *Advantage Stream A(s, a)*.
- **Tối ưu đa mục tiêu (Multi-Objective Reward)**:
  $$R = R_{tenant} + R_{vacancy} - R_{penalty}$$
  - $R_{tenant}$: Độ tương thích ngân sách, khoảng cách vị trí và tỷ lệ đáp ứng tiện ích yêu cầu.
  - $R_{vacancy}$: Điểm thưởng giải phóng phòng trống tồn đọng lâu ngày cho chủ trọ.
  - $R_{penalty}$: Phạt nặng nếu vi phạm các ràng buộc cứng (giá vượt quá trần, vi phạm quy định).
- **Trải nghiệm khách hàng tinh tế**: Toàn bộ điểm số Q-value được chuyển đổi sang thanh chỉ số trực quan (*"98% Phù hợp"*, *"Phù hợp ngân sách"*, *"Đầy đủ tiện ích"*, *"Sẵn sàng dọn vào ngay"*), không hiển thị các thuật ngữ toán học phức tạp.

### 💳 Phân Hệ Thanh Toán Tự Động VietQR
- **Tự động sinh mã VietQR động**: Tạo mã QR thanh toán theo chuẩn EMVCo/Napas247 với số tiền chính xác và nội dung chuyển khoản duy nhất cho từng hóa đơn.
- **Webhook Gạch nợ thời gian thực**: Khi khách chuyển khoản thành công, hệ thống ngân hàng gửi webhook về máy chủ, hóa đơn tự động chuyển trạng thái `PAID` và cập nhật tức thì trên giao diện qua cơ chế Long-polling.
- **Mô phỏng 1-Click**: Tích hợp sẵn nút thanh toán thử nghiệm phục vụ demo nghiệm thu đồ án nhanh chóng.

### 🌐 Phân Hệ Web Portal & Mobile
- **Khách thuê**: Tra cứu phòng, xem phân tích mức độ phù hợp, theo dõi hợp đồng, báo cáo sự cố hư hỏng, thanh toán tiền điện/nước/phòng.
- **Chủ trọ**: Bảng điều khiển trực quan, quản lý danh sách phòng (trống, đang thuê, bảo trì), tạo hóa đơn hàng tháng, quản trị danh sách người thuê.
- **Quản trị viên (Admin)**: Thống kê toàn diện doanh thu hệ thống, tỷ lệ lấp đầy phòng, tỷ lệ ghép phòng thành công.

---

## 📂 4. Cấu Trúc Mã Nguồn (Repository Structure)

```
Graduation-Thesis/
├── NhaTro1/                  # Web Portal & Giao diện quản trị (Laravel PHP - Port 8000)
│   ├── app/                  # Controllers, Models, Middleware
│   ├── resources/views/      # Blade templates (Khách thuê, Chủ trọ, Admin, Checkout)
│   ├── public/               # CSS, JavaScript (portal.css, portal.js)
│   └── routes/               # Web & API routes (routes/web.php, routes/api.php)
│
├── nhatro-main/              # Core API Backend (Laravel PHP - Port 8001)
│   ├── app/                  # API Controllers, Business Logic, Services
│   ├── database/             # Migrations, Seeders
│   └── routes/api.php        # Danh mục RESTful API endpoints
│
├── ai_engine/                # Dịch vụ AI Dueling DQN (Python PyTorch - Port 8002)
│   ├── dqn_model.py          # Kiến trúc Dueling Dynamic DQN Network
│   ├── train_dqn.py          # Huấn luyện mô hình với Replay Buffer & Target Net
│   ├── evaluate_dqn.py       # Đánh giá so sánh: DQN vs Greedy vs Random
│   ├── api.py                # FastAPI microservice (/recommend, /score)
│   ├── utils.py              # Hàm tính Reward đa mục tiêu & mã hóa Feature
│   ├── dqn_room.pt           # Trọng số mô hình đã được huấn luyện hội tụ
│   └── requirements.txt      # Thư viện Python phụ thuộc
│
├── DATN_Mobile/              # Ứng dụng di động đa nền tảng (Flutter)
│   ├── lib/                  # Screens, Controllers, Models, Services
│   └── pubspec.yaml          # Cấu hình dependencies Flutter
│
├── nha_tro.sql               # Cơ sở dữ liệu mẫu MySQL đầy đủ dữ liệu demo
├── .gitignore                # Cấu hình bỏ qua các file tạm, cache, vendor
└── README.md                 # Tài liệu hướng dẫn đồ án
```

---

## 🚀 5. Hướng Dẫn Cài Đặt & Vận Hành

### ⚙️ Yêu Cầu Môi Trường
- **PHP**: >= 8.0 với các extension `pdo_mysql`, `mbstring`, `openssl`
- **Composer**: >= 2.0
- **Node.js & NPM**: >= 16.x
- **Python**: >= 3.8 với `pip`
- **MySQL**: >= 5.7 hoặc 8.0
- **Flutter SDK**: >= 3.0 (cho ứng dụng di động)

---

### Bước 1: Khởi Tạo Cơ Sở Dữ Liệu MySQL
1. Khởi động MySQL (qua XAMPP, Laragon hoặc Docker).
2. Tạo database mới tên `nha_tro`:
   ```sql
   CREATE DATABASE nha_tro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Nhập dữ liệu mẫu từ file `nha_tro.sql`:
   ```bash
   mysql -u root -p nha_tro < nha_tro.sql
   ```

---

### Bước 2: Khởi Động AI Engine Microservice (Port 8002)
```bash
cd ai_engine

# Tạo môi trường ảo Python (khuyến nghị)
python -m venv venv
venv\Scripts\activate   # Trên Windows
# source venv/bin/activate # Trên Linux/Mac

# Cài đặt thư viện phụ thuộc
pip install -r requirements.txt

# Khởi chạy AI API Server
python -m uvicorn api:app --host 127.0.0.1 --port 8002 --reload
```
> **Kiểm tra**: Truy cập tài liệu API tự động tại: `http://localhost:8002/docs`

---

### Bước 3: Khởi Động Backend API (Port 8001)
```bash
cd nhatro-main
composer install
cp .env.example .env
php artisan key:generate

# Cấu hình DB_DATABASE=nha_tro, DB_USERNAME, DB_PASSWORD trong .env
php artisan serve --port=8001
```

---

### Bước 4: Khởi Động Web Portal (Port 8000)
```bash
cd NhaTro1/nha-tro-api
composer install
cp .env.example .env
php artisan key:generate

# Khởi chạy Web Portal
php artisan serve --port=8000
```
> **Đường dẫn truy cập**:
> - 🌐 **Trang chủ Khám phá**: `http://localhost:8000/`
> - ✨ **Gợi ý phòng thông minh (Smart Match)**: `http://localhost:8000/ai-recommend`
> - 🏢 **Cổng Chủ trọ**: `http://localhost:8000/chu-tro`
> - 👥 **Cổng Khách thuê & Hóa đơn**: `http://localhost:8000/khach-thue`
> - 🛡️ **Cổng Quản trị viên (Admin)**: `http://localhost:8000/admin-portal`

---

### Bước 5: Chạy Ứng Dụng Di Động (Flutter)
```bash
cd DATN_Mobile
flutter pub get
flutter run
```

---

## 📊 6. Kết Quả Huấn Luyện Mô Hình AI

So sánh hiệu năng giữa thuật toán **Dueling DQN** và các phương pháp cơ sở trên tập dữ liệu kiểm thử (100 kịch bản ghép phòng):

| Phương pháp | Tổng Reward trung bình | Điểm hài lòng khách thuê | Ngày phòng trống giảm thiểu |
| :--- | :---: | :---: | :---: |
| **Ngẫu nhiên (Random)** | -0.05 | 42.1% | 15.2 ngày |
| **Tham lam (Greedy Matching)** | +11.02 | 82.4% | 68.5 ngày |
| **Dueling DQN (Đề tài)** | **+13.08** | **94.8%** | **152.1 ngày** |

Mô hình Dueling DQN không chỉ tối đa hóa sự hài lòng của người thuê mà còn chứng minh hiệu quả vượt trội trong việc giúp chủ trọ lấp đầy các phòng trống lâu năm.

---

## 👨‍💻 Thông Tin Tác Giả & Đóng Góp

- **Tác giả**: Duy Bảo ([@DevBaor](https://github.com/DevBaor))
- **Email**: baotranduy666666@gmail.com
- **Khóa luận tốt nghiệp**: Ngành Kỹ thuật Phần mềm / Công nghệ Thông tin (2022 - 2026)

---

## 📝 Giấy Phép (License)

Dự án được phân phối dưới giấy phép **MIT License**. Xem chi tiết tại tệp [LICENSE](LICENSE).
