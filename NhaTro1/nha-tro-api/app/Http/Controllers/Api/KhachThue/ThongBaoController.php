<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Support\Facades\Auth;

class ThongBaoController extends Controller
{
    /**
     * 🔹 Danh sách thông báo của khách thuê hiện tại
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        // Lấy thông báo của khách thuê (theo id người dùng)
        $thongBao = ThongBao::where('nguoi_nhan_id', $user->id)
            ->orderByDesc('ngay_tao')
            ->get();

        return response()->json([
            'message' => 'Danh sách thông báo của bạn',
            'data' => $thongBao,
            'chua_doc' => $thongBao->where('da_xem', 0)->count(),
        ]);
    }

    /**
     * 🔹 Đánh dấu 1 thông báo là đã xem
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $tb = ThongBao::where('id', $id)
            ->where('nguoi_nhan_id', $user->id)
            ->first();

        if (!$tb) {
            return response()->json(['error' => 'Không tìm thấy thông báo'], 404);
        }

        $tb->update(['da_xem' => 1]);

        return response()->json(['message' => '✅ Đã đánh dấu thông báo là đã xem.']);
    }

    /**
     * 🔹 Đánh dấu tất cả thông báo là đã xem
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        ThongBao::where('nguoi_nhan_id', $user->id)
            ->where('da_xem', 0)
            ->update(['da_xem' => 1]);

        return response()->json(['message' => '✅ Tất cả thông báo đã được đánh dấu là đã xem.']);
    }
    public function deleteRead()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $query = ThongBao::where('nguoi_nhan_id', $user->id)
            ->where('da_xem', 1);

        $count = $query->count();

        if ($count === 0) {
            return response()->json([
                'message' => 'Không có thông báo đã đọc nào để xóa.',
                'deleted' => 0,
            ]);
        }

        $query->delete();

        return response()->json([
            'message' => "Đã xóa {$count} thông báo đã đọc.",
            'deleted' => $count,
        ]);
    }
}
