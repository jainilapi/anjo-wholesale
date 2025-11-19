@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product Substitutes</h5>
                        <p class="text-muted mt-1">
                            For each product variant, select one or more substitute product <strong>variants</strong>.
                            These can be suggested if a variant is out of stock.
                        </p>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($variantsForSubstitutes->isEmpty())
                            <div class="alert alert-warning" role="alert">
                                You must <a
                                    href="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(2), 'id' => encrypt($product->id)]) }}">generate
                                    variants</a> in Step 2 before you can assign substitutes.
                            </div>
                        @else
                            @foreach ($variantsForSubstitutes as $variant)
                                <div class="row mb-4 border-bottom pb-4 align-items-center">
                                    <div class="col-md-3">
                                        <label
                                            class="form-label fw-bold mb-0">{{ $variant['name'] }}</label>
                                        <p class="text-muted small mb-0">SKU: {{ $variant['sku'] }}</p>
                                    </div>
                                    <div class="col-md-9">

                                        <select class="form-control select2-ajax-variants"
                                            name="substitutes[{{ $variant['id'] }}][]" multiple="multiple"
                                            data-placeholder="Search for substitute variants by name or SKU...">

                                            @foreach ($variant['substitutes'] as $sub)
                                                <option value="{{ $sub['substitute_variant_id'] }}" selected>
                                                    {{ $sub['text_representation'] }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('product-js')
    <script>
        $(document).ready(function() {
            $('.select2-ajax-variants').select2({
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                allowClear: true,
                ajax: {
                    url: '{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(8), 'id' => encrypt($product->id)]) }}',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            _token: '{{ csrf_token() }}',
                            op: 'search-variants',
                            term: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
@endpush