<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassGroupRequest extends FormRequest
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
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'class_letter' => ['required', 'string', 'size:1', 'alpha'],
            'capacity' => ['required', 'integer', 'between:1,60'],
        ];
    }

    /**
     * Aturan tambahan: year_level tidak melebihi jenjang prodi, dan
     * class_code ({KODE}{TAHUN}{HURUF}) unik per tahun ajaran (PRD §2.2).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $prodi = StudyProgram::find($this->input('study_program_id'));
                if (! $prodi) {
                    return;
                }

                $maxYear = intdiv($prodi->total_semesters, 2);
                if ((int) $this->input('year_level') > $maxYear) {
                    $validator->errors()->add('year_level', "Tahun maksimal prodi {$prodi->code} adalah {$maxYear}.");
                }

                $classCode = $prodi->code.$this->input('year_level').strtoupper((string) $this->input('class_letter'));

                $query = ClassGroup::where('academic_year', $this->input('academic_year'))
                    ->where('class_code', $classCode);

                if ($current = $this->route('class_group')) {
                    $query->whereKeyNot($current->getKey());
                }

                if ($query->exists()) {
                    $validator->errors()->add('class_letter', "Kelas {$classCode} sudah ada di tahun ajaran {$this->input('academic_year')}.");
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.regex' => 'Format tahun ajaran: 2025/2026.',
        ];
    }
}
