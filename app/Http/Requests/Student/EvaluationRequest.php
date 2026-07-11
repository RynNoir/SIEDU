<?php

namespace App\Http\Requests\Student;

use App\Models\EvaluationQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EvaluationRequest extends FormRequest
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
            'answers' => ['required', 'array'],
            'answers.*' => ['integer', 'between:0,5'],
            'impression_text' => ['nullable', 'string', 'max:2000'],
            'suggestion_text' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Pastikan SEMUA pertanyaan aktif diberi nilai 1–5 (§6.3, copy §12).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $activeIds = EvaluationQuestion::active()->pluck('id')->all();
                $answers = (array) $this->input('answers', []);

                $allAnswered = collect($activeIds)->every(function ($id) use ($answers): bool {
                    $value = (int) ($answers[$id] ?? 0);

                    return $value >= 1 && $value <= 5;
                });

                if (! $allAnswered) {
                    $validator->errors()->add('answers', 'Semua pertanyaan wajib diberi nilai sebelum mengirim.');
                }
            },
        ];
    }
}
