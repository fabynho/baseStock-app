<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerReturnRequest;
use App\Http\Resources\FloorOptionResource;
use App\Models\Floor;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Devolução de cliente (customer_return_in). */
class CustomerReturnController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('customer-returns/Create', [
            'floors' => FloorOptionResource::collection(Floor::orderBy('code')->get()),
        ]);
    }

    public function store(StoreCustomerReturnRequest $request, StockService $stock): RedirectResponse
    {
        $stock->registerCustomerReturn(
            $request->floorId(),
            $request->quantity(),
            $request->user(),
            $request->validated('document_number'),
            $request->validated('reason'),
        );

        return to_route('movements.index', ['floor_id' => $request->floorId()])
            ->with('success', 'Devolução registrada.');
    }
}
