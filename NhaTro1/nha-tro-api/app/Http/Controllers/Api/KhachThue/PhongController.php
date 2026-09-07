<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\HopDong;
use App\Models\KhachThue;
use Illuminate\Support\Facades\Auth;

class PhongController extends Controller
{
    /**
     * 🔹 Danh sách phòng khách thuê hiện đang thuê
     */
    public function index()
    {
        $user = Auth::user();

        $khach = KhachThue::where('nguoi_dung_id', $user->id)->first();

        if (!$khach) {
            return response()->json([
                'message' => 'Không tìm thấy thông tin khách thuê.',
                'data' => []
            ], 404);
        }

        // Lấy hợp đồng hiệu lực + load quan hệ phòng và ảnh
        $hopDongs = HopDong::with(['phong.dayTro', 'phong.baiDang.anh'])
            ->where('khach_thue_id', $khach->id)
            ->where('trang_thai', 'hieu_luc')
            ->get();

        // Lấy danh sách phòng từ hợp đồng
        $phongs = $hopDongs->pluck('phong')->filter()->values();

        // Xử lý ảnh để có URL đầy đủ
        foreach ($phongs as $phong) {
            if (!empty($phong->baiDang) && !empty($phong->baiDang->anhBaiDang)) {
                foreach ($phong->baiDang->anhBaiDang as $anh) {
                    $url = $anh->url_anh ?? null;

                    if ($url && !str_starts_with($url, 'http')) {
                        $anh->url_anh = asset('storage/' . ltrim($url, '/'));
                    }
                }
            }
        }
 
        return response()->json([
            'message' => 'Danh sách phòng đang thuê.',
            'data' => $phongs
        ]);
    }

    /**
     * 🔹 Chi tiết 1 phòng đang thuê (chỉ phòng thuộc quyền khách thuê)
     */
    public function show($id)
    {
        $user = Auth::user();
        $khach = KhachThue::where('nguoi_dung_id', $user->id)->first();

        if (!$khach) {
            return response()->json([
                'message' => 'Không tìm thấy khách thuê.'
            ], 404);
        }

        // Lấy hợp đồng hiệu lực của phòng thuộc khách thuê này
        $hopDong = HopDong::with(['phong.dayTro', 'phong.baiDang.anh'])
            ->where('khach_thue_id', $khach->id)
            ->where('trang_thai', 'hieu_luc')
            ->whereHas('phong', fn($q) => $q->where('id', $id))
            ->first();

        if (!$hopDong) {
            return response()->json([
                'message' => 'Phòng không thuộc quyền thuê của bạn.'
            ], 403);
        }

        $phong = $hopDong->phong;

        // Chuẩn hoá URL ảnh
        if (!empty($phong->baiDang) && !empty($phong->baiDang->anhBaiDang)) {
            foreach ($phong->baiDang->anhBaiDang as $anh) {
                $url = $anh->url_anh ?? null;
                if ($url && !str_starts_with($url, 'http')) {
                    $anh->url_anh = asset('storage/' . ltrim($url, '/'));
                }
            }
        }

        return response()->json([
            'message' => 'Thông tin chi tiết phòng.',
            'data' => $phong
        ]);
    }
}
