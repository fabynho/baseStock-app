<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBreakageRequest;
use App\Http\Resources\FloorResource;
use App\Models\Floor;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Baixa por quebra/avaria (breakage_out): só sobre o estoque disponível. */
class BreakageController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('breakages/Create', [
            'floors' => FloorResource::collection(Floor::active()->orderBy('code')->get())->resolve(),
        ]);
    }

    public function store(StoreBreakageRequest $request, StockService $stock): RedirectResponse
    {
        $stock->registerBreakage(
            $request->floorId(),
            $request->quantity(),
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('movements.index', ['floor_id' => $request->floorId()])
            ->with('success', 'Baixa por avaria registrada.');
    }
}
