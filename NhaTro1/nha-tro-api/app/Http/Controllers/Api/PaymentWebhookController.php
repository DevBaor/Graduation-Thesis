<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\ThanhToan;
use App\Models\ThongBao;
use App\Models\HopDong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentWebhookController extends Controller
{
    /**
     * Tự động sinh thông tin VietQR cho hóa đơn cụ thể.
     * Cú pháp chuẩn NAPAS247: https://img.vietqr.io/image/<BANK>-<ACCOUNT_NO>-compact2.png?amount=<AMOUNT>&addInfo=<MEMO>&accountName=<NAME>
     */
    public function getVietQRInfo($hoaDonId)
    {
        try {
            $hoaDon = HoaDon::with(['hopDong.phong.dayTro.chuTro', 'hopDong.khachThue.nguoiDung'])->find($hoaDonId);

            if (!$hoaDon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hóa đơn #' . $hoaDonId
                ], 404);
            }

            // Tính số tiền còn lại cần thanh toán
            $daTra = floatval($hoaDon->so_tien_da_tra ?? 0);
            $tongTien = floatval($hoaDon->tong_tien ?? 0);
            $conLai = max(0, $tongTien - $daTra);

            // Lấy thông tin tài khoản của chủ trọ
            $chuTro = null;
            if ($hoaDon->hopDong && $hoaDon->hopDong->phong && $hoaDon->hopDong->phong->dayTro) {
                $chuTro = $hoaDon->hopDong->phong->dayTro->chuTro;
            }

            $bankCode = ($chuTro && !empty($chuTro->bank_code)) ? $chuTro->bank_code : 'MB';
            $accountNo = ($chuTro && !empty($chuTro->account_no)) ? $chuTro->account_no : '16688668068';
            $accountName = ($chuTro && !empty($chuTro->account_name)) ? $chuTro->account_name : 'TRAN DUY BAO';

            $soPhong = ($hoaDon->hopDong && $hoaDon->hopDong->phong) ? $hoaDon->hopDong->phong->so_phong : 'P';
            $memo = 'HD' . $hoaDon->id . ' ' . $soPhong;

            // Link QR động chuẩn VietQR
            $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact2.png?" . http_build_query([
                'amount' => intval($conLai),
                'addInfo' => $memo,
                'accountName' => $accountName
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hoa_don_id' => $hoaDon->id,
                    'thang' => $hoaDon->thang,
                    'so_phong' => $soPhong,
                    'tong_tien' => $tongTien,
                    'da_tra' => $daTra,
                    'con_lai' => $conLai,
                    'trang_thai' => $hoaDon->trang_thai,
                    'han_thanh_toan' => $hoaDon->han_thanh_toan,
                    'bank_code' => $bankCode,
                    'account_no' => $accountNo,
                    'account_name' => $accountName,
                    'memo' => $memo,
                    'qr_url' => $qrUrl
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('getVietQRInfo error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook tiếp nhận biến động số dư ngân hàng (Tương thích SePay, PayOS, Cashtrader).
     * Tự động gạch nợ hóa đơn thời gian thực.
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Payment Webhook Received:', $payload);

            // Trích xuất nội dung chuyển khoản và số tiền từ các dạng gateway
            $content = $request->input('transactionContent') 
                    ?? $request->input('content') 
                    ?? $request->input('description') 
                    ?? $request->input('data.description') 
                    ?? '';

            $amount = floatval(
                $request->input('amountIn') 
                ?? $request->input('amount') 
                ?? $request->input('data.amount') 
                ?? 0
            );

            $refCode = $request->input('referenceNumber') 
                    ?? $request->input('reference') 
                    ?? $request->input('id') 
                    ?? ('AUTO_' . strtoupper(Str::random(10)));

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số tiền giao dịch không hợp lệ.'
                ], 400);
            }

            // Tìm mã hóa đơn trong nội dung: HD{id}
            $hoaDonId = null;
            if (preg_match('/HD\s*([0-9]+)/i', $content, $matches)) {
                $hoaDonId = intval($matches[1]);
            } elseif ($request->has('orderCode')) {
                $hoaDonId = intval($request->input('orderCode'));
            } elseif ($request->has('data.orderCode')) {
                $hoaDonId = intval($request->input('data.orderCode'));
            }

            if (!$hoaDonId) {
                Log::warning('Payment Webhook: Không tìm thấy mã hóa đơn trong nội dung: ' . $content);
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể nhận diện mã hóa đơn HD{id} trong nội dung giao dịch.'
                ], 422);
            }

            // Thực hiện gạch nợ hóa đơn
            $result = $this->processSettlement($hoaDonId, $amount, $refCode, 'chuyen_khoan');

            return response()->json([
                'success' => true,
                'message' => 'Xử lý gạch nợ hóa đơn thành công.',
                'result' => $result
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment Webhook Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint mô phỏng thanh toán trực tiếp phục vụ buổi bảo vệ đồ án.
     */
    public function simulatePayment(Request $request, $hoaDonId)
    {
        try {
            $hoaDon = HoaDon::find($hoaDonId);
            if (!$hoaDon) {
                return response()->json(['success' => false, 'message' => 'Hóa đơn không tồn tại.'], 404);
            }

            $tongTien = floatval($hoaDon->tong_tien);
            $daTra = floatval($hoaDon->so_tien_da_tra ?? 0);
            $conLai = max(0, $tongTien - $daTra);

            $payAmount = floatval($request->input('so_tien', $conLai > 0 ? $conLai : $tongTien));
            $method = $request->input('phuong_thuc', 'chuyen_khoan');
            $fakeRef = 'SIM_' . date('YmdHis') . '_' . strtoupper(Str::random(4));

            $result = $this->processSettlement($hoaDonId, $payAmount, $fakeRef, $method);

            return response()->json([
                'success' => true,
                'message' => 'Mô phỏng thanh toán tự động thành công!',
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Tra cứu trạng thái thanh toán của hóa đơn theo thời gian thực (cho client polling)
     */
    public function checkStatus($hoaDonId)
    {
        $hoaDon = HoaDon::find($hoaDonId);
        if (!$hoaDon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn'], 404);
        }

        $daTra = floatval($hoaDon->so_tien_da_tra ?? 0);
        $tongTien = floatval($hoaDon->tong_tien ?? 0);
        $conLai = max(0, $tongTien - $daTra);

        $giaoDichMoi = ThanhToan::where('hoa_don_id', $hoaDonId)->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'hoa_don_id' => $hoaDon->id,
                'trang_thai' => $hoaDon->trang_thai,
                'tong_tien' => $tongTien,
                'da_tra' => $daTra,
                'con_lai' => $conLai,
                'is_paid' => ($hoaDon->trang_thai === 'da_thanh_toan'),
                'last_transaction' => $giaoDichMoi
            ]
        ]);
    }

    /**
     * Logic gạch nợ dùng chung đảm bảo tính toàn vẹn Transaction
     */
    private function processSettlement($hoaDonId, $amount, $refCode, $method = 'chuyen_khoan')
    {
        return DB::transaction(function () use ($hoaDonId, $amount, $refCode, $method) {
            $hoaDon = HoaDon::lockForUpdate()->find($hoaDonId);
            if (!$hoaDon) {
                throw new \Exception("Hóa đơn #{$hoaDonId} không tồn tại.");
            }

            // Tránh ghi đè giao dịch trùng
            $existing = ThanhToan::where('ma_giao_dich', $refCode)->first();
            if ($existing) {
                return [
                    'hoa_don_id' => $hoaDon->id,
                    'trang_thai' => $hoaDon->trang_thai,
                    'so_tien' => $amount,
                    'message' => 'Giao dịch đã được xử lý trước đó.'
                ];
            }

            // Ghi nhận thanh toán
            $tt = ThanhToan::create([
                'hoa_don_id' => $hoaDon->id,
                'ngay_thanh_toan' => now(),
                'so_tien' => $amount,
                'phuong_thuc' => $method,
                'ma_giao_dich' => $refCode,
            ]);

            // Cập nhật số tiền đã trả
            $hoaDon->so_tien_da_tra = floatval($hoaDon->so_tien_da_tra ?? 0) + $amount;
            $conLai = floatval($hoaDon->tong_tien) - $hoaDon->so_tien_da_tra;

            if ($conLai <= 0) {
                $hoaDon->trang_thai = 'da_thanh_toan';
            } elseif ($hoaDon->so_tien_da_tra > 0) {
                $hoaDon->trang_thai = 'mot_phan';
            }

            $hoaDon->save();

            // Gửi thông báo tự động cho khách thuê nếu có thông tin hợp đồng
            try {
                $hopDong = HopDong::with('khachThue.nguoiDung')->find($hoaDon->hopDong_id ?? $hoaDon->hop_dong_id);
                if ($hopDong && $hopDong->khach_thue_id) {
                    ThongBao::create([
                        'nguoi_dung_id' => $hopDong->khachThue->nguoi_dung_id ?? $hopDong->khach_thue_id,
                        'tieu_de' => 'Thanh toán thành công hóa đơn #' . $hoaDon->id,
                        'noi_dung' => 'Hệ thống đã tự động gạch nợ ' . number_format($amount, 0, ',', '.') . ' VNĐ cho hóa đơn tháng ' . $hoaDon->thang . '. Trạng thái hiện tại: ' . ($hoaDon->trang_thai === 'da_thanh_toan' ? 'Đã hoàn tất' : 'Thanh toán một phần') . '.',
                        'loai' => 'hoa_don',
                        'da_doc' => 0,
                        'ngay_tao' => now(),
                    ]);
                }
            } catch (\Throwable $notiErr) {
                Log::warning('Không thể tạo thông báo: ' . $notiErr->getMessage());
            }

            return [
                'hoa_don_id' => $hoaDon->id,
                'ma_giao_dich' => $refCode,
                'so_tien_thanh_toan' => $amount,
                'da_tra_tong_cong' => $hoaDon->so_tien_da_tra,
                'con_lai' => max(0, $conLai),
                'trang_thai_moi' => $hoaDon->trang_thai,
            ];
        });
    }
}
