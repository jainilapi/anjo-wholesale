@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
    .pricing-table th,
    .pricing-table td {
        text-align: center;
        vertical-align: middle;
    }

    .pricing-table .form-control {
        width: 80px;
    }

    .pricing-table td input[type="number"] {
        max-width: 100px;
    }

    .pricing-table td button {
        font-size: 12px;
        padding: 5px 10px;
    }

    .pricing-table .tab-content {
        padding-top: 20px;
    }

    .add-row-btn {
        text-align: center;
        margin-top: 20px;
    }

    .pricing-table {
        margin-top: 20px;
    }

    .actions-btn {
        display: flex;
        justify-content: space-between;
    }

    .actions-btn button {
        font-size: 14px;
    }
</style>
@endpush

@php
$finalVariants = $finalVariantsInfo = [];
foreach ($product->variants as $variant) {
    $temp = $variant?->additionalUnits()?->with('unit')?->get()?->toArray() ?? [];
    $baseUn = $variant?->baseUnit()?->with('unit')?->first()?->toArray() ?? [];

    array_unshift($temp, $baseUn);
    $finalVariants[] = $temp;
    $finalVariantsInfo[] = $variant;
}
@endphp

@section('product-content')
<div class="row">
    <div class="col-12">
        <label for="variant"> Select Variant </label>
        <select name="variant" id="variant" class="form-control">
            @foreach($product->variants as $variant)
            <option value="{{ $variant->id }}" @if($loop->first) selected @endif> {{ $variant->name }} - {{ $variant->sku }} </option>
            @endforeach
        </select>

        <div id="pricing-matrix" class="mt-4">
            @foreach ($finalVariants as $variant)
                <div class="mt-4 main-visibility-container @if(!$loop->first) d-none @endif" data-current-unit-id="{{ $finalVariantsInfo[$loop->iteration - 1]['id'] }}">
                    <h3 class="titleOfCurrentTabUnit">{{ $variant[0]['unit']['title'] ?? 'N/A' }} Pricing Tiers</h3>
                    <p>Set quantity-based pricing for individual <span class="titleOfCurrentTabUnit"> {{ $variant[0]['unit']['title'] ?? 'N/A' }} </span> </p>

                    <ul class="nav nav-tabs" role="tablist">
                        @if(isset($variant[0]['id']))
                            @foreach ($variant as $row)
                                @php
                                $tabId = md5($row['id'] . '-' . $row['variant_id']);
                                @endphp
                            <li class="nav-item" role="presentation">
                                <a class="nav-link @if($loop->first) active @endif" id="{{ $tabId }}-tab" data-current-unit="{{ $row['unit']['title'] ?? 'N/A' }}" data-bs-toggle="tab" href="#{{ $tabId }}" role="tab" aria-controls="{{ $tabId }}" aria-selected="true">{{ $row['unit']['title'] ?? 'N/A' }} @if($loop->first) (Base Unit) @endif </a>
                            </li>
                            @endforeach
                        @endif
                    </ul>

                    <div class="tab-content">
                        @if(isset($variant[0]['id']))
                            @foreach ($variant as $row)
                                @php
                                $tabId = md5($row['id'] . '-' . $row['variant_id']);
                                @endphp
                            <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                                <table class="table table-bordered pricing-table-instance" data-variant-id="{{ $row['variant_id'] }}" data-unit-row-id="{{ $row['id'] }}">
                                    <thead>
                                        <tr>
                                            <th>Min Quantity</th>
                                            <th>Max Quantity</th>
                                            <th>Price per Unit</th>
                                            <th>Discount %</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(\App\Models\ProductTierPricing::where('product_variant_id', $row['variant_id'])->where('product_additional_unit_id', $row['id'])->get() as $tier)
                                        <tr>
                                            <td><input type="number" class="form-control" name="min_quantity[]" value="{{ $tier->min_qty }}" min="1" step="1"></td>
                                            <td><input type="number" class="form-control" name="max_quantity[]" value="{{ $tier->max_qty }}"></td>
                                            <td><input type="number" class="form-control" name="price_per_unit[]" value="{{ $tier->price_per_unit }}" step="0.01"></td>
                                            <td><input type="number" class="form-control" name="discount[]" value="{{ $tier->discount_amount }}" step="0.01"></td>
                                            <td class="actions-btn">
                                                <button type="button" class="btn btn-danger remove-row">Delete</button>
                                            </td>
                                        </tr>
                                        @empty
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="add-row-btn">
                                    <button type="button" class="btn btn-primary addANewLevel">+ Add New Pricing Tier</button>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('product-js')
