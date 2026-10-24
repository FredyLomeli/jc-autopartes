<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AdminOrderService;
use App\Services\OrderService;
use App\Services\PriceService;
use App\Services\StockService;
use InvalidArgumentException;
use Tests\TestCase;

class AdminOrderServiceTest extends TestCase
{
    private function createSampleOrder(): Order
    {
        $order = new Order([
            'order_number' => 'JC-2026-00088',
            'customer_name' => 'Fernando Castro',
            'customer_email' => 'fernando@ejemplo.com',
            'customer_phone' => '3781234567',
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'shipping_address' => 'Av. Independencia 500',
            'shipping_neighborhood' => 'Lomas Altas',
            'shipping_city' => 'Tepatitlán',
            'shipping_state' => 'Jalisco',
            'shipping_postal_code' => '47600',
            'items_subtotal' => 4500.0,
            'shipping_cost' => 350.0,
            'total' => 4850.0,
            'currency' => 'MXN',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_PREPARING,
            'mercadopago_payment_id' => 'MP-888999',
            'paid_at' => now(),
        ]);
        $order->id = 88;

        $item1 = new OrderItem([
            'product_id' => 201,
            'product_title' => 'Cuerpo de Aceleración Nissan March',
            'product_sku' => 'JC-ACE-001',
            'part_number' => 'NIS-16119-1HC0A',
            'unit_price' => 2500.0,
            'quantity' => 1,
            'subtotal' => 2500.0,
            'warranty_days' => 15,
        ]);
        $item1->id = 1;

        $item2 = new OrderItem([
            'product_id' => 202,
            'product_title' => 'Sensor MAF Nissan March',
            'product_sku' => 'JC-SEN-002',
            'part_number' => 'NIS-22680-7S000',
            'unit_price' => 2000.0,
            'quantity' => 1,
            'subtotal' => 2000.0,
            'warranty_days' => 7,
        ]);
        $item2->id = 2;

        $order->setRelation('items', collect([$item1, $item2]));

        return $order;
    }

    public function test_format_order_list_item_contains_expected_fields(): void
    {
        $order = $this->createSampleOrder();

        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);
        $adminOrderService = new AdminOrderService($orderService, $priceService);

        $formatted = $adminOrderService->formatOrderListItem($order);

        $this->assertEquals(88, $formatted['id']);
        $this->assertEquals('JC-2026-00088', $formatted['order_number']);
        $this->assertEquals('Fernando Castro', $formatted['customer_name']);
        $this->assertEquals(2, $formatted['items_count']);
        $this->assertStringContainsString('Cuerpo de Aceleración Nissan March (+1 más)', $formatted['summary_item']);
        $this->assertEquals('Pagado', $formatted['payment_status_label']);
        $this->assertEquals('En Preparación / Almacén', $formatted['order_status_label']);
        $this->assertStringContainsString('4,850.00', $formatted['formatted_total']);
    }

    public function test_get_order_details_returns_complete_payload(): void
    {
        $order = $this->createSampleOrder();

        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);
        $adminOrderService = new AdminOrderService($orderService, $priceService);

        $details = $adminOrderService->getOrderDetails($order);

        $this->assertEquals('JC-2026-00088', $details['order_number']);
        $this->assertEquals('MP-888999', $details['mercadopago_payment_id']);
        $this->assertCount(2, $details['items']);
        $this->assertEquals('JC-ACE-001', $details['items'][0]['product_sku']);
        $this->assertEquals(15, $details['items'][0]['warranty_days']);
        $this->assertEquals('JC-SEN-002', $details['items'][1]['product_sku']);
        $this->assertEquals('Envío a Domicilio (Paquetería)', $details['delivery_method_label']);
        $this->assertEquals('47600', $details['shipping_postal_code']);
    }

    public function test_update_order_status_rejects_invalid_status(): void
    {
        $order = $this->createSampleOrder();

        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);
        $adminOrderService = new AdminOrderService($orderService, $priceService);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estado de pedido «estado_invalido» no válido.');

        $adminOrderService->updateOrderStatus($order, 'estado_invalido');
    }

    public function test_update_order_status_to_cancelled_delegates_to_order_service(): void
    {
        $order = $this->createSampleOrder();

        $priceService = new PriceService();
        $orderService = $this->createMock(OrderService::class);
        $orderService->expects($this->once())
            ->method('cancelOrder')
            ->with($order, 'Cancelado por solicitud del comprador')
            ->willReturn($order);

        $adminOrderService = new AdminOrderService($orderService, $priceService);

        $result = $adminOrderService->updateOrderStatus($order, Order::STATUS_CANCELLED, 'Cancelado por solicitud del comprador');

        $this->assertSame($order, $result);
    }

    public function test_get_order_stats_returns_proper_keys(): void
    {
        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);
        $adminOrderService = new AdminOrderService($orderService, $priceService);

        $stats = $adminOrderService->getOrderStats();

        $this->assertArrayHasKey('total_orders', $stats);
        $this->assertArrayHasKey('paid_orders', $stats);
        $this->assertArrayHasKey('preparing_orders', $stats);
        $this->assertArrayHasKey('shipped_orders', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('formatted_total_revenue', $stats);
    }
}
