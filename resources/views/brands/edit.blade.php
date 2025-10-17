@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Edit Brand</div>
            <div class="card-body">
                <form id="brandForm" method="POST" action="{{ route('brands.update', encrypt($brand->id)) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $brand->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" required>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $brand->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                        @if($brand->logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/'.$brand->logo) }}" style="height:60px;width:auto;object-fit:contain;">
                        </div>
                        @endif
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', $brand->status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Brand</button>
                    <a href="{{ route('brands.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#brandForm').validate({
        rules: { name: { required: true }, slug: { required: true } },
        submitHandler: function (form) { form.submit(); }
    });
});
</script>
@endpush


