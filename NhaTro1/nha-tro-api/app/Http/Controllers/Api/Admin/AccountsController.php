<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountsController extends Controller
{
    /** Danh sách (pagination + filters) */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $q = NguoiDung::query();

        if ($request->filled('q')) {
            $term = $request->get('q');
            $q->where(function ($s) use ($term) {
                $s->where('ho_ten', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('vai_tro')) {
            $q->where('vai_tro', $request->get('vai_tro'));
        }

        if ($request->filled('trang_thai')) {
            $q->where('trang_thai', $request->get('trang_thai'));
        }

        $p = $q->orderByDesc('id')->paginate($perPage);

        return response()->json($p->toArray());
    }

    /** Thống kê cơ bản */
    public function statistics()
    {
        try {
            $total = NguoiDung::count();
            $byRole = NguoiDung::selectRaw('vai_tro, COUNT(*) as cnt')->groupBy('vai_tro')->get()->pluck('cnt', 'vai_tro')->toArray();
            $newThisMonth = NguoiDung::whereBetween('ngay_tao', [now()->startOfMonth(), now()->endOfMonth()])->count();

            return response()->json([
                'tong_tai_khoan' => $total,
                'theo_vai_tro' => $byRole,
                'moi_trong_thang' => $newThisMonth,
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountsController.statistics error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể lấy thống kê'], 500);
        }
    }

    /** Cập nhật trạng thái */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['trang_thai' => 'required|string']);
        try {
            $u = NguoiDung::findOrFail($id);
            $u->trang_thai = $request->trang_thai;
            $u->save();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('AccountsController.updateStatus error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật trạng thái'], 500);
        }
    }

    /** Cập nhật vai trò */
    public function updateRole(Request $request, $id)
    {
        $request->validate(['vai_tro' => 'required|string']);
        try {
            $u = NguoiDung::findOrFail($id);
            $u->vai_tro = $request->vai_tro;
            $u->save();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('AccountsController.updateRole error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể cập nhật vai trò'], 500);
        }
    }

    /** Xóa tài khoản */
    public function destroy($id)
    {
        try {
            $u = NguoiDung::findOrFail($id);
            $u->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('AccountsController.destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể xóa tài khoản'], 500);
        }
    }
}
