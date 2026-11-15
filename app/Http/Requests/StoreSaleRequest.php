<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\DeliveryType;
use App\Http\Requests\Concerns\ValidatesStockItems;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:150'],
            'seller_id' => ['required', 'integer', Rule::exists('sellers', 'id')->where('is_active', true)],
            'delivery_type' => ['required', Rule::enum(DeliveryType::class)],
            'sold_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $this->itemRules();
    }

    public function messages(): array
    {
        return [
            'seller_id.exists' => 'O vendedor selecionado não existe ou está inativo.',
            'sold_at.before_or_equal' => 'A data da venda não pode ser futura.',
        ] + $this->itemMessages();
    }

    public function attributes(): array
    {
        return [
            'document_number' => 'número do documento',
            'customer_name' => 'cliente',
            'seller_id' => 'vendedor',
            'delivery_type' => 'tipo de entrega',
            'sold_at' => 'data da venda',
            'notes' => 'observações',
        ] + $this->itemAttributes();
    }

    /** Cabeçalho no formato de StockService::createSale(). Status, entregador e created_by não são aceitos do cliente. */
    public function saleData(): array
    {
        return Arr::only($this->validated(), [
            'document_number', 'customer_name', 'seller_id', 'delivery_type', 'sold_at', 'notes',
        ]);
    }
}
