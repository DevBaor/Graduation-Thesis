<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YeuThich extends Model
{
    use HasFactory;

    protected $table = 'yeu_thich';
    protected $fillable = ['khach_thue_id', 'bai_dang_id', 'ngay_tao'];
    public $timestamps = false;

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }

    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }
}
