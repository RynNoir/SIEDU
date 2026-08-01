<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penulis CSV yang ramah Excel, tanpa dependency tambahan.
 *
 * Keputusan format (semuanya untuk satu tujuan: file langsung terbaca benar
 * saat di-double-click di Excel berlokal Indonesia, tanpa wizard impor):
 *
 * - **BOM UTF-8** di awal file. Tanpa ini Excel menebak encoding lokal dan
 *   nama dosen/mahasiswa berhuruf non-ASCII tampil rusak.
 * - **Pemisah titik koma**, bukan koma. Excel memakai daftar pemisah bawaan
 *   lokal; di lokal Indonesia itu `;`, jadi file berkoma akan menumpuk semua
 *   kolom jadi satu. LibreOffice & Google Sheets mendeteksi otomatis.
 * - **Desimal koma** (`4,3`) mengikuti konvensi yang sama. Tidak ambigu karena
 *   pemisah kolomnya titik koma.
 *
 * Kalau nanti perlu diubah untuk lokal lain, cukup dua konstanta di bawah.
 */
class CsvExportService
{
    private const DELIMITER = ';';

    private const BOM = "\xEF\xBB\xBF";

    /**
     * Kirim CSV sebagai unduhan streaming.
     *
     * Streaming dipakai supaya rekap seluruh prodi lintas periode tidak perlu
     * disusun penuh di memori dulu.
     *
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function download(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            echo self::BOM;

            foreach ($rows as $row) {
                // escape: '' -> perilaku RFC 4180 murni; tanpa ini PHP 8.4+
                // memicu deprecation dan memakai backslash sebagai escape.
                fputcsv($handle, $row, self::DELIMITER, '"', '');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Format angka desimal dengan koma, mengikuti konvensi lokal Indonesia.
     */
    public function decimal(?float $value, int $precision = 1): string
    {
        if ($value === null) {
            return '';
        }

        return number_format($value, $precision, ',', '');
    }

    /**
     * Susun nama berkas yang aman: huruf kecil, tanpa spasi, bertanggal.
     */
    public function filename(string $prefix, ?string $context = null): string
    {
        $parts = array_filter([$prefix, $context, now()->format('Y-m-d')]);

        return str(implode('-', $parts))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->append('.csv')
            ->value();
    }
}
