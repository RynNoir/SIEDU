<?php

use App\Services\CsvImportService;

beforeEach(function () {
    $this->csv = new CsvImportService;
    $this->path = sys_get_temp_dir().'/siedu-import-test-'.uniqid().'.csv';
});

afterEach(function () {
    if (file_exists($this->path)) {
        unlink($this->path);
    }
});

function writeCsv(string $path, string $isi): string
{
    file_put_contents($path, $isi);

    return $path;
}

test('membaca baris jadi array berkunci nama kolom', function () {
    writeCsv($this->path, "nim;nama\n2401001;Budi\n2401002;Citra\n");

    $rows = $this->csv->read($this->path);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['data'])->toBe(['nim' => '2401001', 'nama' => 'Budi'])
        ->and($rows[1]['data']['nama'])->toBe('Citra');
});

test('nomor baris mengikuti penomoran spreadsheet dengan header sebagai baris 1', function () {
    writeCsv($this->path, "nim;nama\n2401001;Budi\n2401002;Citra\n");

    $rows = $this->csv->read($this->path);

    expect($rows[0]['line'])->toBe(2)
        ->and($rows[1]['line'])->toBe(3);
});

test('BOM UTF-8 dari Excel dibuang supaya kolom pertama tetap cocok', function () {
    writeCsv($this->path, "\xEF\xBB\xBFnim;nama\n2401001;Budi\n");

    $rows = $this->csv->read($this->path);

    expect($rows[0]['data'])->toHaveKey('nim')
        ->and($rows[0]['data']['nim'])->toBe('2401001');
});

test('pemisah koma ikut dikenali, bukan hanya titik koma', function () {
    writeCsv($this->path, "nim,nama,email\n2401001,Budi,budi@kampus.test\n");

    $rows = $this->csv->read($this->path);

    expect($rows[0]['data']['nama'])->toBe('Budi')
        ->and($rows[0]['data']['email'])->toBe('budi@kampus.test');
});

test('nama kolom dinormalkan jadi huruf kecil bergaris bawah', function () {
    writeCsv($this->path, "NIM; Kode Prodi ;Kode  Kelas\n2401001;MI;MI1A\n");

    $rows = $this->csv->read($this->path);

    expect(array_keys($rows[0]['data']))->toBe(['nim', 'kode_prodi', 'kode_kelas']);
});

test('baris kosong dilewati tanpa merusak penomoran', function () {
    writeCsv($this->path, "nim;nama\n2401001;Budi\n\n;\n2401002;Citra\n");

    $rows = $this->csv->read($this->path);

    expect($rows)->toHaveCount(2)
        ->and($rows[1]['line'])->toBe(5);
});

test('kolom yang kurang diisi string kosong, bukan error', function () {
    writeCsv($this->path, "nim;nama;email\n2401001;Budi\n");

    $rows = $this->csv->read($this->path);

    expect($rows[0]['data']['email'])->toBe('');
});

test('nilai dengan pemisah di dalamnya tetap utuh bila dikutip', function () {
    writeCsv($this->path, "nim;nama\n2401001;\"Budi; Santoso\"\n");

    expect($this->csv->read($this->path)[0]['data']['nama'])->toBe('Budi; Santoso');
});

test('berkas tanpa header ditolak dengan pesan yang bisa dibaca', function () {
    writeCsv($this->path, '');

    expect(fn () => $this->csv->read($this->path))
        ->toThrow(RuntimeException::class, 'Berkas kosong atau tidak memiliki baris judul kolom.');
});

test('daftar nama kolom bisa diambil untuk memberi tahu kolom yang kurang', function () {
    writeCsv($this->path, "nim;nama;email\n");

    expect($this->csv->headers($this->path))->toBe(['nim', 'nama', 'email']);
});
