@extends('layouts.app')

@section('title', 'Bảng Điều Khiển Chủ Trọ - Quản Lý Nhà Trọ')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 2rem;">Bảng Điều Khiển Chủ Trọ</h1>
        <p style="color: var(--text-muted);">Quản lý phòng trọ, điện nước, hợp đồng và theo dõi thu tiền tự động</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="App.showToast('Đang đồng bộ trạng thái thanh toán từ Webhook ngân hàng...', 'info')" class="btn btn-outline btn-sm">
            🔄 Đồng bộ ngân hàng
        </button>
        <button onclick="document.getElementById('sec-dien-nuoc').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary btn-sm">
            ⚡ Nhập số điện nước
        </button>
    </div>
</div>

<!-- 4 THẺ THỐNG KÊ TỔNG QUAN -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Tổng số phòng</div>
        <div class="stat-val" style="color: #4f46e5;">{{ count($rooms) }} <span style="font-size: 1rem; font-weight: 500; color: #64748b;">phòng</span></div>
        <div style="font-size: 0.82rem; color: #10b981; font-weight: 600;">
            ● Tỷ lệ lấp đầy: {{ intval((count(array_filter($rooms, fn($r) => $r['trang_thai'] == 'dang_thue')) / max(1, count($rooms))) * 100) }}%
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Doanh thu dự kiến tháng này</div>
        <div class="stat-val" style="color: #10b981;">12.920.000 <span style="font-size: 1rem; font-weight: 500;">đ</span></div>
        <div style="font-size: 0.82rem; color: #64748b;">Đã thu: 3.960.000 đ (31%)</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Hóa đơn chờ thanh toán</div>
        <div class="stat-val" style="color: #f59e0b;">2 <span style="font-size: 1rem; font-weight: 500;">hóa đơn</span></div>
        <div style="font-size: 0.82rem; color: #f59e0b; font-weight: 600;">VietQR đã sẵn sàng gửi khách</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Phòng trống cần cho thuê</div>
        <div class="stat-val" style="color: #06b6d4;">2 <span style="font-size: 1rem; font-weight: 500;">phòng</span></div>
        <div style="font-size: 0.82rem; color: #06b6d4; font-weight: 600;">Đang được ưu tiên gợi ý cho khách thuê</div>
    </div>
</div>

<!-- SƠ ĐỒ TRẠNG THÁI PHÒNG TRỰC QUAN -->
<div class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.2rem;">Sơ Đồ Trạng Thái Phòng Trọ</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Dãy trọ A - Lê Lợi & Dãy trọ B - Trần Hưng Đạo</p>
        </div>
        <div style="display: flex; gap: 15px; font-size: 0.82rem;">
            <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span> Còn trống</span>
            <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span> Đang thuê</span>
            <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span> Bảo trì</span>
        </div>
    </div>

    <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.2rem;">
        @foreach($rooms as $r)
        <div style="border: 1.5px solid var(--border); border-radius: 12px; padding: 1.2rem; background: #ffffff; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h4 style="font-size: 1.15rem; color: var(--dark);">Phòng {{ $r['so_phong'] }}</h4>
                <span class="status-badge status-{{ $r['trang_thai'] }}">
                    @if($r['trang_thai'] == 'trong') Trống
                    @elseif($r['trang_thai'] == 'dang_thue') Có người
                    @else Bảo trì
                    @endif
                </span>
            </div>

            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 10px;">
                {{ $r['ten_day_tro'] }} • Tầng {{ $r['tang'] }} • {{ $r['dien_tich'] }} m²
            </div>

            <div style="font-size: 0.95rem; font-weight: 700; color: #4f46e5; margin-bottom: 10px;">
                {{ number_format($r['gia'], 0, ',', '.') }} đ/tháng
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 8px; font-size: 0.82rem; color: #475569;">
                @if($r['khach'])
                    👤 {{ $r['khach'] }} ({{ $r['sdt'] }})
                @else
                    <span style="color: #10b981; font-weight: 600;">Sẵn sàng đón khách mới</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- MODULE GHI CHỈ SỐ ĐIỆN NƯỚC & TÍNH TIỀN TỰ ĐỘNG -->
