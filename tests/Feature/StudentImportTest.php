<?php

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Http\UploadedFile;

const HEADER = "nim;nama;email;kode_prodi;kode_kelas;angkatan;semester;status\n";

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    $this->kelas = ClassGroup::factory()->create([
        'study_program_id' => $this->prodi->id,
        'class_code' => 'MI1A',
        'year_level' => 1,
    ]);
    $this->path = sys_get_temp_dir().'/siedu-student-import-'.uniqid().'.csv';
    $this->service = app(StudentImportService::class);
});

afterEach(function () {
    if (file_exists($this->path)) {
        unlink($this->path);
    }
});

function csvFile(string $path, string $body): string
{
    file_put_contents($path, HEADER.$body);

    return $path;
}

test('baris yang benar dinyatakan valid', function () {
    csvFile($this->path, "2401001;Budi Santoso;budi@kampus.test;MI;MI1A;2024;1;aktif\n");

    $preview = $this->service->preview($this->path);

    expect($preview['validCount'])->toBe(1)
        ->and($preview['invalidCount'])->toBe(0)
        ->and($preview['rows'][0]['errors'])->toBeEmpty();
});

test('impor membuat akun mahasiswa lengkap dengan wajib ganti password', function () {
    csvFile($this->path, "2401001;Budi Santoso;budi@kampus.test;MI;MI1A;2024;1;aktif\n");

    $hasil = $this->service->import($this->path, $this->admin->id);

    expect($hasil['imported'])->toBe(1);

    $student = Student::where('nim', '2401001')->firstOrFail();
    expect($student->name)->toBe('Budi Santoso')
        ->and($student->class_group_id)->toBe($this->kelas->id)
        ->and($student->study_program_id)->toBe($this->prodi->id)
        ->and($student->enrollment_year)->toBe(2024)
        ->and($student->current_semester)->toBe(1)
        ->and($student->status)->toBe(StudentStatus::Aktif)
        ->and($student->created_by)->toBe($this->admin->id);

    expect($student->user->email)->toBe('budi@kampus.test')
        ->and($student->user->must_change_password)->toBeTrue();
});

test('kode prodi dan kelas yang tidak dikenal ditolak dengan menyebut kodenya', function () {
    csvFile($this->path, "2401001;Budi;budi@kampus.test;ZZ;MI9Z;2024;1;aktif\n");

    $errors = $this->service->preview($this->path)['rows'][0]['errors'];

    expect(implode(' ', $errors))
        ->toContain('Kode prodi "ZZ" tidak ditemukan')
        ->toContain('Kode kelas "MI9Z" tidak ditemukan');
});

test('semester yang tidak cocok dengan tingkat kelas ditolak', function () {
    csvFile($this->path, "2401001;Budi;budi@kampus.test;MI;MI1A;2024;3;aktif\n");

    expect(implode(' ', $this->service->preview($this->path)['rows'][0]['errors']))
        ->toContain('semester harus 1 atau 2');
});

test('kelas dari prodi lain ditolak', function () {
    $tk = StudyProgram::factory()->create(['code' => 'TK']);
    csvFile($this->path, "2401001;Budi;budi@kampus.test;TK;MI1A;2024;1;aktif\n");

    expect(implode(' ', $this->service->preview($this->path)['rows'][0]['errors']))
        ->toContain('prodi yang sama');
});

test('NIM yang sudah ada di database ditolak', function () {
    Student::factory()->create(['nim' => '2401001', 'class_group_id' => $this->kelas->id]);
    csvFile($this->path, "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n");

    expect($this->service->preview($this->path)['rows'][0]['errors'])->not->toBeEmpty();
});

test('NIM kembar di dalam berkas terdeteksi dan menyebut baris pembandingnya', function () {
    csvFile($this->path,
        "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n".
        "2401002;Citra;citra@kampus.test;MI;MI1A;2024;1;aktif\n".
        "2401001;Budi Lagi;budi2@kampus.test;MI;MI1A;2024;1;aktif\n"
    );

    $preview = $this->service->preview($this->path);

    // Baris pertama tetap lolos; yang kedua-lah yang ditolak.
    expect($preview['validCount'])->toBe(2)
        ->and($preview['invalidCount'])->toBe(1)
        ->and($preview['rows'][2]['errors'][0])->toContain('NIM 2401001 sudah dipakai di baris 2');
});

test('email kembar di dalam berkas juga terdeteksi', function () {
    csvFile($this->path,
        "2401001;Budi;sama@kampus.test;MI;MI1A;2024;1;aktif\n".
        "2401002;Citra;sama@kampus.test;MI;MI1A;2024;1;aktif\n"
    );

    expect($this->service->preview($this->path)['rows'][1]['errors'][0])
        ->toContain('Email sama@kampus.test sudah dipakai di baris 2');
});

