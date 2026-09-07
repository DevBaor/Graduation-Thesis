<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\NguoiThan;
use Illuminate\Http\Request;

class NguoiThanController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Chưa đăng nhập hoặc token không hợp lệ',
                'auth_user' => auth()->user()
            ], 401);
        }

        $user = auth()->user();
        $chuTroId = $user->chu_tro_id ?? $user->id;

        $nguoiThan = \DB::table('nguoi_than')
            ->join('hop_dong', 'nguoi_than.khach_thue_id', '=', 'hop_dong.khach_thue_id')
            ->join('phong', 'hop_dong.phong_id', '=', 'phong.id')
            ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
            ->join('khach_thue', 'nguoi_than.khach_thue_id', '=', 'khach_thue.id')
            ->join('nguoi_dung', 'khach_thue.nguoi_dung_id', '=', 'nguoi_dung.id') // ✅ JOIN để lấy tên khách thuê
            ->where('day_tro.chu_tro_id', $chuTroId)
            ->select(
                'nguoi_than.id',
                'nguoi_than.ho_ten',
                'nguoi_than.moi_quan_he',
                'nguoi_than.so_dien_thoai',
                'nguoi_than.khach_thue_id',
                'nguoi_dung.ho_ten as ten_khach_thue' 
            )
            ->distinct()
            ->get();

        return response()->json(['data' => $nguoiThan]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'khach_thue_id' => 'required|exists:khach_thue,id',
            'ho_ten' => 'required|string|max:100',
            'so_dien_thoai' => 'nullable|string|max:20',
            'moi_quan_he' => 'nullable|string|max:50',
        ]);

        $nguoiThan = NguoiThan::create($validated);

        return response()->json([
            'message' => 'Thêm người thân thành công',
            'data' => $nguoiThan
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $nguoiThan = NguoiThan::findOrFail($id);

        $validated = $request->validate([
            'ho_ten' => 'sometimes|required|string|max:100',
            'so_dien_thoai' => 'nullable|string|max:20',
            'moi_quan_he' => 'nullable|string|max:50',
        ]);

        $nguoiThan->update($validated);

        return response()->json([
            'message' => 'Cập nhật thông tin người thân thành công',
            'data' => $nguoiThan
        ]);
    }

    public function destroy($id)
    {
        $nguoiThan = NguoiThan::findOrFail($id);
        $nguoiThan->delete();

        return response()->json(['message' => 'Đã xóa người thân']);
    }
}
