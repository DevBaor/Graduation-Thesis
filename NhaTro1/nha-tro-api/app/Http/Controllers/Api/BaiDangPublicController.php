<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaiDangPublicController extends Controller
{
    /**
     * Danh sách bài đăng công khai (chỉ hiển thị phòng trống)
     */
    public function index(Request $request)
    {
        $query = DB::table('bai_dang as bd')
            ->join('phong as p', 'p.id', '=', 'bd.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->join('nguoi_dung as nd', 'nd.id', '=', 'd.chu_tro_id')
            ->where('bd.trang_thai', 'dang')
            ->where('p.trang_thai', '<>', 'bao_tri') // không lấy phòng bảo trì
            //->where('p.trang_thai', '=', 'trong')   // chỉ lấy phòng TRỐNG
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
                'd.tien_ich',
                'nd.ho_ten as chu_tro',
                'nd.so_dien_thoai as sdt_chu_tro'
            );

        if ($request->filled('search')) {
            $kw = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($kw) {
                $q->where('bd.tieu_de', 'like', $kw)
                    ->orWhere('d.dia_chi', 'like', $kw)
                    ->orWhere('d.ten_day_tro', 'like', $kw);
            });
        }

        // Support explicit `dia_chi` filter (frontend may submit this param)
        if ($request->filled('dia_chi')) {
            $kw2 = '%' . trim($request->dia_chi) . '%';
            $query->where('d.dia_chi', 'like', $kw2);
        }
        if ($request->filled('min')) {
            $query->where('bd.gia_niem_yet', '>=', (int) $request->min);
        }
        if ($request->filled('max')) {
            $query->where('bd.gia_niem_yet', '<=', (int) $request->max);
        }

        if ($request->has('filters') && is_array($request->filters)) {
            foreach ($request->filters as $filter) {
                $query->where('d.tien_ich', 'like', '%' . trim($filter) . '%');
            }
        }

        switch ($request->input('sort')) {
            case 'desc':
                $query->orderBy('bd.gia_niem_yet', 'desc');
                break;
            case 'newest':
                $query->orderBy('bd.ngay_tao', 'desc');
                break;
            case 'area':
                $query->orderBy('p.dien_tich', 'desc');
                break;
            default:
                $query->orderBy('bd.gia_niem_yet', 'asc');
                break;
        }

        $baiDang = $query->paginate(9)->withQueryString();

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

        $baiDang->getCollection()->transform(function ($item) use ($anhMap, $ratingMap) {
            $anhUrl = $anhMap->get($item->id);

            $item->anh_dai_dien = $anhUrl
                ? asset('storage/' . $anhUrl)
                : asset('images/no-image.png');

            $item->rating = $ratingMap->get($item->id);
            $item->gia_hien_thi = number_format($item->gia_niem_yet, 0, ',', '.') . ' đ/tháng';
            $item->ngay_hien_thi = date('d/m/Y', strtotime($item->ngay_tao)); // ✅ sửa lỗi dấu nháy
            $item->trang_thai_text = 'Trống'; // do đã lọc p.trang_thai = 'trong'

            return $item;
        });

        return response()->json([
            'status' => true,
            'message' => 'Danh sách bài đăng phòng trống',
            'data' => $baiDang->items(),
            'meta' => [
                'current_page' => $baiDang->currentPage(),
                'last_page' => $baiDang->lastPage(),
                'total' => $baiDang->total(),
                'per_page' => $baiDang->perPage(),
            ],
        ]);
    }

    /**
     * Chi tiết bài đăng
     */
    public function show($id)
    {
        $baiDang = DB::table('bai_dang as bd')
            ->join('phong as p', 'p.id', '=', 'bd.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->join('nguoi_dung as nd', 'nd.id', '=', 'd.chu_tro_id')
            ->select(
                'bd.*',
                'p.id as phong_id',
                'p.so_phong',
                'p.dien_tich',
                'p.tang',
                'p.suc_chua',
                'p.trang_thai as trang_thai_phong',
                'd.ten_day_tro',
                'd.dia_chi',
                'd.tien_ich',
                'nd.ho_ten as chu_tro',
                'nd.so_dien_thoai as sdt_chu_tro'
            )
            ->where('bd.id', $id)
            ->where('bd.trang_thai', 'dang')
            ->whereNotIn('p.trang_thai', ['bao_tri'])
            ->first();

        if (!$baiDang) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy bài đăng.'
            ], 404);
        }

        $baiDang->anh = array_values(
            DB::table('anh_bai_dang')
                ->where('bai_dang_id', $id)
                ->orderBy('thu_tu')
                ->pluck('url')
                ->map(fn($url) => asset('storage/' . $url))
                ->toArray()
        );

        /*$baiDang->dich_vu = DB::table('dich_vu_dinh_ky as dvdk')
            ->join('dich_vu as dv', 'dv.id', '=', 'dvdk.dich_vu_id')
            ->where('dvdk.phong_id', $baiDang->phong_id)
            ->select('dv.ten', 'dvdk.don_gia as gia', 'dv.don_vi')
            ->get()
            ->map(fn($dv) => [
                'ten' => $dv->ten,
                'gia' => (float) $dv->gia,
                'don_vi' => $dv->don_vi,
            ]);*/
            $baiDang->dich_vu = DB::table('dich_vu_dinh_ky as dvdk')
    ->join('dich_vu as dv', 'dv.id', '=', 'dvdk.dich_vu_id')
    ->where('dvdk.phong_id', $baiDang->phong_id)
    ->select(
        'dv.ten',
        'dvdk.don_gia as gia',
        'dv.don_vi'
    )
    ->get()
    ->map(fn($dv) => [
        'ten' => $dv->ten,
        'gia' => (float) $dv->gia,
        'don_vi' => $dv->don_vi,
    ]);


        $baiDang->danh_gia = DB::table('danh_gia as dg')
            ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
            ->join('nguoi_dung as nd', 'nd.id', '=', 'hd.khach_thue_id')
            ->where('hd.phong_id', $baiDang->phong_id)
            ->select('nd.ho_ten as nguoi_danh_gia', 'dg.diem_so', 'dg.binh_luan', 'dg.ngay_tao')
            ->orderByDesc('dg.ngay_tao')
            ->get();

        $baiDang->rating = round(DB::table('danh_gia as dg')
            ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
            ->where('hd.phong_id', $baiDang->phong_id)
            ->avg('dg.diem_so'), 1);

        $baiDang->gia_hien_thi = number_format($baiDang->gia_niem_yet, 0, ',', '.') . ' đ/tháng';
        $baiDang->ngay_hien_thi = date('d/m/Y', strtotime($baiDang->ngay_tao));

        return response()->json([
            'status' => true,
            'message' => 'Chi tiết bài đăng',
            'data' => $baiDang
        ]);
    }

    /**
     * Trả về bài đăng (dang) ứng với một phòng cụ thể (theo phong_id)
     */
    public function byPhong($phong_id)
    {
        // tìm bài đăng công khai cho phòng này (một phòng có thể có nhiều bài đăng, lấy bài đăng đang bật gần nhất)
        $id = DB::table('bai_dang')
            ->where('phong_id', $phong_id)
            ->where('trang_thai', 'dang')
            ->orderByDesc('ngay_tao')
            ->value('id');

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy bài đăng cho phòng này.'
            ], 404);
        }

        // reuse existing show method to return full payload
        return $this->show($id);
    }

}
