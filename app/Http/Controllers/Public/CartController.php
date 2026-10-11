<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart();

        return view('public.cart.index', [
            'cart' => $cart,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->cartService->add(
            (int) $validated['product_id'],
            (int) ($validated['quantity'] ?? 1)
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('cart.index')->with('success', $result['message']);
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $result = $this->cartService->update($product, (int) $validated['quantity']);

        if (! $result['ok']) {
            return redirect()->route('cart.index')->with('error', $result['message']);
        }

        return redirect()->route('cart.index')->with('success', $result['message']);
    }

    public function remove(int $product): RedirectResponse
    {
        $this->cartService->remove($product);

        return redirect()->route('cart.index')->with('success', 'La refacción fue eliminada de tu carrito.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clear();

        return redirect()->route('cart.index')->with('success', 'Se vació el carrito de compras.');
    }
}
