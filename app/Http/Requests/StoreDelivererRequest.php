<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDelivererRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $upperFields = [];
        foreach (['name', 'vehicle_info'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $upperFields[$field] = is_string($value) && filled(trim($value))
                    ? Str::upper(trim($value), 'UTF-8')
                    : $value;
            }
        }

        if ($upperFields !== []) {
            $this->merge($upperFields);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'vehicle_info' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'vehicle_info' => 'veículo/placa', 'is_active' => 'ativo'];
    }
}
