<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DichVuDinhKy extends Model
{
    use HasFactory;

    protected $table = 'dich_vu_dinh_ky';
    public $timestamps = false;

    protected $fillable = [
        'phong_id',
        'dich_vu_id',
        'don_gia',
        'so_luong',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function dichVu()
    {
        return $this->belongsTo(DichVu::class, 'dich_vu_id');
    }
}
