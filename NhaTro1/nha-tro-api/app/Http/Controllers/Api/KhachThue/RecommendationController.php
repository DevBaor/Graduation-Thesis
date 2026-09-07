<?php

namespace App\Http\Controllers\Api\KhachThue;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    /**
     * Gợi ý phòng trọ tối ưu sử dụng mô hình Reinforcement Learning (DQN).
     * Tối đa hóa sự hài lòng khách thuê + Giảm ngày trống + Tuân thủ ràng buộc.
     */
    public function recommend(Request $request)
    {
        try {
            $user = $request->user();
            $maxPrice = floatval($request->input('max_price', 4000000));
            $area = floatval($request->input('area', 20));
            $utilities = $request->input('utilities', []);
            if (is_string($utilities)) {
                $utilities = json_decode($utilities, true) ?? [];
            }
            $limit = intval($request->input('limit', 10));

            $aiServerUrl = env('DQN_SCORE_SERVER', 'http://127.0.0.1:8002/recommend');

            // 1. Chuẩn bị danh sách ứng viên phòng trống từ Database
            $candidates = DB::table('bai_dang as bd')
                ->join('phong as p', 'p.id', '=', 'bd.phong_id')
                ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
                ->where('bd.trang_thai', 'dang')
                ->where('p.trang_thai', 'trong')
                ->select(
                    'bd.id as bai_dang_id',
                    'bd.tieu_de',
                    'bd.mo_ta',
                    'bd.gia_niem_yet as gia',
                    'p.id as phong_id',
                    'p.dien_tich',
                    'p.tang',
                    'p.so_phong',
                    'p.trang_thai',
                    'd.ten_day_tro',
                    'd.dia_chi as dia_chi_daytro',
                    DB::raw('TIMESTAMPDIFF(DAY, p.ngay_cap_nhat, NOW()) as days_empty')
                )
                ->limit(60)
                ->get()
                ->toArray();

            // Load map tiện ích của các phòng
            $phongIds = array_column($candidates, 'phong_id');
            $utilsMap = [];
            if (!empty($phongIds)) {
                $rawUtils = DB::table('phong_tien_ich')
                    ->whereIn('phong_id', $phongIds)
                    ->get();
                foreach ($rawUtils as $u) {
                    $utilsMap[$u->phong_id][] = intval($u->tien_ich_id);
                }
            }

            // Gửi request tới Python AI Engine
            try {
                $resp = Http::timeout(3)->post($aiServerUrl, [
                    'user_id' => $user ? $user->id : null,
                    'max_price' => $maxPrice,
                    'area' => $area,
                    'utilities' => $utilities,
                    'limit' => $limit,
                    'candidates' => $candidates,
                    'utilities_map' => $utilsMap
                ]);

                if ($resp->successful()) {
                    $aiData = $resp->json();
                    if (isset($aiData['recommend']) && count($aiData['recommend']) > 0) {
                        return response()->json([
                            'success' => true,
                            'source' => 'DQN_AI_ENGINE',
                            'algorithm' => 'Dueling Dynamic DQN (Reinforcement Learning)',
                            'data' => $aiData['recommend']
                        ]);
                    }
                }
            } catch (\Throwable $aiEx) {
                Log::warning('RecommendationController: Python AI Engine unavailable, using intelligent heuristic fallback: ' . $aiEx->getMessage());
            }

            // 2. Thuật toán Fallback Đa mục tiêu thông minh (chuẩn theo hàm thưởng DQN của đề tài)
            $scoredRooms = [];
            foreach ($candidates as $c) {
                $roomUtils = $utilsMap[$c->phong_id] ?? [];
                
                // Tiêu chí 1: Ngân sách (Thỏa mãn <= max_price nhận điểm cao)
                $budgetScore = 0;
                $pPrice = floatval($c->gia);
                if ($pPrice <= $maxPrice) {
                    $budgetScore = 4.0 + (1.0 - ($pPrice / max($maxPrice, 1))) * 2.0;
                } else {
                    $budgetScore = max(-8.0, -2.0 - (($pPrice - $maxPrice) / max($maxPrice, 1)) * 10.0);
                }

                // Tiêu chí 2: Diện tích
                $areaDiff = abs(floatval($c->dien_tich) - $area);
                $areaScore = max(0, 3.0 - ($areaDiff / 10.0));

                // Tiêu chí 3: Tiện ích
                $overlap = count(array_intersect($roomUtils, $utilities));
                $utilMatchRatio = count($utilities) > 0 ? ($overlap / count($utilities)) : 1.0;
                $utilScore = $utilMatchRatio * 5.0;

                // Tiêu chí 4: Giảm ngày trống (Doanh thu chủ trọ)
                $daysNorm = min(floatval($c->days_empty) / 180.0, 1.0);
                $vacancyScore = $daysNorm * 3.0;

                // Tổng điểm đa mục tiêu
                $totalScore = $budgetScore + $areaScore + $utilScore + $vacancyScore;
                $matchPercent = intval(min(99, max(55, 60 + $totalScore * 3.5)));

                $reasons = [];
                if ($pPrice <= $maxPrice) {
                    $reasons[] = 'Phù hợp ngân sách dự kiến';
                }
                if ($utilMatchRatio >= 0.5) {
                    $reasons[] = 'Đáp ứng đa số tiện ích mong muốn';
                }
                if ($daysNorm >= 0.2) {
                    $reasons[] = 'Ưu tiên phòng trống sẵn sàng dọn vào ngay';
                }

                $scoredRooms[] = [
                    'bai_dang_id' => $c->bai_dang_id,
                    'phong_id' => $c->phong_id,
                    'tieu_de' => $c->tieu_de,
                    'gia' => $pPrice,
                    'dien_tich' => floatval($c->dien_tich),
                    'tang' => $c->tang,
                    'ten_day_tro' => $c->ten_day_tro,
                    'dia_chi' => $c->dia_chi_daytro,
                    'tien_ich' => $roomUtils,
                    'mo_ta' => $c->mo_ta,
                    'days_empty' => floatval($c->days_empty),
                    'q_value' => round($totalScore, 3),
                    'match_score' => $matchPercent,
                    'is_favorite' => 0,
                    'analysis' => [
                        'budget_fit' => $pPrice <= $maxPrice ? 'Tốt' : 'Vượt nhẹ',
                        'util_match_pct' => intval($utilMatchRatio * 100),
                        'reasons' => $reasons
                    ]
                ];
            }

            // Sắp xếp giảm dần theo điểm
            usort($scoredRooms, function($a, $b) {
                return $b['q_value'] <=> $a['q_value'];
            });

            return response()->json([
                'success' => true,
                'source' => 'RL_HEURISTIC_BACKUP',
                'algorithm' => 'Multi-objective Room Allocation Algorithm',
                'data' => array_slice($scoredRooms, 0, $limit)
            ]);

        } catch (\Throwable $e) {
            Log::error('RecommendationController exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đề xuất phòng: ' . $e->getMessage()
            ], 500);
        }
    }
}
