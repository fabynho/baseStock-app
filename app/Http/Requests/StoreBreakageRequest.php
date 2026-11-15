<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesQuantities;

class StoreBreakageRequest extends FormRequest
{
    use ValidatesQuantities;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalFields('quantity_m2');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'floor_id' => ['required', 'integer', 'exists:floors,id'],
            'quantity_m2' => $this->quantityRules(),
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return $this->quantityMessages('quantity_m2');
    }

    public function attributes(): array
    {
        return [
            'floor_id' => 'piso',
            'quantity_m2' => 'quantidade (m²)',
            'reason' => 'motivo da quebra/avaria',
        ];
    }

    public function floorId(): int
    {
        return (int) $this->validated('floor_id');
    }

    public function quantity(): string
    {
        return (string) $this->validated('quantity_m2');
    }
}
