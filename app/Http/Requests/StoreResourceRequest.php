<?php

namespace App\Http\Requests;

use App\Enums\ResourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
{
    /** File types a resource can be shared as. */
    public const FILE_EXTENSIONS = ['zip', 'rar', '7z', 'tar', 'gz', 'pdf', 'md', 'txt', 'json'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(ResourceType::class)],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'skills' => ['nullable', 'array', 'max:10'],
            'skills.*' => ['integer', 'distinct', 'exists:skills,id'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['string', 'min:2', 'max:30'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'file' => ['nullable', 'file', 'extensions:'.implode(',', self::FILE_EXTENSIONS), 'max:10240'],
            'github_url' => ['nullable', 'url:https', 'max:255'],
            'demo_url' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * Tags arrive as one text field ("paiement, mobile money") and are validated as a list.
     */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');

        if (is_string($tags)) {
            $this->merge([
                'tags' => collect(explode(',', $tags))
                    ->map(fn (string $tag) => trim($tag))
                    ->filter()
                    ->unique(fn (string $tag) => Str::slug($tag))
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function resourceData(): array
    {
        $data = $this->safe()->except(['image', 'file']);
        $data['skills'] ??= [];
        $data['tags'] ??= [];

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
            'type' => 'type',
            'country_id' => 'pays',
            'skills' => 'technologies',
            'tags' => 'tags',
            'tags.*' => 'tag',
            'image' => 'image',
            'file' => 'fichier',
            'github_url' => 'lien GitHub',
            'demo_url' => 'lien de démo',
        ];
    }
}
