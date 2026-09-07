<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\ThanhToan;
use App\Models\HoaDon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThanhToanController extends Controller
{
    /*public function store(Request $request)
    {
        $request->validate([
            'hoa_don_id' => 'required|exists:hoa_don,id',
            'so_tien' => 'required|numeric|min:1000',
            'phuong_thuc' => 'required|in:tien_mat,chuyen_khoan,momo,zalopay,khac',
        ]);

        $maGD = 'GD' . strtoupper(Str::random(6));

        $tt = ThanhToan::create([
            'hoa_don_id' => $request->hoa_don_id,
            'ngay_thanh_toan' => now(),
            'so_tien' => $request->so_tien,
            'phuong_thuc' => $request->phuong_thuc,
            'ma_giao_dich' => $maGD,
        ]);

        HoaDon::where('id', $request->hoa_don_id)->increment('so_tien_da_tra', $request->so_tien);

        return response()->json([
            'message' => 'Thanh toán thành công!',
            'data' => $tt
        ], 201);
    }*/
      public function store(Request $request)
    {
        $request->validate([
            'hoa_don_id' => 'required|exists:hoa_don,id',
            'so_tien' => 'required|numeric|min:1000',
            'phuong_thuc' => 'required|in:tien_mat,chuyen_khoan,momo,zalopay,khac',
        ]);

        $maGD = 'GD' . strtoupper(Str::random(6));

        // Tạo thanh toán
        $tt = ThanhToan::create([
            'hoa_don_id' => $request->hoa_don_id,
            'ngay_thanh_toan' => now(),
            'so_tien' => $request->so_tien,
            'phuong_thuc' => $request->phuong_thuc,
            'ma_giao_dich' => $maGD,
        ]);

        // Lấy hóa đơn
        $hoaDon = HoaDon::find($request->hoa_don_id);

        // Cập nhật số tiền đã trả
        $hoaDon->so_tien_da_tra = ($hoaDon->so_tien_da_tra ?? 0) + $request->so_tien;

        // Tính còn lại
        $conLai = $hoaDon->tong_tien - $hoaDon->so_tien_da_tra;

        // Tự tính trạng thái
        if ($conLai <= 0) {
            $hoaDon->trang_thai = 'da_thanh_toan';
        } elseif ($hoaDon->so_tien_da_tra > 0) {
            $hoaDon->trang_thai = 'mot_phan';
        } else {
            $hoaDon->trang_thai = 'chua_thanh_toan';
        }

        $hoaDon->save();

        return response()->json([
            'message' => 'Thanh toán thành công!',
            'trang_thai_moi' => $hoaDon->trang_thai,
            'da_tra' => $hoaDon->so_tien_da_tra,
            'con_lai' => max(0, $conLai),
            'data' => $tt
        ], 201);
    }


}
