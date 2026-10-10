<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'slots' => ['required', 'integer', 'min:1', 'max:10'],
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
            'title' => 'intitulé du poste',
            'skill_id' => 'compétence',
            'slots' => 'nombre de places',
        ];
    }
}
