<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\HopDong;
use App\Models\HoaDon;
use App\Models\ThongBao;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khach = $user->khachThue;
        if (!$khach) {
            return response()->json(['error' => 'Không tìm thấy thông tin khách thuê'], 404);
        }

        // 🏠 Hợp đồng đang hiệu lực
        $hopDong = HopDong::with(['phong.dayTro'])
            ->where('khach_thue_id', $khach->id)
            ->where('trang_thai', 'hieu_luc')
            ->get();

        // 💰 Hóa đơn chưa thanh toán
        $hoaDon = HoaDon::whereHas('hopDong', function ($q) use ($khach) {
            $q->where('khach_thue_id', $khach->id);
        })
            ->orderByDesc('thang')
            ->take(10)
            ->get();

        // 🔔 Thông báo mới
        $thongBao = ThongBao::where('nguoi_nhan_id', $user->id)
            ->orderByDesc('ngay_tao')
            ->take(10)
            ->get();

        // 🧮 Tổng hợp thống kê
        $soHopDong = $hopDong->count();
        $hoaDonChuaTT = $hoaDon->where('trang_thai', 'chua_thanh_toan')->count();
        $thongBaoMoi = $thongBao->where('da_xem', 0)->count();

        return response()->json([
            'message' => 'Dữ liệu dashboard khách thuê',
            'data' => [
                'hop_dong' => $hopDong,
                'hoa_don' => $hoaDon,
                'thong_bao' => $thongBao,
                'thong_ke' => [
                    'so_hop_dong' => $soHopDong,
                    'hoa_don_chua_tt' => $hoaDonChuaTT,
                    'thong_bao_moi' => $thongBaoMoi,
                ]
            ]
        ]);
    }
}
