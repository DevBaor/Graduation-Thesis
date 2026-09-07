<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Import controller cần thiết
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PhongController;
use App\Http\Controllers\DayTroController;
use App\Http\Controllers\Upload\AvatarController;
use App\Http\Controllers\Upload\AnhBaiDangController;
use App\Http\Controllers\Api\ChuTro\ProfileController;
use App\Http\Controllers\Api\ChuTro\DashboardController;
use App\Http\Controllers\Api\ChuTro\PhongController as ChuTroPhongController;
use App\Http\Controllers\Api\ChuTro\HopDongController;
use App\Http\Controllers\Api\ChuTro\YeuCauThueController;
use App\Http\Controllers\Api\ChuTro\KhachThueController as ApiKhachThue;
use App\Http\Controllers\Api\ChuTro\BaiDangController as ChuTroBaiDangController;
use App\Http\Controllers\BaiDangController;
use App\Http\Controllers\Api\ChuTro\ChiSoController;
use App\Http\Controllers\Api\ChuTro\HoaDonController;
use App\Http\Controllers\Api\ChuTro\DongHoController;
use App\Http\Controllers\Api\ChuTro\NguoiThanController;
use App\Http\Controllers\Api\ChuTro\DichVuController;
use App\Http\Controllers\Api\ChuTro\DichVuDinhKyController;
use App\Http\Controllers\Api\ChuTro\TienIchController;
use App\Http\Controllers\Api\BaiDangPublicController;
use App\Http\Controllers\Api\KhachThue\YeuThichController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Api\KhachThue\YeuCauSuaChuaController;
use App\Http\Controllers\Api\KhachThue\RecommendationController;
use App\Http\Controllers\Api\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| 🌐 PUBLIC ROUTES (Không cần đăng nhập)
|--------------------------------------------------------------------------
*/
Route::get('bai-dang', [BaiDangPublicController::class, 'index']);
Route::get('bai-dang/by-phong/{phong_id}', [BaiDangPublicController::class, 'byPhong']);
Route::get('bai-dang/{id}', [BaiDangPublicController::class, 'show']);
Route::get('phong', [PhongController::class, 'index']);
Route::get('phong/{id}', [PhongController::class, 'show']);

// Public AI Recommendation (phục vụ demo nhanh trên web)
Route::post('public/recommend', [RecommendationController::class, 'recommend']);

// Automated VietQR & Payment Webhook routes
Route::prefix('payment')->group(function () {
    Route::get('/vietqr/{hoaDonId}', [PaymentWebhookController::class, 'getVietQRInfo']);
    Route::get('/status/{hoaDonId}', [PaymentWebhookController::class, 'checkStatus']);
    Route::post('/simulate/{hoaDonId}', [PaymentWebhookController::class, 'simulatePayment']);
    Route::post('/webhook', [PaymentWebhookController::class, 'handleWebhook']);
});
Route::post('thanh-toan/webhook', [PaymentWebhookController::class, 'handleWebhook']);
Route::get('thanh-toan/vietqr/{hoaDonId}', [PaymentWebhookController::class, 'getVietQRInfo']);
Route::get('thanh-toan/status/{hoaDonId}', [PaymentWebhookController::class, 'checkStatus']);
Route::post('thanh-toan/simulate/{hoaDonId}', [PaymentWebhookController::class, 'simulatePayment']);

