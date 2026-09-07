<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DichVuController extends Controller
{
    // 📜 Danh sách dịch vụ của chủ trọ
    public function index()
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $dichVus = DB::table('dich_vu')
            ->where('chu_tro_id', $user->id)
            ->orderBy('ten')
            ->get();

        return response()->json($dichVus);
    }

    // 📦 Lấy chi tiết 1 dịch vụ
    public function show($id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $dv = DB::table('dich_vu')
            ->where('chu_tro_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$dv)
            return response()->json(['error' => 'Không tìm thấy dịch vụ!'], 404);

        return response()->json($dv);
    }

    // 🆕 Thêm mới dịch vụ
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $validated = $request->validate([
            'ma' => 'required|string|max:50|unique:dich_vu,ma',
            'ten' => 'required|string|max:255',
            'don_vi' => 'required|string|max:50',
            'don_gia' => 'required|numeric|min:0',
            'co_dong_ho' => 'nullable|boolean',
        ]);

        $validated['co_dong_ho'] = $request->has('co_dong_ho') ? 1 : 0;
        $validated['chu_tro_id'] = $user->id;
        $validated['ngay_tao'] = now();
        $validated['ngay_cap_nhat'] = now();

        $id = DB::table('dich_vu')->insertGetId($validated);

        return response()->json([
            'message' => '✅ Đã thêm dịch vụ mới!',
            'id' => $id
        ]);
    }

    // ✏️ Cập nhật dịch vụ
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $validated = $request->validate([
            'ten' => 'required|string|max:255',
            'don_vi' => 'required|string|max:50',
            'don_gia' => 'required|numeric|min:0',
            'co_dong_ho' => 'nullable|boolean',
        ]);

        $validated['co_dong_ho'] = $request->has('co_dong_ho') ? 1 : 0;

        $exists = DB::table('dich_vu')
            ->where('id', $id)
            ->where('chu_tro_id', $user->id)
            ->exists();

        if (!$exists)
            return response()->json(['error' => 'Không tìm thấy dịch vụ!'], 404);

        DB::table('dich_vu')
            ->where('id', $id)
            ->where('chu_tro_id', $user->id)
            ->update(array_merge($validated, [
                'ngay_cap_nhat' => now()
            ]));

        return response()->json(['message' => '✅ Cập nhật dịch vụ thành công!']);
    }

    // 🗑️ Xóa dịch vụ
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $deleted = DB::table('dich_vu')
            ->where('id', $id)
            ->where('chu_tro_id', $user->id)
            ->delete();

        if (!$deleted)
            return response()->json(['error' => 'Không tìm thấy dịch vụ!'], 404);

        return response()->json(['message' => '🗑️ Đã xóa dịch vụ!']);
    }

    // 📋 Dropdown: tất cả dịch vụ có thể gán
    public function options()
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $options = DB::table('dich_vu')
            ->where('chu_tro_id', $user->id)
            ->select('id', 'ten', 'don_vi', 'don_gia', 'co_dong_ho')
            ->orderBy('ten')
            ->get();

        return response()->json($options);
    }
}
