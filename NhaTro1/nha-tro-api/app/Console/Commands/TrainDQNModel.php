<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TrainDQNModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'train:dqn {--csv= : Path to csv file} {--epochs=5 : Epochs} {--topk=200 : top_k actions} {--out-model= : output model path} {--out-prep= : output preprocessors path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train the DQN model using Train/train_dqn.py (requires python + dependencies)';

    public function handle()
    {
        $python = env('INFER_PYTHON', 'python');
        $trainScript = base_path('../../Train/train_dqn.py');
        $trainDir = dirname($trainScript);

        $csv = $this->option('csv') ?: base_path('../../Train/dataset.csv');
        $epochs = (int) $this->option('epochs');
        $topk = (int) $this->option('topk');
        $outModel = $this->option('out-model') ?: base_path('../../Train/dqn_model.pt');
        $outPrep = $this->option('out-prep') ?: base_path('../../Train/preprocessors.joblib');

        if (!file_exists($trainScript)) {
            $this->error('Train script not found: ' . $trainScript);
            return 1;
        }

        if (!file_exists($csv)) {
            $this->error('CSV not found: ' . $csv . '. Run export:train-dataset first.');
            return 1;
        }

        $cmd = [$python, $trainScript, '--csv', $csv, '--out', $outModel, '--prep', $outPrep, '--topk', (string)$topk, '--epochs', (string)$epochs];

        $this->info('Running training: ' . implode(' ', $cmd));

        $process = new Process($cmd);
        $process->setWorkingDirectory($trainDir);
        $process->setTimeout(3600); // up to 1 hour
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error('Training failed.');
            $this->error($process->getErrorOutput());
            return 1;
        }

        $this->info('Training finished. Model saved to: ' . $outModel . ' preprocessors saved to: ' . $outPrep);
        return 0;
    }
}
