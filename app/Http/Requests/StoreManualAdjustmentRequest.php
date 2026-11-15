<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesQuantities;

class StoreManualAdjustmentRequest extends FormRequest
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
        $this->normalizeDecimalFields('delta_m2');
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
            'delta_m2' => $this->signedQuantityRules(),
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'delta_m2.regex' => 'O ajuste deve ter no máximo 6 dígitos inteiros e 4 casas decimais (use "-" para reduzir).',
            'delta_m2.numeric' => 'Informe o ajuste em m² como número.',
        ];
    }

    public function attributes(): array
    {
        return [
            'floor_id' => 'piso',
            'delta_m2' => 'ajuste (m²)',
            'reason' => 'motivo do ajuste',
        ];
    }

    public function floorId(): int
    {
        return (int) $this->validated('floor_id');
    }

    public function delta(): string
    {
        return (string) $this->validated('delta_m2');
    }
}
