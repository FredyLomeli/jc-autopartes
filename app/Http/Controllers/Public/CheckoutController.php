<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Agrega al menos una refacción al carrito antes de pasar al checkout.');
        }

        return view('public.checkout.index', [
            'cart' => $cart,
        ]);
    }
}
