<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThongBaoChuTroYeuCauMoi extends Mailable
{
    use Queueable, SerializesModels;

    public $chuTro, $khach, $phong, $dayTro, $urlXemYeuCau;

    public function __construct($chuTro, $khach, $phong, $dayTro = null)
    {
        $this->chuTro = $chuTro;
        $this->khach = $khach;
        $this->phong = $phong;
        $this->dayTro = $dayTro;
        $this->urlXemYeuCau = url('/chu-tro/yeu-cau-thue');
    }

    public function build()
    {
        // ✅ Không dùng view nữa, mà gửi HTML inline
        $chuTro = $this->chuTro->ho_ten ?? 'Chủ trọ';
        $khach = $this->khach->ho_ten ?? 'Không rõ';
        $phong = $this->phong->so_phong ?? 'Không xác định';
        $dayTro = $this->dayTro->ten_day_tro ?? 'Không xác định';
        $gia = number_format($this->phong->gia ?? 0);
        $url = $this->urlXemYeuCau;
        $time = now()->format('d/m/Y H:i');

        $html = <<<HTML
        <h2>📩 Yêu cầu thuê phòng mới</h2>
        <p>Xin chào <b>{$chuTro}</b>,</p>
        <p>Khách thuê <b>{$khach}</b> vừa gửi yêu cầu thuê phòng:</p>
        <ul>
            <li>🏠 Phòng: {$phong}</li>
            <li>🏘 Dãy trọ: {$dayTro}</li>
            <li>💰 Tiền phòng: {$gia} VNĐ</li>
            <li>📅 Ngày gửi: {$time}</li>
        </ul>
        <p><a href="{$url}" style="display:inline-block;background:#6d28d9;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;">Xem yêu cầu thuê</a></p>
        <p>Trân trọng,<br><b>Hệ thống Nhà Trọ</b></p>
        HTML;

        return $this->subject('📩 Có yêu cầu thuê phòng mới')
            ->html($html);
    }
}
