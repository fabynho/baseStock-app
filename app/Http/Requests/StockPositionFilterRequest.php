<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StockPositionFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'below_minimum' => ['sometimes', 'boolean'],
            'include_inactive' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array{search: ?string, below_minimum: bool, include_inactive: bool} */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'below_minimum' => $this->boolean('below_minimum'),
            'include_inactive' => $this->boolean('include_inactive'),
        ];
    }
}
