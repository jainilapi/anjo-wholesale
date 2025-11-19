@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Review Product</h5>
                        <p class="text-muted mt-1">
                            Review all the information for your product. You can go back and edit any step.
                            When finished, publish the product or save it as a draft.
                        </p>
                    </div>
                    <div class="card-body">
                        @php
                            $editLink = function ($stepNumber) use ($product, $type) {
                                return route('product-management', ['type' => encrypt($type), 'step' => encrypt($stepNumber), 'id' => encrypt($product->id)]);
                            };
                        @endphp

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 1: Basics</h6>
                                <a href="{{ $editLink(1) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Primary Image:</strong>
                                        @if ($reviewData['primaryImage'])
                                            <img src="{{ asset('storage/' . $reviewData['primaryImage']->file) }}"
                                                alt="Primary" class="img-fluid rounded mt-2" style="max-height: 150px;">
                                        @else
                                            <p class="text-muted">None</p>
                                        @endif
                                    </div>
                                    <div class="col-md-9">
                                        <p class="mb-1"><strong>Name:</strong> {{ $product->name }}</p>
                                        <p class="mb-1"><strong>SKU:</strong> {{ $product->sku }}</p>
                                        <p class="mb-1"><strong>Brand:</strong> {{ $reviewData['brand'] }}</p>
                                        <p class="mb-1"><strong>Status:</strong>
                                            {!! $product->status
                                                ? '<span class="badge bg-success">Active</span>'
                                                : '<span class="badge bg-danger">Inactive</span>' !!}
                                        </p>
                                        <p class="mb-1"><strong>Tags:</strong>
                                            @forelse ($product->tags as $tag)
                                                <span class="badge bg-secondary">{{ $tag }}</span>
                                            @empty
                                                <span class="text-muted">No tags</span>
                                            @endforelse
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 2: Pack Sizes</h6>
                                <a href="{{ $editLink(2) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                @foreach ($reviewData['variants'] as $variant)
                                    <div class="mb-2">
                                        <strong>Variant: {{ $variant->name }}</strong>
                                        <p class="mb-0 ps-3">
                                            Base Unit:
                                            {{ $reviewData['baseUnits'][$variant->id]->unit->title ?? 'N/A' }}
                                        </p>
                                        <p class="mb-0 ps-3">
                                            Additional Units:
                                            {{ count($reviewData['unitHierarchy'][$variant->id] ?? []) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 3: Pricing</h6>
                                <a href="{{ $editLink(3) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                @forelse ($reviewData['tierPricings'] as $variantName => $pricings)
                                    <strong>Variant: {{ $variantName }}</strong>
                                    <p class="ps-3">{{ $pricings->count() }} pricing rule(s) defined.</p>
                                @empty
                                    <p class="text-muted">No tier pricing rules defined for any variant.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 4: Inventory</h6>
                                <a href="{{ $editLink(4) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Track Inventory:</strong>
                                    {{ $reviewData['inventorySettings']['track_inventory'] ? 'Yes' : 'No' }}
                                </p>
                                <p class="mb-1"><strong>Allow Backorders:</strong>
                                    {{ $reviewData['inventorySettings']['allow_backorder'] ? 'Yes' : 'No' }}</p>
                                <p><strong>Total Stock Locations:</strong>
                                    {{ $reviewData['inventoryLocations']->pluck('warehouses')->flatten(1)->unique('id')->count() }}
                                </p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 5: Suppliers</h6>
                                <a href="{{ $editLink(5) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Total Assigned Suppliers:</strong>
                                    {{ $reviewData['suppliers']->pluck('suppliers')->flatten(1)->unique('id')->count() }}
                                </p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 6: Categories & SEO</h6>
                                <a href="{{ $editLink(6) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Primary Category:</strong>
                                    {{ $reviewData['primaryCategory'] }}</p>
                                <p class="mb-1"><strong>Additional Categories:</strong>
                                    {{ $reviewData['additionalCategories']->count() }}</p>
                                <p class="mb-1"><strong>SEO Title:</strong> {{ $reviewData['seo']['title'] ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Step 7: Substitutes</h6>
                                <a href="{{ $editLink(7) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </div>
                            <div class="card-body">
                                @php
                                    $variantsWithSubstitutes = $reviewData['substitutes']->filter(function ($v) {
                                        return $v['substitutes']->isNotEmpty();
                                    });
                                @endphp
                                <p>{{ $variantsWithSubstitutes->count() }} substitutes defined.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection