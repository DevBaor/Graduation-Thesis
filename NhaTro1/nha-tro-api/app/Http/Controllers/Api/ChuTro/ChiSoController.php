<?php

namespace App\Http\Controllers\Api\ChuTro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChiSo;
use App\Models\DongHo;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\Phong;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\HoaDonService;

class ChiSoController extends Controller
{
    protected $hoaDonService;

    public function __construct(HoaDonService $hoaDonService)
    {
        $this->hoaDonService = $hoaDonService;
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $chuTroId = $user->id;

        // 🔹 Lấy tất cả phòng có hợp đồng hiệu lực
        $phongs = Phong::with(['dayTro', 'dongHo.dichVu'])
            ->whereHas('dayTro', fn($q) => $q->where('chu_tro_id', $chuTroId))
            ->whereIn('trang_thai', ['da_thue', 'dang_thue'])
            ->whereHas('hopDongs', fn($q) => $q->whereRaw("LOWER(trang_thai) = 'hieu_luc'"))
            ->orderBy('day_tro_id')
            ->orderBy('so_phong')
            ->get();

        $result = [];

        foreach ($phongs as $phong) {
            // 🔹 Mỗi phòng luôn có ít nhất 2 đồng hồ (điện + nước)
            foreach ($phong->dongHo as $dongHo) {
                // Lấy chỉ số gần nhất (nếu có)
                $chiSoGanNhat = ChiSo::where('dong_ho_id', $dongHo->id)
                    ->orderByDesc('thoi_gian')
                    ->first();

                // Lấy chỉ số cũ hơn 1 bậc (nếu có)
                $chiSoTruoc = ChiSo::where('dong_ho_id', $dongHo->id)
                    ->orderByDesc('thoi_gian')
                    ->skip(1)
                    ->first();

                $hoaDon = null;
                if ($chiSoGanNhat) {
                    $hoaDon = HoaDon::whereHas('hopDong', fn($q) =>
                        $q->where('phong_id', $phong->id))
                        ->orderByDesc('thang')
                        ->first();
                }

                $result[] = [
                    'id' => $chiSoGanNhat->id ?? null,
                    'day_tro' => $phong->dayTro->ten_day_tro ?? 'N/A',
                    'phong' => $phong->so_phong,
                    'dich_vu' => $dongHo->dichVu->ten ?? '(Không xác định)', // ✅ loại đồng hồ
                    'chi_so_cu' => $chiSoTruoc->gia_tri ?? 0,
                    'chi_so_moi' => $chiSoGanNhat->gia_tri ?? 0,
                    'thoi_gian' => $chiSoGanNhat
                        ? Carbon::parse($chiSoGanNhat->thoi_gian)->format('d/m/Y')
                        : '-',
                    'ghi_chu' => $chiSoGanNhat->ghi_chu ?? '',
                    'nguoi_sua' => $chiSoGanNhat->nguoiSua->ho_ten ?? null,
                    'trang_thai_hoa_don' => $hoaDon->trang_thai ?? 'chua_thanh_toan',
                    'updated_at' => $chiSoGanNhat
                        ? optional($chiSoGanNhat->updated_at)->format('d/m/Y H:i')
                        : '-',
                ];
            }

            // 🧩 Nếu phòng chưa có đồng hồ → vẫn hiện placeholder
            if ($phong->dongHo->isEmpty()) {
                $result[] = [
                    'id' => null,
                    'day_tro' => $phong->dayTro->ten_day_tro ?? 'N/A',
                    'phong' => $phong->so_phong,
                    'dich_vu' => '(Chưa có đồng hồ)',
                    'chi_so_cu' => 0,
                    'chi_so_moi' => 0,
                    'thoi_gian' => '-',
                    'ghi_chu' => '',
                    'nguoi_sua' => null,
                    'trang_thai_hoa_don' => '-',
                    'updated_at' => '-',
                ];
            }
        }

        return response()->json($result);
    }


