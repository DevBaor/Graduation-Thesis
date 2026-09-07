# 🏠 Smart Boarding House Management System (KLCN_TH071)
### Reinforcement Learning-Driven Room Allocation via Dueling Deep Q-Network & Automated VietQR Payment Gateway

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-9.0%2B-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?logo=python&logoColor=white)](https://www.python.org/)
[![PyTorch](https://img.shields.io/badge/PyTorch-2.0%2B-EE4C2C?logo=pytorch&logoColor=white)](https://pytorch.org/)
[![Flutter](https://img.shields.io/badge/Flutter-3.0%2B-02569B?logo=flutter&logoColor=white)](https://flutter.dev/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![VietQR](https://img.shields.io/badge/VietQR-AutoPayment-005BAA)](https://vietqr.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **Graduation Thesis Project**  
> **Topic**: *Building a Smart Boarding House Management System with Optimal Room Allocation for Tenants Based on Preferences and Constraints.*  
> **Advisor**: M.Sc. Bui Cong Danh  
> **Author**: Duy Bao ([@DevBaor](https://github.com/DevBaor))

---

## 📌 1. Project Overview

This project delivers a **comprehensive, modern ecosystem** for residential boarding house operations, room discovery, and tenant management. It directly addresses three fundamental industry challenges:

1. **AI-Driven Multi-Objective Room Allocation**: Utilizes a customized **Dueling Deep Q-Network (DQN)** to dynamically match tenant preferences (budget, dimensions, amenities, target location) with available listings while concurrently minimizing vacant room durations for landlords.
2. **Automated VietQR Payment Processing**: Incorporates dynamic Napas247 / EMVCo-compliant QR code generation and real-time webhook listeners, completely eliminating manual payment reconciliation.
3. **Role-Based Portals & Cross-Platform Mobile Application**: Features specialized portals for **Tenants**, **Landlords**, and **System Administrators** across both web browsers (Laravel Blade) and mobile devices (Flutter).

---

## 🏗️ 2. System Architecture

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
│  - RESTful API Backend                  │  - Landlord Operations (Rooms, Beds)  │
│  - Business logic, JWT Authentication   │  - Tenant Self-Service (Bills, Issues)│
│  - Invoicing, Contracts, Service Fees   │  - VietQR Auto Payment Settlement     │
└────────────────────┬────────────────────┴───────────────────┬───────────────────┘
                     │                                        │
                     │ HTTP Rest                              │
                     ▼                                        ▼
┌─────────────────────────────────────────┐      ┌────────────────────────────────┐
│   ai_engine (PyTorch - Port 8002)       │      │   DATABASE (MySQL 8.0)         │
│   - Candidate-Scoring Dueling DQN       │      │   - schema: nha_tro.sql        │
│   - Dynamic Action Space (s_user, a_rm) │      │   - Manages rooms, contracts,  │
│   - Multi-Objective Reward Engine       │      │     invoices, and audit logs   │
│   - FastAPI Microservice Endpoints      │      └────────────────────────────────┘
└─────────────────────────────────────────┘
```

---

## 🌟 3. Core Modules & Innovations

### 🤖 Reinforcement Learning Recommendation Engine (Dueling DQN)
- **Dynamic Candidate-Scoring Architecture**: Conventional DQN formulations presuppose a fixed action space ($|\mathcal{A}| = K$). To accommodate dynamically changing room availability, our model evaluates unified input state-action pairs $(s_{user}, a_{room})$ independently.
- **Dueling Network Streams**: Decomposes the estimated $Q(s, a)$ into:
  $$Q(s, a) = V(s) + \left( A(s, a) - \frac{1}{|\mathcal{A}|} \sum_{a'} A(s, a') \right)$$
  ensuring robust value estimation across heterogeneous property features.
- **Multi-Objective Reward Function**:
  $$R = R_{tenant} + R_{vacancy} - R_{penalty}$$
  - $R_{tenant}$: Quantifies budget conformity, geographic proximity, and amenity satisfaction ratio.
  - $R_{vacancy}$: Rewards the allocation of long-standing vacant rooms to maximize landlord revenue.
  - $R_{penalty}$: Heavily penalizes hard constraint violations (e.g., exceeding budget ceiling).
- **Tenant-Centric User Experience**: Complex reinforcement learning metrics are seamlessly rendered into intuitive front-end badges (*"98% Match"*, *"Budget Friendly"*, *"All Amenities Matched"*, *"Move-in Ready"*), abstracting mathematical jargon from everyday end users.

### 💳 Real-Time VietQR Payment Gateway
- **Dynamic QR Code Generation**: Generates compliant VietQR images on demand with precise billing amounts and unique transfer memos.
- **Automated Webhook Settlement**: Real-time callback listeners process bank transaction notifications instantly, transitioning invoices to `PAID` status and broadcasting live confirmation via polling mechanisms.
- **One-Click Sandbox Mode**: Features a built-in demo simulator for instantaneous evaluation without requiring external bank APIs.

### 🌐 Management Portals
- **Tenant Portal**: Browse verified listings, view AI compatibility scorecards, track lease agreements, report maintenance issues, and pay monthly utility bills.
- **Landlord Dashboard**: Manage room inventories, floors, and occupancy statuses; automate monthly billing calculations; oversee tenant records.
- **Admin Control Center**: Monitor system health, revenue trends, room fill rates, and algorithmic matchmaking metrics.

---

## 📂 4. Repository Structure

```
Graduation-Thesis/
├── NhaTro1/                  # Web Portal & Management Interface (Laravel PHP - Port 8000)
│   ├── app/                  # Controllers, Models, Middleware, Events
│   ├── resources/views/      # Responsive Blade templates (Tenant, Landlord, Admin)
│   ├── public/               # Frontend assets (portal.css, portal.js)
│   └── routes/               # Web & API Route definitions
│
├── nhatro-main/              # Core API Backend Service (Laravel PHP - Port 8001)
│   ├── app/                  # RESTful controllers, domain logic, service providers
│   ├── database/             # Migrations, seeders, factories
│   └── routes/api.php        # API endpoint registry
│
├── ai_engine/                # AI Recommendation Microservice (PyTorch - Port 8002)
│   ├── dqn_model.py          # Dynamic Candidate-Scoring Dueling DQN definition
│   ├── train_dqn.py          # Training pipeline with Experience Replay & Target Net
│   ├── evaluate_dqn.py       # Benchmark evaluation (DQN vs Greedy vs Random)
│   ├── api.py                # FastAPI endpoints (/recommend, /score)
│   ├── utils.py              # Feature encoders & multi-objective reward calculation
│   ├── dqn_room.pt           # Pre-trained converged model weights checkpoint
│   └── requirements.txt      # Python dependencies
│
├── DATN_Mobile/              # Cross-Platform Mobile Application (Flutter)
│   ├── lib/                  # Screens, controllers, models, API integrations
│   └── pubspec.yaml          # Flutter package dependencies
│
├── nha_tro.sql               # Seed database dump with comprehensive demo records
├── .gitignore                # Global ignore rules for clean repository hygiene
└── README.md                 # Complete project documentation
```

---

## 🚀 5. Installation & Setup Guide

### ⚙️ Environment Prerequisites
- **PHP**: $\ge$ 8.0 (with extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer**: $\ge$ 2.0
- **Node.js & NPM**: $\ge$ 16.x
- **Python**: $\ge$ 3.8 with `pip`
- **MySQL Server**: $\ge$ 5.7 or 8.0
- **Flutter SDK**: $\ge$ 3.0 (for mobile client)

---

### Step 1: Database Initialization
1. Start your local MySQL service (via XAMPP, Laragon, or Docker).
2. Create the project database:
   ```sql
   CREATE DATABASE nha_tro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the database schema and sample data:
   ```bash
   mysql -u root -p nha_tro < nha_tro.sql
   ```

---

### Step 2: Start the AI Engine Microservice (Port 8002)
```bash
cd ai_engine

# Initialize and activate Python virtual environment
python -m venv venv
venv\Scripts\activate   # On Windows
# source venv/bin/activate # On Linux/macOS

# Install required dependencies
pip install -r requirements.txt

# Launch FastAPI microservice server
python -m uvicorn api:app --host 127.0.0.1 --port 8002 --reload
```
> **API Docs**: Interactive Swagger documentation is accessible at `http://localhost:8002/docs`.

---

### Step 3: Start the Backend API (Port 8001)
```bash
cd nhatro-main
composer install
cp .env.example .env
php artisan key:generate

# Configure database credentials in .env:
# DB_DATABASE=nha_tro
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

php artisan serve --port=8001
```

---

### Step 4: Start the Web Portal (Port 8000)
```bash
cd NhaTro1/nha-tro-api
composer install
cp .env.example .env
php artisan key:generate

# Launch the Web Portal
php artisan serve --port=8000
```
> **Application Routes**:
> - 🌐 **Public Room Search**: `http://localhost:8000/`
> - ✨ **AI Smart Match**: `http://localhost:8000/ai-recommend`
> - 🏢 **Landlord Portal**: `http://localhost:8000/chu-tro`
> - 👥 **Tenant Portal & Invoices**: `http://localhost:8000/khach-thue`
> - 🛡️ **Administrator Portal**: `http://localhost:8000/admin-portal`

---

### Step 5: Run the Flutter Mobile App
```bash
cd DATN_Mobile
flutter pub get
flutter run
```

---

## 📊 6. Experimental Results & Model Performance

Comparative evaluation across 100 simulated tenant request scenarios:

| Matchmaking Algorithm | Average Cumulative Reward | Tenant Satisfaction Score | Vacancy Reduction (Days) |
| :--- | :---: | :---: | :---: |
| **Random Allocation** | -0.05 | 42.1% | 15.2 days |
| **Greedy Matchmaker** | +11.02 | 82.4% | 68.5 days |
| **Dueling DQN (Ours)** | **+13.08** | **94.8%** | **152.1 days** |

The trained Dueling DQN model significantly outperforms conventional heuristic and greedy algorithms, delivering superior recommendation precision while actively reducing prolonged property vacancy.

---

## 👨‍💻 Author & Contact

- **Lead Developer**: Duy Bao ([@DevBaor](https://github.com/DevBaor))
- **Email**: baotranduy666666@gmail.com
- **Degree Program**: Software Engineering / Information Technology (2022 – 2026)

---

## 📝 License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.
