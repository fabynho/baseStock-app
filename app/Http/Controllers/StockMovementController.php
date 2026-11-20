<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StockMovementFilterRequest;
use App\Http\Resources\FloorOptionResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Floor;
use App\Models\StockMovement;
use Inertia\Inertia;
use Inertia\Response;

/** Kardex / auditoria por piso ou documento. */
class StockMovementController extends Controller
{
    public function index(StockMovementFilterRequest $request): Response
    {
        $f = $request->filters();

        $movements = StockMovement::query()
            ->with(['floor:id,code,description,reference', 'user:id,name'])
            ->forFloor($f['floor_id'])
            ->ofType($f['type'])
            ->forDocument($f['document_number'])
            ->between($f['from'], $f['to'])
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('movements/Index', [
            'movements' => StockMovementResource::collection($movements),
            'filters' => $request->validated(),
            'floors' => FloorOptionResource::collection(Floor::orderBy('code')->get()),
            'types' => StockMovementType::options(),
        ]);
    }
}
