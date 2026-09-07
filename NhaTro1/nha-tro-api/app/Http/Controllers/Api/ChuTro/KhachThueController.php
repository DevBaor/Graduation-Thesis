<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class KhachThueController extends Controller
{
    /** Danh sách khách thuê của 1 chủ trọ (có thể lọc theo dãy) */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $dayTroId = $request->query('day_tro_id');

        // 🧾 Chỉ lấy khách đang có hợp đồng "hiệu lực"
        $rows = DB::table('hop_dong')
            ->join('khach_thue', 'hop_dong.khach_thue_id', '=', 'khach_thue.id')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'khach_thue.nguoi_dung_id')
            ->join('phong', 'hop_dong.phong_id', '=', 'phong.id')
            ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->where('hop_dong.trang_thai', 'hieu_luc')
            ->when($dayTroId, fn($q) => $q->where('day_tro.id', $dayTroId))
            ->select([
                'khach_thue.id',
                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.so_dien_thoai',
                'phong.so_phong',
                'day_tro.ten_day_tro',
                'hop_dong.ngay_bat_dau',
                'hop_dong.ngay_ket_thuc',
                'hop_dong.trang_thai'
            ])
            ->orderBy('nguoi_dung.ho_ten')
            ->get()
            ->map(function ($r) {
                $r->trang_thai_thue = 'Đang thuê';
                return $r;
            });

        return response()->json($rows);
    }


    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $basic = DB::table('khach_thue')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'khach_thue.nguoi_dung_id')
            ->leftJoin('hop_dong', 'hop_dong.khach_thue_id', '=', 'khach_thue.id')
            ->leftJoin('phong', 'phong.id', '=', 'hop_dong.phong_id')
            ->leftJoin('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('khach_thue.id', $id)
            ->where('day_tro.chu_tro_id', $user->id)
            ->select([
                'khach_thue.id',
                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.so_dien_thoai',
                'khach_thue.cccd',
                'khach_thue.ngan_sach_min',
                'khach_thue.ngan_sach_max',
                'khach_thue.ngay_tao',
                'khach_thue.ngay_cap_nhat',
            ])
            ->first();

        if (!$basic) {
            return response()->json(['error' => 'Không tìm thấy khách thuê'], 404);
        }

        $contracts = DB::table('hop_dong')
            ->join('phong', 'phong.id', '=', 'hop_dong.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('hop_dong.khach_thue_id', $id)
            ->where('day_tro.chu_tro_id', $user->id)
            ->select([
                'hop_dong.id',
                'phong.so_phong',
                'day_tro.ten_day_tro',
                'hop_dong.ngay_bat_dau',
                'hop_dong.ngay_ket_thuc',
                'hop_dong.tien_coc',
                'hop_dong.trang_thai'
            ])
            ->orderByDesc('hop_dong.ngay_bat_dau')
            ->get();

        return response()->json([
            'khach_thue' => $basic,
            'hop_dong' => $contracts,
        ]);
    }


    /** Tạo khách thuê mới */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('nguoi_dung', 'email')],
            'so_dien_thoai' => ['nullable', 'string', 'max:30'],
            'cccd' => ['nullable', 'string', 'max:30'],
            'ghi_chu' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $nguoiDungId = DB::table('nguoi_dung')->insertGetId([
                'ho_ten' => $data['ho_ten'],
                'email' => $data['email'] ?? null,
                'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
                'vai_tro' => 'khach_thue',
                'mat_khau' => '12345678', // ❌ KHÔNG HASH
                'ngay_tao' => now(),
            ]);

            $id = DB::table('khach_thue')->insertGetId([
                'nguoi_dung_id' => $nguoiDungId,
                'cccd' => $data['cccd'] ?? null,
                'ghi_chu' => $data['ghi_chu'] ?? null,
                'ngay_tao' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Tạo khách thuê thành công', 'id' => $id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi tạo khách thuê: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể tạo khách thuê'], 500);
        }
    }

    /** Cập nhật khách thuê */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('nguoi_dung', 'email')->ignore($request->input('nguoi_dung_id'))],
            'so_dien_thoai' => ['nullable', 'string', 'max:30'],
            'cccd' => ['nullable', 'string', 'max:30'],
            'ghi_chu' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $row = DB::table('khach_thue')
                ->join('nguoi_dung', 'nguoi_dung.id', '=', 'khach_thue.nguoi_dung_id')
                ->leftJoin('hop_dong', 'hop_dong.khach_thue_id', '=', 'khach_thue.id')
                ->leftJoin('phong', 'phong.id', '=', 'hop_dong.phong_id')
                ->leftJoin('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
                ->where('khach_thue.id', $id)
                //->where('day_tro.chu_tro_id', $user->id)
                ->select('khach_thue.id', 'khach_thue.nguoi_dung_id')
                ->first();

            if (!$row)
                return response()->json(['error' => 'Không tìm thấy khách thuê'], 404);

            DB::table('nguoi_dung')->where('id', $row->nguoi_dung_id)->update([
                'ho_ten' => $data['ho_ten'],
                'email' => $data['email'] ?? null,
                'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
            ]);

            DB::table('khach_thue')->where('id', $id)->update([
                'cccd' => $data['cccd'] ?? null,
                'ghi_chu' => $data['ghi_chu'] ?? null,
                'ngay_cap_nhat' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Cập nhật khách thuê thành công']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi cập nhật khách thuê: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật'], 500);
        }
    }

    /** Xóa khách thuê */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $hasActive = DB::table('hop_dong')
            ->join('phong', 'phong.id', '=', 'hop_dong.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->where('hop_dong.khach_thue_id', $id)
            ->where('hop_dong.trang_thai', 'hieu_luc')
            ->exists();

        if ($hasActive) {
            return response()->json(['error' => 'Không thể xóa: khách đang có hợp đồng hiệu lực'], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('khach_thue')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['message' => 'Đã xóa khách thuê']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi xóa khách thuê: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể xóa'], 500);
        }
    }
}
