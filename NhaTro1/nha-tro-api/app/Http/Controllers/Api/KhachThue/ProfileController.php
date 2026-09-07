<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('khachThue');

        if ($user->anh_dai_dien) {
            $user->anh_dai_dien = asset('storage/' . ltrim($user->anh_dai_dien, '/'));
        }

        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'ho_ten' => 'sometimes|string|max:255',
            'so_dien_thoai' => 'sometimes|string|max:20',
            'anh_dai_dien' => 'nullable|file|image|max:2048', 
        ]);

        /*if ($request->hasFile('anh_dai_dien')) {
            $file = $request->file('anh_dai_dien');
            $path = $file->store('avatars', 'public'); 

            if ($user->anh_dai_dien && Storage::disk('public')->exists($user->anh_dai_dien)) {
                Storage::disk('public')->delete($user->anh_dai_dien);
            }

            $user->anh_dai_dien = $path;
        }*/
        if ($request->hasFile('anh_dai_dien')) {
            $file = $request->file('anh_dai_dien');

            $img = Image::make($file)->resize(600, 600, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            });

            $name = uniqid().'.jpg';
            Storage::disk('public')->put("avatars/$name", (string)$img->encode('jpg', 80));

            if ($user->anh_dai_dien && Storage::disk('public')->exists($user->anh_dai_dien)) {
                Storage::disk('public')->delete($user->anh_dai_dien);
            }

            $user->anh_dai_dien = "avatars/$name";
        }

        $user->fill($request->only(['ho_ten', 'so_dien_thoai']));
        $user->save();

        $user->anh_dai_dien = $user->anh_dai_dien
            ? asset('storage/' . ltrim($user->anh_dai_dien, '/'))
            : null;

        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công!',
            'data' => $user,
        ]);
    }
    public function updateAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|max:2048'
    ]);

    $user = Auth::user();
    $path = $request->file('avatar')->store('avatars', 'public');

    if ($user->anh_dai_dien && Storage::disk('public')->exists($user->anh_dai_dien)) {
        Storage::disk('public')->delete($user->anh_dai_dien);
    }

    $user->anh_dai_dien = $path;
    $user->save();

    return response()->json([
        'message' => 'Cập nhật ảnh đại diện thành công!',
        'avatar_url' => asset('storage/' . $path)
    ]);
}

}
