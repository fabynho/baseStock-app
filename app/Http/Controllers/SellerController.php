<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Http\Resources\SellerResource;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sellers/Index', [
            'sellers' => SellerResource::collection(Seller::orderBy('name')->get())->resolve(),
        ]);
    }

    public function store(StoreSellerRequest $request): RedirectResponse
    {
        Seller::create($request->validated());

        return back()->with('success', 'Vendedor cadastrado.');
    }

    public function update(UpdateSellerRequest $request, Seller $seller): RedirectResponse
    {
        $seller->update($request->validated());

        return back()->with('success', 'Vendedor atualizado.');
    }
}
