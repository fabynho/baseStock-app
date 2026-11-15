<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDelivererRequest;
use App\Http\Requests\UpdateDelivererRequest;
use App\Http\Resources\DelivererResource;
use App\Models\Deliverer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DelivererController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('deliverers/Index', [
            'deliverers' => DelivererResource::collection(Deliverer::orderBy('name')->get())->resolve(),
        ]);
    }

    public function store(StoreDelivererRequest $request): RedirectResponse
    {
        Deliverer::create($request->validated());

        return back()->with('success', 'Entregador cadastrado.');
    }

    public function update(UpdateDelivererRequest $request, Deliverer $deliverer): RedirectResponse
    {
        $deliverer->update($request->validated());

        return back()->with('success', 'Entregador atualizado.');
    }
}
