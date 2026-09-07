<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\HoaDon;
use App\Models\KhachThue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HoaDonController_1111 extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        // 🔹 Lấy khách thuê gắn với tài khoản người dùng
        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê.'], 404);
        }

        // 🔹 Lấy danh sách hóa đơn theo hợp đồng khách thuê
        $hoaDons = HoaDon::with(['hopDong.phong.dayTro'])
            ->whereHas('hopDong', fn($q) => $q->where('khach_thue_id', $khachThue->id))
            ->orderByDesc('thang')
            ->get()
            ->map(function ($hd) {
                $hd->qua_han = (
                    in_array($hd->trang_thai, ['chua_thanh_toan', 'mot_phan'])
                    && Carbon::parse($hd->han_thanh_toan)->lt(now())
                );
                return $hd;
            })
            ->values();

        return response()->json([
            'message' => 'Danh sách hóa đơn của bạn',
            'data' => $hoaDons,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê.'], 404);
        }

        // 🔹 Lấy hóa đơn thuộc hợp đồng của khách thuê
        $hoaDon = HoaDon::with(['hopDong.phong.dayTro.chuTro'])
            ->whereHas('hopDong', fn($q) => $q->where('khach_thue_id', $khachThue->id))
            ->find($id);

        if (!$hoaDon) {
            return response()->json(['error' => 'Không tìm thấy hóa đơn.'], 404);
        }

        $phong = optional($hoaDon->hopDong->phong);
        $dayTro = optional($phong->dayTro);
        $chuTro = optional($dayTro->chuTro);

        // 🔹 Chi tiết dịch vụ (internet, rác, bảo vệ,...)
        $chiTietDichVu = DB::table('chi_tiet_dich_vu')
            ->leftJoin('dich_vu', 'chi_tiet_dich_vu.dich_vu_id', '=', 'dich_vu.id')
            ->where('chi_tiet_dich_vu.hoa_don_id', $hoaDon->id)
            ->select(
                'dich_vu.ten as ten_dich_vu',
                'chi_tiet_dich_vu.so_luong',
                'chi_tiet_dich_vu.don_gia',
                'chi_tiet_dich_vu.thanh_tien'
            )
            ->get();

        // 🔹 Chi tiết điện & nước (đồng hồ)
        $chiTietDongHo = DB::table('chi_tiet_dong_ho')
            ->leftJoin('dong_ho', 'chi_tiet_dong_ho.dong_ho_id', '=', 'dong_ho.id')
            ->leftJoin('dich_vu', 'chi_tiet_dong_ho.dich_vu_id', '=', 'dich_vu.id')
            ->where('chi_tiet_dong_ho.hoa_don_id', $hoaDon->id)
            ->select(
                'dong_ho.ma_dong_ho',
                'dich_vu.ten as ten_dich_vu',
                'chi_tiet_dong_ho.chi_so_cu',
                'chi_tiet_dong_ho.chi_so_moi',
                'chi_tiet_dong_ho.san_luong',
                'chi_tiet_dong_ho.don_gia',
                'chi_tiet_dong_ho.thanh_tien'
            )
            ->get();

        // 🔹 Tính lại tổng thực tế để đảm bảo đồng bộ
        $tongTinhLai = ($hoaDon->tien_phong ?? 0)
            + collect($chiTietDichVu)->sum('thanh_tien')
            + collect($chiTietDongHo)->sum('thanh_tien');

        // 🔹 Trả về JSON chuẩn
        return response()->json([
            'id' => $hoaDon->id,
            'thang' => $hoaDon->thang,
            'tien_phong' => $hoaDon->tien_phong,
            'tien_dich_vu' => collect($chiTietDichVu)->sum('thanh_tien'),
            'tien_dong_ho' => collect($chiTietDongHo)->sum('thanh_tien'),
            'tong_tien' => (float) $tongTinhLai,
            'trang_thai' => $hoaDon->trang_thai,
            'han_thanh_toan' => $hoaDon->han_thanh_toan
                ? Carbon::parse($hoaDon->han_thanh_toan)->format('d/m/Y')
                : null,

            'phong' => [
                'so_phong' => $phong->so_phong ?? 'N/A',
                'dien_tich' => $phong->dien_tich ?? null,
            ],

            'day_tro' => [
                'ten_day_tro' => $dayTro->ten_day_tro ?? 'N/A',
                'dia_chi' => $dayTro->dia_chi ?? 'N/A',
            ],

            'chu_tro' => [
                'ho_ten' => $chuTro->ho_ten ?? 'Chưa cập nhật',
                'so_dien_thoai' => $chuTro->so_dien_thoai ?? 'Chưa cập nhật',
            ],

            'chi_tiet_dich_vu' => $chiTietDichVu,
            'chi_tiet_dien_nuoc' => $chiTietDongHo,
        ]);
    }
}
