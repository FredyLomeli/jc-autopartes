<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AdminOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'payment_status', 'order_status', 'delivery_method']);
        $orders = $this->adminOrderService->getPaginatedOrders($filters);
        $stats = $this->adminOrderService->getOrderStats();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'payment_status' => $filters['payment_status'] ?? 'all',
                'order_status' => $filters['order_status'] ?? 'all',
                'delivery_method' => $filters['delivery_method'] ?? 'all',
            ],
            'stats' => $stats,
        ]);
    }

    public function show(Order $order): Response
    {
        $orderDetails = $this->adminOrderService->getOrderDetails($order);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $orderDetails,
            'business' => config('business'),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'order_status' => ['required', 'string', 'in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_PREPARING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->adminOrderService->updateOrderStatus(
            $order,
            $validated['order_status'],
            $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', 'El estado del pedido fue actualizado correctamente.');
    }
}
