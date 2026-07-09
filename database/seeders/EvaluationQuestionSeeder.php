<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kategori => daftar pertanyaan (PRD §5.3). Semua format
        // "Bagaimana penilaian Anda terhadap ..." agar skala bintang 1-5 konsisten.
        $groups = [
            'Penguasaan & Penyampaian Materi' => [
                'Bagaimana penilaian Anda terhadap penguasaan dosen terhadap materi yang diajarkan?',
                'Bagaimana penilaian Anda terhadap kejelasan dosen dalam menyampaikan materi?',
                'Bagaimana penilaian Anda terhadap relevansi contoh yang diberikan dosen dalam menjelaskan materi?',
            ],
            'Interaksi & Ketersediaan' => [
                'Bagaimana penilaian Anda terhadap keterbukaan dosen dalam menerima pertanyaan/diskusi?',
                'Bagaimana penilaian Anda terhadap kemudahan menghubungi dosen di luar jam kelas?',
            ],
            'Kedisiplinan & Profesionalisme' => [
                'Bagaimana penilaian Anda terhadap kedisiplinan dan ketepatan waktu dosen?',
                'Bagaimana penilaian Anda terhadap kejelasan informasi dosen jika ada perubahan jadwal?',
            ],
            'Penilaian & Feedback' => [
                'Bagaimana penilaian Anda terhadap kejelasan kriteria penilaian tugas/ujian?',
                'Bagaimana penilaian Anda terhadap kualitas feedback yang diberikan dosen atas tugas Anda?',
            ],
            'Rangkuman Keseluruhan' => [
                'Secara keseluruhan, bagaimana penilaian Anda terhadap kualitas pengajaran dosen ini?',
            ],
        ];

        $order = 1;
        foreach ($groups as $category => $questions) {
            foreach ($questions as $text) {
                EvaluationQuestion::create([
                    'category' => $category,
                    'question_text' => $text,
                    'order_number' => $order++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
