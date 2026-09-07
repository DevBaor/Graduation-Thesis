<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\HoaDon;
use App\Models\KhachThue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HoaDonController extends Controller
{
    /**
     * 🔹 Lấy danh sách hóa đơn của khách thuê hiện tại
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê.'], 404);
        }

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

    /**
     * 🔹 Xem chi tiết hóa đơn
     */
    /*public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $khachThue = KhachThue::where('nguoi_dung_id', $user->id)->first();
        if (!$khachThue) {
            return response()->json(['error' => 'Không tìm thấy khách thuê.'], 404);
        }

        $hoaDon = HoaDon::with(['hopDong.phong.dayTro.chuTro'])
            ->whereHas('hopDong', fn($q) => $q->where('khach_thue_id', $khachThue->id))
            ->find($id);

        if (!$hoaDon) {
            return response()->json(['error' => 'Không tìm thấy hóa đơn.'], 404);
        }

        $phong = optional($hoaDon->hopDong->phong);
        $dayTro = optional($phong->dayTro);
        $chuTro = optional($dayTro->chuTro);

        $chiTietDichVu = DB::table('chi_tiet_dich_vu')
            ->leftJoin('dich_vu', 'chi_tiet_dich_vu.dich_vu_id', '=', 'dich_vu.id')
            ->where('chi_tiet_dich_vu.hoa_don_id', $hoaDon->id)
            ->select('dich_vu.ten as ten_dich_vu', 'chi_tiet_dich_vu.so_luong', 'chi_tiet_dich_vu.don_gia', 'chi_tiet_dich_vu.thanh_tien')
            ->get();

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

        $tongTinhLai = ($hoaDon->tien_phong ?? 0)
            + collect($chiTietDichVu)->sum('thanh_tien')
            + collect($chiTietDongHo)->sum('thanh_tien');

        return response()->json([
            'id' => $hoaDon->id,
            'thang' => $hoaDon->thang,
            'tong_tien' => $hoaDon->tong_tien,
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
                'bank_code' => $chuTro->bank_code ?? 'MB',
                'account_no' => $chuTro->account_no ?? '0000000000',
                'account_name' => $chuTro->account_name ?? strtoupper($chuTro->ho_ten ?? 'TEN CHU TRO'),
            ],
            'chi_tiet_dich_vu' => $chiTietDichVu,
            'chi_tiet_dien_nuoc' => $chiTietDongHo,
        ]);

    }*/
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

        // 🔹 load luôn chuTro.nguoiDung để lấy tên + SĐT
$hoaDon = HoaDon::with([
    'hopDong.phong.dayTro.chuTro'
])
->whereHas('hopDong', fn($q) =>
    $q->where('khach_thue_id', $khachThue->id)
)
->where('id', $id)
->first();


if (!$hoaDon) {
    return response()->json(['error' => 'Không tìm thấy hóa đơn.'], 404);
}

$phong    = $hoaDon->hopDong->phong ?? null;
$dayTro   = $phong?->dayTro;
$chuTro   = $dayTro?->chuTro;
$chuTroND = $chuTro?->nguoiDung;


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

        $tongTinhLai = ($hoaDon->tien_phong ?? 0)
            + collect($chiTietDichVu)->sum('thanh_tien')
            + collect($chiTietDongHo)->sum('thanh_tien');

     $tongTien = $hoaDon->tong_tien ?? $tongTinhLai;

if ($hoaDon->trang_thai === 'da_thanh_toan') {
    $soTienDaTra  = $tongTien;
    $soTienConLai = 0;
} else {
    $soTienDaTra  = $hoaDon->so_tien_da_tra ?? 0;
    $soTienConLai = max(0, $tongTien - $soTienDaTra);
}


        return response()->json([
    'message' => 'Chi tiết hóa đơn',
    'data' => [
        'id'              => $hoaDon->id,
        'thang'           => $hoaDon->thang,
        'tong_tien'       => $tongTien,
        'tien_phong'      => $hoaDon->tien_phong ?? 0,
        'tien_dich_vu'    => $hoaDon->tien_dich_vu ?? 0,
        'tien_dong_ho'    => $hoaDon->tien_dong_ho ?? 0,
        'so_tien_da_tra'  => $soTienDaTra,
        'so_tien_con_lai' => $soTienConLai,
        'trang_thai'      => $hoaDon->trang_thai,
        'han_thanh_toan'  => $hoaDon->han_thanh_toan
            ? Carbon::parse($hoaDon->han_thanh_toan)->format('d/m/Y')
            : null,

        'phong' => [
            'so_phong'  => $phong?->so_phong ?? 'N/A',
            'dien_tich' => $phong?->dien_tich ?? null,
        ],
        'day_tro' => [
            'ten_day_tro' => $dayTro?->ten_day_tro ?? 'N/A',
            'dia_chi'     => $dayTro?->dia_chi ?? 'N/A',
        ],
      'chu_tro' => [
    // 👤 thông tin người
    'ho_ten'        => $chuTroND?->ho_ten ?? 'Chưa cập nhật',
    'so_dien_thoai' => $chuTroND?->so_dien_thoai ?? 'Chưa cập nhật',

    // 🏦 thông tin ngân hàng
    'bank_code'     => $chuTro?->bank_code ?? 'MB',
    'account_no'    => $chuTro?->account_no ?? '0000000000',
    'account_name'  => $chuTro?->account_name
        ?? strtoupper($chuTroND?->ho_ten ?? 'TEN CHU TRO'),
],


        'chi_tiet_dich_vu'   => $chiTietDichVu,
        'chi_tiet_dien_nuoc' => $chiTietDongHo,
    ]
]);

    }


        

    /**
     * 🔹 Khách xác nhận đã chuyển khoản
     */
    public function xacNhanThanhToan($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập.'
                ], 401);
            }

            $hoaDon = HoaDon::with('hopDong.phong.dayTro.chuTro')
                ->whereHas('hopDong', fn($q) => $q->whereHas('khachThue', fn($k) => $k->where('nguoi_dung_id', $user->id)))
                ->find($id);

            if (!$hoaDon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hóa đơn.'
                ], 404);
            }

            if (in_array($hoaDon->trang_thai, ['da_thanh_toan', 'cho_xac_nhan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn đã hoặc đang chờ xác nhận.'
                ], 400);
            }

            $hoaDon->update(['trang_thai' => 'cho_xac_nhan']);

            $chuTro = $hoaDon->hopDong->phong->dayTro->chuTro ?? null;

            if ($chuTro) {
                DB::table('thong_bao')->insert([
                    'nguoi_nhan_id' => $chuTro->id,
                    'noi_dung' => "📨 Khách thuê phòng {$hoaDon->hopDong->phong->so_phong} đã xác nhận đã chuyển khoản thanh toán hóa đơn tháng {$hoaDon->thang}.",
                    'loai' => 'hoa_don',
                    'trang_thai' => 'chua_doc',
                    'da_xem' => 0,
                    'lien_ket' => '/chu-tro/hoadon?id=' . $hoaDon->id,
                    'ngay_tao' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Đã gửi xác nhận thanh toán. Chủ trọ sẽ kiểm tra và xác nhận.'
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Lỗi khách thuê xác nhận thanh toán: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi gửi xác nhận thanh toán.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
