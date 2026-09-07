<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\YeuCauSuaChua;
use App\Models\KhachThue;
use App\Models\HopDong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YeuCauSuaChuaController extends Controller
{
    // GET /api/khach-thue/yeu-cau
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê'], 404);
        }

        $list = YeuCauSuaChua::with('phong.dayTro')
            ->where('khach_thue_id', $khachThue->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $list,
        ]);
    }

    // POST /api/khach-thue/yeu-cau
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê'], 404);
        }

        $hopDong = HopDong::with('phong.dayTro.chuTro')
            ->where('khach_thue_id', $khachThue->id)
            ->where('trang_thai', 'hieu_luc')
            ->orderByDesc('id') 
            ->first();

        if (!$hopDong) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn hiện chưa có phòng đang thuê nên không thể gửi yêu cầu.',
            ], 400);
        }

        $data = $request->validate([
            'loai'  => 'required|string|max:255',
            'mo_ta' => 'required|string',
        ]);

        try {
            $yc = YeuCauSuaChua::create([
                'khach_thue_id' => $khachThue->id,
                'phong_id'      => $hopDong->phong_id,
                'loai'          => $data['loai'],
                'mo_ta'         => $data['mo_ta'],
                'trang_thai'    => 'dang_xu_ly',
            ]);

            // thông báo cho chủ trọ
            $phong  = $hopDong->phong;
            $dayTro = $phong?->dayTro;
            $chuTro = $dayTro?->chuTro;

            if ($chuTro) {
                DB::table('thong_bao')->insert([
                    'nguoi_nhan_id' => $chuTro->id,
                    'noi_dung'      => "🔧 Khách thuê phòng {$phong->so_phong} gửi yêu cầu sửa chữa: {$data['loai']}.",
                    'loai'          => 'yeu_cau_sua_chua',
                    'trang_thai'    => 'chua_doc',
                    'da_xem'        => 0,
                    'lien_ket'      => '/chu-tro/yeu-cau-sua-chua/'.$yc->id,
                    'ngay_tao'      => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu thành công!',
                'data'    => $yc,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Lỗi tạo yêu cầu sửa chữa: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi gửi yêu cầu.',
            ], 500);
        }
    }
}
