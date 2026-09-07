@extends('layouts.app')

@section('title', 'Cổng Thông Tin Khách Thuê - Quản Lý Hợp Đồng & Thanh Toán')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 2rem;">Cổng Thông Tin Khách Thuê</h1>
        <p style="color: var(--text-muted);">Theo dõi hợp đồng, xem chi tiết hóa đơn và thanh toán tiền tự động</p>
    </div>
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="background: white; border: 1px solid var(--border); padding: 6px 14px; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
            👤 Nguyễn Văn An (Phòng A101)
        </span>
    </div>
</div>

<!-- THÔNG TIN PHÒNG ĐANG THUÊ & HỢP ĐỒNG -->
<div class="filter-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
        <div>
            <span class="badge-ai" style="padding: 4px 10px; font-size: 0.78rem;">Hợp đồng đang hiệu lực</span>
            <h2 style="font-size: 1.5rem; margin-top: 6px;">Phòng A101 • Dãy trọ A - Lê Lợi</h2>
            <p style="color: #64748b; font-size: 0.9rem;">📍 123 Đường Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM</p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.85rem; color: #64748b;">Giá thuê cố định</div>
            <strong style="font-size: 1.4rem; color: #4f46e5;">3.200.000 đ/tháng</strong>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.9rem;">
        <div>
            <span style="color: #64748b; display: block; font-size: 0.8rem;">Ngày bắt đầu:</span>
            <strong>01/01/2026</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 0.8rem;">Ngày hết hạn:</span>
            <strong>31/12/2026</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 0.8rem;">Tiền đặt cọc:</span>
            <strong style="color: #10b981;">3.200.000 đ</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 0.8rem;">Chủ trọ:</span>
            <strong>Trần Duy Bảo (0901234567)</strong>
        </div>
    </div>
</div>

<!-- BẢNG HÓA ĐƠN THÁNG VÀ NÚT THANH TOÁN VIETQR TỰ ĐỘNG -->
<div class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.25rem;">🧾 Hóa Đơn Cần Thanh Toán</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Quét mã VietQR bằng bất kỳ App ngân hàng nào để gạch nợ tức thì</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Mã hóa đơn</th>
                    <th>Kỳ hóa đơn</th>
                    <th>Tiền phòng</th>
                    <th>Điện (kWh)</th>
                    <th>Nước (m³)</th>
                    <th>Dịch vụ</th>
                    <th>Tổng cộng</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>#101</strong></td>
                    <td>Tháng 09/2026</td>
                    <td>3.200.000 đ</td>
                    <td>350.000 đ (100 kWh)</td>
                    <td>120.000 đ (6 m³)</td>
                    <td>100.000 đ</td>
                    <td><strong style="color: #ef4444; font-size: 1.1rem;">3.770.000 đ</strong></td>
                    <td>
                        <span id="inv-badge-101" class="status-badge status-bao_tri">Chưa thanh toán</span>
                    </td>
                    <td>
                        <button onclick="App.openPaymentModal(101)" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                            💳 Quét VietQR Ngay
                        </button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#98</strong></td>
                    <td>Tháng 08/2026</td>
                    <td>3.200.000 đ</td>
                    <td>315.000 đ (90 kWh)</td>
                    <td>100.000 đ (5 m³)</td>
                    <td>100.000 đ</td>
                    <td><strong>3.715.000 đ</strong></td>
                    <td>
                        <span class="status-badge status-trong">Đã thanh toán</span>
                    </td>
                    <td>
                        <button class="btn btn-outline btn-sm" disabled style="opacity: 0.6;">Đã hoàn tất</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- FORM GỬI YÊU CẦU SỬA CHỮA / BẢO TRÌ -->
<div class="filter-card">
    <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">🔧 Gửi Yêu Cầu Sửa Chữa Thiết Bị</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div class="form-group">
            <label class="form-label">Loại sự cố</label>
            <select class="form-control" id="repair-type">
                <option value="dien">Sự cố Điện / Bóng đèn</option>
                <option value="nuoc">Hệ thống Nước / Vòi sen / Bồn cầu</option>
                <option value="may_lanh">Máy lạnh không lạnh / Chảy nước</option>
                <option value="khac">Khác</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Mức độ ưu tiên</label>
            <select class="form-control" id="repair-priority">
                <option value="binh_thuong">Bình thường</option>
                <option value="gap">Khẩn cấp (Cần xử lý trong 24h)</option>
            </select>
        </div>
    </div>
    <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label">Mô tả chi tiết</label>
        <textarea class="form-control" rows="3" placeholder="Mô tả hiện tượng hư hỏng để chủ trọ cử kỹ thuật viên tới sửa..." id="repair-desc"></textarea>
    </div>
    <button onclick="App.showToast('Yêu cầu sửa chữa đã được gửi tới chủ trọ!', 'success'); document.getElementById('repair-desc').value='';" class="btn btn-primary">
        Gửi yêu cầu bảo trì
    </button>
</div>

@endsection
