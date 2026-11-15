<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\DeliveryType;
use Illuminate\Validation\Rule;

class ConfirmDeliveryRequest extends FormRequest
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
            'deliverer_id' => [
                Rule::requiredIf(fn () => $this->route('sale')?->delivery_type === DeliveryType::ScheduledDelivery),
                'nullable',
                'integer',
                Rule::exists('deliverers', 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'deliverer_id.required' => 'Informe o entregador para confirmar uma entrega.',
            'deliverer_id.exists' => 'O entregador selecionado não existe ou está inativo.',
        ];
    }

    public function attributes(): array
    {
        return ['deliverer_id' => 'entregador'];
    }

    public function delivererId(): ?int
    {
        $id = $this->validated('deliverer_id');

        return $id === null ? null : (int) $id;
    }
}
