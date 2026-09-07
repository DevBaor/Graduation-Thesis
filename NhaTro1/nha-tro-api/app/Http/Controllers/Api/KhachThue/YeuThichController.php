<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\YeuThich;
use App\Models\KhachThue;
use App\Models\BaiDang;
use Illuminate\Support\Facades\DB;
class YeuThichController extends Controller
{
    /**
     * Lấy danh sách bài đăng yêu thích của khách thuê hiện tại
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->vai_tro !== 'khach_thue') {
            return response()->json(['message' => 'Chỉ khách thuê mới có thể xem danh sách yêu thích'], 403);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['message' => 'Không tìm thấy thông tin khách thuê'], 404);
        }

        $baiDangIds = YeuThich::where('khach_thue_id', $khachThue->id)
            ->pluck('bai_dang_id');

        if ($baiDangIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Chưa có bài đăng yêu thích nào',
                'data' => []
            ]);
        }

        $query = DB::table('bai_dang as bd')
            ->join('phong as p', 'p.id', '=', 'bd.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->whereIn('bd.id', $baiDangIds)
            ->where('bd.trang_thai', 'dang')
            ->where('p.trang_thai', '<>', 'bao_tri')
            ->select(
                'bd.id',
                'bd.tieu_de',
                'bd.gia_niem_yet',
                'bd.ngay_tao',
                'p.id as phong_id',
                'p.so_phong',
                'p.dien_tich',
                'p.tang',
                'p.suc_chua',
                'p.trang_thai as trang_thai_phong',
                'd.ten_day_tro',
                'd.dia_chi',
                'd.tien_ich'
            )
            ->orderByDesc('bd.ngay_tao');

        $baiDang = $query->get();

        if ($baiDang->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Không tìm thấy bài đăng hợp lệ trong danh sách yêu thích',
                'data' => []
            ]);
        }

        $anhMap = DB::table('anh_bai_dang')
            ->select('bai_dang_id', 'url')
            ->whereIn('bai_dang_id', $baiDang->pluck('id'))
            ->orderBy('thu_tu')
            ->get()
            ->groupBy('bai_dang_id')
            ->map(fn($list) => $list->first()->url ?? null);

        $ratingMap = DB::table('danh_gia as dg')
            ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
            ->join('phong as p', 'p.id', '=', 'hd.phong_id')
            ->join('bai_dang as bd', 'bd.phong_id', '=', 'p.id')
            ->select('bd.id as bai_dang_id', DB::raw('ROUND(AVG(dg.diem_so), 1) as rating'))
            ->whereIn('bd.id', $baiDang->pluck('id'))
            ->groupBy('bd.id')
            ->pluck('rating', 'bai_dang_id');

        $result = $baiDang->map(function ($item) use ($anhMap, $ratingMap) {
            $anhUrl = $anhMap->get($item->id);

            $item->anh_dai_dien = $anhUrl
                ? asset('storage/' . $anhUrl)
                : asset('images/no-image.png');

            $item->rating = $ratingMap->get($item->id);
            $item->gia_hien_thi = number_format($item->gia_niem_yet, 0, ',', '.') . ' đ/tháng';
            $item->ngay_hien_thi = date('d/m/Y', strtotime($item->ngay_tao));
            $item->trang_thai_text = ucfirst($item->trang_thai_phong);

            return $item;
        });

        return response()->json([
            'status' => true,
            'message' => 'Danh sách bài đăng yêu thích',
            'data' => $result,
        ]);
    }



    /**
     * Thêm bài đăng vào yêu thích
     */
    public function store(Request $request, $baiDangId)
    {
        $user = $request->user();

        if ($user->vai_tro !== 'khach_thue') {
            return response()->json(['message' => 'Chỉ khách thuê mới có thể thêm yêu thích'], 403);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['message' => 'Không tìm thấy thông tin khách thuê'], 404);
        }

        $baiDang = BaiDang::find($baiDangId);
        if (!$baiDang) {
            return response()->json(['message' => 'Bài đăng không tồn tại'], 404);
        }

        $yeuThich = YeuThich::firstOrCreate([
            'khach_thue_id' => $khachThue->id,
            'bai_dang_id' => $baiDangId,
        ]);

        return response()->json(['message' => 'Đã thêm vào yêu thích', 'data' => $yeuThich]);
    }

    /**
     * Xóa bài đăng khỏi danh sách yêu thích
     */
    public function destroy(Request $request, $baiDangId)
    {
        $user = $request->user();

        if ($user->vai_tro !== 'khach_thue') {
            return response()->json(['message' => 'Chỉ khách thuê mới có thể xóa yêu thích'], 403);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['message' => 'Không tìm thấy thông tin khách thuê'], 404);
        }

        $deleted = YeuThich::where('khach_thue_id', $khachThue->id)
            ->where('bai_dang_id', $baiDangId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Đã xoá khỏi danh sách yêu thích']);
        }

        return response()->json(['message' => 'Bài đăng không nằm trong danh sách yêu thích'], 404);
    }
}