<script>
    let priceMatrix = {};

    $(document).ready(function() {

        $('#variant').select2({
            placeholder: 'Select Variant',
            allowClear: true,
            width: '100%'
        }).on('change', function () {
            let variantId = $('option:selected', this).val();
            
            $('.main-visibility-container').addClass('d-none');
            
            $('.main-visibility-container').each(function() {
                if ($(this).data('current-unit-id') == variantId) {
                    $(this).removeClass('d-none');
                }
            });
        });

    function addRow(element) {
      var newRow = `
        <tr>
          <td><input type="number" class="form-control" name="min_quantity[]" value="1" min="1" step="1"></td>
          <td><input type="number" class="form-control" name="max_quantity[]" value="5"></td>
          <td><input type="number" class="form-control" name="price_per_unit[]" value="0" step="0.01"></td>
          <td><input type="number" class="form-control" name="discount[]" value="0" step="0.01"></td>
          <td class="actions-btn">
            <button type="button" class="btn btn-danger remove-row">Delete</button>
          </td>
        </tr>
      `;
      $(element).parent().prev().find('tbody').append(newRow);
    }

    $(document).on('click', '.addANewLevel', function() {
      addRow(this);
    });
    
    $(document).on('click', '.remove-row', function() {
        let that = this;
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $(that).closest('tr').remove();
            }
        });
    });

    $(document).on('shown.bs.tab', '.nav-tabs a', function (e) {
        var targetTab = $(e.target).data('current-unit');
        $(e.target).parent().parent().parent().find('.titleOfCurrentTabUnit').text(`${targetTab} Pricing Tiers`);
    });

    $('#productStep1Form').on('submit', function(e) {
        let items = [];
        let errors = [];
        $('.pricing-table-instance').each(function() {
            const variantId = parseInt($(this).data('variant-id')) || null;
            const unitRowId = parseInt($(this).data('unit-row-id')) || null;
            $(this).find('tbody tr').each(function(index) {
                const minQty = parseFloat($(this).find('input[name="min_quantity[]"]').val());
                const maxQtyRaw = $(this).find('input[name="max_quantity[]"]').val();
                const maxQty = maxQtyRaw === '' ? null : parseFloat(maxQtyRaw);
                const price = parseFloat($(this).find('input[name="price_per_unit[]"]').val());
                const discount = parseFloat($(this).find('input[name="discount[]"]').val());
                if (!isNaN(minQty) || !isNaN(maxQty) || !isNaN(price) || !isNaN(discount)) {
                    items.push({
                        product_variant_id: variantId,
                        product_additional_unit_id: unitRowId,
                        min_qty: isNaN(minQty) ? null : minQty,
                        max_qty: isNaN(maxQty) ? null : maxQty,
                        price_per_unit: isNaN(price) ? null : price,
                        discount_type: 1,
                        discount_amount: isNaN(discount) ? 0 : discount
                    });
                }
            });
        });
        $('#tier_pricings_input').remove();
        $('<input>').attr({type:'hidden', name:'tier_pricings', id:'tier_pricings_input'})
            .val(JSON.stringify(items)).appendTo('#productStep1Form');
    });

    });
</script>
@endpush