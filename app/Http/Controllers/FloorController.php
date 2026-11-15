<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockPositionFilterRequest;
use App\Http\Requests\StoreFloorRequest;
use App\Http\Requests\UpdateFloorRequest;
use App\Http\Resources\FloorResource;
use App\Models\Floor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Cadastro de pisos. Sem destroy: desative com is_active = false. Saldos só mudam via StockService. */
class FloorController extends Controller
{
    public function index(StockPositionFilterRequest $request): Response
    {
        $floors = Floor::query()
            ->search($request->filters()['search'])
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('floors/Index', [
            'floors' => FloorResource::collection($floors),
            'filters' => ['search' => $request->filters()['search']],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('floors/Form');
    }

    public function store(StoreFloorRequest $request): RedirectResponse
    {
        Floor::create($request->validated());

        return to_route('floors.index')->with('success', 'Piso cadastrado.');
    }

    public function edit(Floor $floor): Response
    {
        return Inertia::render('floors/Form', ['floor' => FloorResource::make($floor)->resolve()]);
    }

    public function update(UpdateFloorRequest $request, Floor $floor): RedirectResponse
    {
        $floor->update($request->validated());

        return to_route('floors.index')->with('success', 'Piso atualizado.');
    }
}
