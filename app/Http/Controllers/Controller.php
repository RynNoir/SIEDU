<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * Cek apakah sebuah record masih dipakai relasi lain sebelum dihapus.
     *
     * Seluruh foreign key di sistem ini dibuat dengan `constrained()` tanpa
     * `onDelete`, jadi database menolak penghapusan induk yang masih punya anak
     * (RESTRICT). Tanpa pengecekan ini exception-nya lolos ke halaman error dan
     * admin melihat HTTP 500 alih-alih penjelasan.
     *
     * Sengaja MENGHALANGI, bukan menghapus berantai: evaluasi adalah data inti
     * produk ini, dan satu klik tidak boleh bisa menghapus riwayat evaluasi
     * seorang dosen. Untuk mahasiswa yang berhenti, status `DO`/`lulus` memang
     * sudah disediakan supaya datanya tetap tersimpan sebagai histori (PRD §2.3).
     *
     * @param  array<string, string>  $relations  nama relasi => sebutan untuk pesan
     * @return string|null pesan penolakan, atau null bila aman dihapus
     */
    protected function blockedByRelations(Model $record, string $label, array $relations): ?string
    {
        $inUse = [];

        foreach ($relations as $relation => $noun) {
            $count = $record->{$relation}()->count();

            if ($count > 0) {
                $inUse[] = "{$count} {$noun}";
            }
        }

        if ($inUse === []) {
            return null;
        }

        return "{$label} tidak bisa dihapus karena masih dipakai ".implode(', ', $inUse).'.';
    }
}
