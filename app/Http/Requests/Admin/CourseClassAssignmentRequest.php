<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\EvaluationPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseClassAssignmentRequest extends FormRequest
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
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')],
            'evaluation_period_id' => ['required', Rule::exists('evaluation_periods', 'id')],
            // Unik 4-kolom: dosen yg sama tak boleh diassign 2x ke MK+kelas+periode yg sama.
            'lecturer_id' => [
                'required',
                Rule::exists('lecturers', 'id'),
                Rule::unique('course_class_assignments', 'lecturer_id')
                    ->where('course_id', $this->input('course_id'))
                    ->where('class_group_id', $this->input('class_group_id'))
                    ->where('evaluation_period_id', $this->input('evaluation_period_id'))
                    ->ignore($this->route('course_class_assignment')),
            ],
        ];
    }

    /**
     * §7.1/§7.2: MK & kelas harus satu prodi, dan semester MK cocok tahun kelas.
     * §7.8: paritas semester MK (ganjil/genap) harus cocok dengan tipe periode evaluasi.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $course = Course::find($this->input('course_id'));
                $class = ClassGroup::find($this->input('class_group_id'));
                if (! $course || ! $class) {
                    return;
                }

                if ($course->study_program_id !== $class->study_program_id) {
                    $validator->errors()->add('course_id', 'Mata kuliah dan kelas harus dari prodi yang sama.');

                    return;
                }

                $valid = [$class->year_level * 2 - 1, $class->year_level * 2];
                if (! in_array($course->semester, $valid, true)) {
                    $validator->errors()->add('course_id', "Semester MK ({$course->semester}) tidak sesuai tahun kelas ({$class->year_level}).");
                }

                $period = EvaluationPeriod::find($this->input('evaluation_period_id'));
                if ($period && $course->semesterType() !== $period->semester_type) {
                    $validator->errors()->add(
                        'evaluation_period_id',
                        "Mata kuliah semester {$course->semester} ({$course->semesterType()->value}) tidak bisa diassign ke periode bertipe {$period->semester_type->value}. Pilih periode dengan tipe semester yang sama."
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'lecturer_id.unique' => 'Dosen ini sudah diassign ke mata kuliah & kelas yang sama pada periode ini.',
        ];
    }
}
