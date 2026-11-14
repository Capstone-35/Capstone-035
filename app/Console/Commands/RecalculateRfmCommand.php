<?php

namespace App\Console\Commands;

use App\Domain\Rfm\RfmService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecalculateRfmCommand extends Command
{
    protected $signature = 'rfm:recalculate {--date= : Tanggal analisis YYYY-MM-DD} {--period= : Periode analisis (hari)}';

    protected $description = 'Menghitung ulang skor RFM dan segmentasi seluruh Member 774 HUB berdasarkan transaksi berhasil';

    public function handle(RfmService $rfmService): int
    {
        $date = $this->option('date') ? Carbon::parse((string) $this->option('date')) : now();
        $period = $this->option('period') ? (int) $this->option('period') : null;

        $analysis = $rfmService->runAnalysis($date, $period, 'artisan_cli');

        $this->info("Analisis RFM #{$analysis->id} selesai untuk tanggal {$analysis->analysis_date->format('Y-m-d')} (periode {$analysis->period_days} hari).");
        $this->info('Total Member dianalisis: '.$analysis->results()->count());

        return self::SUCCESS;
    }
}
