<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HopDong extends Model
{
    use HasFactory;

    protected $table = 'hop_dong';

    protected $fillable = [
        'khach_thue_id',
        'phong_id',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'tien_coc',
        'trang_thai',
        'url_file_hop_dong',
    ];

    public $timestamps = false; 

    protected $casts = [
        'ngay_bat_dau' => 'date',
        'ngay_ket_thuc' => 'date',
        'tien_coc' => 'decimal:2',
    ];


    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }

    public function hoaDon()
    {
        return $this->hasMany(HoaDon::class, 'hop_dong_id');
    }
}
