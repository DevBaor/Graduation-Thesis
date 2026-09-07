<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DongHo;

class DongHoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['message' => 'Chưa đăng nhập'], 401);

        $query = DongHo::with(['phong.dayTro', 'dichVu'])
            ->whereHas(
                'phong.dayTro',
                fn($q) =>
                $q->where('chu_tro_id', $user->id)
            )
            ->whereHas(
                'phong',
                fn($q) =>
                $q->whereIn('trang_thai', ['da_thue', 'dang_thue'])
            );

        if ($request->has('phong_id')) {
            $query->where('phong_id', $request->phong_id);
        }

        $dongHo = $query->orderBy('phong_id')->get();

        return response()->json($dongHo);
    }
}
