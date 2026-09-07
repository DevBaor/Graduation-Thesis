<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DayTroController extends Controller
{
    /**
     * 📄 GET /api/day-tro
     * Danh sách dãy trọ của chủ trọ đang đăng nhập
     */
    public function index(Request $r)
    {
        $uid = $r->user()->id;

        $items = DB::table('day_tro')
            ->where('chu_tro_id', $uid)
            ->select(
                'id',
                'ten_day_tro',
                'dia_chi',
                'so_phong',
                'dien_tich_tb',
                'gia_trung_binh',
                'mo_ta',
                'tien_ich',
                'ngay_tao',
                'ngay_cap_nhat'
            )
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Danh sách dãy trọ',
            'data' => $items
        ]);
    }

    /**
     * ➕ POST /api/day-tro
     * Thêm mới dãy trọ
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'ten_day_tro' => 'required|string|max:255',
            'dia_chi' => 'required|string|max:255',
            'so_phong' => 'nullable|integer|min:0',
            'dien_tich_tb' => 'nullable|numeric|min:0',
            'gia_trung_binh' => 'nullable|numeric|min:0',
            'mo_ta' => 'nullable|string|max:1000',
            'tien_ich' => 'nullable|string|max:1000',
        ]);

        $uid = $r->user()->id;

        // ✅ Đảm bảo bản ghi chủ trọ tồn tại
        $exists = DB::table('chu_tro')->where('id', $uid)->exists();
        if (!$exists) {
            DB::table('chu_tro')->insert([
                'id' => $uid,
                'dia_chi' => $data['dia_chi'] ?? null
            ]);
        }

        $id = DB::table('day_tro')->insertGetId(array_merge($data, [
            'chu_tro_id' => $uid,
            'ngay_tao' => now(),
            'ngay_cap_nhat' => now(),
        ]));

        return response()->json([
            'message' => '✅ Thêm dãy trọ thành công!',
            'data' => DB::table('day_tro')->find($id)
        ], 201);
    }

    /**
     * 👁️ GET /api/day-tro/{id}
     * Xem chi tiết 1 dãy trọ
     */
    public function show(Request $r, $id)
    {
        $uid = $r->user()->id;
        $dayTro = DB::table('day_tro')
            ->where('chu_tro_id', $uid)
            ->where('id', $id)
            ->first();

        if (!$dayTro) {
            return response()->json(['message' => 'Không tìm thấy dãy trọ'], 404);
        }

        return response()->json([
            'message' => 'Chi tiết dãy trọ',
            'data' => $dayTro
        ]);
    }

    /**
     * ✏️ PUT /api/day-tro/{id}
     * Cập nhật dãy trọ
     */
    public function update(Request $r, $id)
    {
        $uid = $r->user()->id;

        $data = $r->validate([
            'ten_day_tro' => 'required|string|max:255',
            'dia_chi' => 'required|string|max:255',
            'so_phong' => 'nullable|integer|min:0',
            'dien_tich_tb' => 'nullable|numeric|min:0',
            'gia_trung_binh' => 'nullable|numeric|min:0',
            'mo_ta' => 'nullable|string|max:1000',
            'tien_ich' => 'nullable|string|max:1000',
        ]);

        $dayTro = DB::table('day_tro')
            ->where('chu_tro_id', $uid)
            ->where('id', $id)
            ->first();

        if (!$dayTro) {
            return response()->json(['message' => 'Không tìm thấy dãy trọ'], 404);
        }

        DB::table('day_tro')
            ->where('id', $id)
            ->update(array_merge($data, [
                'ngay_cap_nhat' => now(),
            ]));

        $updated = DB::table('day_tro')->find($id);

        return response()->json([
            'message' => '✅ Cập nhật dãy trọ thành công!',
            'data' => $updated,
        ]);
    }

    /**
     * 🗑️ DELETE /api/day-tro/{id}
     * Xóa dãy trọ
     */
    public function destroy(Request $r, $id)
    {
        $uid = $r->user()->id;

        $deleted = DB::table('day_tro')
            ->where('chu_tro_id', $uid)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Không tìm thấy dãy trọ để xóa'], 404);
        }

        return response()->json(['message' => '🗑️ Xóa dãy trọ thành công!']);
    }

    /**
     * 🧩 GET /api/day-tro/chu-tro
     * Lấy danh sách dãy theo chủ trọ
     */
    public function getByChuTro(Request $r)
    {
        $user = $r->user();

        $blocks = DB::table('day_tro')
            ->where('chu_tro_id', $user->id)
            ->select(
                'id',
                'ten_day_tro',
                'dia_chi',
                'so_phong',
                'dien_tich_tb',
                'gia_trung_binh',
                'mo_ta',
                'tien_ich',
                'ngay_cap_nhat'
            )
            ->orderByDesc('ngay_cap_nhat')
            ->get();

        return response()->json([
            'message' => 'Danh sách dãy theo chủ trọ',
            'data' => $blocks
        ]);
    }
}
