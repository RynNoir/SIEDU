<?php

namespace App\Http\Requests\Admin;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'integer', 'between:1,8'],
            'credit_hours' => ['required', 'integer', 'between:1,6'],
        ];
    }

    /**
     * Aturan §7.2: mata kuliah semester 7/8 hanya valid untuk prodi D4.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $prodi = StudyProgram::find($this->input('study_program_id'));

                if ($prodi && (int) $this->input('semester') >= 7 && $prodi->degree_level !== DegreeLevel::D4) {
                    $validator->errors()->add('semester', 'Semester 7–8 hanya untuk prodi D4 (TRPL, ANIM).');
                }
            },
        ];
    }
}
