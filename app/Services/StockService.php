<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Única fuente de verdad para consultar, validar y actualizar existencias (`stock`).
 * Ningún controlador, vista o componente Vue debe calcular o escribir existencias fuera de este servicio.
 */
class StockService
{
    public function getAvailableStock(Product|int $product): int
    {
        $model = $this->resolveProduct($product);

        return max(0, (int) $model->stock);
    }

    public function hasStock(Product|int $product, int $requestedQuantity = 1): bool
    {
        if ($requestedQuantity <= 0) {
            return false;
        }

        return $this->getAvailableStock($product) >= $requestedQuantity;
    }

    /**
     * Ajusta la cantidad solicitada al rango válido [0, stock disponible].
     */
    public function clampQuantity(Product|int $product, int $requestedQuantity): int
    {
        if ($requestedQuantity <= 0) {
            return 0;
        }

        $available = $this->getAvailableStock($product);

        return min($requestedQuantity, $available);
    }

    /**
     * Actualiza la existencia de un artículo dentro de una transacción con bloqueo de fila.
     */
    public function setStock(Product $product, int $stock): Product
    {
        if ($stock < 0) {
            throw new InvalidArgumentException('La existencia no puede ser negativa.');
        }

        return DB::transaction(function () use ($product, $stock) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked->stock = $stock;
            $locked->save();

            $product->stock = $locked->stock;

            return $locked;
        });
    }

    /**
     * Descuenta existencias de un artículo de forma atómica con bloqueo pesimista contra condiciones de carrera.
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function decrementStock(Product|int $product, int $quantity = 1): Product
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a descontar debe ser mayor a cero.');
        }

        $productId = $product instanceof Product ? $product->id : (int) $product;

        return DB::transaction(function () use ($productId, $quantity, $product) {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($productId);

            if ($locked->stock < $quantity) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Existencia insuficiente para la refacción «{$locked->title}» (SKU: {$locked->sku}). Solicitado: {$quantity}, disponible: {$locked->stock}.",
                    productId: $locked->id,
                    requested: $quantity,
                    available: $locked->stock
                );
            }

            $locked->stock -= $quantity;
            $locked->save();

            if ($product instanceof Product) {
                $product->stock = $locked->stock;
            }

            return $locked;
        });
    }

    /**
     * Incrementa existencias de un artículo (ej. cancelación de orden o devolución) con bloqueo pesimista.
     */
    public function incrementStock(Product|int $product, int $quantity = 1): Product
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a reponer debe ser mayor a cero.');
        }

        $productId = $product instanceof Product ? $product->id : (int) $product;

        return DB::transaction(function () use ($productId, $quantity, $product) {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($productId);
            $locked->stock += $quantity;
            $locked->save();

            if ($product instanceof Product) {
                $product->stock = $locked->stock;
            }

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
