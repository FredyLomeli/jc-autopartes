<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Throwable;

class AdminDashboardService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PriceService $priceService,
    ) {}

    /**
     * Obtiene las métricas del panel administrativo combinando el estado real del catálogo
     * con las tarjetas operativas claramente etiquetadas como demostrativas (Fase A0).
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $products = Product::query()->with('primaryPhoto')->orderByDesc('id')->get();

        $totalProducts = $products->count();
        $publishedProducts = $products->where('status', 'published')->count();
        $draftProducts = $products->where('status', 'draft')->count();

        $totalStockUnits = 0;
        $inventoryValue = 0.0;

        foreach ($products as $product) {
            $stock = $this->stockService->getAvailableStock($product);
            $price = $this->priceService->getUnitPrice($product);
            $totalStockUnits += $stock;
            $inventoryValue += ($stock * $price);
        }

        $recentProducts = $products->take(5)->map(function (Product $product) {
            $unitPrice = $this->priceService->getUnitPrice($product);
            $stock = $this->stockService->getAvailableStock($product);

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'title' => $product->title,
                'category' => $product->category,
                'vehicle' => "{$product->vehicle_brand} {$product->vehicle_model} ({$product->year_start}–{$product->year_end})",
                'status' => $product->status,
                'stock' => $stock,
                'formatted_price' => $this->priceService->format($unitPrice),
                'photo_url' => $product->primary_photo_url,
            ];
        })->values()->all();

        $paidOrdersCount = 0;
        $paidRevenue = 0.0;
        $preparingOrdersCount = 0;

        try {
            $paidQuery = Order::query()->where('payment_status', Order::PAYMENT_PAID);
            $paidOrdersCount = (clone $paidQuery)->count();
            $paidRevenue = (float) (clone $paidQuery)->sum('total');
            $preparingOrdersCount = Order::query()->where('order_status', Order::STATUS_PREPARING)->count();
        } catch (Throwable) {
            // Entorno offline / testing
        }

        return [
            'catalogStats' => [
                'total_products' => $totalProducts,
                'published_products' => $publishedProducts,
                'draft_products' => $draftProducts,
                'total_stock_units' => $totalStockUnits,
                'formatted_inventory_value' => $this->priceService->format($inventoryValue),
            ],
            'orderStats' => [
                'paid_orders' => $paidOrdersCount,
                'total_revenue' => $paidRevenue,
                'formatted_revenue' => $this->priceService->format($paidRevenue),
                'preparing_orders' => $preparingOrdersCount,
            ],
            'demoCards' => [
                [
                    'label' => 'Ventas Web Pagadas',
                    'value' => $this->priceService->format($paidRevenue),
                    'subtext' => "{$paidOrdersCount} órdenes acreditadas en línea",
                    'badge' => 'Fase C Real',
                ],
                [
                    'label' => 'Pedidos por Despachar',
                    'value' => "{$preparingOrdersCount} pedidos",
                    'subtext' => 'En preparación para paquetería o entrega',
                    'badge' => 'Almacén',
                ],
                [
                    'label' => 'Consultas de WhatsApp Hoy',
                    'value' => '23 prospectos',
                    'subtext' => 'Atención directa de compatibilidad y cotizaciones',
                    'badge' => 'Atención',
                ],
                [
                    'label' => 'Sincronización Mercado Libre',
                    'value' => 'Activa',
                    'subtext' => 'Catálogo vinculado con inventario central',
                    'badge' => 'Canal',
                ],
            ],
            'recentProducts' => $recentProducts,
        ];
    }
}
