<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanhGia extends Model
{
    use HasFactory;

    protected $table = 'danh_gia';
    public $timestamps = false;

    protected $fillable = [
        'hop_dong_id',
        'khach_thue_id',
        'diem_so',
        'binh_luan',
        'ngay_tao',
    ];

    /**  Quan hệ: đánh giá thuộc về 1 khách thuê */
    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }

    /** Quan hệ: đánh giá thuộc về 1 hợp đồng */
    public function hopDong()
    {
        return $this->belongsTo(HopDong::class, 'hop_dong_id');
    }
}
