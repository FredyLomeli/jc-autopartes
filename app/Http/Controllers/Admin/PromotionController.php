<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    /**
     * Listado conjunto de Campañas de Promoción y Cupones
     */
    public function index(): Response
    {
        $promotions = Promotion::query()
            ->orderBy('order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $coupons = Coupon::query()
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => $promotions,
            'coupons' => $coupons,
        ]);
    }

    /**
     * Guarda una nueva Campaña de Promoción (Directa o Simulada/Anclaje)
     */
    public function storePromotion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'badge_label' => ['required', 'string', 'max:50'],
            'mode' => ['required', 'in:direct,perceived'],
            'visible_discount_percentage' => ['required', 'numeric', 'min:1', 'max:99'],
            'real_discount_percentage' => ['required', 'numeric', 'min:0', 'max:99'],
            'scope_type' => ['required', 'in:all,category,brand,custom'],
            'scope_value' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        Promotion::create([
            'name' => $validated['name'],
            'badge_label' => strtoupper($validated['badge_label']),
            'mode' => $validated['mode'],
            'visible_discount_percentage' => $validated['visible_discount_percentage'],
            'real_discount_percentage' => $validated['mode'] === 'direct' 
                ? $validated['visible_discount_percentage'] 
                : $validated['real_discount_percentage'],
            'scope_type' => $validated['scope_type'],
            'scope_value' => $validated['scope_value'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'order' => (Promotion::max('order') ?? 0) + 1,
        ]);

        Cache::forget('active_promotions_list');

        return back()->with('success', 'Campaña de promoción creada exitosamente.');
    }

    /**
     * Actualiza una Campaña de Promoción existente
     */
    public function updatePromotion(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'badge_label' => ['required', 'string', 'max:50'],
            'mode' => ['required', 'in:direct,perceived'],
            'visible_discount_percentage' => ['required', 'numeric', 'min:1', 'max:99'],
            'real_discount_percentage' => ['required', 'numeric', 'min:0', 'max:99'],
            'scope_type' => ['required', 'in:all,category,brand,custom'],
            'scope_value' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $promotion->update([
            'name' => $validated['name'],
            'badge_label' => strtoupper($validated['badge_label']),
            'mode' => $validated['mode'],
            'visible_discount_percentage' => $validated['visible_discount_percentage'],
            'real_discount_percentage' => $validated['mode'] === 'direct' 
                ? $validated['visible_discount_percentage'] 
                : $validated['real_discount_percentage'],
            'scope_type' => $validated['scope_type'],
            'scope_value' => $validated['scope_value'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Cache::forget('active_promotions_list');

        return back()->with('success', 'Campaña de promoción actualizada exitosamente.');
    }

    /**
     * Elimina una Campaña de Promoción
     */
    public function destroyPromotion(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();
        Cache::forget('active_promotions_list');

        return back()->with('success', 'Campaña de promoción eliminada exitosamente.');
    }

    /**
     * Guarda un nuevo Cupón de Descuento
     */
    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
        ]);

        Coupon::create([
            'code' => strtoupper(trim($validated['code'])),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_public' => $request->boolean('is_public', false),
        ]);

        return back()->with('success', 'Cupón de descuento creado exitosamente.');
    }

    /**
     * Elimina un Cupón
     */
    public function destroyCoupon(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('success', 'Cupón de descuento eliminado exitosamente.');
    }
}
