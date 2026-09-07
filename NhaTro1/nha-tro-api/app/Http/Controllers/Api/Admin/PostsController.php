<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaiDang;
use App\Models\AnhBaiDang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostsController extends Controller
{
    /** Admin: danh sách bài viết (pagination + filters) */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $q = BaiDang::with(['anh' => fn($qq) => $qq->orderBy('thu_tu')])
            ->join('phong as p', 'p.id', '=', 'bai_dang.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->select('bai_dang.*', 'p.so_phong', 'p.dien_tich', 'd.ten_day_tro', 'd.dia_chi');
        $q = BaiDang::with(['anh' => fn($qq) => $qq->orderBy('thu_tu')])
            ->join('phong as p', 'p.id', '=', 'bai_dang.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->join('nguoi_dung as nd', 'nd.id', '=', 'bai_dang.nguoi_dung_id')
            ->select(
                'bai_dang.*',
                'p.so_phong',
                'p.dien_tich',
                'd.ten_day_tro',
                'd.dia_chi',
                'nd.ho_ten as tac_gia'
            );


        if ($request->filled('q')) {
            $term = $request->get('q');
            $q->where(function ($s) use ($term) {
                $s->where('bai_dang.tieu_de', 'like', "%{$term}%")
                    ->orWhere('bai_dang.mo_ta', 'like', "%{$term}%");
            });
        }

        // allow filtering by owner (chu_tro) - day_tro.chu_tro_id
        if ($request->filled('chu_tro')) {
            $q->where('d.chu_tro_id', $request->get('chu_tro'));
        }

        // allow filtering by region/address (day_tro.dia_chi)
        if ($request->filled('dia_chi')) {
            $q->where('d.dia_chi', $request->get('dia_chi'));
        }

        if ($request->filled('trang_thai')) {
            $q->where('bai_dang.trang_thai', $request->get('trang_thai'));
        }

        // sanitize sort_by: if no table prefix provided, assume bai_dang
        $sortByRaw = (string)$request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        if (strpos($sortByRaw, '.') === false) {
            $sortBy = 'bai_dang.' . $sortByRaw;
        } else {
            $sortBy = $sortByRaw;
        }

        $p = $q->orderBy($sortBy, $sortOrder)->paginate($perPage);

        return response()->json($p->toArray());
    }

    public function show(Request $request, $id)
    {
        try {
            $baiDang = \DB::table('bai_dang as bd')
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
                ->first();

            if (!$baiDang) {
                return back()->with('error', 'Không tìm thấy bài đăng.');
            }

            $baiDang->anh = \DB::table('anh_bai_dang')
                ->where('bai_dang_id', $id)
                ->orderBy('thu_tu')
                ->pluck('url')
                ->map(function ($url) {
                    $base = config('app.url', 'http://127.0.0.1:8000');
                    $url = ltrim($url, '/');
                    if (!str_starts_with($url, 'storage/')) {
                        $url = 'storage/' . $url;
                    }
                    return rtrim($base, '/') . '/' . $url;
                })
                ->values()
                ->toArray();

            $baiDang->dich_vu = \DB::table('dich_vu_dinh_ky as dvdk')
                ->join('dich_vu as dv', 'dv.id', '=', 'dvdk.dich_vu_id')
                ->where('dvdk.phong_id', $baiDang->phong_id)
                ->select('dv.ten', 'dvdk.don_gia as gia', 'dv.don_vi')
                ->get()
                ->map(fn($dv) => [
                    'ten' => $dv->ten,
                    'gia' => (float) $dv->gia,
                    'don_vi' => $dv->don_vi,
                ]);

            $baiDang->danh_gia = \DB::table('danh_gia as dg')
                ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
                ->join('nguoi_dung as nd', 'nd.id', '=', 'hd.khach_thue_id')
                ->where('hd.phong_id', $baiDang->phong_id)
                ->select('nd.ho_ten as nguoi_danh_gia', 'dg.diem_so', 'dg.binh_luan', 'dg.ngay_tao')
                ->orderByDesc('dg.ngay_tao')
                ->get();

            $baiDang->rating = round(
                \DB::table('danh_gia as dg')
                    ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
                    ->where('hd.phong_id', $baiDang->phong_id)
                    ->avg('dg.diem_so'),
                1
            );

            $baiDang->gia_hien_thi = number_format($baiDang->gia_niem_yet, 0, ',', '.') . ' đ/tháng';
            $baiDang->ngay_hien_thi = date('d/m/Y', strtotime($baiDang->ngay_tao));

            return view('admin.posts.post-detail', [
                'post' => (array) $baiDang
            ]);
        } catch (\Throwable $e) {
            \Log::error('Admin\PostsController.show error: ' . $e->getMessage());
            return back()->with('error', 'Không thể lấy chi tiết bài viết.');
        }
    }


    /** Admin: cập nhật bài viết (đơn giản, hỗ trợ tiêu đề, mô tả, giá) */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tieu_de' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia_niem_yet' => 'nullable|numeric',
            // allow updating status via admin patch
            'trang_thai' => 'nullable|string|in:nhap,dang,cho_duyet,an,tu_choi'
        ]);

        try {
            $post = BaiDang::findOrFail($id);
            $post->tieu_de = $request->tieu_de;
            $post->mo_ta = $request->mo_ta ?? $post->mo_ta;
            if ($request->filled('gia_niem_yet'))
                $post->gia_niem_yet = $request->gia_niem_yet;

            // update status if provided
            if ($request->filled('trang_thai')) {
                $post->trang_thai = $request->trang_thai;
            }
            $post->save();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Admin\PostsController.update error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật bài viết'], 500);
        }
    }

    /** Admin: xóa bài viết */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            // Use Role helper for consistent checks
            if (!$user || !\App\Support\Role::isAdmin($user)) {
                return response()->json(['error' => 'Bạn không có quyền'], 403);
            }

            $post = BaiDang::with('anh')->findOrFail($id);

            foreach ($post->anh as $anh) {
                // Try to delete storage file if exists (safe)
                try {
                    if (!empty($anh->url)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($anh->url);
                    }
                } catch (\Throwable $e) {
                    // ignore file deletion errors
                }
            }

            $post->anh()->delete();
            $post->delete();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Admin\PostsController.destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể xóa bài viết'], 500);
        }
    }
}