test('baris yang ditolak tidak mengunci NIM milik baris lain yang benar', function () {
    // Baris 2 ditolak karena kelasnya tidak ada, tapi NIM-nya sama dengan baris 3.
    // Baris 3 seharusnya tetap boleh masuk.
    csvFile($this->path,
        "2401001;Budi;budi@kampus.test;MI;MI9Z;2024;1;aktif\n".
        "2401001;Budi Benar;budi2@kampus.test;MI;MI1A;2024;1;aktif\n"
    );

    $preview = $this->service->preview($this->path);

    expect($preview['rows'][1]['valid'])->toBeTrue()
        ->and($preview['validCount'])->toBe(1);
});

test('hanya baris valid yang tersimpan, sisanya dilewati', function () {
    csvFile($this->path,
        "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n".
        "2401002;Citra;citra@kampus.test;MI;MI9Z;2024;1;aktif\n".
        "2401003;Andi;andi@kampus.test;MI;MI1A;2024;2;aktif\n"
    );

    $hasil = $this->service->import($this->path, $this->admin->id);

    expect($hasil)->toBe(['imported' => 2, 'skipped' => 1])
        ->and(Student::whereIn('nim', ['2401001', '2401003'])->count())->toBe(2)
        ->and(Student::where('nim', '2401002')->exists())->toBeFalse();
});

test('pratinjau tidak menyimpan apa pun', function () {
    csvFile($this->path, "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n");

    $this->service->preview($this->path);

    expect(Student::where('nim', '2401001')->exists())->toBeFalse()
        ->and(User::where('email', 'budi@kampus.test')->exists())->toBeFalse();
});

test('kolom yang kurang dilaporkan sebagai masalah berkas, bukan per baris', function () {
    file_put_contents($this->path, "nim;nama\n2401001;Budi\n");

    $preview = $this->service->preview($this->path);

    expect($preview['missingColumns'])->toContain('email', 'kode_prodi', 'kode_kelas')
        ->and($preview['rows'])->toBeEmpty();
});

test('kode prodi dan kelas tidak peka huruf besar-kecil', function () {
    csvFile($this->path, "2401001;Budi;budi@kampus.test;mi;mi1a;2024;1;aktif\n");

    expect($this->service->preview($this->path)['validCount'])->toBe(1);
});

test('status kosong dianggap aktif', function () {
    csvFile($this->path, "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;\n");

    $this->service->import($this->path, $this->admin->id);

    expect(Student::where('nim', '2401001')->firstOrFail()->status)->toBe(StudentStatus::Aktif);
});

// ---------------------------------------------------------------------------
// Alur HTTP: unggah -> pratinjau -> konfirmasi
// ---------------------------------------------------------------------------

/**
 * Berkas unggahan palsu berisi header + body yang diberikan.
 */
function uploadedCsv(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('mahasiswa.csv', HEADER.$body);
}

test('halaman impor bisa dibuka admin dan tidak oleh peran lain', function () {
    $this->actingAs($this->admin)->get(route('admin.students.import.index'))
        ->assertOk()->assertSee('Unduh Template');

    $mahasiswa = Student::factory()->create(['class_group_id' => $this->kelas->id]);
    $this->actingAs($mahasiswa->user)->get(route('admin.students.import.index'))->assertForbidden();
});

test('template berisi baris judul kolom yang diharapkan', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.students.import.template'));
    $response->assertOk();

    expect($response->streamedContent())->toContain('nim;nama;email;kode_prodi');
});

test('pratinjau lewat unggahan menampilkan baris valid & bermasalah', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
        'file' => uploadedCsv(
            "2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n".
            "2401002;Citra;citra@kampus.test;MI;MI9Z;2024;1;aktif\n"
        ),
    ]);

    $response->assertOk()->assertSee('1 siap diimpor')->assertSee('1 bermasalah')
        ->assertSee('Kode kelas &quot;MI9Z&quot; tidak ditemukan', escape: false);

    // Pratinjau tidak boleh menyimpan apa pun.
    expect(Student::where('nim', '2401001')->exists())->toBeFalse();
});

test('konfirmasi menyimpan baris valid lalu mengarahkan ke daftar mahasiswa', function () {
    $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
        'file' => uploadedCsv("2401001;Budi;budi@kampus.test;MI;MI1A;2024;1;aktif\n"),
    ])->assertOk();

    $this->actingAs($this->admin)->post(route('admin.students.import.store'))
        ->assertRedirect(route('admin.students.index'))
        ->assertSessionHas('success');

    expect(Student::where('nim', '2401001')->exists())->toBeTrue();
});

test('konfirmasi tanpa pratinjau ditolak dengan pesan yang jelas', function () {
    $this->actingAs($this->admin)->post(route('admin.students.import.store'))
        ->assertRedirect(route('admin.students.import.index'))
        ->assertSessionHas('error');
});

test('berkas bukan CSV ditolak', function () {
    $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
        'file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});

test('berkas tanpa kolom wajib dilaporkan sebagai masalah berkas', function () {
    $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
        'file' => UploadedFile::fake()->createWithContent('salah.csv', "nim;nama\n2401001;Budi\n"),
    ])->assertOk()->assertSee('Kolom berikut tidak ditemukan');
});

test('tombol Impor CSV muncul di halaman mahasiswa', function () {
    $this->actingAs($this->admin)->get(route('admin.students.index'))
        ->assertOk()->assertSee(route('admin.students.import.index'), escape: false);
});
