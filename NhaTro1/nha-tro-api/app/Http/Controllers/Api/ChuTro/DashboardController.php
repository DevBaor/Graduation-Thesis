<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Kiểm tra quyền chủ trọ
        if (!$user || $user->vai_tro !== 'chu_tro') {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $userId = $user->id;

        /* ===================================================
         THỐNG KÊ PHÒNG & DÃY TRỌ
        =================================================== */
        $soDayTro = DB::table('day_tro')
            ->where('chu_tro_id', $userId)
            ->count();

        $phongQuery = DB::table('phong')
            ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
            ->where('day_tro.chu_tro_id', $userId);

        $soPhong = (clone $phongQuery)->count();
        $soPhongTrong = (clone $phongQuery)->where('phong.trang_thai', 'trong')->count();
        $soPhongDangThue = (clone $phongQuery)->where('phong.trang_thai', 'da_thue')->count();
        $soPhongBaoTri = (clone $phongQuery)->where('phong.trang_thai', 'bao_tri')->count();

        /* ===================================================
         DOANH THU THÁNG NÀY
        =================================================== */
       $doanhThuThang = DB::table('hoa_don')
    ->join('hop_dong', 'hoa_don.hop_dong_id', '=', 'hop_dong.id')
    ->join('phong', 'hop_dong.phong_id', '=', 'phong.id')
    ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
    ->where('day_tro.chu_tro_id', $userId)
    ->where('hoa_don.trang_thai', 'da_thanh_toan')
    ->whereMonth(
        DB::raw('COALESCE(hoa_don.ngay_thanh_toan, hoa_don.ngay_tao)'),
        now()->month
    )
    ->whereYear(
        DB::raw('COALESCE(hoa_don.ngay_thanh_toan, hoa_don.ngay_tao)'),
        now()->year
    )
    ->sum('hoa_don.tong_tien');


        /* ===================================================
         DOANH THU 6 THÁNG GẦN NHẤT
        =================================================== */
        $doanhThu6Thang = DB::table('hoa_don')
    ->join('hop_dong', 'hoa_don.hop_dong_id', '=', 'hop_dong.id')
    ->join('phong', 'hop_dong.phong_id', '=', 'phong.id')
    ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
    ->where('day_tro.chu_tro_id', $userId)
    ->where('hoa_don.trang_thai', 'da_thanh_toan')
    ->whereBetween(
        DB::raw('COALESCE(hoa_don.ngay_thanh_toan, hoa_don.ngay_tao)'),
        [now()->subMonths(6)->startOfMonth(), now()]
    )
    ->selectRaw("
        DATE_FORMAT(
            COALESCE(hoa_don.ngay_thanh_toan, hoa_don.ngay_tao),
            '%Y-%m'
        ) as thang,
        SUM(hoa_don.tong_tien) as tong_tien
    ")
    ->groupBy('thang')
    ->orderBy('thang')
    ->pluck('tong_tien', 'thang')
    ->toArray();


      $labels = collect(range(5, 0))->map(fn($i) =>
    now()->subMonths($i)->format('Y-m')
);

$doanhThu6Thang = $labels->mapWithKeys(fn($thang) => [
    $thang => $doanhThu6Thang[$thang] ?? 0
])->toArray();


        /* ===================================================
         BÀI ĐĂNG GẦN ĐÂY
        =================================================== */
        $baiDangGanDay = DB::table('bai_dang')
            ->join('phong', 'bai_dang.phong_id', '=', 'phong.id')
            ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
            ->where('day_tro.chu_tro_id', $userId)
            ->orderByDesc('bai_dang.ngay_tao')
            ->select([
                'bai_dang.id',
                'bai_dang.tieu_de',
                'bai_dang.gia_niem_yet',
                'bai_dang.trang_thai',
                'bai_dang.ngay_tao'
            ])
            ->limit(5)
            ->get();

        /* ===================================================
         HOẠT ĐỘNG GẦN ĐÂY (lấy từ bảng thong_bao)
        =================================================== */
        $hoatDongGanDay = DB::table('thong_bao')
            ->where('nguoi_nhan_id', $userId)
            ->orderByDesc('ngay_tao')
            ->limit(6)
            ->get(['noi_dung', 'ngay_tao']);

        /* ===================================================
         TRẢ KẾT QUẢ JSON
        =================================================== */
        return response()->json([
            'so_day_tro' => $soDayTro,
            'so_phong' => $soPhong,
            'so_phong_trong' => $soPhongTrong,
            'so_phong_dang_thue' => $soPhongDangThue,
            'so_phong_bao_tri' => $soPhongBaoTri,
            'doanh_thu_thang' => $doanhThuThang ?? 0,
            'doanh_thu_6_thang' => $doanhThu6Thang,
            'bai_dang_gan_day' => $baiDangGanDay,
            'hoat_dong_gan_day' => $hoatDongGanDay,
        ]);
    }
}
