<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:255', Rule::unique('students', 'nim')->ignore($this->route('student'))],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student')?->user_id)],
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')],
            'current_semester' => ['required', 'integer', 'between:1,8'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * §7.1: current_semester harus konsisten dengan year_level kelas
     * (tahun Y → semester 2Y-1 atau 2Y), dan prodi kelas cocok prodi mhs.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $class = ClassGroup::find($this->input('class_group_id'));
                if (! $class) {
                    return;
                }

                $sem = (int) $this->input('current_semester');
                $valid = [$class->year_level * 2 - 1, $class->year_level * 2];
                if (! in_array($sem, $valid, true)) {
                    $validator->errors()->add('current_semester', "Untuk kelas tahun {$class->year_level}, semester harus {$valid[0]} atau {$valid[1]}.");
                }

                if ((int) $this->input('study_program_id') !== $class->study_program_id) {
                    $validator->errors()->add('class_group_id', 'Kelas harus dari prodi yang sama dengan mahasiswa.');
                }
            },
        ];
    }
}
