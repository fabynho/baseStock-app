<?php

namespace App\Http\Requests\Concerns;

/**
 * Regras e normalização para quantidades em m² — decimal(10,4):
 * até 6 dígitos inteiros e 4 casas decimais, sem notação científica.
 */
trait ValidatesQuantities
{
    protected function quantityRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'numeric',
            'gt:0',
            'regex:/^\d{1,6}(\.\d{1,4})?$/',
        ];
    }

    /** Variação com sinal (ajuste manual); zero é rejeitado. */
    protected function signedQuantityRules(): array
    {
        return [
            'required',
            'numeric',
            'regex:/^-?\d{1,6}(\.\d{1,4})?$/',
            function (string $attribute, mixed $value, \Closure $fail) {
                if ((float) $value == 0.0) {
                    $fail('O ajuste não pode ser zero.');
                }
            },
        ];
    }

    protected function quantityMessages(string $key): array
    {
        return [
            "{$key}.gt" => 'A quantidade deve ser maior que zero.',
            "{$key}.regex" => 'A quantidade deve ter no máximo 6 dígitos inteiros e 4 casas decimais.',
            "{$key}.numeric" => 'Informe a quantidade em m² como número.',
        ];
    }

    /** "1.234,5" / "12,5" (pt-BR) → "1234.5" / "12.5". Valores sem vírgula passam intactos. */
    protected function normalizeDecimal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if (str_contains($value, ',')) {
            $value = str_replace(',', '.', str_replace('.', '', $value));
        }

        return $value;
    }

    protected function normalizeDecimalFields(string ...$keys): void
    {
        $merge = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $merge[$key] = $this->normalizeDecimal($this->input($key));
            }
        }

        $this->merge($merge);
    }
}
