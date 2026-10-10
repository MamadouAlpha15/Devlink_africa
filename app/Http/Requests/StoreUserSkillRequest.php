<?php

namespace App\Http\Requests;

use App\Enums\SkillLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserSkillRequest extends FormRequest
{
    public const MAX_SKILLS = 15;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'skill_id' => [
                'required', 'integer', 'exists:skills,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $user = $this->user();
                    $isNew = ! $user->skills()->whereKey($value)->exists();

                    if ($isNew && $user->skills()->count() >= self::MAX_SKILLS) {
                        $fail('Vous pouvez ajouter au maximum '.self::MAX_SKILLS.' compétences.');
                    }
                },
            ],
            'level' => ['required', Rule::enum(SkillLevel::class)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'skill_id' => 'compétence',
            'level' => 'niveau',
        ];
    }
}
