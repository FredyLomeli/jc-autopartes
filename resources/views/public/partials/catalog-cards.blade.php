@foreach($products as $product)
    @include('public.partials.product-card', [
        'product' => $product,
        'priceService' => $priceService ?? null,
        'stockService' => $stockService ?? null,
    ])
@endforeach
