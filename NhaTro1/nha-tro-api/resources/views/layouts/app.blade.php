<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Hệ Thống Thuê & Quản Lý Nhà Trọ Thông Minh')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Portal CSS -->
    <link rel="stylesheet" href="/css/portal.css">
    
    <!-- Meta SEO -->
    <meta name="description" content="Hệ thống tìm kiếm phòng trọ thông minh, gợi ý phòng trọ tối ưu theo ngân sách, tiện ích và thanh toán tiền phòng tự động.">
</head>
<body>

    <!-- NAVBAR CHÍNH -->
    <header class="navbar">
        <a href="{{ route('home') }}" class="nav-brand">
            <span>🏘️ NhàTrọ<strong style="color: #06b6d4;">Smart</strong></span>
            <span class="badge-ai">Smart Match</span>
        </a>

        <ul class="nav-links">
            <li class="nav-item">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    🔍 Tìm phòng
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('ai.recommend') }}" class="{{ request()->routeIs('ai.recommend') ? 'active' : '' }}" style="color: #4f46e5; font-weight: 700;">
                    ✨ Gợi ý phòng phù hợp
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('chu-tro.web') }}" class="{{ request()->routeIs('chu-tro.web') ? 'active' : '' }}">
                    🏠 Cổng Chủ trọ
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('khach-thue.web') }}" class="{{ request()->routeIs('khach-thue.web') ? 'active' : '' }}">
                    👤 Cổng Khách thuê
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.web') }}" class="{{ request()->routeIs('admin.web') ? 'active' : '' }}">
                    ⚙️ Quản trị Admin
                </a>
            </li>
        </ul>

        <div class="nav-cta">
            <a href="{{ route('ai.recommend') }}" class="btn btn-ai btn-sm">
                ✨ Tìm phòng nhanh
            </a>
        </div>
    </header>

    <!-- NỘI DUNG TỪNG TRANG -->
    <main class="container">
        @yield('content')
    </main>

    <!-- MODAL THANH TOÁN VIETQR TỰ ĐỘNG -->
    <div id="payment-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    💳 Thanh toán hóa đơn tự động (VietQR)
                </h3>
                <button onclick="App.closePaymentModal()" style="background:none; border:none; font-size: 1.5rem; cursor: pointer; color: #94a3b8;">&times;</button>
            </div>

            <div class="modal-body">
                <div id="qr-loading" style="text-align: center; padding: 2rem;">
                    <div style="font-weight: 600; color: #4f46e5;">Đang khởi tạo mã VietQR động...</div>
                </div>

                <div id="qr-content" style="display: none;">
                    <div class="qr-frame">
                        <img id="qr-image" src="" alt="Mã VietQR thanh toán">
                        <div style="font-size: 0.82rem; color: #64748b; margin-top: 8px;">
                            Mở ứng dụng Ngân hàng (MB, VCB, Techcombank, MoMo...) để quét mã
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-radius: 12px; padding: 14px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #64748b;">Ngân hàng:</span>
                            <strong id="qr-bank">MB Bank</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #64748b;">Số tài khoản:</span>
                            <strong id="qr-account" style="color: #4f46e5; font-family: monospace;">16688668068</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #64748b;">Chủ tài khoản:</span>
                            <strong id="qr-name">TRAN DUY BAO</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #64748b;">Số tiền cần trả:</span>
                            <strong id="qr-amount" style="color: #10b981; font-size: 1.05rem;">0 VNĐ</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">Nội dung CK:</span>
                            <strong id="qr-memo" style="color: #d97706; font-family: monospace; background: #fef3c7; padding: 2px 6px; border-radius: 4px;">HD...</strong>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button id="btn-simulate-pay" onclick="App.simulatePaymentSuccess()" class="btn btn-success" style="width: 100%;">
                            ⚡ Mô phỏng chuyển khoản thành công (Demo)
                        </button>
                        <p style="font-size: 0.78rem; text-align: center; color: #94a3b8;">
                            Hệ thống tự động lắng nghe Webhook ngân hàng và cập nhật tức thì trong 1 giây.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <p><strong>Hệ Thống Thuê & Quản Lý Nhà Trọ Thông Minh</strong></p>
        <p style="margin-top: 4px; font-size: 0.85rem;">Tìm kiếm phòng trọ tối ưu theo ngân sách & tiện nghi • Thanh toán hóa đơn tự động qua VietQR</p>
    </footer>

    <!-- App JS -->
    <script src="/js/portal.js"></script>
    @yield('scripts')
</body>
</html>
