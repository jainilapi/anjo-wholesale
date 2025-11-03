@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')



@endsection

@push('product-js')
<script>
$(document).ready(function () {
    
});
</script>
@endpush
