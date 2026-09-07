<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    use HasFactory;

    protected $table = 'thong_bao';
    public $timestamps = false;

    protected $fillable = [
        'nguoi_nhan_id',
        'noi_dung',
        'lien_ket',
        'da_xem',
        'ngay_tao',
    ];
}
