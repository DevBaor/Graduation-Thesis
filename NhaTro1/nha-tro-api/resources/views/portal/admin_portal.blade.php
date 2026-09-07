@extends('layouts.app')

@section('title', 'Cổng Quản Trị Viên (Admin) - Quản Lý Nhà Trọ')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 2rem;">Cổng Quản Trị Hệ Thống (Admin)</h1>
        <p style="color: var(--text-muted);">Quản trị toàn diện người dùng, phê duyệt bài đăng phòng trọ và giám sát nền tảng</p>
    </div>
    <span class="badge-ai" style="padding: 6px 14px; font-size: 0.85rem;">Phân quyền: Administrator</span>
</div>

<!-- THỐNG KÊ TOÀN NỀN TẢNG -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Tổng người dùng</div>
        <div class="stat-val" style="color: #4f46e5;">128 <span style="font-size: 1rem; font-weight: 500;">tài khoản</span></div>
        <div style="font-size: 0.82rem; color: #10b981;">● 24 Chủ trọ • 104 Khách thuê</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tổng bài đăng phòng trọ</div>
        <div class="stat-val" style="color: #06b6d4;">42 <span style="font-size: 1rem; font-weight: 500;">bài</span></div>
        <div style="font-size: 0.82rem; color: #64748b;">38 đã duyệt • 4 chờ duyệt</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tổng giao dịch VietQR tự động</div>
        <div class="stat-val" style="color: #10b981;">89.450.000 <span style="font-size: 1rem; font-weight: 500;">đ</span></div>
        <div style="font-size: 0.82rem; color: #10b981; font-weight: 600;">Gạch nợ tự động qua Webhook</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tỷ lệ ghép phòng thành công</div>
        <div class="stat-val" style="color: #d97706;">96.0%</div>
        <div style="font-size: 0.82rem; color: #4f46e5; font-weight: 600;">Hệ thống gợi ý thông minh Active</div>
    </div>
</div>

<!-- BÀI ĐĂNG CHỜ DUYỆT -->
<div class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.25rem;">📝 Bài Đăng Chờ Phê Duyệt</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Kiểm duyệt thông tin và hình ảnh trước khi hiển thị công khai</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Mã bài</th>
                    <th>Tiêu đề</th>
                    <th>Chủ trọ</th>
                    <th>Khu vực</th>
                    <th>Giá niêm yết</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <tr id="row-post-19">
                    <td><strong>#19</strong></td>
                    <td>Phòng Studio B201 Đầy đủ máy lạnh</td>
                    <td>Trần Duy Bảo</td>
                    <td>Quận 5, TP.HCM</td>
                    <td>4.500.000 đ</td>
                    <td>Hôm nay</td>
                    <td><span class="status-badge status-bao_tri">Chờ duyệt</span></td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button onclick="approvePost(19)" class="btn btn-success btn-sm">Duyệt</button>
                            <button onclick="rejectPost(19)" class="btn btn-outline btn-sm" style="color: #ef4444;">Từ chối</button>
                        </div>
                    </td>
                </tr>
                <tr id="row-post-20">
                    <td><strong>#20</strong></td>
                    <td>Phòng C301 Căn góc 2 mặt thoáng view đẹp</td>
                    <td>Trần Duy Bảo</td>
                    <td>Quận 10, TP.HCM</td>
                    <td>5.200.000 đ</td>
                    <td>Hôm qua</td>
                    <td><span class="status-badge status-bao_tri">Chờ duyệt</span></td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button onclick="approvePost(20)" class="btn btn-success btn-sm">Duyệt</button>
                            <button onclick="rejectPost(20)" class="btn btn-outline btn-sm" style="color: #ef4444;">Từ chối</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- QUẢN LÝ PHÂN QUYỀN TÀI KHOẢN -->
<div class="table-card">
    <div class="table-header">
        <div>
            <h3 style="font-size: 1.25rem;">👥 Quản Lý Người Dùng & Phân Quyền</h3>
            <p style="font-size: 0.85rem; color: #64748b;">Phân quyền hệ thống: Admin, Chủ trọ, Khách thuê</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>1</strong></td>
                    <td>Quản trị viên Hệ Thống</td>
                    <td>admin@nhatro.vn</td>
                    <td>0900000001</td>
                    <td><span class="badge-ai" style="background: #4f46e5;">Admin</span></td>
                    <td><span class="status-badge status-trong">Hoạt động</span></td>
                    <td><button class="btn btn-outline btn-sm" disabled>Mặc định</button></td>
                </tr>
                <tr>
                    <td><strong>2</strong></td>
                    <td>Trần Duy Bảo</td>
                    <td>chutro@nhatro.vn</td>
                    <td>0901234567</td>
                    <td><span class="badge-ai" style="background: #06b6d4;">Chủ trọ</span></td>
                    <td><span class="status-badge status-trong">Hoạt động</span></td>
                    <td><button onclick="App.showToast('Đã lưu quyền tài khoản!', 'info')" class="btn btn-outline btn-sm">Đổi quyền</button></td>
                </tr>
                <tr>
                    <td><strong>3</strong></td>
                    <td>Nguyễn Văn An</td>
                    <td>khach@nhatro.vn</td>
                    <td>0912345678</td>
                    <td><span class="badge-ai" style="background: #10b981;">Khách thuê</span></td>
                    <td><span class="status-badge status-trong">Hoạt động</span></td>
                    <td><button onclick="App.showToast('Đã lưu quyền tài khoản!', 'info')" class="btn btn-outline btn-sm">Đổi quyền</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function approvePost(id) {
    const row = document.getElementById(`row-post-${id}`);
    if (row) {
        row.querySelector('.status-badge').className = 'status-badge status-trong';
        row.querySelector('.status-badge').innerText = 'Đã duyệt';
        App.showToast(`Đã duyệt thành công bài đăng #${id}! Bài viết đã công khai.`, 'success');
    }
}

function rejectPost(id) {
    const row = document.getElementById(`row-post-${id}`);
    if (row) {
        row.remove();
        App.showToast(`Đã từ chối bài đăng #${id}.`, 'info');
    }
}
</script>

@endsection
