<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\BaiDang;
use App\Models\AnhBaiDang;
use App\Models\DichVu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class BaiDangController extends Controller
{
    /** 📋 Danh sách bài đăng */
    /*public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Chưa đăng nhập'], 401);
        }

        $posts = BaiDang::with([
            'anh' => fn($q) => $q->orderBy('thu_tu')
        ])
            ->join('phong as p', 'p.id', '=', 'bai_dang.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->where('d.chu_tro_id', $user->id)
            ->select('bai_dang.*', 'p.so_phong', 'p.dien_tich', 'd.ten_day_tro', 'd.dia_chi')
            ->orderByDesc('bai_dang.id')
            ->paginate(12);

        return response()->json(['success' => true, 'data' => $posts]);
    }*/
        public function index(Request $request)
{
    $user = $request->user();

    $query = BaiDang::with([
        'anh' => fn($q) => $q->orderBy('thu_tu')
    ])
    ->join('phong as p', 'p.id', '=', 'bai_dang.phong_id')
    ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
    ->where('d.chu_tro_id', $user->id);

    // ✅ chỉ lọc khi truyền status
    if ($request->filled('status')) {
        $query->where('bai_dang.trang_thai', $request->status);
    }

    $posts = $query
        ->select('bai_dang.*', 'p.so_phong', 'p.dien_tich', 'd.ten_day_tro', 'd.dia_chi')
        ->orderByDesc('bai_dang.id')
        ->paginate(12);

    return response()->json(['success' => true, 'data' => $posts]);
}


    /** ➕ Tạo bài đăng (có kiểm tra chủ trọ uy tín) */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'phong_id' => 'required|exists:phong,id',
            'tieu_de' => 'required|string|max:255',
            'mo_ta' => 'required|string',
            'gia_niem_yet' => 'required|numeric|min:0',
            'dia_chi' => 'nullable|string|max:255',
            'anh' => 'required|array',
            'anh.*' => 'file|mimes:jpg,jpeg,png|max:10240',
            'tien_ich' => 'nullable|array',
            'tien_ich.*' => 'exists:dich_vu,id',
            'tien_ich_moi' => 'nullable|array',
            'tien_ich_moi.*' => 'string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $phong = \App\Models\Phong::with('dayTro')->findOrFail($validated['phong_id']);

            if ($phong->dayTro->chu_tro_id !== $user->id) {
                return response()->json(['error' => 'Bạn không có quyền đăng bài cho phòng này'], 403);
            }

            $isVerified = $user->is_verified ?? false;
            $status = $isVerified ? 'dang' : 'cho_duyet';

            $baiDang = BaiDang::create([
                'nguoi_dung_id' => $user->id,
                'phong_id' => $phong->id,
                'tieu_de' => $validated['tieu_de'],
                'mo_ta' => $validated['mo_ta'],
                'gia_niem_yet' => $validated['gia_niem_yet'],
                'dia_chi' => $validated['dia_chi'] ?? $phong->dayTro->dia_chi,
                'trang_thai' => $status,
            ]);

            foreach ($request->file('anh') as $index => $file) {
                $filename = 'bai_dang_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('bai_dang', $filename, 'public');
                $this->ensureImageExposed($path);

                $baiDang->anh()->create([
                    'url' => $path,
                    'thu_tu' => $index,
                ]);
            }

            if (!empty($validated['tien_ich'])) {
                foreach ($validated['tien_ich'] as $dvId) {
                   /* DB::table('dich_vu_dinh_ky')->updateOrInsert(
                        ['phong_id' => $phong->id, 'dich_vu_id' => $dvId],
                        ['ngay_cap_nhat' => now()]
                    );*/
                    $dv = DichVu::find($dvId);
                    DB::table('dich_vu_dinh_ky')->updateOrInsert(
                        [
                            'phong_id' => $phong->id,
                            'dich_vu_id' => $dv->id
                        ],
                        [
                            'don_gia' => $dv->don_gia,
                            'so_luong' => 1,
                            'ngay_cap_nhat' => now()
                        ]
                    );
                }
            }

            if (!empty($validated['tien_ich_moi'])) {
                foreach ($validated['tien_ich_moi'] as $tenMoi) {
                    $dvMoi = DichVu::create([
                        'ten' => ucfirst($tenMoi),
                        'don_gia' => 0,
                        'don_vi' => 'tháng',
                    ]);

                    DB::table('dich_vu_dinh_ky')->insert([
                        'phong_id' => $phong->id,
                        'dich_vu_id' => $dvMoi->id,
                        'don_gia' => 0,
                        'so_luong' => 1,
                        'ngay_cap_nhat' => now(),
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isVerified
                    ? '✅ Bài đăng đã được hiển thị ngay (chủ trọ uy tín)'
                    : '🕓 Bài đăng đang chờ duyệt bởi quản trị viên',
                'bai_dang_id' => $baiDang->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Lỗi tạo bài đăng: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể tạo bài đăng.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'tieu_de' => 'required|string|max:255',
            'mo_ta' => 'required|string',
            'gia_niem_yet' => 'required|numeric|min:0',
            'tien_ich' => 'nullable|array',
            'tien_ich.*' => 'exists:dich_vu,id',
            'xoa_anh_cu' => 'nullable|array',
            'anh' => 'nullable|array',
            'anh.*' => 'file|mimes:jpg,jpeg,png|max:10240',
        ]);

        $post = BaiDang::with('anh')->findOrFail($id);

        DB::beginTransaction();
        try {
            $requireReview = (
                $post->tieu_de !== $validated['tieu_de'] ||
                $post->mo_ta !== $validated['mo_ta'] ||
                $post->gia_niem_yet != $validated['gia_niem_yet']
            );

            $isVerified = $user->is_verified ?? false;
            $nonReviewStatuses = ['da_thue', 'an'];

            $currentStatus = BaiDang::where('id', $id)->value('trang_thai');

            $post->tieu_de = $validated['tieu_de'];
            $post->mo_ta = $validated['mo_ta'];
            $post->gia_niem_yet = $validated['gia_niem_yet'];

            if (!in_array($currentStatus, $nonReviewStatuses)) {
                if ($requireReview && !$isVerified) {
                    $post->trang_thai = 'cho_duyet';
                } elseif ($requireReview && $isVerified) {
                    $post->trang_thai = 'dang';
                } else {
                    $post->trang_thai = $currentStatus;
                }
            } else {
                $post->trang_thai = $currentStatus;
            }

            $post->save();

            if (!empty($validated['xoa_anh_cu'])) {
                $anhList = AnhBaiDang::whereIn('id', $validated['xoa_anh_cu'])->get();
                foreach ($anhList as $anh) {
                    Storage::disk('public')->delete($anh->url);
                    $anh->delete();
                }
            }

            if ($request->hasFile('anh')) {
                foreach ($request->file('anh') as $index => $file) {
                    $filename = 'bai_dang_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('bai_dang', $filename, 'public');
                    $this->ensureImageExposed($path);
                    $post->anh()->create([
                        'url' => $path,
                        'thu_tu' => $index,
                    ]);
                }
            }

            if (!empty($validated['tien_ich'])) {
                $dvList = DichVu::whereIn('id', $validated['tien_ich'])->get();
                foreach ($dvList as $dv) {
                    /*DB::table('dich_vu_dinh_ky')->updateOrInsert(
                        ['phong_id' => $post->phong_id, 'dich_vu_id' => $dv->id],
                        ['don_gia' => $dv->don_gia, 'so_luong' => 1, 'ngay_cap_nhat' => now()]
                    );*/
                    DB::table('dich_vu_dinh_ky')->updateOrInsert(
                        [
                            'phong_id' => $post->phong_id,
                            'dich_vu_id' => $dv->id
                        ],
                        [
                            'don_gia' => $dv->don_gia,
                            'so_luong' => 1,
                            'ngay_cap_nhat' => now()
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => match (true) {
                    in_array($currentStatus, $nonReviewStatuses)
                    => '✅ Giữ nguyên trạng thái (đã thuê / đã ẩn).',
                    $requireReview && !$isVerified
                    => '🕓 Cập nhật thành công — bài đăng đang chờ duyệt lại.',
                    $requireReview && $isVerified
                    => '✅ Cập nhật thành công — bài đăng được duyệt tự động (chủ trọ uy tín).',
                    default
                    => '✅ Cập nhật thành công — bài đăng vẫn hiển thị bình thường.',
                },
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Lỗi cập nhật bài đăng: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật bài đăng'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();

            $post = BaiDang::with('phong.dayTro', 'anh')->findOrFail($id);

            $isAdmin = $user->vai_tro === 'admin';
            $isChuTro = $post->phong->dayTro->chu_tro_id === $user->id;

            if (!$isAdmin && !$isChuTro) {
                return response()->json(['error' => 'Bạn không có quyền xóa bài đăng này.'], 403);
            }

            foreach ($post->anh as $anh) {
                Storage::disk('public')->delete($anh->url);
            }

            $post->anh()->delete();
            $post->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '🗑️ Đã xóa bài đăng thành công.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Lỗi xóa bài đăng: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể xóa bài đăng'], 500);
        }
    }

    public function toggle(Request $request, $id)
    {
        try {
            $user = $request->user();
            $post = BaiDang::with('phong.dayTro')->findOrFail($id);

            $isAdmin = $user->vai_tro === 'admin' ?? false;
            $isChuTro = $post->phong->dayTro->chu_tro_id === $user->id;

            if (!$isAdmin && !$isChuTro) {
                return response()->json(['error' => 'Bạn không có quyền thay đổi trạng thái bài đăng này.'], 403);
            }

            $blockedStatuses = ['cho_duyet', 'tu_choi', 'da_thue', 'nhap'];
            if ($post->trang_thai === 'cho_duyet' && !$isAdmin) {
    return response()->json([
        'error' => 'Bài đăng đang chờ duyệt, không thể thay đổi.'
    ], 400);
}


            if ($post->trang_thai === 'dang') {
                $post->trang_thai = 'an';
                $msg = '👁️ Bài đăng đã được ẩn khỏi trang công khai.';
            } elseif ($post->trang_thai === 'an') {
                $post->trang_thai = 'dang';
                $msg = '✅ Bài đăng đã được hiển thị trở lại.';
            } else {
                return response()->json(['error' => 'Trạng thái bài đăng không hợp lệ.'], 400);
            }

            $post->save();

            return response()->json([
                'success' => true,
                'message' => $msg,
                'new_status' => $post->trang_thai,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Lỗi toggle bài đăng: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể thay đổi trạng thái bài đăng.'], 500);
        }
    }

    /**
     * Mirror uploaded files from storage/app/public into public/storage when
     * the OS (e.g. Windows) cannot keep the symbolic link in sync.
     */
    private function ensureImageExposed(string $relativePath): void
    {
        try {
            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
            $diskPath = Storage::disk('public')->path($relativePath);
            if (!file_exists($diskPath)) {
                return;
            }

            $storageRoot = storage_path('app/public');
            $publicStorage = public_path('storage');

            if (!file_exists($publicStorage)) {
                try {
                    File::link($storageRoot, $publicStorage);
                } catch (\Throwable $linkException) {
                    File::ensureDirectoryExists($publicStorage);
                }
            }

            if (is_link($publicStorage)) {
                return;
            }

            $targetPath = $publicStorage . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));

            if (!file_exists($targetPath) || md5_file($targetPath) !== md5_file($diskPath)) {
                File::copy($diskPath, $targetPath);
            }
        } catch (\Throwable $e) {
            Log::warning('Không thể sao chép ảnh sang public/storage', [
                'path' => $relativePath ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
