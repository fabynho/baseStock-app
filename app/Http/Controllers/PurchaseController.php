<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFilterRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\FloorResource;
use App\Http\Resources\PurchaseResource;
use App\Models\Floor;
use App\Models\Purchase;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/** Entradas por compra. Sem edição/exclusão: registros que mexem em saldo são imutáveis. */
class PurchaseController extends Controller
{
    public function index(DocumentFilterRequest $request): Response
    {
        $v = $request->validated();

        $purchases = Purchase::query()
            ->with('creator:id,name')
            ->search($v['search'] ?? null)
            ->when($v['date_from'] ?? null, fn ($q, $d) => $q->where('received_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($v['date_to'] ?? null, fn ($q, $d) => $q->where('received_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('received_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('purchases/Index', [
            'purchases' => PurchaseResource::collection($purchases),
            'filters' => $v,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchases/Create', [
            'floors' => FloorResource::collection(Floor::active()->orderBy('code')->get())->resolve(),
        ]);
    }

    public function store(StorePurchaseRequest $request, StockService $stock): RedirectResponse
    {
        $purchase = $stock->createPurchase($request->purchaseData(), $request->items(), $request->user());

        return to_route('purchases.show', $purchase)->with('success', 'Entrada registrada.');
    }

    public function show(Purchase $purchase): Response
    {
        return Inertia::render('purchases/Show', [
            'purchase' => PurchaseResource::make(
                $purchase->load(['creator:id,name', 'items.floor'])
            )->resolve(),
        ]);
    }
}
