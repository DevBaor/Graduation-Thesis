<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DichVuDinhKyController extends Controller
{
    // 📜 Danh sách dịch vụ định kỳ của tất cả phòng thuộc chủ trọ
    public function index()
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $rows = DB::table('dich_vu_dinh_ky')
            ->join('phong', 'phong.id', '=', 'dich_vu_dinh_ky.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->join('dich_vu', 'dich_vu.id', '=', 'dich_vu_dinh_ky.dich_vu_id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->select([
                'dich_vu_dinh_ky.id',
                'day_tro.ten_day_tro',
                'phong.so_phong',
                'dich_vu.ten as ten_dich_vu',
                'dich_vu_dinh_ky.don_gia',
                'dich_vu_dinh_ky.so_luong',
                'dich_vu_dinh_ky.ngay_cap_nhat'
            ])
            ->orderBy('day_tro.ten_day_tro')
            ->orderBy('phong.so_phong')
            ->get();

        return response()->json($rows);
    }

    // 📦 Dịch vụ định kỳ của 1 phòng
    public function show($phong_id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $rows = DB::table('dich_vu_dinh_ky')
            ->join('dich_vu', 'dich_vu.id', '=', 'dich_vu_dinh_ky.dich_vu_id')
            ->join('phong', 'phong.id', '=', 'dich_vu_dinh_ky.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->where('phong.id', $phong_id)
            ->select([
                'dich_vu_dinh_ky.id',
                'dich_vu.ten as ten_dich_vu',
                'dich_vu.don_vi',
                'dich_vu_dinh_ky.don_gia',
                'dich_vu_dinh_ky.so_luong',
                'phong.so_phong',
                'day_tro.ten_day_tro'
            ])
            ->get();

        return response()->json($rows);
    }

    // ➕ Thêm dịch vụ định kỳ cho phòng
    public function store(Request $request, $phong_id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $validated = $request->validate([
            'dich_vu_id' => 'required|integer|exists:dich_vu,id',
            'don_gia' => 'required|numeric|min:0',
            'so_luong' => 'required|numeric|min:0.1',
        ]);

        $checkPhong = DB::table('phong')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('phong.id', $phong_id)
            ->where('day_tro.chu_tro_id', $user->id)
            ->exists();

        if (!$checkPhong)
            return response()->json(['error' => 'Phòng không thuộc chủ trọ này!'], 403);

        $exists = DB::table('dich_vu_dinh_ky')
            ->where('phong_id', $phong_id)
            ->where('dich_vu_id', $validated['dich_vu_id'])
            ->exists();

        if ($exists)
            return response()->json(['error' => 'Dịch vụ này đã gán cho phòng!'], 400);

        DB::table('dich_vu_dinh_ky')->insert([
            'phong_id' => $phong_id,
            'dich_vu_id' => $validated['dich_vu_id'],
            'don_gia' => $validated['don_gia'],
            'so_luong' => $validated['so_luong'],
            'ngay_tao' => now(),
            'ngay_cap_nhat' => now(),
        ]);

        return response()->json(['message' => '✅ Đã thêm dịch vụ định kỳ cho phòng!']);
    }

    // ✏️ Cập nhật giá / số lượng dịch vụ định kỳ
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $validated = $request->validate([
            'don_gia' => 'required|numeric|min:0',
            'so_luong' => 'required|numeric|min:0.1',
        ]);

        $exists = DB::table('dich_vu_dinh_ky')
            ->join('phong', 'phong.id', '=', 'dich_vu_dinh_ky.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->where('dich_vu_dinh_ky.id', $id)
            ->exists();

        if (!$exists)
            return response()->json(['error' => 'Không tìm thấy dịch vụ định kỳ hoặc không có quyền!'], 404);

        DB::table('dich_vu_dinh_ky')
            ->where('id', $id)
            ->update(array_merge($validated, ['ngay_cap_nhat' => now()]));

        return response()->json(['message' => '✅ Đã cập nhật dịch vụ định kỳ!']);
    }

    // 🗑️ Xóa dịch vụ định kỳ
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $deleted = DB::table('dich_vu_dinh_ky')
            ->join('phong', 'phong.id', '=', 'dich_vu_dinh_ky.phong_id')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('day_tro.chu_tro_id', $user->id)
            ->where('dich_vu_dinh_ky.id', $id)
            ->delete();

        if (!$deleted)
            return response()->json(['error' => 'Không tìm thấy dịch vụ định kỳ!'], 404);

        return response()->json(['message' => '🗑️ Đã xóa dịch vụ định kỳ!']);
    }
}
