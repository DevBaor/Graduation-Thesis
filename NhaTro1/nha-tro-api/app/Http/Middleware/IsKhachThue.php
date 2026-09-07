<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsKhachThue
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->vai_tro !== 'khach_thue') {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập tài nguyên này (Khách thuê)'
            ], 403);
        }

        return $next($request);
    }
}
