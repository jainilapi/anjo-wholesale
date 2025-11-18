@extends('layouts.app')

@section('title', 'Create Bundled Product')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create Bundled Product</h4>
            </div>
        </div>
    </div>

    <form action="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt($step), 'id' => encrypt($product->id)]) }}" method="POST" enctype="multipart/form-data" id="bundleForm">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">General Information</h5>
                        <div class="mb-3">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g. Summer Sale Pack" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="sku" value="{{ old('sku') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Barcode Type</label>
                                <select class="form-control form-select" name="barcode_type">
                                    <option value="C128">Code 128</option>
                                    <option value="C39">Code 39</option>
                                    <option value="UPCA">UPC-A</option>
                                    <option value="EAN13">EAN-13</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Bundle Items</h5>
                        <p class="text-muted">Search and add products to this bundle.</p>
                        
                        <div class="mb-4 position-relative">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                <input type="text" id="product-search" class="form-control" placeholder="Type product name or SKU to search...">
                            </div>
                            <div id="search-results" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="bundle-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th width="150">Quantity</th>
                                        <th width="150">Price (Override)</th>
                                        <th width="100">Total</th>
                                        <th width="50"><i class="fa fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Dynamic Rows will be appended here --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Estimated Cost:</td>
                                        <td colspan="2" id="total-cost-display">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @error('bundle_items')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Pricing & Inventory</h5>
                        <div class="mb-3">
                            <label class="form-label">Bundle Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" name="price" value="{{ old('price') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cost Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" name="cost" value="{{ old('cost') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" step="0.01" class="form-control" name="tax" value="{{ old('tax') }}">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Organization</h5>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control select2" name="category_id">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <select class="form-control select2" name="brand_id">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <select class="form-control select2" name="unit_id">
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Product Image</h5>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Create Bundle</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Simple client-side search using the passed simple_products data
    const allProducts = @json($simple_products);
    
    $('#product-search').on('keyup', function() {
        let query = $(this).val().toLowerCase();
        let results = [];
        
        if(query.length > 1) {
             results = allProducts.filter(p => 
                p.name.toLowerCase().includes(query) || 
                (p.sku && p.sku.toLowerCase().includes(query))
            );
        }

        let html = '';
        if(results.length > 0) {
            results.forEach(p => {
                html += `<a href="javascript:void(0)" class="list-group-item list-group-item-action add-product" 
                            data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">
                            ${p.name} (${p.sku}) - $${p.price}
                         </a>`;
            });
            $('#search-results').html(html).show();
        } else {
            $('#search-results').hide();
        }
    });

    // Add Product to Table
    $(document).on('click', '.add-product', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let price = $(this).data('price');
        
        // Check if exists
        if($(`input[name="bundle_items[${id}][product_id]"]`).length > 0) {
            alert('Product already in bundle');
            $('#search-results').hide();
            $('#product-search').val('');
            return;
        }

        let row = `
            <tr data-id="${id}">
                <td>
                    ${name}
                    <input type="hidden" name="bundle_items[${id}][product_id]" value="${id}">
                </td>
                <td>
                    <input type="number" class="form-control qty-input" name="bundle_items[${id}][quantity]" value="1" min="1">
                </td>
                <td>
                    <input type="number" class="form-control price-input" name="bundle_items[${id}][price]" value="${price}" step="0.01">
                </td>
                <td class="row-total">${price}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `;
        
        $('#bundle-table tbody').append(row);
        $('#search-results').hide();
        $('#product-search').val('');
        calculateTotal();
    });

    // Remove Row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // Update Totals on Change
    $(document).on('change keyup', '.qty-input, .price-input', function() {
        let row = $(this).closest('tr');
        let qty = parseFloat(row.find('.qty-input').val()) || 0;
        let price = parseFloat(row.find('.price-input').val()) || 0;
        row.find('.row-total').text((qty * price).toFixed(2));
        calculateTotal();
    });

    function calculateTotal() {
        let total = 0;
        $('.row-total').each(function() {
            total += parseFloat($(this).text()) || 0;
        });
        $('#total-cost-display').text(total.toFixed(2));
    }

    // Hide search when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#product-search, #search-results').length) {
            $('#search-results').hide();
        }
    });
</script>
@endpush
@endsection