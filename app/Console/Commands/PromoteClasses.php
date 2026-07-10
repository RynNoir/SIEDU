<?php

namespace App\Console\Commands;

use App\Services\ClassPromotionService;
use Illuminate\Console\Command;

class PromoteClasses extends Command
{
    /**
     * @var string
     */
    protected $signature = 'class:promote
                            {fromYear : Tahun ajaran asal (mis. 2025/2026)}
                            {toYear : Tahun ajaran tujuan (mis. 2026/2027)}';

    /**
     * @var string
     */
    protected $description = 'Naikkan tingkat semua kelas dari satu tahun ajaran ke tahun berikutnya (PRD §6.1)';

    public function handle(ClassPromotionService $service): int
    {
        $from = (string) $this->argument('fromYear');
        $to = (string) $this->argument('toYear');

        if ($from === $to) {
            $this->error('Tahun asal dan tujuan tidak boleh sama.');

            return self::FAILURE;
        }

        $this->info("Menjalankan promosi kelas: {$from} → {$to} ...");

        $summary = $service->promote($from, $to);

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Kelas dinaikkan', $summary['classes_promoted']],
                ['Kelas lulus (tidak dinaikkan)', $summary['classes_graduated']],
                ['Mahasiswa dipindahkan', $summary['students_promoted']],
            ],
        );

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
