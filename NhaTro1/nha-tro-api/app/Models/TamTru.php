<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamTru extends Model
{
    protected $table = 'tam_tru';
    protected $fillable = ['hop_dong_id', 'ngay_lam', 'co_quan', 'so_giay_to'];
    public $timestamps = false;

    public function hopDong()
    {
        return $this->belongsTo(HopDong::class, 'hop_dong_id');
    }
}
