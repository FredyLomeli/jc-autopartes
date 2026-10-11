<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Única fuente de verdad para consultar, calcular, formatear y actualizar precios (`price`).
 * Ningún controlador, vista o componente Vue debe calcular o guardar precios fuera de este servicio.
 */
class PriceService
{
    public function getUnitPrice(Product|int $product): float
    {
        $model = $this->resolveProduct($product);

        return round(max(0.0, (float) $model->price), 2);
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
     * Actualiza el precio unitario de un artículo dentro de una transacción.
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

    private function resolveProduct(Product|int $product): Product
    {
        if ($product instanceof Product) {
            return $product;
        }

        return Product::query()->findOrFail($product);
    }
}