/*
|--------------------------------------------------------------------------
| 👤 AUTH ROUTES (Đăng nhập / Đăng ký / Đăng xuất)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::get('/google/redirect', [GoogleController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [GoogleController::class, 'handleGoogleCallback']);
    Route::post('/google/mobile-login', [GoogleController::class, 'mobileLogin']);


    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| 🔒 AUTHENTICATED ROUTES (Cần token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 🧍 Thông tin người dùng hiện tại
    Route::get('me', function (Request $r) {
        return response()->json($r->user()->makeHidden(['mat_khau']));
    });

    /*
    |--------------------------------------------------------------------------
    | 🏘️ DÃY TRỌ & PHÒNG
    |--------------------------------------------------------------------------
    */
    Route::prefix('day-tro')->group(function () {
        Route::get('/', [DayTroController::class, 'index']);
        Route::post('/', [DayTroController::class, 'store']);
        Route::get('/{id}', [DayTroController::class, 'show']);
        Route::put('/{id}', [DayTroController::class, 'update']);
        Route::delete('/{id}', [DayTroController::class, 'destroy']);
        Route::get('/chu-tro', [DayTroController::class, 'getByChuTro']);
    });

    Route::prefix('phong')->group(function () {
        Route::post('/', [PhongController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | UPLOAD ẢNH
    |--------------------------------------------------------------------------
    */
    Route::prefix('upload')->group(function () {
        Route::post('/avatar', [AvatarController::class, 'upload']);
        Route::delete('/avatar', [AvatarController::class, 'destroy']);
        Route::post('/anh-bai-dang', [AnhBaiDangController::class, 'upload']);
        Route::delete('/anh-bai-dang/{id}', [AnhBaiDangController::class, 'destroy']);
    });

    Route::prefix('chu-tro')->middleware(['auth:sanctum', 'isChuTro'])->group(function () {
        // Hồ sơ
        Route::get('/profile', [ProfileController::class, 'show'])->name('chu-tro.profile.show');
        Route::post('/update-profile', [ProfileController::class, 'update'])->name('chu-tro.profile.update');
        Route::get('/profile/bank', [ProfileController::class, 'getBankInfo']);
        Route::post('/profile/bank', [ProfileController::class, 'updateBankInfo']);
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('chu-tro.dashboard');

        // Bài đăng (CRUD)
        Route::prefix('bai-dang')->group(function () {
            Route::get('/', [ChuTroBaiDangController::class, 'index']);
            Route::post('/', [ChuTroBaiDangController::class, 'store']);
            Route::put('/{id}', [ChuTroBaiDangController::class, 'update']);
            Route::delete('/{id}', [ChuTroBaiDangController::class, 'destroy']);
            Route::get('/{id}', [ChuTroBaiDangController::class, 'edit']);
            Route::post('/{id}/toggle', [ChuTroBaiDangController::class, 'toggle']); // ✅ đúng rồi nè
        });

        //  Dịch vụ gốc
        Route::get('dich-vu', [DichVuController::class, 'index']);
        Route::get('dich-vu/options', [DichVuController::class, 'options']);
        Route::get('dich-vu/{id}', [DichVuController::class, 'show']);
        Route::post('dich-vu', [DichVuController::class, 'store']);
        Route::put('dich-vu/{id}', [DichVuController::class, 'update']);
        Route::delete('dich-vu/{id}', [DichVuController::class, 'destroy']);

        // Dịch vụ định kỳ (phòng)
        Route::get('dich-vu-dinh-ky', [DichVuDinhKyController::class, 'index']);
        Route::get('dich-vu-dinh-ky/{phong_id}', [DichVuDinhKyController::class, 'show']);
        Route::post('dich-vu-dinh-ky/{phong_id}', [DichVuDinhKyController::class, 'store']);
        Route::put('dich-vu-dinh-ky/{id}', [DichVuDinhKyController::class, 'update']);
        Route::delete('dich-vu-dinh-ky/{id}', [DichVuDinhKyController::class, 'destroy']);

        //Tien ich
        Route::get('/tien-ich', [TienIchController::class, 'index']);
        Route::post('/tien-ich', [TienIchController::class, 'store']);
        Route::put('tien-ich/{id}', [TienIchController::class, 'update']);
        Route::delete('/tien-ich/{id}', [TienIchController::class, 'destroy']);

        Route::get('/phong/{phong_id}/tien-ich', [TienIchController::class, 'phong']);
        Route::post('/phong/{phong_id}/tien-ich', [TienIchController::class, 'ganTienIch']);
        //  Phòng
        Route::prefix('phong')->group(function () {
            Route::get('/', [ChuTroPhongController::class, 'index']);
            Route::post('/', [ChuTroPhongController::class, 'store']);
            Route::get('/{id}', [ChuTroPhongController::class, 'show']);
            Route::put('/{id}', [ChuTroPhongController::class, 'update']);
            Route::delete('/{id}', [ChuTroPhongController::class, 'destroy']);


        });
        Route::get('/phong-su-dung', [ChuTroPhongController::class, 'danhSachPhongDangSuDung']);
        //  Hợp đồng
        Route::prefix('hop-dong')->group(function () {
            Route::get('/', [HopDongController::class, 'index']);
            Route::post('/', [HopDongController::class, 'store'])->middleware('checkBaoTri');
            Route::get('/{id}', [HopDongController::class, 'show']);
            Route::put('/{id}', [HopDongController::class, 'update']);
            Route::delete('/{id}', [HopDongController::class, 'destroy']);
        });

        //  Yêu cầu thuê (dành cho chủ trọ)
        Route::prefix('yeu-cau-thue')->group(function () {
            // Danh sách yêu cầu thuê của chủ trọ hiện tại
            Route::get('/', [YeuCauThueController::class, 'index'])->name('chu-tro.yeu-cau-thue.index');

            // Xem chi tiết yêu cầu thuê cụ thể
            Route::get('/{id}', [YeuCauThueController::class, 'show'])->name('chu-tro.yeu-cau-thue.show');

            // Chấp nhận yêu cầu thuê (tạo hợp đồng hoặc chỉ đổi trạng thái)
            Route::post('/{id}/chap-nhan', [YeuCauThueController::class, 'chapNhan'])->name('chu-tro.yeu-cau-thue.chap-nhan');

            // Từ chối yêu cầu thuê
            Route::post('/{id}/tu-choi', [YeuCauThueController::class, 'tuChoi'])->name('chu-tro.yeu-cau-thue.tu-choi');
        });


        //hóa đơn
        Route::get('/hoa-don', [HoaDonController::class, 'index']);
        Route::post('/hoa-don/generate', [HoaDonController::class, 'generate'])->middleware('checkBaoTri');
        Route::post(
            '/hoa-don/gui-yeu-cau-thanh-toan-all',
            [HoaDonController::class, 'guiYeuCauThanhToanAll']
        );

        Route::put('/hoa-don/{id}', [HoaDonController::class, 'update']);
        Route::get('/hoa-don/{id}', [HoaDonController::class, 'show']);
        Route::get('/hoa-don/{id}/pdf', [HoaDonController::class, 'exportPdf']);
        Route::post('/hoa-don/{id}/thanh-toan', [HoaDonController::class, 'thanhToan']);
        Route::post('/hoa-don/{id}/huy', [HoaDonController::class, 'huy']);
        Route::post('/hoa-don/{id}/yeu-cau-thanh-toan', [HoaDonController::class, 'guiYeuCauThanhToan']);
        Route::post('/hoa-don/{id}/xac-nhan-thanh-toan', [HoaDonController::class, 'xacNhanThanhToan']);
        Route::post('/hoa-don/{id}/huy-xac-nhan', [HoaDonController::class, 'huyXacNhan']);
    });
});
Route::middleware('auth:sanctum')->prefix('chu-tro')->group(function () {
    Route::get('/dong-ho', [DongHoController::class, 'index']);
    Route::get('/chi-so', [ChiSoController::class, 'index']);
    Route::post('/chi-so', [ChiSoController::class, 'store'])->middleware('checkBaoTri');
    Route::put('/chi-so/{id}', [ChiSoController::class, 'update']);
    Route::delete('/chi-so/{id}', [ChiSoController::class, 'destroy']);

    Route::get('/danh-sach-phong-dang-su-dung', [ChiSoController::class, 'danhSachPhongDangSuDung']);

    //  Khách thuê
    Route::prefix('khach-thue')->group(function () {
        Route::get('/', [ApiKhachThue::class, 'index']);
        Route::get('/{id}', [ApiKhachThue::class, 'show']);

        Route::post('/', [ApiKhachThue::class, 'store']);
        Route::put('/{id}', [ApiKhachThue::class, 'update']);
        Route::delete('/{id}', [ApiKhachThue::class, 'destroy']);
    });
    // Người thân (mới thêm)
    Route::prefix('nguoi-than')->group(function () {
        Route::get('/', [NguoiThanController::class, 'index']);
        Route::post('/', [NguoiThanController::class, 'store']);
        Route::put('/{id}', [NguoiThanController::class, 'update']);
        Route::delete('/{id}', [NguoiThanController::class, 'destroy']);
    });
});
/*
Route::prefix('admin/bai-dang')->middleware('auth:sanctum')->group(function () {
    Route::get('pending', [BaiDangDuyetController::class, 'pending']);
    Route::post('{id}/approve', [BaiDangDuyetController::class, 'approve']);
    Route::post('{id}/reject', [BaiDangDuyetController::class, 'reject']);
});*/

// Admin endpoints used by the admin web UI
use App\Http\Controllers\Api\Admin\AccountsController;
use App\Http\Controllers\Api\Admin\PostsController;
use App\Http\Controllers\Api\Admin\BaiDangDuyetController;

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Admin dashboard: basic stats used by the admin web UI
    Route::get('dashboard', function (Request $request) {
        try {
            $now = now();

            $postsThisMonth = DB::table('bai_dang')
                ->whereYear('ngay_tao', $now->year)
                ->whereMonth('ngay_tao', $now->month)
                ->count();

            $pending = DB::table('bai_dang')->where('trang_thai', 'cho_duyet')->count();
            $users = DB::table('nguoi_dung')->count();
            $regions = DB::table('dia_chi')->count();
            $totalPosts = DB::table('bai_dang')->count();
            $approved = DB::table('bai_dang')->where('trang_thai', 'dang')->count();
            $hidden = DB::table('bai_dang')->where('trang_thai', 'tu_choi')->count();

            return response()->json([
                'posts_this_month' => $postsThisMonth,
                'dang_cho_duyet' => $pending,
                'nguoi_dung' => $users,
                'khu_vuc' => $regions,
                'total_posts' => $totalPosts,
                'approved' => $approved,
                'hidden' => $hidden,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'error', 'error' => $e->getMessage()], 500);
        }
    });

    // Accounts management
    Route::get('accounts', [AccountsController::class, 'index']);
    Route::get('accounts/statistics', [AccountsController::class, 'statistics']);
    Route::patch('accounts/{id}/status', [AccountsController::class, 'updateStatus']);
    Route::patch('accounts/{id}/role', [AccountsController::class, 'updateRole']);
    Route::delete('accounts/{id}', [AccountsController::class, 'destroy']);

    // Posts management (admin)
    Route::get('posts', [PostsController::class, 'index']);
    Route::get('posts/{id}', [PostsController::class, 'show']);
    Route::patch('posts/{id}', [PostsController::class, 'update']);
    Route::delete('posts/{id}', [PostsController::class, 'destroy']);

    // Approvals (admin)
    Route::get('approvals/pending', [BaiDangDuyetController::class, 'pending']);
    Route::get('approvals/{id}', [BaiDangDuyetController::class, 'show'])->whereNumber('id');
    Route::post('approvals/{id}/approve', [BaiDangDuyetController::class, 'approve']);
    Route::post('approvals/{id}/reject', [BaiDangDuyetController::class, 'reject']);
    Route::get('approvals/statistics', [BaiDangDuyetController::class, 'statistics'] ?? function () {
        return response()->json(['message' => 'not implemented'], 501);
    });
});


