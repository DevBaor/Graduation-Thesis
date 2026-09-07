<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DongHo extends Model
{
    protected $table = 'dong_ho';
    protected $fillable = ['phong_id', 'dich_vu_id', 'ma_dong_ho'];
    public $timestamps = false;

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function dichVu()
    {
        return $this->belongsTo(DichVu::class, 'dich_vu_id');
    }

    public function chiSo()
    {
        return $this->hasMany(ChiSo::class, 'dong_ho_id');
    }

    public function chiTietDongHo()
    {
        return $this->hasMany(ChiTietDongHo::class, 'dong_ho_id');
    }
}
