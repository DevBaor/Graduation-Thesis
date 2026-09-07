<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhToan extends Model
{
    protected $table = 'thanh_toan';

    // Nếu trong bảng có cột ngay_tao thì cho vào fillable luôn
    protected $fillable = [
        'hoa_don_id',
        'ngay_thanh_toan',
        'so_tien',
        'phuong_thuc',
        'ma_giao_dich',
        'ngay_tao',
    ];
    public $timestamps = false;


    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'hoa_don_id');
    }
}
