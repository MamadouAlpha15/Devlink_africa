<?php

namespace App\Http\Requests;

use App\Enums\ProjectCategory;
use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(ProjectCategory::class)],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'status' => ['required', Rule::enum(ProjectStatus::class)->only($this->allowedStatuses())],
            'members_wanted' => ['required', 'integer', 'min:1', 'max:50'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'skills' => ['nullable', 'array', 'max:10'],
            'skills.*' => ['integer', 'distinct', 'exists:skills,id'],
            'roles' => ['nullable', 'array', 'max:10'],
            'roles.*.title' => ['required', 'string', 'max:80'],
            'roles.*.skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'roles.*.slots' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * A new project starts as an idea, recruiting or in progress.
     *
     * @return list<ProjectStatus>
     */
    protected function allowedStatuses(): array
    {
        return [ProjectStatus::Idea, ProjectStatus::Recruiting, ProjectStatus::InProgress];
    }

    /**
     * Drop empty role rows sent by the form before validating.
     */
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('roles'))) {
            $this->merge([
                'roles' => array_values(array_filter(
                    $this->input('roles'),
                    fn ($role) => is_array($role) && filled($role['title'] ?? null),
                )),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function projectData(): array
    {
        $data = $this->safe()->except(['cover']);
        $data['roles'] = array_map(fn (array $role) => [
            'title' => $role['title'],
            'skill_id' => $role['skill_id'] ?? null,
            'slots' => $role['slots'] ?? 1,
        ], $data['roles'] ?? []);

        return $data;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'category' => 'catégorie',
            'country_id' => 'pays',
            'status' => 'statut',
            'members_wanted' => 'nombre de membres recherchés',
            'cover' => 'image',
            'skills' => 'technologies',
            'roles.*.title' => 'intitulé du poste',
            'roles.*.slots' => 'nombre de places',
        ];
    }
}
