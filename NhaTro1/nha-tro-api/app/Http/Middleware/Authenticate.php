<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Với API: Không redirect, chỉ trả JSON 401.
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        return null; // Không redirect tới login
    }
}
