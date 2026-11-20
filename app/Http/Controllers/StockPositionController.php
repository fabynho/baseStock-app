<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\StockPositionFilterRequest;
use App\Http\Resources\FloorResource;
use App\Models\Floor;
use App\Models\Sale;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard / Posição de Estoque: Físico × Comprometido × Disponível. */
class StockPositionController extends Controller
{
    public function index(StockPositionFilterRequest $request): Response
    {
        $f = $request->filters();

        $floors = Floor::query()
            ->search($f['search'])
            ->when(! $f['include_inactive'], fn ($q) => $q->active())
            ->when($f['below_minimum'], fn ($q) => $q->belowMinimum())
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('stock/Index', [
            'floors' => FloorResource::collection($floors),
            'filters' => $f,
            'stats' => [
                'pending_deliveries' => Sale::where('status', SaleStatus::PendingDelivery->value)->count(),
                'below_minimum' => Floor::active()->belowMinimum()->count(),
            ],
        ]);
    }
}
