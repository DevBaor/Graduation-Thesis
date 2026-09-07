<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use App\Models\HopDong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DanhGiaController extends Controller
{
    public function index()
    {
        $khach = Auth::user()->khachThue;

        $danhGia = DanhGia::with(['hopDong.phong.dayTro'])
            ->where('khach_thue_id', $khach->id)
            ->orderByDesc('ngay_tao')
            ->get();

        return response()->json([
            'message' => 'Danh sách đánh giá của bạn',
            'data' => $danhGia,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'hop_dong_id' => 'required|exists:hop_dong,id',
            'diem_so' => 'required|integer|min:1|max:5',
            'binh_luan' => 'nullable|string|max:500',
        ]);

        $khach = Auth::user()->khachThue;

        // ✅ Kiểm tra hợp đồng có thuộc về khách thuê này không
        $hopDong = HopDong::where('id', $request->hop_dong_id)
            ->where('khach_thue_id', $khach->id)
            ->firstOrFail();

        // ✅ Không cho đánh giá trùng hợp đồng
        if (DanhGia::where('hop_dong_id', $hopDong->id)->where('khach_thue_id', $khach->id)->exists()) {
            return response()->json(['error' => 'Bạn đã đánh giá hợp đồng này rồi.'], 409);
        }

        $dg = DanhGia::create([
            'hop_dong_id' => $hopDong->id,
            'khach_thue_id' => $khach->id,
            'diem_so' => $request->diem_so,
            'binh_luan' => $request->binh_luan,
            'ngay_tao' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Cảm ơn bạn đã đánh giá!',
            'data' => $dg,
        ], 201);
    }
}
