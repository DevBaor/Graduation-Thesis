<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebPortalController extends Controller
{
    /**
     * Trang chủ: Khám phá phòng trọ & tìm kiếm
     */
    public function index()
    {
        $posts = $this->getSamplePosts();
        return view('portal.index', compact('posts'));
    }

    /**
     * Giao diện đề xuất phòng thông minh bằng AI (DQN Recommendation Hub)
     */
    public function aiRecommend()
    {
        return view('portal.ai_recommend');
    }

    /**
     * Bảng điều khiển Chủ trọ (Quản lý phòng, dãy trọ, chỉ số điện nước, hóa đơn)
     */
    public function chuTroDashboard()
    {
        $rooms = $this->getSampleRooms();
        $invoices = $this->getSampleInvoices();
        return view('portal.chu_tro_dashboard', compact('rooms', 'invoices'));
    }

    /**
     * Cổng thông tin Khách thuê (Hợp đồng, hóa đơn, thanh toán tự động VietQR)
     */
    public function khachThuePortal()
    {
        $invoices = $this->getSampleInvoices();
        return view('portal.khach_thue_portal', compact('invoices'));
    }

    /**
     * Cổng quản trị Admin
     */
    public function adminPortal()
    {
        return view('portal.admin_portal');
    }

    /**
     * Trang thanh toán hóa đơn VietQR trực tiếp
     */
    public function checkout($id)
    {
        $invoice = collect($this->getSampleInvoices())->firstWhere('id', intval($id)) 
                   ?? (object)[
                       'id' => $id, 
                       'thang' => date('Y-m'), 
                       'so_phong' => '101', 
                       'ten_day_tro' => 'Dãy trọ A - Lê Lợi',
                       'tong_tien' => 3250000, 
                       'da_tra' => 0, 
                       'trang_thai' => 'chua_thanh_toan'
                   ];
        return view('payment.qr_checkout', compact('invoice'));
    }

    /**
     * Trợ giúp lấy danh sách bài đăng (tự động fallback nếu DB MySQL chưa kết nối)
     */
    private function getSamplePosts()
    {
        try {
            $dbPosts = DB::table('bai_dang as bd')
                ->join('phong as p', 'p.id', '=', 'bd.phong_id')
                ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
                ->where('bd.trang_thai', 'dang')
                ->select(
                    'bd.id as bai_dang_id', 'bd.tieu_de', 'bd.mo_ta', 'bd.gia_niem_yet as gia',
                    'p.id as phong_id', 'p.so_phong', 'p.dien_tich', 'p.tang', 'p.trang_thai',
                    'd.ten_day_tro', 'd.dia_chi'
                )
                ->limit(20)
                ->get();

            if ($dbPosts->count() > 0) {
                return $dbPosts;
            }
        } catch (\Throwable $e) {
            // Dùng fallback mẫu
        }

        return collect([
            (object)[
                'bai_dang_id' => 1, 'phong_id' => 1, 'so_phong' => 'A101',
                'tieu_de' => 'Phòng Studio A101 Ban công view thoáng, Full nội thất',
                'mo_ta' => 'Phòng rộng thoáng mát, gần đại học KHTN, giờ giấc tự do, bảo vệ 24/7.',
                'gia' => 3200000, 'dien_tich' => 20, 'tang' => 1, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Dãy trọ A - Lê Lợi', 'dia_chi' => '123 Đường Lê Lợi, Q1, TP.HCM',
                'tien_ich' => ['Máy lạnh', 'Giường nệm', 'Bàn học', 'Wifi tốc độ cao']
            ],
            (object)[
                'bai_dang_id' => 2, 'phong_id' => 2, 'so_phong' => 'A102',
                'tieu_de' => 'Phòng A102 Giá sinh viên sạch đẹp có gác lửng',
                'mo_ta' => 'Phòng có gác lửng đúc kiên cố, bồn rửa chén riêng, camera an ninh.',
                'gia' => 2500000, 'dien_tich' => 16, 'tang' => 1, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Dãy trọ A - Lê Lợi', 'dia_chi' => '123 Đường Lê Lợi, Q1, TP.HCM',
                'tien_ich' => ['Gác lửng', 'Giường', 'Kệ bếp', 'Wifi']
            ],
            (object)[
                'bai_dang_id' => 3, 'phong_id' => 3, 'so_phong' => 'B201',
                'tieu_de' => 'Căn hộ mini B201 Đầy đủ máy giặt, máy lạnh inverter',
                'mo_ta' => 'Căn hộ mini cao cấp mới xây, nội thất nhập khẩu, máy giặt riêng.',
                'gia' => 4500000, 'dien_tich' => 28, 'tang' => 2, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Ký túc xá B - Trần Hưng Đạo', 'dia_chi' => '456 Đường Trần Hưng Đạo, Q5, TP.HCM',
                'tien_ich' => ['Máy lạnh', 'Máy giặt riêng', 'Tủ lạnh', 'Tủ quần áo']
            ],
            (object)[
                'bai_dang_id' => 4, 'phong_id' => 4, 'so_phong' => 'B202',
                'tieu_de' => 'Phòng 2 phòng ngủ B202 ở được nhóm 3-4 bạn',
                'mo_ta' => 'Phòng rộng thoáng 25m2, có sân phơi riêng, không chung chủ.',
                'gia' => 3800000, 'dien_tich' => 25, 'tang' => 2, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Ký túc xá B - Trần Hưng Đạo', 'dia_chi' => '456 Đường Trần Hưng Đạo, Q5, TP.HCM',
                'tien_ich' => ['Máy lạnh', 'Máy giặt chung', 'Ban công']
            ],
            (object)[
                'bai_dang_id' => 5, 'phong_id' => 5, 'so_phong' => 'C301',
                'tieu_de' => 'Phòng C301 Căn góc 2 mặt thoáng view landmark',
                'mo_ta' => 'Mới 100%, thang máy thẻ từ, bảo vệ 24/7, hầm để xe rộng rãi.',
                'gia' => 5200000, 'dien_tich' => 32, 'tang' => 3, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Tòa nhà Happy House', 'dia_chi' => '789 Nguyễn Tri Phương, Q10, TP.HCM',
                'tien_ich' => ['Thang máy', 'Full nội thất', 'Máy lạnh', 'Hầm xe']
            ],
            (object)[
                'bai_dang_id' => 6, 'phong_id' => 6, 'so_phong' => 'C302',
                'tieu_de' => 'Phòng C302 Tiết kiệm cho sinh viên năm 1',
                'mo_ta' => 'Gần trạm xe buýt và các trường ĐH, chi phí dịch vụ giá rẻ.',
                'gia' => 2200000, 'dien_tich' => 15, 'tang' => 3, 'trang_thai' => 'trong',
                'ten_day_tro' => 'Tòa nhà Happy House', 'dia_chi' => '789 Nguyễn Tri Phương, Q10, TP.HCM',
                'tien_ich' => ['Wifi', 'Giường', 'Kệ sách']
            ]
        ]);
    }

    private function getSampleRooms()
    {
        return [
            ['id' => 1, 'so_phong' => '101', 'ten_day_tro' => 'Dãy A - Lê Lợi', 'gia' => 3200000, 'dien_tich' => 20, 'tang' => 1, 'trang_thai' => 'dang_thue', 'khach' => 'Nguyễn Văn An', 'sdt' => '0912345678'],
            ['id' => 2, 'so_phong' => '102', 'ten_day_tro' => 'Dãy A - Lê Lợi', 'gia' => 2500000, 'dien_tich' => 16, 'tang' => 1, 'trang_thai' => 'trong', 'khach' => null, 'sdt' => null],
            ['id' => 3, 'so_phong' => '201', 'ten_day_tro' => 'Dãy A - Lê Lợi', 'gia' => 3500000, 'dien_tich' => 22, 'tang' => 2, 'trang_thai' => 'dang_thue', 'khach' => 'Trần Thị Mai', 'sdt' => '0987654321'],
            ['id' => 4, 'so_phong' => '202', 'ten_day_tro' => 'Dãy A - Lê Lợi', 'gia' => 3200000, 'dien_tich' => 20, 'tang' => 2, 'trang_thai' => 'bao_tri', 'khach' => null, 'sdt' => null],
            ['id' => 5, 'so_phong' => '301', 'ten_day_tro' => 'Dãy B - Trần Hưng Đạo', 'gia' => 4500000, 'dien_tich' => 28, 'tang' => 3, 'trang_thai' => 'dang_thue', 'khach' => 'Lê Hoàng Nam', 'sdt' => '0903112233'],
            ['id' => 6, 'so_phong' => '302', 'ten_day_tro' => 'Dãy B - Trần Hưng Đạo', 'gia' => 3800000, 'dien_tich' => 25, 'tang' => 3, 'trang_thai' => 'trong', 'khach' => null, 'sdt' => null],
        ];
    }

    private function getSampleInvoices()
    {
        return [
            [
                'id' => 101, 'hop_dong_id' => 1, 'so_phong' => '101', 'ten_day_tro' => 'Dãy A - Lê Lợi',
                'khach_thue' => 'Nguyễn Văn An', 'thang' => '2026-09',
                'tien_phong' => 3200000, 'tien_dien' => 350000, 'tien_nuoc' => 120000, 'tien_dich_vu' => 100000,
                'tong_tien' => 3770000, 'so_tien_da_tra' => 0, 'trang_thai' => 'chua_thanh_toan',
                'han_thanh_toan' => '2026-09-15'
            ],
            [
                'id' => 102, 'hop_dong_id' => 3, 'so_phong' => '201', 'ten_day_tro' => 'Dãy A - Lê Lợi',
                'khach_thue' => 'Trần Thị Mai', 'thang' => '2026-09',
                'tien_phong' => 3500000, 'tien_dien' => 280000, 'tien_nuoc' => 100000, 'tien_dich_vu' => 80000,
                'tong_tien' => 3960000, 'so_tien_da_tra' => 3960000, 'trang_thai' => 'da_thanh_toan',
                'han_thanh_toan' => '2026-09-15'
            ],
            [
                'id' => 103, 'hop_dong_id' => 5, 'so_phong' => '301', 'ten_day_tro' => 'Dãy B - Trần Hưng Đạo',
                'khach_thue' => 'Lê Hoàng Nam', 'thang' => '2026-09',
                'tien_phong' => 4500000, 'tien_dien' => 420000, 'tien_nuoc' => 150000, 'tien_dich_vu' => 120000,
                'tong_tien' => 5190000, 'so_tien_da_tra' => 2000000, 'trang_thai' => 'mot_phan',
                'han_thanh_toan' => '2026-09-12'
            ]
        ];
    }
}
