<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsChuTro
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập tài nguyên này (Chủ trọ)'
            ], 403);
        }

        return $next($request);
    }
}
