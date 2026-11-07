<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    /**
     * Listado de banners ordenados
     */
    public function index(): Response
    {
        $banners = HomeBanner::query()
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
        ]);
    }

    /**
     * Guarda un nuevo banner
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // Máximo 5MB
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $imagePath = $request->file('image')->store('banners', 'public');

        // El nuevo banner se asigna automáticamente al final de la lista
        $order = (HomeBanner::max('order') ?? 0) + 1;

        HomeBanner::create([
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $imagePath,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'order' => $order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Banner agregado exitosamente.');
    }

    /**
     * Actualiza un banner existente
     */
    public function update(Request $request, HomeBanner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe en el disco público local
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $banner->image_path = $request->file('image')->store('banners', 'public');
        }

        $banner->title = $validated['title'] ?? null;
        $banner->subtitle = $validated['subtitle'] ?? null;
        $banner->button_text = $validated['button_text'] ?? null;
        $banner->button_url = $validated['button_url'] ?? null;
        $banner->is_active = $request->boolean('is_active', true);
        $banner->save();

        return back()->with('success', 'Banner actualizado exitosamente.');
    }

    /**
     * Reordena un banner hacia arriba o hacia abajo
     */
    public function reorder(Request $request, HomeBanner $banner): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        // Obtener todos los banners ordenados por order y id
        $allBanners = HomeBanner::query()
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $currentIndex = $allBanners->search(fn ($b) => $b->id === $banner->id);

        if ($currentIndex === false) {
            return back();
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $allBanners->count()) {
            $otherBanner = $allBanners[$targetIndex];

            // Intercambiar posiciones en la colección
            $allBanners[$currentIndex] = $otherBanner;
            $allBanners[$targetIndex] = $banner;

            // Renumerar todos secuencialmente (1, 2, 3...) para garantizar que nunca haya duplicados
            foreach ($allBanners->values() as $index => $item) {
                $item->order = $index + 1;
                $item->save();
            }
        }

        return back()->with('success', 'Orden de banner actualizado.');
    }

    /**
     * Elimina un banner
     */
    public function destroy(HomeBanner $banner): RedirectResponse
    {
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('success', 'Banner eliminado exitosamente.');
    }
}
