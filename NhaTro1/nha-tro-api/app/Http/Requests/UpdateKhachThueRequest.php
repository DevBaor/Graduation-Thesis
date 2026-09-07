<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKhachThueRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('khach_thue');
        return [
            'ho_ten' => 'required|string|max:255',
            'so_dien_thoai' => ['required', 'string', 'max:20', Rule::unique('nguoi_dung')->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('nguoi_dung')->ignore($userId)],
            'cccd' => ['required', 'string', 'max:20', Rule::unique('khach_thue')->ignore($userId)],
        ];
    }
}