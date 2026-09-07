<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DichVu extends Model
{
    use HasFactory;

    protected $table = 'dich_vu';
    public $timestamps = false;

    protected $fillable = [
        'ma',
        'ten',
        'don_vi',
        'don_gia',
        'co_dong_ho',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function dichVuDinhKy()
    {
        return $this->hasMany(DichVuDinhKy::class, 'dich_vu_id');
    }
    public function dongHo()
    {
        return $this->hasMany(DongHo::class, 'dich_vu_id');
    }
}
