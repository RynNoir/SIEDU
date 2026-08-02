<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StudentStatus;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\ClassGroup;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Impor massal mahasiswa dari CSV.
 *
 * Prinsip yang dipegang di sini:
 *
 * 1. **Validasi tidak ditulis ulang.** Aturannya diambil dari StudentRequest,
 *    sumber yang sama dengan form manual, supaya kedua jalur tidak bisa
 *    menerima data yang berbeda.
 * 2. **Berkas dirujuk lewat kode, bukan ID.** Admin punya "MI1A" di
 *    spreadsheet-nya, bukan class_group_id = 7.
 * 3. **Duplikat di dalam berkas ikut diperiksa.** Aturan `unique` hanya melihat
 *    database; dua baris ber-NIM sama dalam satu berkas akan sama-sama lolos
 *    (belum ada yang tersimpan saat diperiksa) lalu menabrak satu sama lain
 *    saat penyimpanan. Ini kasus yang lumrah terjadi karena salin-tempel.
 */
class StudentImportService
{
    public const COLUMNS = ['nim', 'nama', 'email', 'kode_prodi', 'kode_kelas', 'angkatan', 'semester', 'status'];

    public function __construct(private CsvImportService $csv) {}

    /**
     * Periksa seluruh berkas tanpa menyimpan apa pun.
     *
     * @return array{
     *     rows: list<array{line:int, data:array<string,string>, errors:list<string>, valid:bool}>,
     *     validCount: int, invalidCount: int, missingColumns: list<string>
     * }
     */
    public function preview(string $path): array
    {
        $missingColumns = array_values(array_diff(self::COLUMNS, $this->csv->headers($path)));

        if ($missingColumns !== []) {
            return ['rows' => [], 'validCount' => 0, 'invalidCount' => 0, 'missingColumns' => $missingColumns];
        }

        $programsByCode = StudyProgram::pluck('id', 'code');
        $classesByCode = ClassGroup::pluck('id', 'class_code');
        $seen = ['nim' => [], 'email' => []];
        $rows = [];

        foreach ($this->csv->read($path) as $row) {
            $attributes = $this->toAttributes($row['data'], $programsByCode, $classesByCode);
            $errors = $this->errorsFor($row['data'], $attributes);

            // Duplikat dalam berkas hanya diperiksa untuk baris yang selebihnya
            // sudah valid, dan nilainya baru "diklaim" kalau barisnya lolos
            // seutuhnya. Kalau tidak, baris yang toh akan ditolak bisa mengunci
            // NIM milik baris lain yang sebenarnya benar.
            if ($errors === []) {
                $errors = $this->duplicateErrors($row, $seen);

                if ($errors === []) {
                    $seen['nim'][$row['data']['nim']] = $row['line'];
                    $seen['email'][$row['data']['email']] = $row['line'];
                }
            }

            $rows[] = [
                'line' => $row['line'],
                'data' => $row['data'],
                'attributes' => $attributes,
                'errors' => $errors,
                'valid' => $errors === [],
            ];
        }

        return [
            'rows' => $rows,
            'validCount' => count(array_filter($rows, fn (array $r): bool => $r['valid'])),
            'invalidCount' => count(array_filter($rows, fn (array $r): bool => ! $r['valid'])),
            'missingColumns' => [],
        ];
    }

