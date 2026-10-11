<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Product;
use App\Services\AdminProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly AdminProductService $adminProductService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'q' => (string) $request->query('q', ''),
            'category' => (string) $request->query('category', ''),
            'status' => (string) $request->query('status', ''),
        ];

        return Inertia::render('Admin/Products/Index', [
            'products' => $this->adminProductService->getPaginatedProducts($filters, 12),
            'filters' => $filters,
            'options' => $this->adminProductService->getFilterAndFormOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'create',
            'product' => null,
            'options' => $this->adminProductService->getFilterAndFormOptions(),
        ]);
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $product = $this->adminProductService->createProduct(
            $request->validated(),
            $request->file('photos', [])
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Artículo «{$product->title}» (SKU: {$product->sku}) creado correctamente.");
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'edit',
            'product' => $this->adminProductService->formatProductForForm($product),
            'options' => $this->adminProductService->getFilterAndFormOptions(),
        ]);
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $updated = $this->adminProductService->updateProduct(
            $product,
            $request->validated(),
            $request->file('photos', [])
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Artículo «{$updated->title}» (SKU: {$updated->sku}) actualizado correctamente.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $title = $product->title;
        $sku = $product->sku;

        $this->adminProductService->deleteProduct($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Artículo «{$title}» (SKU: {$sku}) eliminado del catálogo.");
    }
}
