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

@section('product-content')
    <div class="row">
        <div class="col-12">
            <div id="pricing-matrix" class="mt-4">
                <h3 class="titleOfCurrentTabUnit">{{ $baseUnit?->unit?->title ?? 'N/A' }} Pricing Tiers</h3>
                <p>Set quantity-based pricing for individual <span
                        class="titleOfCurrentTabUnit">{{ $baseUnit?->unit?->title ?? 'N/A' }}</span></p>

                <ul class="nav nav-tabs" role="tablist">
                    @if($baseUnit)
                        @php
                            $baseTabId = md5('base-' . $baseUnit->id);
                        @endphp
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="{{ $baseTabId }}-tab"
                                data-current-unit="{{ $baseUnit->unit->title ?? 'N/A' }}" data-bs-toggle="tab"
                                href="#{{ $baseTabId }}" role="tab" aria-controls="{{ $baseTabId }}"
                                aria-selected="true">{{ $baseUnit->unit->title ?? 'N/A' }} (Base Unit)</a>
                        </li>
                    @endif
                    @foreach($additionalUnits as $row)
                        @php
                            $tabId = md5('add-' . $row->id);
                        @endphp
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="{{ $tabId }}-tab" data-current-unit="{{ $row->unit->title ?? 'N/A' }}"
                                data-bs-toggle="tab" href="#{{ $tabId }}" role="tab" aria-controls="{{ $tabId }}"
                                aria-selected="false">{{ $row->unit->title ?? 'N/A' }}</a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    @if($baseUnit)
                        @php
                            $baseTabId = md5('base-' . $baseUnit->id);
                        @endphp
                        <div class="tab-pane fade show active" id="{{ $baseTabId }}" role="tabpanel"
                            aria-labelledby="{{ $baseTabId }}-tab">

                            <table class="table table-bordered pricing-table-instance" data-variant-id=""
                                data-unit-row-id="{{ $baseUnit->id }}" data-unit-type="0">
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
                                    @forelse(\App\Models\ProductTierPricing::where('unit_type', 0)->where('product_id', $product->id)->whereNull('product_variant_id')->where('product_additional_unit_id', $baseUnit->id)->get() as $tier)
                                        <tr>
                                            <td>

                                                <input type="number" class="form-control" name="min_quantity[]"
                                                    value="{{ $tier->min_qty }}" min="1" step="1">
                                            </td>
                                            <td><input type="number" class="form-control" name="max_quantity[]"
                                                    value="{{ $tier->max_qty ?: '' }}"></td>
                                            <td><input type="number" class="form-control" name="price_per_unit[]"
                                                    value="{{ $tier->price_per_unit }}" step="0.01"></td>
                                            <td><input type="number" class="form-control" name="discount[]"
                                                    value="{{ $tier->discount_amount }}" step="0.01"></td>
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
                    @endif

                    @foreach($additionalUnits as $row)
                        @php
                            $tabId = md5('add-' . $row->id);
                        @endphp
                        <div class="tab-pane fade @if(!$baseUnit && $loop->first) show active @endif" id="{{ $tabId }}"
                            role="tabpanel" aria-labelledby="{{ $tabId }}-tab">

                            <table class="table table-bordered pricing-table-instance" data-variant-id=""
                                data-unit-row-id="{{ $row->id }}" data-unit-type="1">
                                <thead>

                                </thead>
                                <tbody>
                                    @forelse(\App\Models\ProductTierPricing::where('unit_type', 1)->where('product_id', $product->id)->whereNull('product_variant_id')->where('product_additional_unit_id', $row->id)->get() as $tier)
                                        <tr>

                                            <td><input type="number" class="form-control" name="min_quantity[]"
                                                    value="{{ $tier->min_qty }}" min="1" step="1"></td>
                                            <td><input type="number" class="form-control" name="max_quantity[]"
                                                    value="{{ $tier->max_qty ?: '' }}"></td>
                                            <td><input type="number" class="form-control" name="price_per_unit[]"
                                                    value="{{ $tier->price_per_unit }}" step="0.01"></td>
                                            <td><input type="number" class="form-control" name="discount[]"
                                                    value="{{ $tier->discount_amount }}" step="0.01"></td>
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
                </div>
            </div>
        </div>
    </div>
@endsection

@push('product-js')
    <script>
        $(document).ready(function () {
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

            $(document).on('click', '.addANewLevel', function () {
                addRow(this);
            });

            $(document).on('click', '.remove-row', function () {
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
                $(e.target).closest('#pricing-matrix').find('.titleOfCurrentTabUnit').text(`${targetTab} Pricing Tiers`);
            });

            $('#productStep1Form').on('submit', function (e) {
                let items = [];
                $('.pricing-table-instance').each(function () {
                    const unitRowId = parseInt($(this).data('unit-row-id')) || null;

                    const unitType = $(this).data('unit-type');

                    $(this).find('tbody tr').each(function () {
                        const minQty = parseFloat($(this).find('input[name="min_quantity[]"]').val());
                        const maxQtyRaw = $(this).find('input[name="max_quantity[]"]').val();
                        const maxQty = maxQtyRaw === '' ? null : parseFloat(maxQtyRaw);
                        const price = parseFloat($(this).find('input[name="price_per_unit[]"]').val());
                        const discount = parseFloat($(this).find('input[name="discount[]"]').val());

                        if (!isNaN(minQty) || !isNaN(maxQty) || !isNaN(price) || !isNaN(discount)) {
                            items.push({
                                product_variant_id: null,
                                is_base_unit: unitType,
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
                $('<input>').attr({ type: 'hidden', name: 'tier_pricings', id: 'tier_pricings_input' })
                    .val(JSON.stringify(items)).appendTo('#productStep1Form');
            });
        });
    </script>
@endpush