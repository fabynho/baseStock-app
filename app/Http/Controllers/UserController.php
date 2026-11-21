<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Rotas protegidas pelo middleware `admin`. */
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/Index', [
            'users' => UserResource::collection(User::orderBy('name')->paginate(25)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/Form', ['roles' => UserRole::options()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // O cast `hashed` do User aplica o hash da senha.
        User::create($request->validated());

        return to_route('users.index')->with('success', 'Usuário criado.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/Form', [
            'user' => UserResource::make($user)->resolve(),
            'roles' => UserRole::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return to_route('users.index')->with('success', 'Usuário atualizado.');
    }
}
