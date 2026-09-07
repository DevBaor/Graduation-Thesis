<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Lấy thông tin người dùng hiện tại
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng.'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'so_dien_thoai' => $user->so_dien_thoai,
            'vai_tro' => $user->vai_tro,
            'anh_dai_dien' => $user->anh_dai_dien
                ? url($user->anh_dai_dien)
                : null,
        ]);
    }

    /**
     * Cập nhật thông tin & ảnh đại diện
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng.'], 404);
        }

        $validated = $request->validate([
            'ho_ten' => 'required|string|max:255',
            'so_dien_thoai' => 'nullable|string|max:20',
            'anh_dai_dien' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        if ($request->hasFile('anh_dai_dien')) {
            if ($user->anh_dai_dien && str_starts_with($user->anh_dai_dien, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $user->anh_dai_dien);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('anh_dai_dien')->store('avatars', 'public');
            $this->ensureFileExposed($path);
            $user->anh_dai_dien = '/storage/' . ltrim($path, '/');
        }

        $user->ho_ten = $validated['ho_ten'];
        $user->so_dien_thoai = $validated['so_dien_thoai'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công!',
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'so_dien_thoai' => $user->so_dien_thoai,
                'vai_tro' => $user->vai_tro,
                'anh_dai_dien' => $user->anh_dai_dien
                    ? url($user->anh_dai_dien)
                    : null,
            ],
        ]);
    }
    /**
     * Lấy thông tin ngân hàng hiện tại của chủ trọ
     */
    public function getBankInfo(Request $request)
    {
        $user = $request->user();

        $chuTro = \App\Models\ChuTro::where('id', $user->id)->first();
        if (!$chuTro) {
            return response()->json(['message' => 'Không tìm thấy thông tin chủ trọ.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bank_code' => $chuTro->bank_code,
                'account_no' => $chuTro->account_no,
                'account_name' => $chuTro->account_name,
            ]
        ]);
    }

    /**
     * Cập nhật thông tin ngân hàng
     */
    public function updateBankInfo(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'bank_code' => 'required|string|max:20',
            'account_no' => 'required|string|max:30',
            'account_name' => 'required|string|max:100',
        ]);

        $chuTro = \App\Models\ChuTro::where('id', $user->id)->first();
        if (!$chuTro) {
            return response()->json(['message' => 'Không tìm thấy chủ trọ.'], 404);
        }

        $chuTro->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin ngân hàng thành công!',
            'data' => $chuTro
        ]);
    }

    /**
     * Mirror stored files into public/storage when the OS cannot maintain
     * the default storage symlink (common on Windows).
     */
    private function ensureFileExposed(string $relativePath): void
    {
        try {
            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
            $diskPath = Storage::disk('public')->path($relativePath);
            if (!file_exists($diskPath)) {
                return;
            }

            $storageRoot = storage_path('app/public');
            $publicStorage = public_path('storage');

            if (!file_exists($publicStorage)) {
                try {
                    File::link($storageRoot, $publicStorage);
                } catch (\Throwable $linkException) {
                    File::ensureDirectoryExists($publicStorage);
                }
            }

            if (is_link($publicStorage)) {
                return;
            }

            $targetPath = $publicStorage . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));

            if (!file_exists($targetPath) || md5_file($targetPath) !== md5_file($diskPath)) {
                File::copy($diskPath, $targetPath);
            }
        } catch (\Throwable $e) {
            Log::warning('Không thể đồng bộ avatar vào public/storage', [
                'path' => $relativePath ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

