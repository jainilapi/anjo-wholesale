@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Name:</strong> {{ $product->name }}</div>
                    <div class="col-md-4"><strong>SKU:</strong> {{ $product->sku }}</div>
                    <div class="col-md-4"><strong>Category:</strong> {{ $product->category?->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Status:</strong> {{ $product->status ? 'Active' : 'Inactive' }}</div>
                    <div class="col-md-4"><strong>Stock:</strong> {{ $product->in_stock ? 'In stock' : 'Out of stock' }}</div>
                </div>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div>{!! $product->description !!}</div>
                </div>
                <div class="mb-3">
                    <strong>Images:</strong>
                    <div class="row">
                        @foreach(($product->images ?? []) as $img)
                        <div class="col-md-3 mb-2">
                            <div class="card">
                                <img src="{{ asset('storage/'.$img) }}" class="card-img-top" style="height:140px;object-fit:cover;">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection


