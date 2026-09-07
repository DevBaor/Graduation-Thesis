<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use App\Models\YeuCauThue;
use App\Models\BaiDang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\ThongBaoChuTroYeuCauMoi;

class YeuCauThueController extends Controller
{
    public function index()
    {
        $khach = Auth::user()->khachThue;

        $ds = YeuCauThue::with(['baiDang.phong.dayTro'])
            ->where('khach_thue_id', $khach->id)
            ->orderByDesc('ngay_tao')
            ->get();

        return response()->json(['data' => $ds]);
    }

    /**
     * 🔍 Xem chi tiết yêu cầu thuê của khách
     */
    public function show($id)
    {
        $user = Auth::user();
        $khach = $user->khachThue;

        if (!$khach) {
            return response()->json(['error' => 'Không tìm thấy thông tin khách thuê.'], 403);
        }

        $yc = DB::table('yeu_cau_thue')
            ->join('bai_dang', 'yeu_cau_thue.bai_dang_id', '=', 'bai_dang.id')
            ->join('phong', 'bai_dang.phong_id', '=', 'phong.id')
            ->join('day_tro', 'phong.day_tro_id', '=', 'day_tro.id')
            ->join('nguoi_dung as chu_tro', 'bai_dang.nguoi_dung_id', '=', 'chu_tro.id')
            ->join('khach_thue', 'yeu_cau_thue.khach_thue_id', '=', 'khach_thue.id')
            ->join('nguoi_dung as nguoi_dung', 'khach_thue.nguoi_dung_id', '=', 'nguoi_dung.id')
            ->where('yeu_cau_thue.khach_thue_id', $khach->id)
            ->where('yeu_cau_thue.id', $id)
            ->select([
                'yeu_cau_thue.id',
                'yeu_cau_thue.cccd',
                'yeu_cau_thue.ngay_bat_dau',
                'yeu_cau_thue.ngay_ket_thuc',
                'yeu_cau_thue.tien_coc',
                'yeu_cau_thue.ghi_chu',
                'yeu_cau_thue.nguoi_than',
                'yeu_cau_thue.file_hop_dong',
                'yeu_cau_thue.trang_thai',
                'yeu_cau_thue.ngay_tao',
                'phong.so_phong',
                'day_tro.ten_day_tro',
                'chu_tro.ho_ten as chu_tro_ten',
                'chu_tro.so_dien_thoai as chu_tro_sdt',
                'nguoi_dung.ho_ten as khach_ten',
                'nguoi_dung.so_dien_thoai as khach_sdt',
                'nguoi_dung.email as khach_email'
            ])
            ->first();

        if (!$yc) {
            return response()->json(['error' => 'Không tìm thấy yêu cầu thuê'], 404);
        }

        $yc->ngay_bat_dau = Carbon::parse($yc->ngay_bat_dau)->format('Y-m-d');
        $yc->ngay_ket_thuc = Carbon::parse($yc->ngay_ket_thuc)->format('Y-m-d');
        $yc->ngay_tao = Carbon::parse($yc->ngay_tao)->format('Y-m-d H:i:s');

        if (!empty($yc->nguoi_than)) {
            $decoded = json_decode($yc->nguoi_than, true);
            $yc->nguoi_than = json_last_error() === JSON_ERROR_NONE ? $decoded : $yc->nguoi_than;
        }

        return response()->json($yc);
    }

