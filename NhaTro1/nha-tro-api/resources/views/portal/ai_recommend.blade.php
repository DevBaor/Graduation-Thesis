@extends('layouts.app')

@section('title', 'Gợi Ý Phòng Trọ Phù Hợp Theo Nhu Cầu')

@section('content')

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
        <span class="badge-ai" style="padding: 6px 14px; font-size: 0.85rem;">✨ Gợi ý thông minh</span>
        <span style="color: #64748b; font-size: 0.9rem;">Phù hợp ngân sách & tiện nghi</span>
    </div>
    <h1 style="font-size: 2.2rem;">Gợi Ý Phòng Trọ Phù Hợp Cho Bạn</h1>
    <p style="color: var(--text-muted); font-size: 1.05rem; max-width: 850px;">
        Hệ thống tự động phân tích ngân sách, diện tích và các tiện ích mong muốn để chọn lọc cho bạn những căn phòng trọ ưng ý nhất với mức giá hợp lý và sẵn sàng vào ở ngay.
    </p>
</div>

<div class="ai-hub-layout">
    <!-- KHUNG NHẬP TIÊU CHÍ KHÁCH THUÊ -->
    <div class="ai-sidebar">
        <h3 style="font-size: 1.25rem; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 8px;">
            ⚙️ Thiết lập Tiêu chí Của Bạn
        </h3>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label class="form-label">Ngân sách tối đa</label>
                <strong id="ai-price-val" style="color: var(--primary); font-size: 1rem;">3.500.000 VNĐ</strong>
            </div>
            <input type="range" id="ai-price" min="1500000" max="10000000" step="100000" value="3500000" style="width: 100%; accent-color: var(--primary); margin-top: 6px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.76rem; color: #94a3b8;">
                <span>1.5 Tr</span>
                <span>5.0 Tr</span>
                <span>10 Tr</span>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Diện tích mong muốn (m²)</label>
            <input type="number" id="ai-area" class="form-control" value="20" min="10" max="80">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="margin-bottom: 8px;">Tiện ích mong muốn</label>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" class="ai-util-checkbox" value="1" checked> ❄️ Máy lạnh
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" class="ai-util-checkbox" value="2"> 🧺 Máy giặt
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" class="ai-util-checkbox" value="3" checked> 🛏️ Giường nệm
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" class="ai-util-checkbox" value="4"> 📚 Bàn học
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" class="ai-util-checkbox" value="5"> 🚪 Tủ quần áo
                </label>
            </div>
        </div>

        <button onclick="App.triggerAiRecommendation()" class="btn btn-ai" style="width: 100%; padding: 12px; font-size: 1rem;">
            🔍 Tìm Phòng Phù Hợp Nhất
        </button>

        <div style="margin-top: 1.5rem; background: #f8fafc; border-radius: 12px; padding: 14px; font-size: 0.84rem; color: #475569; border: 1px solid var(--border); line-height: 1.6;">
            <strong style="color: #0f172a; font-size: 0.9rem;">💡 Tiêu chí chọn lọc phòng:</strong><br>
            • Ưu tiên các phòng trong tầm giá bạn chọn<br>
            • Đảm bảo đầy đủ các tiện ích bạn cần<br>
            • Phòng trọ sạch sẽ, sẵn sàng dọn vào ở ngay
        </div>
    </div>

    <!-- KHUNG HIỂN THỊ KẾT QUẢ GỢI Ý -->
    <div>
        <div id="ai-loading" style="display: none; text-align: center; padding: 3rem; background: white; border-radius: var(--radius); border: 1px solid var(--border);">
            <div style="font-size: 1.15rem; font-weight: 700; color: #4f46e5; margin-bottom: 8px;">
                🔍 Đang tìm kiếm và chọn lọc phòng phù hợp nhất...
            </div>
            <p style="color: #64748b; font-size: 0.9rem;">
                Đang so sánh mức giá và tiện ích theo nhu cầu của bạn
            </p>
        </div>

        <div id="ai-results-container">
            <!-- Kết quả gợi ý sẽ được render tự động bởi portal.js -->
        </div>

        <!-- CAM KẾT CHẤT LƯỢNG -->
        <div style="margin-top: 2rem; background: white; border-radius: var(--radius); padding: 1.75rem; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <h4 style="font-size: 1.15rem; margin-bottom: 8px; color: #4f46e5;">
                🌟 Cam kết tìm phòng nhanh chóng & minh bạch
            </h4>
            <p style="font-size: 0.92rem; color: #475569; line-height: 1.6;">
                Hệ thống giúp bạn tiết kiệm tối đa thời gian tìm kiếm. Thay vì phải liên hệ hỏi từng nơi, công nghệ gợi ý thông minh sẽ tự động so sánh và chọn ra những căn phòng vừa vặn ngân sách, đầy đủ tiện nghi bạn cần và có chất lượng tốt nhất.
            </p>
        </div>
    </div>
</div>
@endsection
