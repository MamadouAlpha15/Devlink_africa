<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * "Proposer une collaboration": invite a developer to one of my projects.
 */
class InviteToProjectRequest extends CollaborationRequestRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
    }

    public function invitee(): User
    {
        return User::findOrFail($this->validated('user_id'));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), ['user_id' => 'développeur']);
    }
}