    /*public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['message' => 'Chưa đăng nhập'], 401);

        $request->validate([
            'dong_ho_id' => 'required|integer|exists:dong_ho,id',
            'gia_tri' => 'required|numeric|min:0',
            'thoi_gian' => 'required|date',
            'ghi_chu' => 'nullable|string|max:255',
        ]);

        $dongHo = DongHo::with('phong.dayTro')->find($request->dong_ho_id);
        if (!$dongHo)
            return response()->json(['message' => 'Không tìm thấy đồng hồ'], 404);
        if ($dongHo->phong->dayTro->chu_tro_id !== $user->id)
            return response()->json(['message' => 'Bạn không có quyền thêm chỉ số cho dãy trọ này'], 403);

        if (!in_array($dongHo->phong->trang_thai, ['da_thue', 'dang_thue']))
            return response()->json(['message' => 'Phòng chưa có người thuê, không thể nhập chỉ số!'], 400);

        $hopDong = HopDong::where('phong_id', $dongHo->phong_id)
            ->where('trang_thai', 'hieu_luc')
            ->first();

        if (!$hopDong)
            return response()->json(['message' => 'Phòng chưa có hợp đồng hiệu lực, không thể nhập chỉ số!'], 400);

        $chiSoGanNhat = ChiSo::where('dong_ho_id', $dongHo->id)
            ->orderByDesc('thoi_gian')
            ->first();

        if ($chiSoGanNhat) {
            if (Carbon::parse($request->thoi_gian)->lte(Carbon::parse($chiSoGanNhat->thoi_gian))) {
                return response()->json([
                    'message' => '❌ Ngày nhập phải sau chỉ số gần nhất (' . Carbon::parse($chiSoGanNhat->thoi_gian)->format('d/m/Y') . ')'
                ], 400);
            }

            if ($request->gia_tri < $chiSoGanNhat->gia_tri) {
                \Log::warning("⚠️ Chỉ số mới ({$request->gia_tri}) nhỏ hơn chỉ số cũ ({$chiSoGanNhat->gia_tri}) — có thể do thay đồng hồ hoặc nhập sai.");
            }
        }

        $thang = Carbon::parse($request->thoi_gian)->format('Y-m');
        $hoaDon = HoaDon::where('hop_dong_id', $hopDong->id)
            ->where('thang', $thang)
            ->first();

        if ($hoaDon && $hoaDon->trang_thai !== 'chua_thanh_toan') {
            return response()->json(['message' => '❌ Hóa đơn tháng này đã thanh toán, không thể thêm chỉ số mới!'], 403);
        }

        DB::beginTransaction();
        try {
            $chiSo = ChiSo::create([
                'dong_ho_id' => $dongHo->id,
                'gia_tri' => $request->gia_tri,
                'thoi_gian' => $request->thoi_gian ?? now(),
                'ghi_chu' => $request->ghi_chu,
                'nguoi_nhap_id' => $user->id,
            ]);

            if ($hoaDon) {
                $this->hoaDonService->capNhatChiTietDongHo($dongHo, $hoaDon);
                $this->hoaDonService->capNhatTongTienHoaDon($hoaDon);
            }

            DB::commit();
            return response()->json([
                'message' => '✅ Thêm chỉ số thành công!',
                'data' => $chiSo
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ChiSo store error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể thêm chỉ số'], 500);
        }
    }*/
public function store(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Chưa đăng nhập'], 401);
    }

    $request->validate([
        'dong_ho_id' => 'required|integer|exists:dong_ho,id',
        'gia_tri' => 'required|numeric|min:0',
        'thoi_gian' => 'required|date',
        'ghi_chu' => 'nullable|string|max:255',
    ]);

    // 🔹 Lấy đồng hồ + phòng + dãy trọ
    $dongHo = DongHo::with('phong.dayTro')->find($request->dong_ho_id);
    if (!$dongHo) {
        return response()->json(['message' => 'Không tìm thấy đồng hồ'], 404);
    }

    // 🔒 Kiểm tra quyền chủ trọ
    if ($dongHo->phong->dayTro->chu_tro_id !== $user->id) {
        return response()->json(['message' => 'Bạn không có quyền thao tác'], 403);
    }

    // ❌ Phòng chưa thuê
    if (!in_array($dongHo->phong->trang_thai, ['da_thue', 'dang_thue'])) {
        return response()->json(['message' => 'Phòng chưa có người thuê'], 400);
    }

    // 🔹 Hợp đồng hiệu lực
    $hopDong = HopDong::where('phong_id', $dongHo->phong_id)
        ->where('trang_thai', 'hieu_luc')
        ->first();

    if (!$hopDong) {
        return response()->json(['message' => 'Không có hợp đồng hiệu lực'], 400);
    }

    // 🔹 Kiểm tra chỉ số gần nhất
    $chiSoGanNhat = ChiSo::where('dong_ho_id', $dongHo->id)
        ->orderByDesc('thoi_gian')
        ->first();

    if ($chiSoGanNhat) {
        if (Carbon::parse($request->thoi_gian)->lte(Carbon::parse($chiSoGanNhat->thoi_gian))) {
            return response()->json([
                'message' => '❌ Ngày nhập phải sau chỉ số gần nhất ('
                    . Carbon::parse($chiSoGanNhat->thoi_gian)->format('d/m/Y') . ')'
            ], 400);
        }
    }

