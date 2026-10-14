<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $productId = $product?->id;

        return [
            'sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'mercadolibre_id' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('products', 'mercadolibre_id')->ignore($productId),
            ],
            'mercadolibre_permalink' => ['nullable', 'string', 'max:500'],
            'part_number' => ['nullable', 'string', 'max:64'],
            'oem_number' => ['nullable', 'string', 'max:64'],
            'side' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'category' => ['required', 'string', 'max:80'],
            'vehicle_brand' => ['required', 'string', 'max:80'],
            'vehicle_model' => ['required', 'string', 'max:80'],
            'year_start' => ['required', 'integer', 'min:1980', 'max:2035'],
            'year_end' => ['required', 'integer', 'gte:year_start', 'max:2035'],
            'condition' => ['required', 'string', Rule::in(['A', 'B', 'C', 'used'])],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:99999'],
            'description' => ['nullable', 'string', 'max:10000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'width_cm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'length_cm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'is_featured' => ['nullable', 'boolean'],
            'photo_urls' => ['nullable', 'array', 'max:10'],
            'photo_urls.*' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['file', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU / Código interno',
            'title' => 'título de la refacción',
            'category' => 'categoría',
            'vehicle_brand' => 'marca del vehículo',
            'vehicle_model' => 'modelo del vehículo',
            'year_start' => 'año inicial',
            'year_end' => 'año final',
            'condition' => 'condición',
            'price' => 'precio unitario',
            'stock' => 'existencia disponible',
            'status' => 'estado de publicación',
        ];
    }
}
