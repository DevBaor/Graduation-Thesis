<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AnhBaiDangController extends Controller
{
    // POST /api/anh-bai-dang/upload
    // form-data: bai_dang_id, anh[] (1 hoặc nhiều file)
    public function upload(Request $r)
    {
        $r->validate([
            'bai_dang_id' => 'required|exists:bai_dang,id',
            'anh' => 'required',
            'anh.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        $baiDangId = (int) $r->bai_dang_id;

        // Bảo vệ: chỉ chủ bài đăng (nguoi_dung_id) mới được up ảnh vào bài đăng đó
        $bd = DB::table('bai_dang')->where('id', $baiDangId)->first();
        if (!$bd)
            return response()->json(['message' => 'Bài đăng không tồn tại'], 404);
        if ($bd->nguoi_dung_id !== $r->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền sửa bài đăng này'], 403);
        }

        $saved = [];
        $thu_tu_base = (int) DB::table('anh_bai_dang')->where('bai_dang_id', $baiDangId)->max('thu_tu');
        $thu_tu = $thu_tu_base ?: 0;

        // Hỗ trợ 1 hoặc nhiều file
        $files = is_array($r->file('anh')) ? $r->file('anh') : [$r->file('anh')];

        foreach ($files as $file) {
            // store under storage/app/public/bai_dang/{bai_dang_id}/xxx.jpg
            $path = $file->store('bai_dang/' . $baiDangId, 'public');
            $url = '/storage/' . $path;

            $id = DB::table('anh_bai_dang')->insertGetId([
                'bai_dang_id' => $baiDangId,
                'url' => $url,
                'thu_tu' => $thu_tu,
                'ngay_tao' => now(),
            ]);

            $saved[] = ['id' => $id, 'url' => url($url), 'relative_url' => $url, 'thu_tu' => $thu_tu];
            $thu_tu++;
        }

        return response()->json([
            'message' => 'Upload ảnh bài đăng thành công',
            'items' => $saved
        ], 201);
    }

    // DELETE /api/anh-bai-dang/{id}
    public function destroy(Request $r, $id)
    {
        $row = DB::table('anh_bai_dang')->where('id', $id)->first();
        if (!$row)
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);

        // Chỉ chủ bài đăng mới được xoá ảnh
        $bd = DB::table('bai_dang')->where('id', $row->bai_dang_id)->first();
        if (!$bd || $bd->nguoi_dung_id !== $r->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xoá ảnh này'], 403);
        }

        // Xoá file vật lý nếu nằm trong /storage/bai_dang/
        if (!empty($row->url) && str_starts_with($row->url, '/storage/bai_dang/')) {
            $file = str_replace('/storage/', '', $row->url);
            Storage::disk('public')->delete($file);
        }

        DB::table('anh_bai_dang')->where('id', $id)->delete();

        return response()->json(['message' => 'Đã xoá ảnh']);
    }
}
