<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nguoi_dung';
    protected $primaryKey = 'id';
    public $timestamps = true;

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';
    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected $fillable = [
        'ho_ten',
        'so_dien_thoai',
        'email',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'anh_dai_dien',
        'ngay_tao',
        'ngay_cap_nhat',
        'google_id',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }
public function chuTro()
{
    return $this->hasOne(ChuTro::class, 'id', 'id');
}

    

    public function khachThue()
    {
        return $this->hasOne(KhachThue::class, 'nguoi_dung_id');
    }
    public function laChuTro()
    {
        return $this->vai_tro === 'chu_tro';
    }

}
