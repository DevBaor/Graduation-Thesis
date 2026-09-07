<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    use HasFactory;

    protected $table = 'hoa_don';
    public $timestamps = false;
    protected $fillable = [
        'hop_dong_id',
        'thang',
        'tien_phong',
        'tien_dich_vu',
        'tien_dong_ho',
        'tong_tien',
        'trang_thai',
        'han_thanh_toan'
    ];
    public function hopDong()
    {
        return $this->belongsTo(HopDong::class, 'hop_dong_id');
    }
    protected $casts = [
        'thang' => 'string',
        'han_thanh_toan' => 'string',
    ];

}
