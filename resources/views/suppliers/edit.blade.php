@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<style>
    div.iti--inline-dropdown {
        min-width: 100%!important;
    }
    .iti__selected-flag {
        height: 32px!important;
    }
    .iti--show-flags {
        width: 100%!important;
    }  
    label.error {
        color: red;
    }
    #phone_number{
        font-family: "Hind Vadodara",-apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;
        font-size: 15px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Edit Supplier</div>
            <div class="card-body">
                <form id="supplierForm" method="POST" action="{{ route('suppliers.update', encrypt($supplier->id)) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 row">
                        <div class="col-12">
                            <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="hidden" name="dial_code" id="dial_code">                            
                            <input type="tel" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $supplier->phone_number) }}" required>
                            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="country_id" class="form-label">Country <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $id => $name)
                                <option value="{{ $id }}" {{ old('country_id', $supplier->country_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="state_id" class="form-label">State <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('state_id') is-invalid @enderror" id="state_id" name="state_id" required>
                            <option value="">Select State</option>
                        </select>
                        @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="city_id" class="form-label">City <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('city_id') is-invalid @enderror" id="city_id" name="city_id" required>
                            <option value="">Select City</option>
                        </select>
                        @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="1" {{ old('status', $supplier->status) == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status', $supplier->status) == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Leave blank to keep unchanged.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intel-tel.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
@endpush

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/intel-tel.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>
$(document).ready(function() {

    const input = document.querySelector('#phone_number');
    const errorMap = ["Phone number is invalid.", "Invalid country code", "Too short", "Too long"];
    const iti = window.intlTelInput(input, {
        initialCountry: "{{ Helper::getIso2ByDialCode($supplier->dial_code) }}",
        separateDialCode:true,
        nationalMode:false,
        preferredCountries: @json(\App\Models\Country::select('iso2')->pluck('iso2')->toArray()),
        utilsScript: "{{ asset('assets/js/intel-tel-2.min.js') }}"
    });
    input.addEventListener("countrychange", function() {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });
    input.addEventListener('keyup', () => {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });

    $('#country_id').select2({
        allowClear: true,
        placeholder: 'Select country',
        width: '100%'
    });
    
    $('#state_id').select2({
        allowClear: true,
        placeholder: 'Select state',
        width: '100%',
        ajax: {
            url: "{{ route('state-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    country_id: $('#country_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    $('#city_id').select2({
        allowClear: true,
        placeholder: 'Select city',
        width: '100%',
        ajax: {
            url: "{{ route('city-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    state_id: $('#state_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    @if(isset($supplier->country->id))
        $('#country_id').append(new Option('{{ $supplier->country->name ?? '' }}', '{{ $supplier->country->id ?? '' }}', true, true));
    @endif
    @if(isset($supplier->state->id))
        $('#state_id').append(new Option('{{ $supplier->state->name ?? '' }}', '{{ $supplier->state->id ?? '' }}', true, true));
    @endif
    @if(isset($supplier->city->id))
        $('#city_id').append(new Option('{{ $supplier->city->name ?? '' }}', '{{ $supplier->city->id ?? '' }}', true, true));
    @endif

    $('#supplierForm').validate({
        rules: {
            name: { required: true },
            email: { required: true, email: true },
            phone_number: { required: true },
            country_id: { required: true },
            state_id: { required: true },
            city_id: { required: true },
            status: { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'phone_number') {
                error.insertAfter(element.parent());
            } else {
                error.appendTo(element.parent());
            }
        },
        submitHandler: function (form) {
            $('#dial_code').val(iti.s.dialCode);
            form.submit();
        }
    });
});
</script>
@endpush
