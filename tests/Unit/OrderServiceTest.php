<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\PriceService;
use App\Services\StockService;
use InvalidArgumentException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    public function test_cannot_create_order_with_empty_cart(): void
    {
        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No es posible crear un pedido con el carrito vacío.');

        $orderService->createOrderFromCart([
            'customer_name' => 'Juan Pérez',
            'customer_email' => 'juan@ejemplo.com',
        ], ['items' => []]);
    }

    public function test_throws_insufficient_stock_exception_if_item_out_of_stock(): void
    {
        $stockService = $this->createMock(StockService::class);
        $stockService->expects($this->once())
            ->method('hasStock')
            ->with(101, 1)
            ->willReturn(false);

        $stockService->expects($this->once())
            ->method('getAvailableStock')
            ->with(101)
            ->willReturn(0);

        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Existencia insuficiente para la refacción «Faro LED Nissan Kicks»');

        $orderService->createOrderFromCart([
            'customer_name' => 'Juan Pérez',
            'customer_email' => 'juan@ejemplo.com',
        ], [
            'items' => [
                [
                    'product_id' => 101,
                    'title' => 'Faro LED Nissan Kicks',
                    'sku' => 'JC-FAR-001',
                    'quantity' => 1,
                    'unit_price' => 3800.0,
                ],
            ],
            'subtotal' => 3800.0,
        ]);
    }

    public function test_generate_order_number_format(): void
    {
        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);

        $orderNumber = $orderService->generateOrderNumber();
        $year = date('Y');

        $this->assertMatchesRegularExpression("/^JC-{$year}-\d{5}$/", $orderNumber);
    }

    public function test_order_model_helper_methods_and_labels(): void
    {
        $order = new Order([
            'order_number' => 'JC-2026-00001',
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
        ]);

        $this->assertTrue($order->isPendingPayment());
        $this->assertFalse($order->isPaid());
        $this->assertTrue($order->isShipping());
        $this->assertFalse($order->isPickup());
        $this->assertSame('Pendiente de Pago', $order->paymentStatusLabel());
        $this->assertSame('Pendiente', $order->orderStatusLabel());
        $this->assertSame('Envío a Domicilio (Paquetería)', $order->deliveryMethodLabel());

        $order->payment_status = Order::PAYMENT_PAID;
        $order->order_status = Order::STATUS_PREPARING;
        $order->delivery_method = Order::DELIVERY_PICKUP;

        $this->assertTrue($order->isPaid());
        $this->assertTrue($order->isPickup());
        $this->assertSame('Pagado', $order->paymentStatusLabel());
        $this->assertSame('En Preparación / Almacén', $order->orderStatusLabel());
        $this->assertSame('Recolección en Mostrador (Tepatitlán)', $order->deliveryMethodLabel());
    }

    public function test_order_item_model_instantiation_and_casts(): void
    {
        $item = new OrderItem([
            'product_title' => 'Cremallera Electroasistida Ford Figo',
            'product_sku' => 'JC-DIR-001',
            'part_number' => 'E3B1-3A500-AF',
            'unit_price' => 4200.50,
            'quantity' => 2,
            'subtotal' => 8401.00,
            'warranty_days' => 15,
        ]);

        $this->assertSame(4200.50, $item->unit_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(8401.00, $item->subtotal);
        $this->assertSame(15, $item->warranty_days);
        $this->assertSame('E3B1-3A500-AF', $item->part_number);
    }

    public function test_insufficient_stock_exception_properties(): void
    {
        $ex = new InsufficientStockException('Sin stock', productId: 50, requested: 2, available: 1);
        $this->assertSame(50, $ex->productId);
        $this->assertSame(2, $ex->requested);
        $this->assertSame(1, $ex->available);
        $this->assertSame('Sin stock', $ex->getMessage());
    }
}