/*
|--------------------------------------------------------------------------
| KHÁCH THUÊ  (Tenant side)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\KhachThue\{
    PhongController as KhachThuePhongController,
    YeuCauThueController as KhachThueYeuCauThueController,
    HopDongController as KhachThueHopDongController,
    HoaDonController as KhachThueHoaDonController,
    ThanhToanController as KhachThueThanhToanController,
    DanhGiaController as KhachThueDanhGiaController,
    ThongBaoController as KhachThueThongBaoController,
    ProfileController as KhachThueProfileController
};
use App\Http\Controllers\Api\KhachThue\RecommendationController;
use Laravel\Sanctum\PersonalAccessToken;

/*
|--------------------------------------------------------------------------
| KHÁCH THUÊ (Tenant)
|--------------------------------------------------------------------------
*/
Route::prefix('khach-thue')->middleware(['auth:sanctum', 'isKhachThue'])->group(function () {

    //  Danh sách & chi tiết phòng
    Route::get('phong', [KhachThuePhongController::class, 'index']);
    Route::get('phong/{id}', [KhachThuePhongController::class, 'show']);
    Route::get('/dashboard', [App\Http\Controllers\Api\KhachThue\DashboardController::class, 'index']);
    // Recommendation (calls Python inference)
    Route::post('/recommend', [RecommendationController::class, 'recommend']);
    // Yêu cầu thuê
    Route::get('yeu-cau-thue', [KhachThueYeuCauThueController::class, 'index']);
    Route::post('yeu-cau-thue', [KhachThueYeuCauThueController::class, 'store']);
    Route::delete('yeu-cau-thue/{id}/huy', [KhachThueYeuCauThueController::class, 'huy']);
    // Hợp đồng
    Route::get('hop-dong', [KhachThueHopDongController::class, 'index']);
    Route::get('hop-dong/{id}', [KhachThueHopDongController::class, 'show']);

    // Hóa đơn
    Route::get('hoa-don', [KhachThueHoaDonController::class, 'index']);
    Route::get('hoa-don/{id}', [KhachThueHoaDonController::class, 'show']);
    Route::post('/hoa-don/{id}/xac-nhan-thanh-toan', [KhachThueHoaDonController::class, 'xacNhanThanhToan']);

    //  Thanh toán
    Route::post('thanh-toan', [KhachThueThanhToanController::class, 'store']);

    //  Đánh giá
    Route::get('/danh-gia', [KhachThueDanhGiaController::class, 'index']);
    Route::post('/danh-gia', [KhachThueDanhGiaController::class, 'store']);

    // Thông báo
    Route::get('/thong-bao', [KhachThueThongBaoController::class, 'index']);

    // Đánh dấu 1 cái đã đọc
    Route::post('/thong-bao/{id}/mark-as-read', [KhachThueThongBaoController::class, 'markAsRead']);

    // Đánh dấu tất cả đã đọc
    Route::post('/thong-bao/mark-all-read', [KhachThueThongBaoController::class, 'markAllAsRead']);

    // Xóa tất cả đã đọc
    Route::delete('/thong-bao/xoa-da-doc', [KhachThueThongBaoController::class, 'deleteRead']);

    Route::get('yeu-cau', [YeuCauSuaChuaController::class, 'index']);
    Route::post('yeu-cau', [YeuCauSuaChuaController::class, 'store']);

    Route::post('profile/avatar', [KhachThueProfileController::class, 'updateAvatar']);

    
    //BÀI ĐĂNG YÊU THÍCH
    Route::prefix('yeu-thich')->group(function () {
        Route::get('/', [YeuThichController::class, 'index']);
        Route::post('/{baiDangId}', [YeuThichController::class, 'store']);
        Route::delete('/{baiDangId}', [YeuThichController::class, 'destroy']);
    });

    // Hồ sơ
    Route::get('profile', [KhachThueProfileController::class, 'show']);
    Route::post('profile', [KhachThueProfileController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| XEM FILE HỢP ĐỒNG (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::get('khach-thue/hop-dong/file/{filename}', function ($filename) {
    $token = request()->query('token');

    if ($token) {
        $valid = PersonalAccessToken::findToken($token);
        if (!$valid) {
            return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn.'], 403);
        }
    }

    $path = storage_path('app/public/hop_dong_files/' . $filename);

    if (!file_exists($path)) {
        return response()->json(['message' => 'File không tồn tại.'], 404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . basename($filename) . '"'
    ]);
});

use App\Models\YeuCauThue;

Route::get(
    'chu-tro/yeu-cau-thue/{id}/xem-hop-dong',
    function ($id) {
        $token = request()->query('token');
        abort_if(!$token, 401, 'Chưa đăng nhập');

        $accessToken = PersonalAccessToken::findToken($token);
        abort_if(!$accessToken, 401, 'Token không hợp lệ');

        $user = $accessToken->tokenable;

        abort_if($user->vai_tro !== 'chu_tro', 403);

        $yc = YeuCauThue::findOrFail($id);

        $path = storage_path('app/public/' . $yc->file_hop_dong);
        abort_if(!file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }
);


Route::get('khach-thue/hop-dong/file/{filename}', function ($filename) {
    $token = request()->query('token');

    if (!$token) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $accessToken = PersonalAccessToken::findToken($token);
    if (!$accessToken) {
        return response()->json(['message' => 'Token không hợp lệ'], 401);
    }

    $path = storage_path('app/public/yeu_cau_files/' . $filename);

    if (!file_exists($path)) {
        return response()->json(['message' => 'File không tồn tại'], 404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline'
    ]);
});

