<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

/**
 * Servicio de Carrito en Sesión del Servidor.
 *
 * Reglas de negocio (GEMINI.md):
 * - Existencias validadas exclusivamente mediante StockService.
 * - Precios y subtotales recalculados en servidor exclusivamente mediante PriceService.
 */
class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(
        private readonly StockService $stockService,
        private readonly PriceService $priceService,
    ) {}

    /**
     * Obtiene el estado hidratado y validado del carrito desde la sesión.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     totalQuantity: int,
     *     subtotal: float,
     *     formattedSubtotal: string
     * }
     */
    public function getCart(): array
    {
        /** @var array<int|string, array{product_id: int, quantity: int}> $stored */
        $stored = Session::get(self::SESSION_KEY, []);

        if (empty($stored)) {
            return [
                'items' => [],
                'totalQuantity' => 0,
                'subtotal' => 0.0,
                'formattedSubtotal' => $this->priceService->format(0.0),
            ];
        }

        $productIds = array_map('intval', array_keys($stored));

        $products = Product::query()
            ->published()
            ->with('photos')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $normalizedSession = [];
        $hydratedItems = [];
        $totalQuantity = 0;
        $subtotal = 0.0;

        foreach ($stored as $rawId => $entry) {
            $productId = (int) $rawId;
            $requestedQty = (int) ($entry['quantity'] ?? 0);

            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product || ! $this->stockService->hasStock($product, 1) || $requestedQty <= 0) {
                continue;
            }

            $validQty = $this->stockService->clampQuantity($product, $requestedQty);
            if ($validQty <= 0) {
                continue;
            }

            $availableStock = $this->stockService->getAvailableStock($product);
            $unitPrice = $this->priceService->getUnitPrice($product);
            $lineSubtotal = $this->priceService->calculateSubtotal($product, $validQty);

            $normalizedSession[$productId] = [
                'product_id' => $productId,
                'quantity' => $validQty,
            ];

            $hydratedItems[] = [
                'product' => $product,
                'product_id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'category' => $product->category,
                'vehicle_label' => "{$product->vehicle_brand} {$product->vehicle_model} ({$product->year_start}–{$product->year_end})",
                'condition' => $product->condition,
                'warranty_days' => $product->warrantyDays(),
                'photo_url' => $product->primary_photo_url,
                'quantity' => $validQty,
                'available_stock' => $availableStock,
                'unit_price' => $unitPrice,
                'formatted_unit_price' => $this->priceService->format($unitPrice),
                'line_subtotal' => $lineSubtotal,
                'formatted_line_subtotal' => $this->priceService->format($lineSubtotal),
            ];

            $totalQuantity += $validQty;
            $subtotal = round($subtotal + $lineSubtotal, 2);
        }

        Session::put(self::SESSION_KEY, $normalizedSession);

        return [
            'items' => $hydratedItems,
            'totalQuantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'formattedSubtotal' => $this->priceService->format($subtotal),
        ];
    }

    /**
     * Agrega un artículo al carrito respetando el límite de existencia en StockService.
     *
     * @return array{ok: bool, quantity: int, clamped: bool, available: int, message: string}
     */
    public function add(int $productId, int $quantity = 1): array
    {
        $product = Product::query()->published()->find($productId);

        if (! $product) {
            return [
                'ok' => false,
                'quantity' => 0,
                'clamped' => false,
                'available' => 0,
                'message' => 'El artículo solicitado no se encuentra disponible.',
            ];
        }

        $available = $this->stockService->getAvailableStock($product);
        if ($available <= 0) {
            return [
                'ok' => false,
                'quantity' => 0,
                'clamped' => false,
                'available' => 0,
                'message' => 'Esta pieza se encuentra agotada temporalmente.',
            ];
        }

        $requestedToAdd = max(1, $quantity);
        $stored = Session::get(self::SESSION_KEY, []);
        $currentInCart = (int) ($stored[$productId]['quantity'] ?? 0);
        $desiredTotal = $currentInCart + $requestedToAdd;

        $clampedQty = $this->stockService->clampQuantity($product, $desiredTotal);
        $wasClamped = $clampedQty < $desiredTotal;

        $stored[$productId] = [
            'product_id' => $productId,
            'quantity' => $clampedQty,
        ];

        Session::put(self::SESSION_KEY, $stored);

        $message = $wasClamped
            ? "Se ajustó la cantidad de «{$product->title}» al máximo disponible en almacén ({$available} pza(s))."
            : "«{$product->title}» se agregó al carrito.";

        return [
            'ok' => true,
            'quantity' => $clampedQty,
            'clamped' => $wasClamped,
            'available' => $available,
            'message' => $message,
        ];
    }

    /**
     * Actualiza la cantidad de un artículo en el carrito validando contra StockService.
     *
     * @return array{ok: bool, quantity: int, clamped: bool, available: int, message: string}
     */
    public function update(int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            $this->remove($productId);

            return [
                'ok' => true,
                'quantity' => 0,
                'clamped' => false,
                'available' => 0,
                'message' => 'La pieza fue retirada del carrito.',
            ];
        }

        $product = Product::query()->published()->find($productId);
        if (! $product) {
            $this->remove($productId);

            return [
                'ok' => false,
                'quantity' => 0,
                'clamped' => false,
                'available' => 0,
                'message' => 'El artículo ya no está disponible y fue retirado del carrito.',
            ];
        }

        $available = $this->stockService->getAvailableStock($product);
        if ($available <= 0) {
            $this->remove($productId);

            return [
                'ok' => false,
                'quantity' => 0,
                'clamped' => false,
                'available' => 0,
                'message' => 'El artículo se quedó sin existencia y fue retirado del carrito.',
            ];
        }

        $clampedQty = $this->stockService->clampQuantity($product, $quantity);
        $wasClamped = $clampedQty < $quantity;

        $stored = Session::get(self::SESSION_KEY, []);
        $stored[$productId] = [
            'product_id' => $productId,
            'quantity' => $clampedQty,
        ];
        Session::put(self::SESSION_KEY, $stored);

        $message = $wasClamped
            ? "Solo contamos con {$available} pieza(s) en existencia de «{$product->title}». Se ajustó tu carrito."
            : 'Cantidad actualizada en el carrito.';

        return [
            'ok' => true,
            'quantity' => $clampedQty,
            'clamped' => $wasClamped,
            'available' => $available,
            'message' => $message,
        ];
    }

    public function remove(int $productId): void
    {
        $stored = Session::get(self::SESSION_KEY, []);
        unset($stored[$productId]);
        Session::put(self::SESSION_KEY, $stored);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
