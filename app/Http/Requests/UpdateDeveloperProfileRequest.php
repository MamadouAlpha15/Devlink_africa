<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeveloperProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_type' => ['required', Rule::enum(ProfileType::class)],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'availability' => ['required', Rule::enum(Availability::class)],
            'github_url' => ['nullable', 'url:https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'is_public' => ['boolean'],
            'show_email' => ['boolean'],
            'show_availability' => ['boolean'],
            'notify_by_email' => ['boolean'],
            'notify_on_platform' => ['boolean'],
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
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'avatar' => 'photo',
            'country_id' => 'pays',
            'city' => 'ville',
            'profile_type' => 'rôle',
            'experience_level' => 'niveau',
            'availability' => 'disponibilité',
            'github_url' => 'lien GitHub',
            'linkedin_url' => 'lien LinkedIn',
            'portfolio_url' => 'portfolio',
            'website_url' => 'site personnel',
        ];
    }
}
