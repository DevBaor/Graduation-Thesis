<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KhachThue extends Model
{
    use HasFactory;

    protected $table = 'khach_thue';
    public $timestamps = false;

    protected $fillable = [
        'nguoi_dung_id',
        'cccd',
        'ngan_sach_min',
        'ngan_sach_max',
        'ngay_tao',
        'ngay_cap_nhat',
    ];


    public function hopDongs()
    {
        return $this->hasMany(HopDong::class, 'khach_thue_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function phuongTien()
    {
        return $this->hasMany(PhuongTien::class, 'khach_thue_id');
    }

    public function nguoiThan()
    {
        return $this->hasMany(NguoiThan::class, 'khach_thue_id');
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'khach_thue_id');
    }
    public function yeuCauThue()
    {
        return $this->hasMany(YeuCauThue::class, 'khach_thue_id');
    }
    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

}
