<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/** Requer o parâmetro de rota `user` (route model binding). Senha em branco = manter a atual. */
class UpdateUserRequest extends StoreUserRequest
{
    protected function uniqueEmail(): Unique
    {
        return Rule::unique('users', 'email')->ignore($this->route('user'));
    }

    protected function passwordRules(): array
    {
        return ['nullable', 'confirmed', Password::defaults()];
    }

    /** Evita que o admin se tranque para fora do sistema. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $target = $this->route('user');

                if (! $target?->is($this->user())) {
                    return;
                }

                $losesAdmin = $this->input('role') !== UserRole::Admin->value;
                $deactivates = ! $this->boolean('is_active', true);

                if ($losesAdmin || $deactivates) {
                    $validator->errors()->add(
                        'role',
                        'Você não pode remover seu próprio acesso de administrador nem desativar a si mesmo.'
                    );
                }
            },
        ];
    }
}
