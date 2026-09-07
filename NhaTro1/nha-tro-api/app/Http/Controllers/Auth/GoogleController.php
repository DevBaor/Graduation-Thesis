<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\NguoiDung;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use App\Models\KhachThue;
use Illuminate\Support\Facades\Http;

class GoogleController extends Controller
{
    /**
     * Chuyển hướng người dùng đến trang xác thực của Google.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        $frontendCallbackUrl = env('FRONTEND_CALLBACK_URL', 'http://localhost:8001/auth/google/callback-handler');
        $frontendLoginUrl    = env('FRONTEND_LOGIN_URL', 'http://localhost:8001/login');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = NguoiDung::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Tạo user mới với vai trò KHÁCH THUÊ
                $user = NguoiDung::create([
                    'ho_ten'       => $googleUser->getName(),
                    'email'        => $googleUser->getEmail(),
                    'mat_khau'     => Str::random(24),
                    'anh_dai_dien' => $googleUser->getAvatar(),
                    'vai_tro'      => 'khach_thue',      
                    'google_id'    => $googleUser->getId(),
                    'trang_thai'   => 'hoat_dong',
                ]);

                // Tạo record khách thuê tương ứng (giống logic register)
                KhachThue::create([
                    'nguoi_dung_id' => $user->id,
                    'cccd'          => null,
                    'ngan_sach_min' => 0,
                    'ngan_sach_max' => 0,
                ]);
            } else {
                // Nếu đã có user thì chỉ cập nhật thông tin Google
                $user->update([
                    'anh_dai_dien' => $googleUser->getAvatar(),
                    'google_id'    => $googleUser->getId(),
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = [
                'id'          => $user->id,
                'ho_ten'      => $user->ho_ten,
                'email'       => $user->email,
                'vai_tro'     => $user->vai_tro,
                'anh_dai_dien'=> $user->anh_dai_dien ? url($user->anh_dai_dien) : null,
            ];

            $encodedUser = base64_encode(json_encode($userData));

            return Redirect::to("{$frontendCallbackUrl}?token={$token}&user={$encodedUser}");
        } catch (\Exception $e) {
            $errorMessage = base64_encode($e->getMessage());
            return Redirect::to("{$frontendLoginUrl}?google_error={$errorMessage}");
        }
    }
    public function mobileLogin(Request $request)
    {
        $data = $request->validate([
            'id_token' => 'required|string',
        ]);

        $googleRes = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $data['id_token'],
        ]);

        if ($googleRes->failed()) {
            return response()->json([
                'message' => 'Token Google không hợp lệ.',
            ], 401);
        }

        $payload = $googleRes->json();

        $email    = $payload['email'] ?? null;
        $name     = $payload['name'] ?? ($email ?? 'Người dùng Google');
        $googleId = $payload['sub'] ?? null;

        if (!$email) {
            return response()->json([
                'message' => 'Không lấy được email từ tài khoản Google.',
            ], 400);
        }

        $user = NguoiDung::where('email', $email)->first();

        if (!$user) {
            $user = NguoiDung::create([
                'ho_ten'        => $name,
                'email'         => $email,
                'so_dien_thoai' => '',
                'mat_khau'      => Str::random(16),
                'vai_tro'       => 'khach_thue',
                'trang_thai'    => 'hoat_dong',
                'google_id'     => $googleId,
            ]);

            KhachThue::create([
                'nguoi_dung_id' => $user->id,
                'ngan_sach_min' => 0,
                'ngan_sach_max' => 0,
            ]);
        } else {
            if ($user->vai_tro !== 'khach_thue') {
                return response()->json([
                    'message' => 'Ứng dụng mobile chỉ dành cho khách thuê.',
                ], 403);
            }
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập Google thành công!',
            'token'   => $token,
            'user'    => [
                'id'          => $user->id,
                'ho_ten'      => $user->ho_ten,
                'email'       => $user->email,
                'vai_tro'     => $user->vai_tro,
                'anh_dai_dien'=> $user->anh_dai_dien ? url($user->anh_dai_dien) : null,
            ],
        ]);
    }
}
