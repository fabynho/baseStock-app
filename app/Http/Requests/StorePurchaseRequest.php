<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesStockItems;
use Illuminate\Support\Arr;

class StorePurchaseRequest extends FormRequest
{
    use ValidatesStockItems;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeItemQuantities();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:60'],
            'supplier_name' => ['required', 'string', 'max:150'],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'received_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $this->itemRules();
    }

    public function messages(): array
    {
        return [
            'document_date.before_or_equal' => 'A data do documento não pode ser futura.',
            'received_at.before_or_equal' => 'A data de entrada não pode ser futura.',
        ] + $this->itemMessages();
    }

    public function attributes(): array
    {
        return [
            'document_number' => 'número do documento',
            'supplier_name' => 'fornecedor',
            'document_date' => 'data do documento',
            'received_at' => 'data de entrada',
            'notes' => 'observações',
        ] + $this->itemAttributes();
    }

    /** Cabeçalho no formato de StockService::createPurchase(). Nunca inclui created_by. */
    public function purchaseData(): array
    {
        return Arr::only($this->validated(), [
            'document_number', 'supplier_name', 'document_date', 'received_at', 'notes',
        ]);
    }
}
