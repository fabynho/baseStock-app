<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualAdjustmentRequest;
use App\Http\Resources\FloorResource;
use App\Models\Floor;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Ajuste manual de inventário. Rotas protegidas pelo middleware `admin`. */
class ManualAdjustmentController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('adjustments/Create', [
            'floors' => FloorResource::collection(Floor::orderBy('code')->get()),
        ]);
    }

    public function store(StoreManualAdjustmentRequest $request, StockService $stock): RedirectResponse
    {
        $stock->adjustManually(
            $request->floorId(),
            $request->delta(),
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('movements.index', ['floor_id' => $request->floorId()])
            ->with('success', 'Ajuste registrado.');
    }
}
