<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductDimensionsAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductDimensionsAuditController extends Controller
{
    public function __construct(
        private readonly ProductDimensionsAuditService $auditService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'scope' => (string) $request->query('scope', 'suspect'),
            'category' => (string) $request->query('category', ''),
            'q' => (string) $request->query('q', ''),
        ];

        return Inertia::render('Admin/Products/DimensionsAudit', [
            'items' => $this->auditService->getPaginatedAuditItems($filters, 20),
            'stats' => $this->auditService->getSummaryStats(),
            'filters' => $filters,
            'categories' => array_keys(config('catalog.categories', [])),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'length_cm' => ['required', 'numeric', 'min:1', 'max:1000'],
            'width_cm' => ['required', 'numeric', 'min:1', 'max:1000'],
            'height_cm' => ['required', 'numeric', 'min:1', 'max:1000'],
            'dimensions_locked' => ['nullable', 'boolean'],
        ], [
            'weight_kg.min' => 'El peso debe ser mayor a 0.1 kg.',
            'length_cm.min' => 'El largo debe ser de al menos 1 cm.',
            'width_cm.min' => 'El ancho debe ser de al menos 1 cm.',
            'height_cm.min' => 'El alto debe ser de al menos 1 cm.',
        ]);

        $updated = $this->auditService->updateDimensions($product, $validated);

        return redirect()
            ->back()
            ->with('success', "Medidas y peso actualizados para «{$updated->sku}» ({$updated->weight_kg} kg).");
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'scope' => (string) $request->query('scope', 'suspect'),
            'category' => (string) $request->query('category', ''),
            'q' => (string) $request->query('q', ''),
        ];

        return $this->auditService->streamCsvExport($filters);
    }
}
