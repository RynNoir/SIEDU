<?php

namespace App\Http\Requests\Admin;

use App\Enums\DegreeLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // sudah dijaga middleware role:admin
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:10',
                Rule::unique('study_programs', 'code')->ignore($this->route('study_program')),
            ],
            'degree_level' => ['required', Rule::enum(DegreeLevel::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Kode prodi sudah dipakai.',
        ];
    }
}
