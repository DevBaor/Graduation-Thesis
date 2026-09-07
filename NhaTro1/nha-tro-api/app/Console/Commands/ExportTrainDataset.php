<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportTrainDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:train-dataset {--out= : Output path (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export posts to Train/dataset.csv for DQN training';

    public function handle()
    {
        $out = $this->option('out') ?: base_path('../../Train/dataset.csv');
        $dir = dirname($out);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                $this->error('Failed to create directory: ' . $dir);
                return 1;
            }
        }

        $this->info('Collecting posts...');

        $query = DB::table('bai_dang as bd')
            ->join('phong as p', 'p.id', '=', 'bd.phong_id')
            ->join('day_tro as d', 'd.id', '=', 'p.day_tro_id')
            ->where('bd.trang_thai', 'dang')
            ->where('p.trang_thai', '<>', 'bao_tri')
            ->select(
                'bd.id as bai_dang_id',
                'bd.tieu_de',
                'bd.mo_ta',
                'bd.gia_niem_yet',
                'p.id as phong_id',
                'p.dien_tich',
                'p.suc_chua',
                'd.dia_chi',
                'd.tien_ich'
            );

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->warn('No posts found to export.');
            return 0;
        }

        // build rating map
        $ratingMap = DB::table('danh_gia as dg')
            ->join('hop_dong as hd', 'hd.id', '=', 'dg.hop_dong_id')
            ->join('phong as p', 'p.id', '=', 'hd.phong_id')
            ->join('bai_dang as bd', 'bd.phong_id', '=', 'p.id')
            ->select('bd.id as bai_dang_id', DB::raw('ROUND(AVG(dg.diem_so), 1) as rating'))
            ->whereIn('bd.id', $posts->pluck('bai_dang_id'))
            ->groupBy('bd.id')
            ->pluck('rating', 'bai_dang_id')
            ->toArray();

        $handle = fopen($out, 'w');
        if ($handle === false) {
            $this->error('Failed to open output file: ' . $out);
            return 1;
        }

        // header expected by Train/train_dqn.py
        $header = ['dia_chi_quan','gia_phong','danh_gia_sao','so_nguoi_o','dien_tich','phong_id_duoc_chon','dich_vu','tien_ich','mo_ta'];
        fputcsv($handle, $header);

        $this->info('Writing ' . count($posts) . ' rows to ' . $out);

        foreach ($posts as $p) {
            // collect services for this room (dich_vu)
            $services = DB::table('dich_vu_dinh_ky as dvdk')
                ->join('dich_vu as dv', 'dv.id', '=', 'dvdk.dich_vu_id')
                ->where('dvdk.phong_id', $p->phong_id)
                ->pluck('dv.ten')
                ->toArray();

            $dich_vu = implode(',', $services);

            $row = [
                $p->dia_chi ?? '',
                $p->gia_niem_yet ?? 0,
                $ratingMap[$p->bai_dang_id] ?? '',
                $p->suc_chua ?? 1,
                $p->dien_tich ?? 0,
                $p->phong_id ?? '',
                $dich_vu,
                $p->tien_ich ?? '',
                $p->mo_ta ?? $p->tieu_de ?? '',
            ];

            // ensure UTF-8 safe
            $row = array_map(function($v){ return is_null($v) ? '' : (string)$v; }, $row);
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->info('Dataset exported successfully to: ' . $out);
        return 0;
    }
}
