<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** Requer o parâmetro de rota `floor` (route model binding). */
class UpdateFloorRequest extends StoreFloorRequest
{
    protected function uniqueCode(): Unique
    {
        return Rule::unique('floors', 'code')->ignore($this->route('floor'));
    }
}
