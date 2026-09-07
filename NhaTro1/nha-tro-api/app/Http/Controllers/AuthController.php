<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use App\Models\ChuTro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:nguoi_dung,email',
            'phone_number' => 'required|string|max:20',
            //'cccd' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:khach_thue,chu_tro,quan_tri',
        ]);

        try {
            $user = NguoiDung::create([
                'ho_ten' => $data['full_name'],
                'email' => $data['email'],
                'so_dien_thoai' => $data['phone_number'],
                'mat_khau' => $data['password'],
                'vai_tro' => $data['role'],
                'trang_thai' => 'hoat_dong',
            ]);

            if ($user->vai_tro === 'khach_thue') {
                \App\Models\KhachThue::create([
                    'id' => $user->id,
                    'nguoi_dung_id' => $user->id,
                    //'cccd' => $data['cccd'] ?? null,
                    'ngan_sach_min' => 0,
                    'ngan_sach_max' => 0,
                ]);
            }

            if ($user->vai_tro === 'chu_tro') {
                ChuTro::create([
                    'id' => $user->id,
                    'dia_chi' => '',
                ]);
            }

            // ✅ Tạo token
            $token = $user->createToken('api')->plainTextToken;

            return response()->json([
                'message' => 'Đăng ký thành công!',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'ho_ten' => $user->ho_ten,
                    'email' => $user->email,
                    'vai_tro' => $user->vai_tro,
                ]
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Đăng ký thất bại', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $r)
    {
        $validator = Validator::make($r->all(), [
            'email' => 'required|email',
            'mat_khau' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user = NguoiDung::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        if (trim($user->mat_khau) !== trim($data['mat_khau'])) {
            return response()->json(['message' => 'Sai mật khẩu!'], 401);
        }

        if (($user->trang_thai ?? null) !== 'hoat_dong') {
            return response()->json(['message' => 'Tài khoản chưa được kích hoạt hoặc đã bị khoá.'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'vai_tro' => $user->vai_tro,
                'anh_dai_dien' => $user->anh_dai_dien ? url($user->anh_dai_dien) : null,
            ]
        ], 200);
    }


    public function logout(Request $r)
    {
        $r->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Đã đăng xuất']);
    }

    public function refresh(Request $r)
    {
        $user = $r->user();
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại hoặc token hết hạn.'], 401);
        }

        $r->user()->currentAccessToken()->delete();

        $newToken = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Làm mới token thành công!',
            'token' => $newToken
        ]);
    }
}
