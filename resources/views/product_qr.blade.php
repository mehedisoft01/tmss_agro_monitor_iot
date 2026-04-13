@extends('web.layout.master')
@section('content')
    @php
        $images = json_decode($product->image ?? '[]', true);
        $documents = json_decode($product->custom_document_file ?? '[]', true);

        $productSlug = request()->query('product');
    @endphp

    <div class="main-body">
        <div class="container-fluid py-3">

            @if(!$product)
                <div class="text-center text-danger py-5">
                    <h5 class="fw-bold">Product not found</h5>
                </div>
            @else
                <div class="mb-4">
                    <h5 class="mb-1"><strong>Product Name:</strong> {{ $product->product_name }}</h5>
                    <span>Product Code: <strong>{{ $product->product_code }}</strong></span>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4 col-md-5 col-sm-12">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="fw-semibold mb-3">Product Images</h6>
                            @if(count($images))
                                <div class="d-flex flex-wrap gap-2 justify-content-start">
                                    @foreach($images as $img)
                                        <img src="{{ storageImage($img) }}" class="rounded border" style="width: 90px; height: 90px; object-fit: cover;">
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small mb-0">No images available</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-8 col-md-7 col-sm-12">
                        <div class="border rounded-3 h-100">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <tbody>
                                    <tr>
                                        <th class="w-25">Brand</th>
                                        <td>{{ $product->brand->brand_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>{{ $product->description }}</td>
                                    </tr>
                                    <tr>
                                        <th>Model</th>
                                        <td>{{ $product->model }}</td>
                                    </tr>
                                    <tr>
                                        <th>Size</th>
                                        <td>{{ $product->size }}</td>
                                    </tr>
                                    <tr>
                                        <th>Net Weight</th>
                                        <td>{{ $product->net_weight }}</td>
                                    </tr>
                                    <tr>
                                        <th>MRP (Customer Price)</th>
                                        <td class="fw-semibold text-success">
                                            {{ $stock->selling_price ?? $product->selling_price }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>DP (Dealer Price)</th>
                                        <td class="fw-semibold text-primary">
                                            {{ $stock->dealer_price ?? $product->dealer_price }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Stock</th>
                                        <td>
                                            <span class="fw-semibold {{ $product->stock_quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $product->stock_quantity ?? $product->stock_quantity}}
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
