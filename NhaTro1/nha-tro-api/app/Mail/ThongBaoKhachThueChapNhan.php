<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThongBaoKhachThueChapNhan extends Mailable
{
    use Queueable, SerializesModels;

    public $khach, $chuTro, $phong, $urlXemYeuCau;

    public function __construct($khach, $chuTro, $phong)
    {
        $this->khach = $khach;
        $this->chuTro = $chuTro;
        $this->phong = $phong;
        $this->urlXemYeuCau = url('/khach-thue/yeu-cau-thue');
    }

    public function build()
    {
        $chuTro = e($this->chuTro->ho_ten ?? 'Chủ trọ');
        $khach = e($this->khach->ho_ten ?? 'Khách thuê');
        $phong = e($this->phong->so_phong ?? 'Không xác định');
        $dayTro = e($this->phong->day_tro ?? 'Không rõ dãy trọ');
        $url = e($this->urlXemYeuCau);
        $time = now()->format('d/m/Y H:i');

        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;background:#f9fafb;padding:20px;color:#333;">
            <div style="max-width:600px;margin:auto;background:#fff;padding:20px 30px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <h2 style="color:#16a34a;">✅ Yêu cầu thuê phòng đã được chấp nhận</h2>
                <p>Xin chào <b>{$khach}</b>,</p>
                <p>Chủ trọ <b>{$chuTro}</b> đã <b style="color:#16a34a;">chấp nhận</b> yêu cầu thuê phòng của bạn.</p>
                <ul>
                    <li>🏠 Phòng: <b>{$phong}</b></li>
                    <li>🏘 Dãy trọ: <b>{$dayTro}</b></li>
                    <li>📅 Thời gian xử lý: {$time}</li>
                </ul>
                <p style="margin-top:20px;">
                    <a href="{$url}" style="background:#16a34a;color:#fff;padding:12px 18px;text-decoration:none;border-radius:8px;font-weight:bold;">
                        🔍 Xem chi tiết yêu cầu thuê
                    </a>
                </p>
                <hr style="margin:25px 0;">
                <p style="font-size:14px;color:#666;">
                    Email tự động từ hệ thống Nhà Trọ.<br>
                    📅 Gửi lúc: {$time}
                </p>
            </div>
        </div>
        HTML;

        return $this->from(
            env('MAIL_FROM_ADDRESS'),
            'Hệ thống Nhà Trọ (Thay mặt ' . ($this->chuTro->ho_ten ?? 'Chủ trọ') . ')'
        )
            ->subject('✅ Yêu cầu thuê phòng của bạn đã được chấp nhận')
            ->html($html);
    }
}
