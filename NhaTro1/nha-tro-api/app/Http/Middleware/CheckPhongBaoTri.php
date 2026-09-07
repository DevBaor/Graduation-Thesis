<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Phong;
use App\Models\DongHo;

class CheckPhongBaoTri
{
    public function handle(Request $request, Closure $next)
    {
        $phongId = null;

        //  Nếu request có phong_id (hợp đồng, v.v.)
        if ($request->has('phong_id')) {
            $phongId = $request->phong_id;
        }

        // Nếu request có dong_ho_id (chỉ số điện nước)
        elseif ($request->has('dong_ho_id')) {
            $dongHo = DongHo::with('phong')->find($request->dong_ho_id);
            $phongId = $dongHo ? $dongHo->phong->id : null;
        }

        //Nếu request có hop_dong_id (tạo hóa đơn)
        elseif ($request->has('hop_dong_id')) {
            $hopDong = \App\Models\HopDong::with('phong')->find($request->hop_dong_id);
            $phongId = $hopDong ? $hopDong->phong->id : null;
        }

        if ($phongId) {
            $phong = Phong::find($phongId);

            if ($phong && $phong->trang_thai === 'bao_tri') {
                return response()->json([
                    'message' => 'Phòng đang bảo trì, không thể thực hiện thao tác này!'
                ], 400);
            }
        }

        return $next($request);
    }
}
