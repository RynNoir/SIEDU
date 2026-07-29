<?php

namespace App\Console\Commands;

use App\Enums\SemesterType;
use App\Services\SemesterPromotionService;
use Illuminate\Console\Command;

class PromoteSemester extends Command
{
    /**
     * @var string
     */
    protected $signature = 'semester:promote
                            {academicYear : Tahun ajaran periode asal (mis. 2025/2026)}
                            {semesterType : Tipe semester periode asal: ganjil atau genap}
                            {--force : Jalankan walau masih ada periode evaluasi yang dibuka}';

    /**
     * @var string
     */
    protected $description = 'Naikkan mahasiswa satu semester dari periode berjalan (PRD §6.1)';

    public function handle(SemesterPromotionService $service): int
    {
        $academicYear = (string) $this->argument('academicYear');
        $semesterType = SemesterType::tryFrom((string) $this->argument('semesterType'));

        if (! $semesterType) {
            $this->error('Tipe semester harus "ganjil" atau "genap".');

            return self::FAILURE;
        }

        if (! $this->option('force') && $blocking = $service->blockingPeriod()) {
            $this->error("Periode evaluasi \"{$blocking->name}\" masih dibuka.");
            $this->line('Mahasiswa yang pindah kelas akan kehilangan akses ke evaluasi yang belum diisi.');
            $this->line('Tutup periode tersebut dulu, atau jalankan ulang dengan --force.');

            return self::FAILURE;
        }

        $this->info("Menaikkan semester dari periode {$semesterType->value} {$academicYear} ...");

        $summary = $service->promote($academicYear, $semesterType);

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Periode tujuan', $summary['to_semester_type']->value.' '.$summary['to_academic_year']],
                ['Mahasiswa naik semester', $summary['students_promoted']],
                ['Mahasiswa lulus', $summary['students_graduated']],
                ['Kelas baru dibuat', $summary['classes_created']],
            ],
        );

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
