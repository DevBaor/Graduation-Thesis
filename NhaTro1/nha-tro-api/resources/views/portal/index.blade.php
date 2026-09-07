@extends('layouts.app')

@section('title', 'Trang Chủ - Hệ Thống Quản Lý Nhà Trọ Thông Minh')

@section('content')

<!-- HERO SECTION -->
<div class="hero-box">
    <div style="display: inline-block; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); padding: 4px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem;">
        ✨ Nền tảng tìm & thuê phòng trọ thông minh
    </div>
    <h1>Tìm Kiếm Phòng Trọ Ưng Ý & Nhanh Chóng</h1>
    <p>
        Hệ thống tự động phân tích và chọn lọc các phòng trọ phù hợp nhất theo ngân sách và tiện nghi bạn mong muốn. Minh bạch giá cả, xem phòng trực quan và thanh toán hóa đơn tiện lợi qua VietQR.
    </p>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('ai.recommend') }}" class="btn btn-ai">
            ✨ Gợi ý phòng phù hợp cho bạn
        </a>
        <a href="{{ route('chu-tro.web') }}" class="btn btn-outline" style="color: white; border-color: rgba(255,255,255,0.4);">
            🏠 Quản lý trọ (Dành cho Chủ trọ)
        </a>
    </div>
</div>

<!-- BỘ LỌC TÌM KIẾM NHANH -->
<div class="filter-card">
    <div class="filter-grid">
        <div class="form-group">
            <label class="form-label">Khu vực / Quận huyện</label>
            <select class="form-control" id="filter-district">
                <option value="">Tất cả khu vực TP.HCM</option>
                <option value="Q1">Quận 1</option>
                <option value="Q5">Quận 5</option>
                <option value="Q10">Quận 10</option>
                <option value="ThuDuc">TP. Thủ Đức</option>
                <option value="BinhThanh">Bình Thạnh</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Mức giá tối đa</label>
            <select class="form-control" id="filter-price">
                <option value="0">Tất cả mức giá</option>
                <option value="2500000">Dưới 2.5 triệu</option>
                <option value="3500000">Dưới 3.5 triệu</option>
                <option value="5000000">Dưới 5.0 triệu</option>
                <option value="10000000">Trên 5.0 triệu</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Diện tích tối thiểu</label>
            <select class="form-control" id="filter-area">
                <option value="0">Tất cả diện tích</option>
                <option value="15">Từ 15 m²</option>
                <option value="20">Từ 20 m²</option>
                <option value="30">Từ 30 m² trở lên</option>
            </select>
        </div>

        <div>
            <button onclick="App.showToast('Đã áp dụng bộ lọc danh sách phòng!', 'info')" class="btn btn-primary" style="width: 100%;">
                Lọc phòng
            </button>
        </div>
    </div>
</div>

<!-- DANH SÁCH BÀI ĐĂNG PHÒNG TRỌ -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.6rem;">Danh Sách Phòng Trọ Đang Khả Dụng</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Được cập nhật tự động thời gian thực</p>
    </div>
    <span class="badge-ai" style="padding: 6px 12px; font-size: 0.8rem;">{{ count($posts) }} Phòng trống</span>
</div>

<div class="room-grid">
    @foreach($posts as $post)
    <div class="room-card" id="room-{{ $post->phong_id ?? $post->bai_dang_id }}">
        <div class="room-thumb">
            <img src="/room1.jpg" onerror="this.src='/avatar.jpg'" alt="{{ $post->tieu_de }}">
            <span class="price-tag">{{ number_format($post->gia ?? 0, 0, ',', '.') }} đ/tháng</span>
            <span class="status-badge status-{{ $post->trang_thai ?? 'trong' }}">
                {{ ($post->trang_thai ?? 'trong') == 'trong' ? 'Còn trống' : 'Đang thuê' }}
            </span>
        </div>

        <div class="room-body">
            <h3 class="room-title">{{ $post->tieu_de }}</h3>
            <p class="room-location">📍 {{ $post->dia_chi ?? $post->ten_day_tro ?? 'TP. Hồ Chí Minh' }}</p>

            <div class="room-specs">
                <span>📐 <strong>{{ $post->dien_tich ?? 20 }}</strong> m²</span>
                <span>🏢 Tầng <strong>{{ $post->tang ?? 1 }}</strong></span>
                <span>🚪 Số phòng: <strong>{{ $post->so_phong ?? 'A1' }}</strong></span>
            </div>

            <div class="room-utils">
                @if(isset($post->tien_ich) && is_array($post->tien_ich))
                    @foreach($post->tien_ich as $ti)
                        <span class="util-badge">✓ {{ $ti }}</span>
                    @endforeach
                @else
                    <span class="util-badge">✓ Máy lạnh</span>
                    <span class="util-badge">✓ Wifi</span>
                    <span class="util-badge">✓ Giường nệm</span>
                @endif
            </div>

            <div class="room-footer">
                <a href="{{ route('ai.recommend') }}" class="btn btn-outline btn-sm" title="Tìm phòng phù hợp nhất">
                    ✨ Xem độ phù hợp
                </a>
                <button onclick="App.showRentalRequestModal({{ $post->phong_id ?? 1 }}, '{{ addslashes($post->tieu_de) }}')" class="btn btn-primary btn-sm">
                    Gửi yêu cầu thuê
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
