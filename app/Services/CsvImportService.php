<?php

namespace App\Services;

use RuntimeException;

/**
 * Pembaca CSV untuk berkas yang diunggah admin.
 *
 * Dibuat toleran terhadap berkas hasil Excel di lapangan, bukan CSV ideal:
 *
 * - **BOM UTF-8 dibuang.** Excel menulisnya di awal berkas, dan kalau dibiarkan
 *   nama kolom pertama terbaca sebagai "\u{FEFF}nim" sehingga tidak pernah cocok.
 * - **Pemisah dideteksi otomatis.** Ekspor kita memakai titik koma, tapi Excel
 *   berlokal lain menyimpan dengan koma. Menuntut satu format saja akan membuat
 *   admin menghadapi "semua kolom kosong" tanpa tahu sebabnya.
 * - **Nama kolom dinormalkan** (huruf kecil, spasi jadi garis bawah) supaya
 *   "Kode Prodi", "kode prodi", dan "kode_prodi" sama-sama diterima.
 * - **Nomor baris mengikuti penomoran spreadsheet** (header = baris 1), supaya
 *   pesan galat bisa langsung ditelusuri admin di Excel.
 */
class CsvImportService
{
    private const SUPPORTED_DELIMITERS = [';', ',', "\t"];

    /**
     * Baca berkas jadi daftar baris.
     *
     * @return list<array{line:int, data:array<string, string>}>
     *
     * @throws RuntimeException bila berkas tak terbaca atau tanpa header
     */
    public function read(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        try {
            $delimiter = $this->detectDelimiter($path);
            $headers = $this->readHeaders($handle, $delimiter);
            $rows = [];
            $line = 1;

            while (($columns = fgetcsv($handle, separator: $delimiter, escape: '')) !== false) {
                $line++;

                if ($this->isBlank($columns)) {
                    continue;
                }

                $rows[] = ['line' => $line, 'data' => $this->combine($headers, $columns)];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Nama kolom yang ada di berkas — dipakai untuk memberi tahu admin
     * kolom apa yang kurang, bukan sekadar menolak berkasnya.
     *
     * @return list<string>
     */
    public function headers(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        try {
            return $this->readHeaders($handle, $this->detectDelimiter($path));
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @return list<string>
     */
    private function readHeaders($handle, string $delimiter): array
    {
        $first = fgetcsv($handle, separator: $delimiter, escape: '');

        if ($first === false || $this->isBlank($first)) {
            throw new RuntimeException('Berkas kosong atau tidak memiliki baris judul kolom.');
        }

        // BOM hanya menempel di sel pertama baris pertama.
        $first[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $first[0]);

        return array_map($this->normalizeHeader(...), $first);
    }

    /**
     * Tebak pemisah dari baris pertama: yang paling sering muncul menang.
     * Cukup andal karena baris judul kolom tidak pernah berisi teks bebas.
     */
    private function detectDelimiter(string $path): string
    {
        $firstLine = (string) (@file($path, FILE_IGNORE_NEW_LINES)[0] ?? '');

        $counts = [];
        foreach (self::SUPPORTED_DELIMITERS as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($counts);
        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ';';
    }

    private function normalizeHeader(?string $header): string
    {
        return str((string) $header)->trim()->lower()->replaceMatches('/\s+/', '_')->value();
    }

    /**
     * Pasangkan header dengan nilai. Kolom yang kurang diisi string kosong,
     * kolom berlebih diabaikan — berkas dengan jumlah kolom tak rata tetap
     * bisa diproses lalu ditolak per baris dengan pesan yang jelas.
     *
     * @param  list<string>  $headers
     * @param  list<string|null>  $columns
     * @return array<string, string>
     */
    private function combine(array $headers, array $columns): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            $data[$header] = trim((string) ($columns[$index] ?? ''));
        }

        return $data;
    }

    /**
     * @param  list<string|null>  $columns
     */
    private function isBlank(array $columns): bool
    {
        return $columns === [null] || implode('', array_map(fn ($v) => trim((string) $v), $columns)) === '';
    }
}