    public function store(Request $request)
    {
        try {
            \Log::info('📨 [API] Bắt đầu xử lý yêu cầu thuê', [
                'user_id' => auth()->id(),
                'input' => $request->except(['file_hop_dong'])
            ]);

            $request->validate([
                'bai_dang_id' => 'required|exists:bai_dang,id',
                'cccd' => 'required|digits_between:9,12',
                'ngay_bat_dau' => 'required|date',
                'ngay_ket_thuc' => 'required|date|after:ngay_bat_dau',
                'tien_coc' => 'required|numeric|min:0',
                'ghi_chu' => 'nullable|string|max:500',
                'nguoi_than' => 'nullable',
                'file_hop_dong' => 'required|file|mimes:pdf|max:4096',
            ]);

            $khach = auth()->user()?->khachThue;
            if (!$khach) {
                return response()->json(['error' => 'Không tìm thấy thông tin khách thuê.'], 403);
            }

            $baiDang = BaiDang::with(['phong.dayTro', 'chuTro'])->findOrFail($request->bai_dang_id);
            if (!$baiDang->phong) {
                return response()->json(['error' => 'Phòng không tồn tại.'], 404);
            }
            if ($baiDang->phong->trang_thai !== 'trong') {
                return response()->json(['error' => 'Phòng này không còn trống.'], 400);
            }

            $tonTai = YeuCauThue::where('bai_dang_id', $baiDang->id)
                ->where('khach_thue_id', $khach->id)
                ->whereIn('trang_thai', ['cho_duyet', 'chap_nhan'])
                ->exists();

            if ($tonTai) {
                return response()->json(['error' => 'Bạn đã gửi yêu cầu thuê phòng này rồi.'], 409);
            }

            DB::beginTransaction();

            $yc = YeuCauThue::create([
                'bai_dang_id' => $baiDang->id,
                'phong_id' => $baiDang->phong->id,
                'chu_tro_id' => $baiDang->nguoi_dung_id,
                'khach_thue_id' => $khach->id,
                'cccd' => $request->cccd,
                'ghi_chu' => $request->ghi_chu,
                'nguoi_than' => $request->nguoi_than,
                'ngay_bat_dau' => $request->ngay_bat_dau,
                'ngay_ket_thuc' => $request->ngay_ket_thuc,
                'tien_coc' => $request->tien_coc,
                'trang_thai' => 'cho_duyet',
                'ngay_tao' => now(),
            ]);

            if ($request->hasFile('file_hop_dong')) {
                $path = $request->file('file_hop_dong')->store('yeu_cau_files', 'public');
                $yc->file_hop_dong = $path;
                $yc->save();
            }
            if (!empty($request->nguoi_than)) {
                $nguoiThanList = is_string($request->nguoi_than)
                    ? json_decode($request->nguoi_than, true)
                    : $request->nguoi_than;

                if (is_array($nguoiThanList)) {
                    foreach ($nguoiThanList as $nt) {
                        DB::table('nguoi_than')->insert([
                            'khach_thue_id' => $khach->id,
                            'ho_ten' => $nt['ho_ten'] ?? null,
                            'so_dien_thoai' => $nt['so_dien_thoai'] ?? null,
                            'moi_quan_he' => $nt['moi_quan_he'] ?? null,
                        ]);
                    }
                }
            }

            if (!empty($baiDang->chuTro?->email)) {
                Mail::to($baiDang->chuTro->email)->send(
                    new ThongBaoChuTroYeuCauMoi(
                        $baiDang->chuTro,
                        auth()->user(),
                        $baiDang->phong,
                        $baiDang->phong->dayTro
                    )
                );
            }

            DB::commit();

            return response()->json([
                'message' => '✅ Gửi yêu cầu thuê phòng thành công!',
                'data' => [
                    'id' => $yc->id,
                    'cccd' => $yc->cccd,
                    'file_hop_dong' => $yc->file_hop_dong,
                    'chu_tro_email' => $baiDang->chuTro->email ?? null,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('💥 Lỗi trong YeuCauThueController@store', [
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
        }
    }

    public function huy($id)
    {
        $khach = Auth::user()->khachThue;

        $yc = YeuCauThue::where('id', $id)
            ->where('khach_thue_id', $khach->id)
            ->where('trang_thai', 'cho_duyet')
            ->first();

        if (!$yc) {
            return response()->json(['error' => 'Không tìm thấy yêu cầu thuê hợp lệ hoặc đã được xử lý.'], 404);
        }

        $yc->trang_thai = 'huy';
        $yc->save();

        return response()->json(['message' => 'Đã hủy yêu cầu thuê thành công.']);
    }
}
