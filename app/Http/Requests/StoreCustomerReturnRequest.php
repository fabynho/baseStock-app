<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStockItems;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class StoreCustomerReturnRequest extends FormRequest
{
    use ValidatesStockItems;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeItemQuantities();
    }

    public function rules(): array
    {
        return [
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'document_number' => ['required', 'string', 'max:60'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ] + $this->itemRules();
    }

    /** Sobrescreve a regra do trait: devolução aceita pisos inativos (o piso pode ter saído de linha). */
    protected function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.floor_id' => ['required', 'integer', 'distinct', 'exists:floors,id'],
            'items.*.quantity_m2' => $this->quantityRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Informe a quantidade de ao menos um piso.',
            'items.min' => 'Informe a quantidade de ao menos um piso.',
            'items.*.floor_id.exists' => 'O piso selecionado não existe.',
            'sale_id.exists' => 'A venda informada não existe.',
        ] + $this->itemMessages();
    }

    public function attributes(): array
    {
        return [
            'sale_id' => 'venda',
            'document_number' => 'número do documento',
            'customer_name' => 'cliente',
            'reason' => 'motivo da devolução',
        ] + $this->itemAttributes();
    }

    public function returnData(): array
    {
        return Arr::only($this->validated(), ['sale_id', 'document_number', 'customer_name', 'reason']);
    }
}