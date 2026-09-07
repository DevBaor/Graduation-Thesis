<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YeuCauThue extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_thue';
    public $timestamps = false; // vì bạn dùng cột 'ngay_tao' thay cho created_at

    protected $fillable = [
        'bai_dang_id',
        'phong_id',
        'chu_tro_id',
        'khach_thue_id',
        'cccd',
        'ghi_chu',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'tien_coc',
        'file_hop_dong',
        'nguoi_than',
        'trang_thai',
        'ngay_tao',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
        'ngay_bat_dau' => 'date',
        'ngay_ket_thuc' => 'date',
        'nguoi_than' => 'array', // vì bạn lưu dạng JSON
        'tien_coc' => 'decimal:0',
    ];

    // ==========================
    // 🔗 Quan hệ Eloquent
    // ==========================

    /** 🏠 Bài đăng liên quan */
    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }

    /** 👤 Khách thuê gửi yêu cầu */
    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }

    /** 🧑‍💼 Chủ trọ nhận yêu cầu */
    public function chuTro()
    {
        return $this->belongsTo(NguoiDung::class, 'chu_tro_id');
    }

    /** 🚪 Phòng cụ thể mà yêu cầu thuê */
    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }
}
