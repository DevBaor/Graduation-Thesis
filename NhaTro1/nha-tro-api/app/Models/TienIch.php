<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TienIch extends Model
{
    protected $table = 'tien_ich';
    public $timestamps = false;
    protected $fillable = ['ten'];
    public function phongs()
    {
        return $this->belongsToMany(Phong::class, 'phong_tien_ich', 'tien_ich_id', 'phong_id');
    }
}
