<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Única fuente de verdad para consultar, calcular, formatear y actualizar precios (`price`),
 * promociones de campaña (directas y simuladas con anclaje) y cupones de descuento.
 */
class PriceService
{
    /**
     * Devuelve el precio base real del artículo (sin promociones temporales).
     */
    public function getBasePrice(Product|int $product): float
    {
        $model = $this->resolveProduct($product);
        return round(max(0.0, (float) $model->price), 2);
    }

    /**
     * Devuelve el precio unitario a cobrar (aplicando campaña de promoción si califica).
     */
    public function getUnitPrice(Product|int $product): float
    {
        $model = $this->resolveProduct($product);
        $basePrice = $this->getBasePrice($model);

        $promo = $this->getActivePromotionForProduct($model);

        if (! $promo) {
            return $basePrice;
        }

        // Descuento real aplicable sobre el precio base
        $realPercent = max(0.0, min(100.0, (float) $promo->real_discount_percentage));
        $discountAmount = $basePrice * ($realPercent / 100);

        return round(max(0.0, $basePrice - $discountAmount), 2);
    }

    /**
     * Devuelve el detalle promocional del producto (para mostrar en tarjetas y ficha).
     * Incluye precio tachado (anclaje), precio final a pagar, porcentaje visible y badge.
     *
     * @return array{
     *     has_promotion: bool,
     *     badge_label: string|null,
     *     visible_discount_percentage: float,
     *     original_strike_price: float,
     *     formatted_strike_price: string|null,
     *     final_price: float,
     *     formatted_final_price: string
     * }
     */
    public function getPromotionDetails(Product|int $product): array
    {
        $model = $this->resolveProduct($product);
        $basePrice = $this->getBasePrice($model);
        $finalPrice = $this->getUnitPrice($model);

        $promo = $this->getActivePromotionForProduct($model);

        if (! $promo) {
            return [
                'has_promotion' => false,
                'badge_label' => null,
                'visible_discount_percentage' => 0.0,
                'original_strike_price' => $basePrice,
                'formatted_strike_price' => null,
                'final_price' => $finalPrice,
                'formatted_final_price' => $this->format($finalPrice),
            ];
        }

        $visiblePercent = max(0.0, min(99.0, (float) $promo->visible_discount_percentage));

        // El precio tachado es siempre el precio base real del producto en base de datos
        $strikePrice = $basePrice;
        $savings = max(0.0, $strikePrice - $finalPrice);

        return [
            'has_promotion' => true,
            'badge_label' => $promo->badge_label ?: "-{$visiblePercent}% OFF",
            'visible_discount_percentage' => $visiblePercent,
            'original_strike_price' => $strikePrice,
            'formatted_strike_price' => $this->format($strikePrice),
            'final_price' => $finalPrice,
            'formatted_final_price' => $this->format($finalPrice),
            'savings_amount' => $savings,
            'formatted_savings' => $this->format($savings),
        ];
    }

    /**
     * Busca la promoción activa prioritaria aplicable a un producto.
     */
    public function getActivePromotionForProduct(Product $product): ?Promotion
    {
        // Se pueden cachear las promociones activas por 5 minutos
        $promotions = Cache::remember('active_promotions_list', 300, function () {
            return Promotion::query()
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'desc')
                ->get();
        });

        foreach ($promotions as $promo) {
            if ($promo->appliesToProduct($product)) {
                return $promo;
            }
        }

        return null;
    }

    public function calculateSubtotal(Product|int $product, int $quantity): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        return round($this->getUnitPrice($product) * $quantity, 2);
    }

    public function format(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',') . ' MXN';
    }

    /**
     * Actualiza el precio base de un artículo dentro de una transacción.
     */
    public function setPrice(Product $product, float $price): Product
    {
        if ($price < 0) {
            throw new InvalidArgumentException('El precio no puede ser negativo.');
        }

        $normalized = round($price, 2);

        return DB::transaction(function () use ($product, $normalized) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked->price = $normalized;
            $locked->save();

            $product->price = $locked->price;

            return $locked;
        });
    }

    /**
     * Valida un código de cupón y calcula su descuento sobre un subtotal dado.
     *
     * @return array{valid: bool, discount: float, message: string, coupon: Coupon|null}
     */
    public function applyCoupon(string $code, float $subtotal): array
    {
        $cleanCode = strtoupper(trim($code));

        if (empty($cleanCode)) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Código de cupón vacío.', 'coupon' => null];
        }

        $coupon = Coupon::query()
            ->where('code', $cleanCode)
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'El cupón no existe o ha expirado.', 'coupon' => null];
        }

        if (! $coupon->isValidForAmount($subtotal)) {
            $msg = 'El cupón no es válido para este pedido.';
            if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
                $msg = 'El monto mínimo de compra para este cupón es de ' . $this->format($coupon->min_order_amount);
            }
            return ['valid' => false, 'discount' => 0.0, 'message' => $msg, 'coupon' => null];
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'valid' => true,
            'discount' => $discount,
            'message' => 'Cupón aplicado con éxito.',
            'coupon' => $coupon,
        ];
    }

    private function resolveProduct(Product|int $product): Product
    {
        if ($product instanceof Product) {
            return $product;
        }

        return Product::query()->findOrFail($product);
    }
}
