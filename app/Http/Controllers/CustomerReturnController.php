<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StoreCustomerReturnRequest;
use App\Http\Resources\FloorResource;
use App\Models\Floor;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Devolução de cliente (customer_return_in): avulsa ou a partir de uma venda entregue (?sale=ID). */
class CustomerReturnController extends Controller
{
    public function create(Request $request, StockService $stock): Response|RedirectResponse
    {
        $sale = null;
        $payload = null;

        if ($request->filled('sale')) {
            $sale = Sale::query()->with('items.floor')->findOrFail($request->integer('sale'));

            if (! $sale->isDelivered()) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'Só é possível gerar devolução de vendas entregues.',
                ]);

                return to_route('sales.show', $sale);
            }

            $summary = $stock->returnSummary($sale);

            $payload = [
                'id' => $sale->id,
                'document_number' => $sale->document_number,
                'customer_name' => $sale->customer_name,
                'delivered_at' => $sale->delivered_at?->toIso8601String(),
                'items' => $sale->items->map(fn ($item) => [
                    'floor' => [
                        'id' => $item->floor->id,
                        'code' => $item->floor->code,
                        'description' => $item->floor->description,
                        'reference' => $item->floor->reference,
                    ],
                    'sold' => (string) $summary[$item->floor_id]['sold'],
                    'returned' => (string) $summary[$item->floor_id]['returned'],
                    'returnable' => (string) $summary[$item->floor_id]['returnable'],
                ])->values()->all(),
            ];
        }

        return Inertia::render('customer-returns/Create', [
            'sale' => $payload,
            'floors' => $sale ? [] : FloorResource::collection(Floor::orderBy('code')->get())->resolve(),
        ]);
    }

    public function store(StoreCustomerReturnRequest $request, StockService $stock): RedirectResponse
    {
        $return = $stock->createCustomerReturn($request->returnData(), $request->items(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Devolução registrada.']);

        return $return->sale_id
            ? to_route('sales.show', $return->sale_id)
            : to_route('movements.index', [
                'type' => StockMovementType::CustomerReturnIn->value,
                'document_number' => $return->document_number,
            ]);
    }
}