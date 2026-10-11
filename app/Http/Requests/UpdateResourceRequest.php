<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateResourceRequest extends StoreResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['boolean'],
            'remove_file' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function resourceData(): array
    {
        $data = parent::resourceData();
        $data['remove_image'] = $this->boolean('remove_image');
        $data['remove_file'] = $this->boolean('remove_file');

        return $data;
    }
}
