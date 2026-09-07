<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\NguoiDung;

class AvatarController extends Controller
{
    // POST /api/me/avatar
    public function upload(Request $r)
    {
        $r->validate([
            'anh' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $r->user();

        // Xoá ảnh cũ nếu có (và nếu nó nằm trong /storage/avatars/)
        if (!empty($user->anh_dai_dien) && str_starts_with($user->anh_dai_dien, '/storage/avatars/')) {
            $oldPath = str_replace('/storage/', '', $user->anh_dai_dien); // "avatars/..."
            Storage::disk('public')->delete($oldPath);
        }

        // Lưu ảnh mới
        $path = $r->file('anh')->store('avatars/' . $user->id, 'public'); // storage/app/public/avatars/{id}/xxx.jpg

        // Lưu URL public vào DB (cột anh_dai_dien là VARCHAR)
        $publicUrl = '/storage/' . $path;

        NguoiDung::where('id', $user->id)->update(['anh_dai_dien' => $publicUrl]);

        return response()->json([
            'message' => 'Upload avatar thành công',
            'url' => url($publicUrl),
            'relative_url' => $publicUrl,
        ], 201);
    }

    // DELETE /api/me/avatar (tuỳ chọn)
    public function destroy(Request $r)
    {
        $user = $r->user();
        if (!empty($user->anh_dai_dien) && str_starts_with($user->anh_dai_dien, '/storage/avatars/')) {
            $oldPath = str_replace('/storage/', '', $user->anh_dai_dien);
            Storage::disk('public')->delete($oldPath);
        }
        NguoiDung::where('id', $user->id)->update(['anh_dai_dien' => '']);
        return response()->json(['message' => 'Đã xoá avatar']);
    }
}
