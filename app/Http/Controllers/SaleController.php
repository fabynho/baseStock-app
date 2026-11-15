<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryType;
use App\Enums\SaleStatus;
use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\DocumentFilterRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\DelivererResource;
use App\Http\Resources\FloorResource;
use App\Http\Resources\SaleResource;
use App\Http\Resources\SellerResource;
use App\Models\Deliverer;
use App\Models\Floor;
use App\Models\Sale;
use App\Models\Seller;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(DocumentFilterRequest $request): Response
    {
        $v = $request->validated();

        $sales = Sale::query()
            ->with('seller:id,name')
            ->search($v['search'] ?? null)
            ->when($v['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($v['date_from'] ?? null, fn ($q, $d) => $q->where('sold_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($v['date_to'] ?? null, fn ($q, $d) => $q->where('sold_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('sold_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('sales/Index', [
            'sales' => SaleResource::collection($sales),
            'filters' => $v,
            'statuses' => SaleStatus::options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sales/Create', [
            'sellers' => SellerResource::collection(Seller::active()->orderBy('name')->get())->resolve(),
            'floors' => FloorResource::collection(Floor::active()->orderBy('code')->get())->resolve(),
            'deliveryTypes' => DeliveryType::options(),
        ]);
    }

    public function store(StoreSaleRequest $request, StockService $stock): RedirectResponse
    {
        $sale = $stock->createSale($request->saleData(), $request->items(), $request->user());

        return to_route('sales.show', $sale)->with('success', 'Venda registrada.');
    }

    public function show(Sale $sale): Response
    {
        $sale->load(['seller', 'deliverer', 'creator:id,name', 'items.floor']);

        return Inertia::render('sales/Show', [
            'sale' => SaleResource::make($sale)->resolve(),
            'deliverers' => $sale->isPending()
                ? DelivererResource::collection(Deliverer::active()->orderBy('name')->get())
                : [],
        ]);
    }

    /** Somente admin (middleware `admin` + CancelSaleRequest). Apenas vendas pendentes. */
    public function cancel(CancelSaleRequest $request, Sale $sale, StockService $stock): RedirectResponse
    {
        $stock->cancelSale($sale, $request->user(), $request->reason());

        return to_route('sales.show', $sale)->with('success', 'Venda cancelada e estoque liberado.');
    }
}
