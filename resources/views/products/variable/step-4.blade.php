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
$defaultVariant = $defaultVariantUnit = [];
@endphp

@section('product-content')
<div class="row">
    <div class="col-12">
        <label for="variant"> Select Variant </label>
        <select name="variant" id="variant" class="form-control">
            @foreach($product->variants as $variant)
            @php
                $units = $variant->additionalUnits()->with('unit')->get()->toArray();
                $baseUn = $variant->baseUnit()->with('unit')->first()->toArray();

                array_unshift($units, $baseUn);

                if ($loop->first) {
                    $defaultVariantUnit = $units;
                    $defaultVariant = $variant;
                }
            @endphp

            <option value="{{ $variant->id }}" data-units="{{ json_encode($units)  }}" @if($loop->first) selected @endif> {{ $variant->name }} - {{ $variant->sku }} </option>
            @endforeach
        </select>

        <div id="pricing-matrix" class="mt-4">
            <div class="mt-4">
                <h3 id="titleOfCurrentTabUnit">{{ $defaultVariantUnit[0]['unit']['title'] ?? 'N/A' }} Pricing Tiers</h3>
                <p>Set quantity-based pricing for individual {{ $defaultVariantUnit[0]['unit']['title'] ?? 'N/A' }}</p>

                <ul class="nav nav-tabs" id="pricingTabs" role="tablist">
                    @foreach ($defaultVariantUnit as $row)
                        @php
                        $tabId = md5($row['id']);
                        @endphp
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($loop->first) active @endif" id="{{ $tabId }}-tab" data-current-unit="{{ $row['unit']['title'] ?? 'N/A' }}" data-bs-toggle="tab" href="#{{ $tabId }}" role="tab" aria-controls="{{ $tabId }}" aria-selected="true">{{ $row['unit']['title'] ?? 'N/A' }} @if($loop->first) (Base Unit) @endif </a>
                    </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    @foreach ($defaultVariantUnit as $row)
                        @php
                        $tabId = md5($row['id']);
                        @endphp
                    <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                        <table class="table table-bordered">
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
        });

    function addRow(element) {
      var newRow = `
        <tr>
          <td><input type="number" class="form-control" name="min_quantity[]" value="1"></td>
          <td><input type="number" class="form-control" name="max_quantity[]" value="99"></td>
          <td><input type="number" class="form-control" name="price_per_unit[]" value="2.50"></td>
          <td><input type="number" class="form-control" name="discount[]" value="0"></td>
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

    $(document).on('shown.bs.tab', '#pricingTabs a', function (e) {
        var targetTab = $(e.target).data('current-unit');
        $('#titleOfCurrentTabUnit').text(`${targetTab} Pricing Tiers`);
    });

    });
</script>
@endpush