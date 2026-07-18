<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShareholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($q) => $q->where('is_draft', false)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_investment' => ['required', 'numeric', 'min:0'],
        ];
    }
}
