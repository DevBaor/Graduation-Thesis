<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TienIch;
use App\Models\Phong;
use Illuminate\Support\Facades\Auth;

class TienIchController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $tienIchs = TienIch::with([
            'phongs' => function ($query) use ($user) {
                $query->whereHas('dayTro', function ($q) use ($user) {
                    $q->where('chu_tro_id', $user->id);
                })
                    ->select('phong.id', 'so_phong');
            }
        ])
            ->whereHas('phongs.dayTro', function ($q) use ($user) {
                $q->where('chu_tro_id', $user->id);
            })
            ->orWhereDoesntHave('phongs')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($tienIchs);
    }


    public function store(Request $request)
    {
        $validated = $request->validate(['ten' => 'required|string|max:255']);
        $tienIch = TienIch::create($validated);

        return response()->json(['message' => 'Thêm tiện ích thành công', 'data' => $tienIch]);
    }

    public function update(Request $request, $id)
    {
        $tienIch = TienIch::findOrFail($id);
        $validated = $request->validate(['ten' => 'required|string|max:255']);
        $tienIch->update($validated);

        return response()->json(['message' => 'Cập nhật thành công']);
    }
    public function destroy($id)
    {
        $tienIch = TienIch::find($id);
        if (!$tienIch) {
            return response()->json(['error' => 'Không tìm thấy tiện ích'], 404);
        }

        $tienIch->delete();
        return response()->json(['message' => 'Xóa tiện ích thành công']);
    }
}
