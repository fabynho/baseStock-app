<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use App\Http\Requests\Concerns\ValidatesQuantities;

class StoreFloorRequest extends FormRequest
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
        $this->normalizeDecimalFields('m2_per_box', 'min_stock_alert');
    }

    protected function uniqueCode(): Unique
    {
        return Rule::unique('floors', 'code');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode()],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'm2_per_box' => $this->quantityRules(required: false),
            'min_stock_alert' => ['nullable', 'numeric', 'gte:0', 'regex:/^\d{1,6}(\.\d{1,4})?$/'],
            'is_active' => ['sometimes', 'boolean'],

            // Saldos só mudam via StockService (movimentações). Saldo inicial: lance uma compra ou ajuste manual.
            'physical_stock' => ['prohibited'],
            'committed_stock' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Já existe um piso com este código.',
            'physical_stock.prohibited' => 'O estoque físico só pode ser alterado por movimentações.',
            'committed_stock.prohibited' => 'O estoque comprometido só pode ser alterado por movimentações.',
        ] + $this->quantityMessages('m2_per_box');
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'reference' => 'referência',
            'description' => 'descrição',
            'brand' => 'marca',
            'm2_per_box' => 'm² por caixa',
            'min_stock_alert' => 'estoque mínimo',
            'is_active' => 'ativo',
        ];
    }
}