    /**
     * Simpan baris yang lolos. Berkas diperiksa ulang dari nol, bukan memercayai
     * hasil pratinjau — isi database bisa berubah di antara dua langkah itu.
     *
     * Seluruh penyimpanan berada dalam satu transaction supaya kegagalan di
     * tengah jalan tidak menyisakan puluhan akun setengah jadi.
     *
     * @return array{imported:int, skipped:int}
     */
    public function import(string $path, int $createdBy): array
    {
        $preview = $this->preview($path);
        $valid = array_values(array_filter($preview['rows'], fn (array $row): bool => $row['valid']));

        DB::transaction(function () use ($valid, $createdBy): void {
            foreach ($valid as $row) {
                $attributes = $row['attributes'];

                $user = User::create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => Hash::make(config('evaluation.default_password')),
                    'role' => Role::Student,
                    'must_change_password' => true,
                ]);

                $user->student()->create([
                    'nim' => $attributes['nim'],
                    'name' => $attributes['name'],
                    'study_program_id' => $attributes['study_program_id'],
                    'class_group_id' => $attributes['class_group_id'],
                    'enrollment_year' => $attributes['enrollment_year'],
                    'current_semester' => $attributes['current_semester'],
                    'status' => $attributes['status'],
                    'created_by' => $createdBy,
                ]);
            }
        });

        return ['imported' => count($valid), 'skipped' => $preview['invalidCount']];
    }

    /**
     * NIM/email yang bentrok dengan baris sebelumnya di berkas yang sama.
     *
     * @param  array{line:int, data:array<string,string>}  $row
     * @param  array{nim:array<string,int>, email:array<string,int>}  $seen
     * @return list<string>
     */
    private function duplicateErrors(array $row, array $seen): array
    {
        $errors = [];

        foreach (['nim' => 'NIM', 'email' => 'Email'] as $field => $label) {
            $value = $row['data'][$field];

            if ($value !== '' && isset($seen[$field][$value])) {
                $errors[] = "{$label} {$value} sudah dipakai di baris {$seen[$field][$value]}.";
            }
        }

        return $errors;
    }

    /**
     * Baris CSV berbentuk kode diterjemahkan jadi bentuk yang dimengerti
     * validator & model (ID relasi, tipe angka).
     *
     * @param  array<string, string>  $data
     * @param  Collection<string, int>  $programsByCode
     * @param  Collection<string, int>  $classesByCode
     * @return array<string, mixed>
     */
    private function toAttributes(array $data, Collection $programsByCode, Collection $classesByCode): array
    {
        return [
            'name' => $data['nama'],
            'nim' => $data['nim'],
            'email' => $data['email'],
            'study_program_id' => $programsByCode[strtoupper($data['kode_prodi'])] ?? null,
            'class_group_id' => $classesByCode[strtoupper($data['kode_kelas'])] ?? null,
            'enrollment_year' => is_numeric($data['angkatan']) ? (int) $data['angkatan'] : $data['angkatan'],
            'current_semester' => is_numeric($data['semester']) ? (int) $data['semester'] : $data['semester'],
            'status' => strtolower($data['status']) !== '' ? strtolower($data['status']) : StudentStatus::Aktif->value,
        ];
    }

    /**
     * Kumpulkan seluruh alasan penolakan satu baris.
     *
     * Kode prodi/kelas yang tak dikenal dilaporkan dengan menyebut kodenya, bukan
     * "class_group_id tidak valid" — admin tidak pernah melihat ID di berkasnya.
     *
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function errorsFor(array $data, array $attributes): array
    {
        $errors = [];

        if ($data['kode_prodi'] !== '' && $attributes['study_program_id'] === null) {
            $errors[] = "Kode prodi \"{$data['kode_prodi']}\" tidak ditemukan.";
        }

        if ($data['kode_kelas'] !== '' && $attributes['class_group_id'] === null) {
            $errors[] = "Kode kelas \"{$data['kode_kelas']}\" tidak ditemukan.";
        }

        $validator = Validator::make($attributes, StudentRequest::rulesFor(), [], [
            'name' => 'nama',
            'study_program_id' => 'kode prodi',
            'class_group_id' => 'kode kelas',
            'enrollment_year' => 'angkatan',
            'current_semester' => 'semester',
        ]);

        foreach ($validator->errors()->all() as $message) {
            $errors[] = $message;
        }

        if ($validator->errors()->isEmpty()) {
            $errors = [...$errors, ...array_values(StudentRequest::classConsistencyErrors($attributes))];
        }

        return array_values(array_unique($errors));
    }
}
