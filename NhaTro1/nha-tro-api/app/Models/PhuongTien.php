<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhuongTien extends Model
{
    use HasFactory;

    protected $table = 'phuong_tien';
    public $timestamps = false;

    protected $fillable = [
        'khach_thue_id',
        'loai',
        'bien_so',
        'mo_ta',
        'hoat_dong',
        'ngay_tao',
    ];

    /**  Quan hệ: phương tiện thuộc về 1 khách thuê */
    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }
}
