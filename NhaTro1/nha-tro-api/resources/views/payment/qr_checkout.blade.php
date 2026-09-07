@extends('layouts.app')

@section('title', 'Thanh Toán Hóa Đơn Tiền Phòng VietQR')

@section('content')

<div style="max-width: 680px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="badge-ai" style="padding: 6px 14px; font-size: 0.85rem;">Cổng Thanh Toán Tự Động</span>
        <h1 style="font-size: 2.2rem; margin-top: 8px;">Thanh Toán Hóa Đơn #{{ $invoice->id ?? 101 }}</h1>
        <p style="color: var(--text-muted);">Quét mã VietQR bằng bất kỳ App ngân hàng nào để thanh toán và gạch nợ tức thì</p>
    </div>

    <div class="filter-card" style="box-shadow: var(--shadow-lg); padding: 2rem;">
        <div class="qr-frame" style="background: white; border-color: #4f46e5;">
            <img id="checkout-qr" src="https://img.vietqr.io/image/MB-16688668068-compact2.png?amount={{ intval($invoice->tong_tien ?? 3250000) }}&addInfo=HD{{ $invoice->id ?? 101 }}%20P101&accountName=TRAN%20DUY%20BAO" alt="VietQR code">
            <div style="font-size: 0.85rem; color: #475569; margin-top: 10px; font-weight: 500;">
                Mở ứng dụng Ngân hàng (MB, Vietcombank, Techcombank, VPBank, MoMo...) để quét
            </div>
        </div>

        <div style="background: #f8fafc; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; font-size: 0.95rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #64748b;">Hóa đơn:</span>
                <strong>#{{ $invoice->id ?? 101 }} • {{ $invoice->thang ?? 'Tháng 09/2026' }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #64748b;">Phòng:</span>
                <strong>Phòng {{ $invoice->so_phong ?? '101' }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #64748b;">Chủ tài khoản thụ hưởng:</span>
                <strong>TRAN DUY BAO (MB Bank)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #64748b;">Số tài khoản:</span>
                <strong style="color: #4f46e5; font-family: monospace; font-size: 1.1rem;">16688668068</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #64748b;">Nội dung chuyển khoản:</span>
                <strong style="color: #d97706; font-family: monospace; background: #fef3c7; padding: 2px 6px; border-radius: 4px;">HD{{ $invoice->id ?? 101 }} P101</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid var(--border);">
                <span style="color: #64748b; font-size: 1.05rem;">Tổng tiền cần thanh toán:</span>
                <strong style="color: #10b981; font-size: 1.3rem;">{{ number_format($invoice->tong_tien ?? 3250000, 0, ',', '.') }} VNĐ</strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button id="btn-simulate-pay" data-invoice-id="{{ $invoice->id ?? 101 }}" onclick="App.simulatePaymentSuccess()" class="btn btn-success" style="padding: 14px; font-size: 1rem;">
                ⚡ Mô phỏng chuyển khoản thành công (Phục vụ Demo)
            </button>
            <a href="{{ route('khach-thue.web') }}" class="btn btn-outline" style="text-align: center;">
                Quay lại Cổng Khách Thuê
            </a>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Bắt đầu lắng nghe trạng thái thanh toán
    App.startPaymentPolling({{ $invoice->id ?? 101 }});
});
</script>
@endsection
