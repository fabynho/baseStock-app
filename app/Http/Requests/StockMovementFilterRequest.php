<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\StockMovementType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

class StockMovementFilterRequest extends FormRequest
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
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'document_number' => ['nullable', 'string', 'max:60'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ];
    }

    public function messages(): array
    {
        return ['date_to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.'];
    }

    public function attributes(): array
    {
        return [
            'floor_id' => 'piso',
            'type' => 'tipo de movimentação',
            'document_number' => 'número do documento',
            'date_from' => 'data inicial',
            'date_to' => 'data final',
        ];
    }

    /**
     * @return array{floor_id: ?int, type: ?StockMovementType, document_number: ?string, from: ?CarbonImmutable, to: ?CarbonImmutable}
     */
    public function filters(): array
    {
        $v = $this->validated();

        return [
            'floor_id' => isset($v['floor_id']) ? (int) $v['floor_id'] : null,
            'type' => isset($v['type']) ? StockMovementType::from($v['type']) : null,
            'document_number' => $v['document_number'] ?? null,
            'from' => isset($v['date_from']) ? CarbonImmutable::parse($v['date_from'])->startOfDay() : null,
            'to' => isset($v['date_to']) ? CarbonImmutable::parse($v['date_to'])->endOfDay() : null,
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 50);
    }
}
