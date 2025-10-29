@extends('layouts.app', ['title' => 'Product Management', 'subTitle' => 'Enter the information for your product', 'select2' => true, 'editor' => true])

@section('content')
@include('products.steps', ['currentStep' => $step, 'type' => $type])

<form action="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt($step), 'id' => encrypt($product->id)]) }}" method="POST" enctype="multipart/form-data" id="productStep1Form">
    @csrf

    @yield('product-content')

    <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save & Continue</button>
    </div>
</form>
@endsection

@push('js')
@stack('product-js')
@endpush