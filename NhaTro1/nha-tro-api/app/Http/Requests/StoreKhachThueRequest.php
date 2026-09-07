<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreKhachThueRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ho_ten' => 'required|string|max:255',
            'so_dien_thoai' => 'required|string|max:20|unique:nguoi_dung,so_dien_thoai',
            'email' => 'required|email|max:255|unique:nguoi_dung,email',
            'mat_khau' => 'required|string|min:6',
            'cccd' => 'required|string|max:20|unique:khach_thue,cccd',
        ];
    }
}