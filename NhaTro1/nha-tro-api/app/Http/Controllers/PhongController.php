<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhongController extends Controller
{
    // GET /api/phong?per_page=...
    public function index(Request $r)
    {
        $query = DB::table('phong')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->select('phong.*', 'day_tro.ten_day_tro', 'day_tro.dia_chi');

        if ($r->filled('day_tro_id'))
            $query->where('phong.day_tro_id', $r->day_tro_id);
        if ($r->filled('trang_thai'))
            $query->where('phong.trang_thai', $r->trang_thai);

        if ($r->filled('per_page'))
            return $query->orderByDesc('phong.id')->paginate((int) $r->per_page);
        return $query->orderByDesc('phong.id')->get();
    }

    // GET /api/phong/{id}
    public function show($id)
    {
        $p = DB::table('phong')
            ->join('day_tro', 'day_tro.id', '=', 'phong.day_tro_id')
            ->where('phong.id', $id)
            ->select('phong.*', 'day_tro.ten_day_tro', 'day_tro.dia_chi')
            ->first();
        if (!$p)
            return response()->json(['message' => 'Không tìm thấy phòng'], 404);
        return response()->json($p);
    }

    // POST /api/phong (chủ trọ tạo phòng)
    public function store(Request $r)
    {
        $data = $r->validate([
            'day_tro_id' => 'required|integer|exists:day_tro,id',
            'so_phong' => 'required|string|max:50',
            'gia' => 'required|numeric|min:0',
            'trang_thai' => 'required|in:trong,da_thue,bao_tri',
            'suc_chua' => 'required|integer|min:1',
            'dien_tich' => 'required|numeric|min:0',
            'tang' => 'required|integer',
        ]);

        $uid = $r->user()->id;
        // Chỉ cho phép tạo phòng trong dãy của chính chủ trọ
        $own = DB::table('day_tro')->where('id', $data['day_tro_id'])->where('chu_tro_id', $uid)->exists();
        if (!$own)
            return response()->json(['message' => 'Bạn không sở hữu dãy trọ này'], 403);

        $id = DB::table('phong')->insertGetId([
            'day_tro_id' => $data['day_tro_id'],
            'so_phong' => $data['so_phong'],
            'gia' => $data['gia'],
            'trang_thai' => $data['trang_thai'],
            'suc_chua' => $data['suc_chua'],
            'dien_tich' => $data['dien_tich'],
            'tang' => $data['tang'],
            'ngay_tao' => now(),
            'ngay_cap_nhat' => now(),
        ]);

        return response()->json(['id' => $id] + $data, 201);
    }
    public function getByChuTro(Request $request)
    {
        $user = $request->user();

        $rooms = \App\Models\Phong::query()
            ->where('chu_tro_id', $user->id)
            ->with('dayTro:id,ten_day')
            ->get();

        return response()->json($rooms);
    }

}
