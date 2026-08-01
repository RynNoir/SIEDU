<?php

use App\Services\CsvExportService;

beforeEach(function () {
    $this->csv = new CsvExportService;
});

test('desimal memakai koma sesuai konvensi lokal Indonesia', function () {
    expect($this->csv->decimal(4.25))->toBe('4,3')
        ->and($this->csv->decimal(4.0))->toBe('4,0')
        ->and($this->csv->decimal(3.456, 2))->toBe('3,46')
        ->and($this->csv->decimal(null))->toBe('');
});

test('nama berkas dislugifikasi dan diberi tanggal', function () {
    $nama = $this->csv->filename('rekap-evaluasi', 'Manajemen Informatika');

    expect($nama)->toBe('rekap-evaluasi-manajemen-informatika-'.now()->format('Y-m-d').'.csv');
});

test('nama berkas tetap aman tanpa konteks dan dengan karakter aneh', function () {
    expect($this->csv->filename('Hasil / Evaluasi'))
        ->toBe('hasil-evaluasi-'.now()->format('Y-m-d').'.csv');
});
