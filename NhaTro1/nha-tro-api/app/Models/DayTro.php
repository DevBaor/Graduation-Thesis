<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayTro extends Model
{
    protected $table = 'day_tro';
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'chu_tro_id',
        'ten_day_tro',
        'dia_chi',
        'so_phong',
        'dien_tich_tb',
        'gia_trung_binh',
        'mo_ta',
        'ngay_tao',
        'ngay_cap_nhat'
    ];


    /*public function chuTro()
    {
        return $this->belongsTo(NguoiDung::class, 'chu_tro_id', 'id');
    }*/
    /*public function chuTroNguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'chu_tro_id', 'id');
    }

    public function chuTro()
    {
        return $this->belongsTo(\App\Models\ChuTro::class, 'chu_tro_id', 'id');
    }*/
public function chuTro()
{
    return $this->belongsTo(ChuTro::class, 'chu_tro_id', 'id');
}
    public function phong()
    {
        return $this->hasMany(Phong::class, 'day_tro_id');
    }
}
