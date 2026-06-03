# 🏠 DATN_K13 - Hệ Thống Quản Lý Nhà Trọ Thông Minh

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-9.0%2B-orange?logo=laravel)](https://laravel.com/)
[![Flutter](https://img.shields.io/badge/Flutter-3.0%2B-02569B?logo=flutter)](https://flutter.dev/)
[![Python](https://img.shields.io/badge/Python-3.8%2B-blue?logo=python)](https://www.python.org/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

**Đồ án tốt nghiệp 2022-2025 - Hệ sinh thái toàn diện hỗ trợ quản lý nhà trọ hiện đại**

---

## 📌 Giới Thiệu

DATN_K13 là hệ thống quản lý nhà trọ thông minh được xây dựng như một hệ sinh thái hoàn chỉnh với:
- **Backend API**: Laravel xử lý logic quản lý
- **AI Engine**: Phân tích dữ liệu, dự báo xu hướng
- **Frontend Web**: Giao diện quản lý cho chủ trọ
- **Mobile App**: Ứng dụng Flutter cho khách và chủ trọ

Hệ thống giúp tối ưu hóa vận hành và quản lý nhà trọ, từ quản lý phòng, phí dịch vụ đến tương tác khách.

---

## 🎯 Tính Năng Chính

### 🏢 Quản Lý Nhà Trọ
- ✅ Quản lý danh sách phòng (loại, giá, trạng thái)
- ✅ Quản lý tầng, khu vực
- ✅ Theo dõi trạng thái phòng (trống, có người, bảo trì)
- ✅ Quản lý người thuê và hợp đồng

### 👥 Quản Lý Khách
- ✅ Lưu thông tin khách chi tiết
- ✅ Theo dõi lịch sử khách
- ✅ Quản lý thành viên gia đình
- ✅ Liên lạc và thông báo

### 💰 Quản Lý Tài Chính
- ✅ Quản lý tiền phòng và dịch vụ
- ✅ Thanh toán online
- ✅ Báo cáo doanh thu
- ✅ Quản lý chi phí vận hành

### 🤖 AI Engine
- ✅ Dự báo nhu cầu phòng
- ✅ Phân tích hành vi khách
- ✅ Gợi ý giá phòng tối ưu
- ✅ Phân loại khách rủi ro

### 📱 Mobile App
- ✅ Khách xem thông tin phòng
- ✅ Thanh toán tiền phòng
- ✅ Gửi yêu cầu bảo trì
- ✅ Nhận thông báo từ chủ trọ

---

## 🛠️ Công Nghệ Sử Dụng

### Backend
- **PHP** 90.3%
- **Laravel 9.0+** - Web framework
- **MySQL** - Database
- **Laravel API** - RESTful API

### AI Engine
- **Python 3.8+**
- **TensorFlow / Scikit-learn** - Machine Learning
- **Pandas, NumPy** - Data processing
- **Uvicorn / FastAPI** - Python backend

### Frontend Web
- **Blade Templates** - Server-side rendering
- **Bootstrap 5** - UI Framework
- **JavaScript / jQuery** - Client-side scripting

### Mobile
- **Flutter 3.0+**
- **Dart** - Programming language
- **GetX / Provider** - State management
- **HTTP Client** - API communication

---

## 📦 Cấu Trúc Dự Án

```
DATN_K13/
├── nhatro-main/              # Laravel API chính (Port 8001)
│   ├── app/
│   ├── routes/
│   ├── resources/
│   ├── database/
│   └── .env
├── NhaTro1/                  # Laravel Frontend (Port 8000)
│   ├── resources/views/
│   ├── app/
│   └── public/
├── ai_engine/                # Python AI Service (Port 8002)
│   ├── main.py
│   ├── requirements.txt
│   └── models/
├── DATN_Mobile/              # Flutter Mobile App
│   ├── lib/
│   ├── pubspec.yaml
│   └── android/
├── Source_code/              # Tài liệu source code
└── file_bao_cao/            # Báo cáo và tài liệu
```

---

## 🚀 Hướng Dẫn Cài Đặt

### ✅ Yêu Cầu Hệ Thống
- PHP 7.4+ hoặc PHP 8.0+
- Composer
- MySQL 5.7+
- Python 3.8+
- Flutter 3.0+
- Node.js 14+ (nếu sử dụng assets build)

### 1️⃣ Cài Đặt Backend API (nhatro-main)

```bash
# Clone repository
git clone https://github.com/DevBaor/DATN_K13.git
cd DATN_K13/nhatro-main

# Cài đặt dependencies
composer install

# Copy file .env
cp .env.example .env

# Sinh application key
php artisan key:generate

# Cấu hình database trong .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=datn_db
# DB_USERNAME=root
# DB_PASSWORD=

# Migrate database
php artisan migrate

# Seed data (optional)
php artisan db:seed

# Chạy server
php artisan serve --port=8001
```

**URL API**: `http://localhost:8001`

### 2️⃣ Cài Đặt Frontend Web (NhaTro1)

```bash
cd ../NhaTro1

# Cài đặt dependencies
composer install

# Copy file .env
cp .env.example .env

# Sinh application key
php artisan key:generate

# Migrate database
php artisan migrate

# Chạy server
php artisan serve --port=8000
```

**URL Frontend**: `http://localhost:8000`

### 3️⃣ Cài Đặt AI Engine (ai_engine)

```bash
cd ../ai_engine

# Tạo virtual environment
python -m venv venv

# Activate virtual environment
# Windows
venv\Scripts\activate
# Mac/Linux
source venv/bin/activate

# Cài đặt dependencies
pip install -r requirements.txt

# Chạy AI service
uvicorn main:app --port 8002 --reload
```

**URL AI API**: `http://localhost:8002`

### 4️⃣ Cài Đặt Mobile App (DATN_Mobile)

```bash
cd ../DATN_Mobile

# Lấy Flutter dependencies
flutter pub get

# Chạy trên device hoặc emulator
flutter run

# Hoặc build APK
flutter build apk --release
```

---

## 📖 Hướng Dẫn Sử Dụng

### 🌐 Truy Cập Web
1. Mở `http://localhost:8000` trong trình duyệt
2. Đăng nhập với tài khoản admin (xem seed data)
3. Điều hướng đến các module quản lý

### 📱 Sử Dụng Mobile App
1. Cài đặt app từ APK hoặc build từ source
2. Đăng nhập với tài khoản khách
3. Xem thông tin phòng, thanh toán, gửi yêu cầu

### 🤖 Sử Dụng AI Engine
- AI Engine tự động chạy khi backend API khởi động
- Dữ liệu được cập nhật hàng ngày/hàng giờ
- Kết quả phân tích có sẵn qua API

---

## 📊 API Endpoints Chính

### Authentication
- `POST /api/login` - Đăng nhập
- `POST /api/register` - Đăng ký
- `POST /api/logout` - Đăng xuất

### Phòng
- `GET /api/rooms` - Lấy danh sách phòng
- `POST /api/rooms` - Tạo phòng mới
- `GET /api/rooms/{id}` - Chi tiết phòng
- `PUT /api/rooms/{id}` - Cập nhật phòng
- `DELETE /api/rooms/{id}` - Xóa phòng

### Khách
- `GET /api/residents` - Danh sách khách
- `POST /api/residents` - Thêm khách
- `GET /api/residents/{id}` - Chi tiết khách

### Tài Chính
- `GET /api/payments` - Danh sách thanh toán
- `POST /api/payments` - Tạo thanh toán
- `GET /api/reports` - Báo cáo doanh thu

### AI Predictions
- `GET /api/ai/price-suggestions` - Gợi ý giá phòng
- `GET /api/ai/demand-forecast` - Dự báo nhu cầu
- `GET /api/ai/risk-analysis` - Phân tích rủi ro

---

## 👨‍💻 Những Người Đóng Góp

- **Duy Bảo (DevBaor)** - Project Lead & Full Stack Developer

---

## 📄 Tài Liệu

- `report_coopy_KLCN_TH071_HeThongTro.pdf` - Báo cáo chi tiết đồ án
- `file_bao_cao/` - Thư mục tài liệu bổ sung

---

## 🔗 Liên Kết

- 📧 Email: baotranduy666666@gmail.com
- 🔗 LinkedIn: [Duy Bảo](https://linkedin.com/in/duybaot105)
- 💬 GitHub: [@DevBaor](https://github.com/DevBaor)

---

## 📝 License

Dự án này được cấp phép theo **MIT License** - xem file [LICENSE](LICENSE) để chi tiết.

---

## 🙏 Lời Cảm Ơn

Cảm ơn các thầy cô hướng dẫn và những người góp ý trong quá trình phát triển dự án.

---

**Made with ❤️ by Duy Bảo - DATN_K13**