    DB::beginTransaction();
    try {
        // ===============================
        // 1️⃣ LƯU CHỈ SỐ
        // ===============================
        $chiSo = ChiSo::create([
            'dong_ho_id' => $dongHo->id,
            'gia_tri' => $request->gia_tri,
            'thoi_gian' => $request->thoi_gian,
            'ghi_chu' => $request->ghi_chu,
            'nguoi_nhap_id' => $user->id,
        ]);

        // ===============================
        // 2️⃣ TỰ ĐỘNG TẠO / LẤY HÓA ĐƠN
        // ===============================
        $thang = Carbon::parse($request->thoi_gian)->format('Y-m');

       /* $hoaDon = HoaDon::firstOrCreate(
            [
                'hop_dong_id' => $hopDong->id,
                'thang' => $thang,
            ],
            [
                'trang_thai' => 'chua_thanh_toan',
                'tien_phong' => $dongHo->phong->gia ?? 0,
                'tien_dich_vu' => 0,
                'tien_dong_ho' => 0,
                'tong_tien' => 0,
                'han_thanh_toan' => now()->addDays(7),
            ]
        );*/
$hoaDon = HoaDon::firstOrCreate(
    [
        'hop_dong_id' => $hopDong->id,
        'thang' => $thang,
    ],
    [
        'trang_thai' => 'chua_thanh_toan',
        'tien_phong' => $dongHo->phong->gia ?? 0,
        'tien_dich_vu' => 0,
        'tien_dong_ho' => 0,
        'tong_tien' => 0,
        'han_thanh_toan' => now()->addDays(7),
    ]
);

if ($hoaDon->trang_thai !== 'chua_thanh_toan') {
    DB::rollBack();
    return response()->json([
        'message' => '❌ Hóa đơn tháng này đã thanh toán, không thể nhập chỉ số!'
    ], 403);
}

        // ===============================
        // 3️⃣ CẬP NHẬT ĐIỆN / NƯỚC
        // ===============================
        $this->hoaDonService->capNhatChiTietDongHo($dongHo, $hoaDon);

        // ===============================
        // 4️⃣ CẬP NHẬT TỔNG TIỀN
        // ===============================
        $this->hoaDonService->capNhatTongTienHoaDon($hoaDon);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => '✅ Nhập chỉ số và cập nhật hóa đơn thành công!',
            'chi_so' => $chiSo,
            'hoa_don_id' => $hoaDon->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('ChiSo store error: ' . $e->getMessage());

        return response()->json([
            'error' => '❌ Không thể lưu chỉ số điện nước'
        ], 500);
    }
}



    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['message' => 'Chưa đăng nhập'], 401);

        $chiSo = ChiSo::with('dongHo.phong.dayTro')->find($id);
        if (!$chiSo)
            return response()->json(['message' => 'Không tìm thấy chỉ số'], 404);

        if ($chiSo->dongHo->phong->dayTro->chu_tro_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền chỉnh sửa chỉ số này'], 403);
        }

        $dongHo = $chiSo->dongHo;
        $phong = $dongHo->phong;

        $thangChiSo = Carbon::parse($chiSo->thoi_gian)->format('Y-m');
        $hoaDon = HoaDon::whereHas('hopDong', fn($q) =>
            $q->where('phong_id', $phong->id))
            ->where('thang', $thangChiSo)
            ->first();

        if ($hoaDon && $hoaDon->trang_thai !== 'chua_thanh_toan') {
            return response()->json([
                'message' => '❌ Hóa đơn tháng này đã thanh toán, không thể chỉnh chỉ số điện nước!'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $chiSo->update([
                'gia_tri' => $request->gia_tri,
                'ghi_chu' => $request->ghi_chu,
                'nguoi_sua_id' => $user->id,
                'updated_at' => now(),
            ]);

            if ($hoaDon) {
                $this->hoaDonService->capNhatChiTietDongHo($dongHo, $hoaDon);
                $this->hoaDonService->capNhatTongTienHoaDon($hoaDon);
            }

            DB::commit();
            return response()->json(['message' => '✅ Cập nhật chỉ số và hóa đơn thành công!']);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ChiSo update error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật'], 500);
        }
    }


    public function destroy($id)
    {
        $chiSo = ChiSo::find($id);
        if (!$chiSo)
            return response()->json(['message' => 'Không tìm thấy chỉ số'], 404);

        $thang = Carbon::parse($chiSo->thoi_gian)->format('Y-m');
        $hoaDonTonTai = HoaDon::whereHas('hopDong', fn($q) =>
            $q->whereHas('phong.dongHo', fn($dh) =>
                $dh->where('id', $chiSo->dong_ho_id)))
            ->where('thang', $thang)
            ->exists();

        if ($hoaDonTonTai) {
            return response()->json(['message' => '❌ Chỉ số này đã được dùng trong hóa đơn, không thể xóa!'], 400);
        }

        $chiSo->delete();
        return response()->json(['message' => '✅ Xóa chỉ số thành công!']);
    }
    public function danhSachPhongDangSuDung()
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['message' => 'Chưa đăng nhập'], 401);

        $phongs = Phong::with('dayTro')
            ->whereHas('dayTro', fn($q) => $q->where('chu_tro_id', $user->id))
            ->whereIn('trang_thai', ['da_thue', 'dang_thue'])
            ->whereHas('hopDongs', function ($q) {
                $q->whereRaw("LOWER(trang_thai) = 'hieu_luc'");
            })
            ->orderBy('day_tro_id')
            ->orderBy('so_phong')
            ->get(['id', 'so_phong', 'day_tro_id', 'trang_thai']);


        return response()->json($phongs);
    }

}
