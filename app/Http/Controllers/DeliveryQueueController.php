<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDeliveryRequest;
use App\Http\Requests\DocumentFilterRequest;
use App\Http\Resources\DelivererResource;
use App\Http\Resources\SaleResource;
use App\Models\Deliverer;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Fila de Entregas / Retiradas Pendentes. */
class DeliveryQueueController extends Controller
{
    public function index(DocumentFilterRequest $request): Response
    {
        $search = $request->validated('search');

        $sales = Sale::query()
            ->pendingDelivery()
            ->with(['seller:id,name', 'items.floor'])
            ->search($search)
            ->orderBy('sold_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('deliveres/Index', [
            'sales' => SaleResource::collection($sales),
            'deliverers' => DelivererResource::collection(Deliverer::active()->orderBy('name')->get())->resolve(),
            'filters' => ['search' => $search],
        ]);
    }

    /** "Marcar como Entregue": baixa o comprometido e o físico. */
    public function store(ConfirmDeliveryRequest $request, Sale $sale, StockService $stock): RedirectResponse
    {
        $stock->confirmDelivery($sale, $request->user(), $request->delivererId());

        return back()->with('success', "Venda {$sale->document_number} marcada como entregue.");
    }
}
