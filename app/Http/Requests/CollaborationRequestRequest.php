<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Application to join a project, or invitation sent to a developer.
 */
class CollaborationRequestRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The role must belong to this project.
            'project_role_id' => ['nullable', 'integer', Rule::exists('project_roles', 'id')->where('project_id', $this->project()->id)],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function project(): Project
    {
        return $this->route('project');
    }

    public function role(): ?ProjectRole
    {
        $id = $this->validated('project_role_id');

        return $id ? ProjectRole::find($id) : null;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_role_id' => 'poste',
        ];
    }
}
