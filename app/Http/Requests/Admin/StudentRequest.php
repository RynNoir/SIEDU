<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return static::rulesFor($this->route('student'));
    }

    /**
     * Aturan lapangan mahasiswa, dipakai bersama form manual dan impor massal.
     *
     * Sengaja dipisah jadi method statis: kalau impor menulis ulang aturannya
     * sendiri, cepat atau lambat kedua jalur akan menerima data yang berbeda
     * tanpa ada yang menyadarinya sampai datanya sudah kacau.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rulesFor(?Student $student = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:255', Rule::unique('students', 'nim')->ignore($student)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')],
            // Sengaja hanya divalidasi rentangnya, tidak dipaksa cocok dengan kelas:
            // mahasiswa yang pernah cuti memang tertinggal dari kelasnya, dan justru
            // kasus itulah yang membuat angkatan perlu disimpan terpisah.
            'enrollment_year' => ['required', 'integer', 'between:2000,'.(now()->year + 1)],
            'current_semester' => ['required', 'integer', 'between:1,8'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * Aturan silang §7.1 yang tak bisa dinyatakan per-lapangan: semester harus
     * konsisten dengan year_level kelas (tahun Y → semester 2Y-1 atau 2Y), dan
     * prodi kelas harus cocok dengan prodi mahasiswa.
     *
     * Mengembalikan galat alih-alih menyentuh validator, supaya impor massal
     * bisa memakai aturan yang sama untuk menyusun laporan per baris.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string> nama lapangan => pesan
     */
    public static function classConsistencyErrors(array $data): array
    {
        $class = ClassGroup::find($data['class_group_id'] ?? null);

        if (! $class) {
            return [];
        }

        $errors = [];
        $semester = (int) ($data['current_semester'] ?? 0);
        $allowed = [$class->year_level * 2 - 1, $class->year_level * 2];

        if (! in_array($semester, $allowed, true)) {
            $errors['current_semester'] = "Untuk kelas tahun {$class->year_level}, semester harus {$allowed[0]} atau {$allowed[1]}.";
        }

        if ((int) ($data['study_program_id'] ?? 0) !== $class->study_program_id) {
            $errors['class_group_id'] = 'Kelas harus dari prodi yang sama dengan mahasiswa.';
        }

        return $errors;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                foreach (static::classConsistencyErrors($this->all()) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }
}
