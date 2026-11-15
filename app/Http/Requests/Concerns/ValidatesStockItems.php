<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Regras do array `items` (piso + quantidade) compartilhadas por compras e vendas.
 */
trait ValidatesStockItems
{
    use ValidatesQuantities;

    protected function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.floor_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('floors', 'id')->where('is_active', true),
            ],
            'items.*.quantity_m2' => $this->quantityRules(),
        ];
    }

    protected function itemMessages(): array
    {
        return [
            'items.required' => 'Adicione ao menos um piso.',
            'items.min' => 'Adicione ao menos um piso.',
            'items.*.floor_id.required' => 'Selecione o piso.',
            'items.*.floor_id.exists' => 'O piso selecionado não existe ou está inativo.',
            'items.*.floor_id.distinct' => 'O mesmo piso foi informado mais de uma vez.',
        ] + $this->quantityMessages('items.*.quantity_m2');
    }

    protected function itemAttributes(): array
    {
        return [
            'items' => 'itens',
            'items.*.floor_id' => 'piso',
            'items.*.quantity_m2' => 'quantidade (m²)',
        ];
    }

    protected function normalizeItemQuantities(): void
    {
        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $i => $item) {
            if (is_array($item) && array_key_exists('quantity_m2', $item)) {
                $items[$i]['quantity_m2'] = $this->normalizeDecimal($item['quantity_m2']);
            }
        }

        $this->merge(['items' => $items]);
    }

    /**
     * Itens validados no formato esperado pelo StockService.
     *
     * @return array<int, array{floor_id: int, quantity_m2: string}>
     */
    public function items(): array
    {
        return array_map(
            fn (array $item) => [
                'floor_id' => (int) $item['floor_id'],
                'quantity_m2' => (string) $item['quantity_m2'],
            ],
            array_values($this->validated('items')),
        );
    }
}
