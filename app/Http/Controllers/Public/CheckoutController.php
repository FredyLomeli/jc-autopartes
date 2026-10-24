<?php

namespace App\Http\Controllers\Public;

use App\Contracts\PaymentGateway;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreOrderRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly PaymentGateway $paymentGateway,
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

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Tu carrito está vacío. Agrega refacciones para continuar.');
        }

        try {
            try {
                $customerData = $request->validated();
            } catch (\Throwable) {
                $customerData = $request->all();
            }

            // 1. Crear el pedido en estado 'pending_payment'
            $order = $this->orderService->createOrderFromCart(
                $customerData,
                $cart
            );

            // 2. Generar la preferencia de Checkout Pro en Mercado Pago
            $preference = $this->paymentGateway->createCheckoutPreference(
                $order->toPaymentPayload()
            );

            // 3. Vincular la preferencia generada con la orden
            $order->mercadopago_preference_id = $preference['id'];
            if ($order->exists) {
                try {
                    $order->save();
                } catch (\Throwable $e) {
                    Log::warning('No se pudo guardar mercadopago_preference_id en DB', ['error' => $e->getMessage()]);
                }
            }

            // Guardar folio en sesión para seguimiento si el navegador no reenvía parámetros
            session()->put('checkout.last_order_number', $order->order_number);

            // 4. Redirigir al cliente al init_point de Mercado Pago (o sandbox según config)
            $isSandbox = (bool) config('mercadopago.sandbox', true);
            $redirectUrl = ($isSandbox && ! empty($preference['sandbox_init_point']))
                ? $preference['sandbox_init_point']
                : ($preference['init_point'] ?? '');

            if (empty($redirectUrl)) {
                throw new \RuntimeException('No se obtuvo una URL válida de pago desde la pasarela.');
            }

            return redirect()->away($redirectUrl);
        } catch (InsufficientStockException $e) {
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error al procesar checkout con Mercado Pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('checkout.index')
                ->withInput()
                ->with('error', 'Ocurrió un error al conectar con la pasarela de pago. Intenta nuevamente.');
        }
    }

    public function success(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('external_reference')
            ?? session()->get('checkout.last_order_number');

        if (! $orderNumber) {
            return redirect()->route('home');
        }

        $order = Order::query()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return redirect()->route('home');
        }

        // Si Mercado Pago adjunta payment_id en la redirección, registrarlo si no está presente
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        if ($paymentId && empty($order->mercadopago_payment_id)) {
            $order->update([
                'mercadopago_payment_id' => (string) $paymentId,
                'mercadopago_payment_type' => $request->query('payment_type'),
                'mercadopago_merchant_order_id' => $request->query('merchant_order_id'),
            ]);
        }

        // Limpiar carrito de compras al confirmar pago
        $this->cartService->clear();

        return view('public.checkout.success', [
            'order' => $order,
        ]);
    }

    public function failure(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('external_reference')
            ?? session()->get('checkout.last_order_number');

        $order = $orderNumber
            ? Order::query()->with('items')->where('order_number', $orderNumber)->first()
            : null;

        return view('public.checkout.failure', [
            'order' => $order,
            'errorMessage' => 'El pago no fue acreditado por la entidad bancaria o fue rechazado por Mercado Pago.',
        ]);
    }

    public function pending(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('external_reference')
            ?? session()->get('checkout.last_order_number');

        if (! $orderNumber) {
            return redirect()->route('home');
        }

        $order = Order::query()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return redirect()->route('home');
        }

        // Vaciar el carrito porque la orden ya fue apartada para pago en ventanilla/SPEI
        $this->cartService->clear();

        return view('public.checkout.pending', [
            'order' => $order,
        ]);
    }
}
