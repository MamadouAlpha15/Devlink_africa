<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Same fields as the creation form, without the roles (managed separately),
 * and every status is allowed (completed, archived…).
 */
class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['roles'], $rules['roles.*.title'], $rules['roles.*.skill_id'], $rules['roles.*.slots']);
        $rules['remove_cover'] = ['boolean'];

        return $rules;
    }

    protected function allowedStatuses(): array
    {
        return ProjectStatus::cases();
    }

    /**
     * @return array<string, mixed>
     */
    public function projectData(): array
    {
        $data = $this->safe()->except(['cover']);
        $data['skills'] ??= [];
        $data['remove_cover'] = $this->boolean('remove_cover');

        return $data;
    }
}