<div id="sec-dien-nuoc" class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.2rem;">⚡ Chốt Số Điện Nước & Tự Động Tính Tiền Hóa Đơn</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Đơn giá: Điện 3.500 đ/kWh • Nước 20.000 đ/m³</p>
        </div>
        <button onclick="calculateAutoInvoice()" class="btn btn-primary btn-sm">
            Tự động tính toán & Tạo hóa đơn
        </button>
    </div>

    <div style="padding: 1.5rem; overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Số phòng</th>
                    <th>Khách thuê</th>
                    <th>Điện cũ (kWh)</th>
                    <th>Điện mới (kWh)</th>
                    <th>Nước cũ (m³)</th>
                    <th>Nước mới (m³)</th>
                    <th>Tạm tính điện nước</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Phòng 101</strong></td>
                    <td>Nguyễn Văn An</td>
                    <td><input type="number" id="ec-101" value="1240" class="form-control" style="width: 100px; padding: 6px 10px;"></td>
                    <td><input type="number" id="en-101" value="1340" class="form-control" style="width: 100px; padding: 6px 10px;"></td>
                    <td><input type="number" id="wc-101" value="45" class="form-control" style="width: 90px; padding: 6px 10px;"></td>
                    <td><input type="number" id="wn-101" value="51" class="form-control" style="width: 90px; padding: 6px 10px;"></td>
                    <td><strong id="calc-101" style="color: #10b981;">470.000 đ</strong></td>
                    <td>
                        <button onclick="App.showToast('Đã lưu chỉ số điện nước Phòng 101!', 'success')" class="btn btn-outline btn-sm">Lưu số</button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Phòng 201</strong></td>
                    <td>Trần Thị Mai</td>
                    <td><input type="number" id="ec-201" value="890" class="form-control" style="width: 100px; padding: 6px 10px;"></td>
                    <td><input type="number" id="en-201" value="970" class="form-control" style="width: 100px; padding: 6px 10px;"></td>
                    <td><input type="number" id="wc-201" value="32" class="form-control" style="width: 90px; padding: 6px 10px;"></td>
                    <td><input type="number" id="wn-201" value="37" class="form-control" style="width: 90px; padding: 6px 10px;"></td>
                    <td><strong id="calc-201" style="color: #10b981;">380.000 đ</strong></td>
                    <td>
                        <button onclick="App.showToast('Đã lưu chỉ số điện nước Phòng 201!', 'success')" class="btn btn-outline btn-sm">Lưu số</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- BẢNG QUẢN LÝ HÓA ĐƠN & GẠCH NỢ TỰ ĐỘNG -->
<div class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.2rem;">📋 Danh Sách Hóa Đơn & Theo Dõi Thanh Toán</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Hệ thống tự động đồng bộ gạch nợ khi khách quét VietQR</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Phòng</th>
                    <th>Khách thuê</th>
                    <th>Kỳ thu</th>
                    <th>Tổng tiền</th>
                    <th>Đã thanh toán</th>
                    <th>Còn lại</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                <tr>
                    <td><strong>#{{ $inv['id'] }}</strong></td>
                    <td>Phòng {{ $inv['so_phong'] }}</td>
                    <td>{{ $inv['khach_thue'] }}</td>
                    <td>{{ $inv['thang'] }}</td>
                    <td><strong>{{ number_format($inv['tong_tien'], 0, ',', '.') }} đ</strong></td>
                    <td style="color: #10b981;">{{ number_format($inv['so_tien_da_tra'], 0, ',', '.') }} đ</td>
                    <td style="color: #ef4444; font-weight: 700;">
                        {{ number_format(max(0, $inv['tong_tien'] - $inv['so_tien_da_tra']), 0, ',', '.') }} đ
                    </td>
                    <td>
                        <span id="inv-badge-{{ $inv['id'] }}" class="status-badge {{ $inv['trang_thai'] == 'da_thanh_toan' ? 'status-trong' : 'status-bao_tri' }}">
                            {{ $inv['trang_thai'] == 'da_thanh_toan' ? 'Đã thanh toán' : ($inv['trang_thai'] == 'mot_phan' ? 'Một phần' : 'Chưa thanh toán') }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button onclick="App.openPaymentModal({{ $inv['id'] }})" class="btn btn-outline btn-sm" title="Mở mã QR">
                                💳 VietQR
                            </button>
                            <a href="{{ route('payment.checkout', $inv['id']) }}" class="btn btn-primary btn-sm">
                                Chi tiết
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- CẤU HÌNH TÀI KHOẢN NGÂN HÀNG VIETQR -->
<div class="filter-card">
    <h3 style="font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
        🏦 Cấu Hình Tài Khoản Ngân Hàng Nhận Tiền VietQR
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) 140px; gap: 1rem; align-items: flex-end;">
        <div class="form-group">
            <label class="form-label">Ngân hàng</label>
            <select class="form-control" id="bank-select">
                <option value="MB">MB Bank (Quân Đội)</option>
                <option value="VCB">Vietcombank</option>
                <option value="TCB">Techcombank</option>
                <option value="ACB">ACB</option>
                <option value="VPB">VPBank</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Số tài khoản</label>
            <input type="text" class="form-control" value="16688668068" id="bank-acc">
        </div>
        <div class="form-group">
            <label class="form-label">Tên chủ tài khoản (In hoa không dấu)</label>
            <input type="text" class="form-control" value="TRAN DUY BAO" id="bank-name">
        </div>
        <div>
            <button onclick="App.showToast('Đã lưu cấu hình tài khoản VietQR!', 'success')" class="btn btn-primary" style="width: 100%;">
                Lưu cấu hình
            </button>
        </div>
    </div>
</div>

<script>
function calculateAutoInvoice() {
    const eDiff101 = Math.max(0, parseInt(document.getElementById('en-101').value) - parseInt(document.getElementById('ec-101').value));
    const wDiff101 = Math.max(0, parseInt(document.getElementById('wn-101').value) - parseInt(document.getElementById('wc-101').value));
    const total101 = (eDiff101 * 3500) + (wDiff101 * 20000);
    document.getElementById('calc-101').innerText = new Intl.NumberFormat('vi-VN').format(total101) + ' đ';

    App.showToast(`Đã tự động tính toán điện nước: Phòng 101 (${new Intl.NumberFormat('vi-VN').format(total101)} đ)`, 'success');
}
</script>

@endsection
