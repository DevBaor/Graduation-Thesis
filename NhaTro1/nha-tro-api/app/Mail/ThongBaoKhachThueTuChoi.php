<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThongBaoKhachThueTuChoi extends Mailable
{
    use Queueable, SerializesModels;

    public $khach;
    public $chuTro;
    public $phong;

    public function __construct($khach, $chuTro, $phong)
    {
        $this->khach = (object) $khach;
        $this->chuTro = (object) $chuTro;
        $this->phong = (object) $phong;
    }

    public function build()
    {
        return $this->subject('❌ Yêu cầu thuê phòng của bạn đã bị từ chối')
            ->view('emails.khachthue.tu_choi');
    }
}
