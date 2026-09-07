/**
 * HỆ THỐNG QUẢN LÝ NHÀ TRỌ - XỬ LÝ CLIENT-SIDE & TÍCH HỢP AI / THANH TOÁN
 * Đề tài tốt nghiệp KLCN_TH071
 */

const App = {
  activePollingInterval: null,

  // TOAST NOTIFICATIONS
  showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6');
    toast.style.cssText = `background: ${bg}; color: white; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(0,0,0,0.15); animation: toastIn 0.3s ease; display: flex; align-items: center; gap: 8px;`;
    toast.innerHTML = `<span>${type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ')}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  // THANH TOÁN VIETQR & GẠCH NỢ TỰ ĐỘNG
  openPaymentModal(invoiceId) {
    const modal = document.getElementById('payment-modal');
    if (!modal) return;

    modal.classList.add('active');
    document.getElementById('qr-loading').style.display = 'block';
    document.getElementById('qr-content').style.display = 'none';

    // Gọi API lấy thông tin VietQR động
    fetch(`/api/payment/vietqr/${invoiceId}`)
      .then(res => res.json())
      .then(res => {
        document.getElementById('qr-loading').style.display = 'none';
        if (res.success && res.data) {
          const d = res.data;
          document.getElementById('qr-content').style.display = 'block';
          document.getElementById('qr-image').src = d.qr_url;
          document.getElementById('qr-bank').innerText = d.bank_code;
          document.getElementById('qr-account').innerText = d.account_no;
          document.getElementById('qr-name').innerText = d.account_name;
          document.getElementById('qr-amount').innerText = new Intl.NumberFormat('vi-VN').format(d.con_lai) + ' VNĐ';
          document.getElementById('qr-memo').innerText = d.memo;

          // Lưu invoiceId cho nút mô phỏng
          document.getElementById('btn-simulate-pay').dataset.invoiceId = invoiceId;

          // Bắt đầu polling lắng nghe kết quả gạch nợ tự động
          App.startPaymentPolling(invoiceId);
        } else {
          App.showToast('Không thể tải mã QR: ' + (res.message || 'Lỗi server'), 'error');
        }
      })
      .catch(err => {
        document.getElementById('qr-loading').style.display = 'none';
        App.showToast('Lỗi kết nối tới cổng thanh toán: ' + err.message, 'error');
      });
  },

  closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) modal.classList.remove('active');
    if (App.activePollingInterval) {
      clearInterval(App.activePollingInterval);
      App.activePollingInterval = null;
    }
  },

  startPaymentPolling(invoiceId) {
    if (App.activePollingInterval) clearInterval(App.activePollingInterval);

    App.activePollingInterval = setInterval(() => {
      fetch(`/api/payment/status/${invoiceId}`)
        .then(res => res.json())
        .then(res => {
          if (res.success && res.data && res.data.is_paid) {
            clearInterval(App.activePollingInterval);
            App.activePollingInterval = null;
            App.showToast('Hóa đơn #' + invoiceId + ' đã được thanh toán thành công qua chuyển khoản tự động!', 'success');
            
            // Cập nhật giao diện nếu có bảng hóa đơn
            const badge = document.getElementById(`inv-badge-${invoiceId}`);
            if (badge) {
              badge.className = 'status-badge status-trong';
              badge.innerText = 'Đã thanh toán';
            }
            setTimeout(() => {
              App.closePaymentModal();
              if (window.location.pathname.includes('/thanh-toan/')) {
                window.location.reload();
              }
            }, 1800);
          }
        })
        .catch(() => {});
    }, 3000);
  },

  simulatePaymentSuccess() {
    const btn = document.getElementById('btn-simulate-pay');
    const invoiceId = btn.dataset.invoiceId;
    if (!invoiceId) return;

    btn.disabled = true;
    btn.innerText = 'Đang xử lý Webhook...';

    fetch(`/api/payment/simulate/${invoiceId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
    })
      .then(res => res.json())
      .then(res => {
        btn.disabled = false;
        btn.innerText = '⚡ Mô phỏng chuyển khoản thành công (Demo)';
        if (res.success) {
          App.showToast('Mô phỏng chuyển khoản thành công! Webhook đã tự động gạch nợ.', 'success');
          // Polling sẽ tự bắt được trạng thái is_paid ngay sau 1s
        } else {
          App.showToast(res.message || 'Mô phỏng thất bại', 'error');
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Mô phỏng chuyển khoản thành công (Demo)';
        App.showToast('Lỗi: ' + err.message, 'error');
      });
  },

  // THUẬT TOÁN HỌC TĂNG CƯỜNG (DQN) - GỢI Ý PHÒNG THÔNG MINH
  triggerAiRecommendation() {
    const maxPrice = parseFloat(document.getElementById('ai-price').value);
    const area = parseFloat(document.getElementById('ai-area').value);
    const resultBox = document.getElementById('ai-results-container');
    const loadingBox = document.getElementById('ai-loading');

    // Lấy danh sách tiện ích được chọn
    const selectedUtils = [];
    document.querySelectorAll('.ai-util-checkbox:checked').forEach(cb => {
      selectedUtils.push(parseInt(cb.value));
    });

    if (loadingBox) loadingBox.style.display = 'block';
    if (resultBox) resultBox.innerHTML = '';

    fetch('/api/public/recommend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        max_price: maxPrice,
        area: area,
        utilities: selectedUtils,
        limit: 8
      })
    })
      .then(res => res.json())
      .then(res => {
        if (loadingBox) loadingBox.style.display = 'none';
        if (res.success && res.data) {
          App.renderAiRecommendations(res.data, res.algorithm);
        } else {
          resultBox.innerHTML = `<div style="text-align:center; padding: 2rem; color: #ef4444;">Không thể lấy kết quả gợi ý: ${res.message || 'Lỗi'}</div>`;
        }
      })
      .catch(err => {
        if (loadingBox) loadingBox.style.display = 'none';
        resultBox.innerHTML = `<div style="text-align:center; padding: 2rem; color: #ef4444;">Lỗi kết nối AI Engine: ${err.message}</div>`;
      });
  },

  renderAiRecommendations(rooms, algorithm) {
    const resultBox = document.getElementById('ai-results-container');
    if (!resultBox) return;

    if (rooms.length === 0) {
      resultBox.innerHTML = '<div style="text-align:center; padding: 2rem; color: #64748b;">Không tìm thấy phòng nào phù hợp với yêu cầu này.</div>';
      return;
    }

    let html = `
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
          <h3 style="font-size: 1.35rem; color: #0f172a;">Các phòng phù hợp nhất với bạn</h3>
          <p style="color: #64748b; font-size: 0.9rem;">Tìm thấy ${rooms.length} phòng trọ tối ưu theo ngân sách và tiện ích bạn đã chọn</p>
        </div>
        <span style="background: #ecfdf5; color: #059669; font-weight: 700; font-size: 0.8rem; padding: 6px 12px; border-radius: 20px; border: 1px solid #a7f3d0;">
          ✓ Đã lọc thông minh
        </span>
      </div>
    `;

    rooms.forEach((r, idx) => {
      const reasonsHtml = (r.analysis && r.analysis.reasons) 
        ? r.analysis.reasons.map(rs => `<span class="reason-tag">✓ ${rs}</span>`).join('')
        : '<span class="reason-tag">✓ Phù hợp tiêu chí</span>';

      html += `
        <div class="ai-card">
          <div class="ai-card-header">
            <div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span style="font-weight: 800; font-size: 1.1rem; color: #4f46e5;">#${idx + 1}</span>
                <h4 style="font-size: 1.15rem; color: #0f172a;">${r.tieu_de}</h4>
              </div>
              <p style="color: #64748b; font-size: 0.88rem;">${r.ten_day_tro || ''} • ${r.dia_chi || ''}</p>
            </div>
            <div style="text-align: right;">
              <span class="match-badge">${r.match_score}% Phù hợp</span>
            </div>
          </div>

          <div style="display: flex; gap: 20px; font-size: 0.92rem; margin: 12px 0; color: #334155; flex-wrap: wrap;">
            <div><strong>Giá thuê:</strong> <span style="color: #4f46e5; font-weight: 700; font-size: 1.05rem;">${new Intl.NumberFormat('vi-VN').format(r.gia)} đ/tháng</span></div>
            <div><strong>Diện tích:</strong> ${r.dien_tich} m²</div>
            <div><strong>Tầng:</strong> ${r.tang || 1}</div>
            <div><strong>Tình trạng:</strong> <span style="color: #059669; font-weight: 600;">Sẵn sàng dọn vào ngay</span></div>
          </div>

          <div style="margin-bottom: 14px;">
            ${reasonsHtml}
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
            <a href="/#room-${r.phong_id}" class="btn btn-outline btn-sm">Xem chi tiết phòng</a>
            <button onclick="App.showRentalRequestModal(${r.phong_id}, '${r.tieu_de.replace(/'/g, "\\'")}')" class="btn btn-primary btn-sm">Gửi yêu cầu thuê phòng</button>
          </div>
        </div>
      `;
    });

    resultBox.innerHTML = html;
  },

  showRentalRequestModal(roomId, roomTitle) {
    App.showToast(`Đã gửi yêu cầu thuê cho ${roomTitle}! Chủ trọ sẽ duyệt yêu cầu sớm nhất.`, 'success');
  }
};

// Khởi tạo các sự kiện khi load trang
document.addEventListener('DOMContentLoaded', () => {
  // Sync thanh trượt giá AI với text hiển thị
  const priceRange = document.getElementById('ai-price');
  const priceDisplay = document.getElementById('ai-price-val');
  if (priceRange && priceDisplay) {
    priceRange.addEventListener('input', (e) => {
      priceDisplay.innerText = new Intl.NumberFormat('vi-VN').format(e.target.value) + ' VNĐ';
    });
  }

  // Tự động chạy gợi ý mẫu lần đầu nếu đang ở trang AI
  if (document.getElementById('ai-results-container')) {
    App.triggerAiRecommendation();
  }
});
