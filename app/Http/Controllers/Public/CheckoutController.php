<?php

namespace App\Http\Controllers\Public;

use App\Contracts\PaymentGateway;
use App\Contracts\ShippingQuoteProvider;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreOrderRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PriceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutController extends Controller
{
    private readonly ShippingQuoteProvider $shippingQuoteProvider;

    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly PaymentGateway $paymentGateway,
        ?ShippingQuoteProvider $shippingQuoteProvider = null,
    ) {
        $this->shippingQuoteProvider = $shippingQuoteProvider ?? app(ShippingQuoteProvider::class);
    }

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Agrega al menos una refacción al carrito antes de pasar al checkout.');
        }

        $initialQuote = null;
        $oldPostalCode = old('shipping_postal_code');
        $oldVariant = old('delivery_variant');
        $variant = in_array($oldVariant, ['domicilio', 'ocurre', 'pickup'], true)
            ? $oldVariant
            : (old('delivery_method') === Order::DELIVERY_PICKUP ? 'pickup' : 'domicilio');

        if ($variant === 'pickup') {
            $initialQuote = $this->quoteWithProvider('47600', [], 'pickup');
        } elseif (is_string($oldPostalCode) && preg_match('/^[0-9]{5}$/', trim($oldPostalCode))) {
            $packages = $this->cartService->buildShippingPackages($cart['items']);
            $initialQuote = $this->quoteWithProvider(trim($oldPostalCode), $packages, $variant);
        }

        return view('public.checkout.index', [
            'cart' => $cart,
            'initialQuote' => $initialQuote,
        ]);
    }

    public function quoteShipping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'postal_code' => ['required_unless:delivery_variant,pickup', 'nullable', 'regex:/^[0-9]{5}$/'],
            'delivery_variant' => ['nullable', 'string', 'in:domicilio,ocurre,pickup'],
        ], [
            'postal_code.required_unless' => 'Ingresa tu Código Postal de 5 dígitos para cotizar el envío.',
            'postal_code.regex' => 'El Código Postal debe constar exactamente de 5 dígitos numéricos.',
        ]);

        $cart = $this->cartService->getCart();
        $variant = (string) ($validated['delivery_variant'] ?? 'domicilio');
        $postalCode = (string) ($validated['postal_code'] ?? '47600');
        $packages = $this->cartService->buildShippingPackages($cart['items'] ?? []);

        $quote = $this->quoteWithProvider($postalCode, $packages, $variant);

        if (empty($quote['valid'])) {
            return response()->json([
                'ok' => false,
                'message' => $quote['message'] ?? 'No fue posible cotizar el envío para ese Código Postal.',
            ], 422);
        }

        $merchandiseTotal = (float) ($cart['total'] ?? $cart['subtotal'] ?? 0.0);
        $shippingCost = (float) ($quote['cost'] ?? 0.0);
        $grandTotal = round($merchandiseTotal + $shippingCost, 2);

        return response()->json([
            'ok' => true,
            'quote' => $quote,
            'grand_total' => $grandTotal,
            'formatted_grand_total' => app(PriceService::class)->format($grandTotal),
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

        $customerData = $request->route() !== null
            ? $request->validated()
            : Arr::only($request->all(), [
                'customer_name',
                'customer_phone',
                'customer_email',
                'delivery_method',
                'delivery_variant',
                'shipping_postal_code',
                'shipping_address',
                'shipping_neighborhood',
                'shipping_city',
                'shipping_state',
                'shipping_notes',
            ]);

        try {
            $deliveryMethod = ($customerData['delivery_method'] ?? 'shipping') === Order::DELIVERY_PICKUP
                ? Order::DELIVERY_PICKUP
                : Order::DELIVERY_SHIPPING;
            $variant = match ($customerData['delivery_variant'] ?? null) {
                'ocurre' => $deliveryMethod === Order::DELIVERY_SHIPPING ? 'ocurre' : 'pickup',
                'pickup' => 'pickup',
                default => $deliveryMethod === Order::DELIVERY_PICKUP ? 'pickup' : 'domicilio',
            };

            if ($deliveryMethod === Order::DELIVERY_PICKUP) {
                $customerData['shipping_cost'] = 0.0;
            } else {
                $packages = $this->cartService->buildShippingPackages($cart['items']);
                $quote = $this->quoteWithProvider((string) ($customerData['shipping_postal_code'] ?? ''), $packages, $variant);
                $customerData['shipping_cost'] = ! empty($quote['valid']) ? (float) ($quote['cost'] ?? 0.0) : 0.0;
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

    /**
     * Invoca el cotizador configurado pasando la modalidad cuando el proveedor la soporte.
     *
     * @param  array<int, array<string, float|int>>  $packages
     * @return array<string, mixed>
     */
    private function quoteWithProvider(string $postalCode, array $packages, string $deliveryVariant): array
    {
        $method = new \ReflectionMethod($this->shippingQuoteProvider, 'quote');

        if ($method->getNumberOfParameters() >= 3) {
            return $this->shippingQuoteProvider->quote($postalCode, $packages, $deliveryVariant);
        }

        return $this->shippingQuoteProvider->quote($postalCode, $packages);
    }
}
