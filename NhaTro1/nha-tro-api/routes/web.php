<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebPortalController;

/*
|--------------------------------------------------------------------------
| Web Routes - Hệ thống Quản lý Nhà trọ (KLCN_TH071)
|--------------------------------------------------------------------------
*/

// 1. Khám phá phòng trọ (Public Landing & Search)
Route::get('/', [WebPortalController::class, 'index'])->name('home');

// 2. Gợi ý phòng thông minh bằng DQN (AI Recommendation Hub)
Route::get('/ai-recommend', [WebPortalController::class, 'aiRecommend'])->name('ai.recommend');

// 3. Cổng quản lý dành cho Chủ trọ
Route::get('/chu-tro', [WebPortalController::class, 'chuTroDashboard'])->name('chu-tro.web');

// 4. Cổng thông tin dành cho Khách thuê (Hợp đồng, Hóa đơn, VietQR)
Route::get('/khach-thue', [WebPortalController::class, 'khachThuePortal'])->name('khach-thue.web');

// 5. Cổng Quản trị viên (Admin)
Route::get('/admin-portal', [WebPortalController::class, 'adminPortal'])->name('admin.web');

// 6. Thanh toán VietQR hóa đơn trực tiếp
Route::get('/thanh-toan/{id}', [WebPortalController::class, 'checkout'])->name('payment.checkout');
