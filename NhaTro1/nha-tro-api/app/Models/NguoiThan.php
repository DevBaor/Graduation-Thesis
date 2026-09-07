<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NguoiThan extends Model
{
    use HasFactory;

    protected $table = 'nguoi_than';

    protected $fillable = [
        'khach_thue_id',
        'ho_ten',
        'so_dien_thoai',
        'moi_quan_he',
    ];

    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }
}
