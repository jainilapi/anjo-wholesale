@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'select2' => true, 'editor' => true])

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">Add New Product</div>
            <div class="card-body">
                <form id="productForm" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}" required>
                                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="brands" class="form-label">Brands</label>
                                <select class="form-select select2 @error('brands') is-invalid @enderror" id="brands" name="brands[]" multiple>
                                    @foreach($brands as $id => $name)
                                        <option value="{{ $id }}" {{ (collect(old('brands'))->contains($id)) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('brands')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <div id="editor" style="height: 250px;">{!! old('description') !!}</div>
                        <textarea name="description" id="description" class="d-none">{!! old('description') !!}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                        <div id="previewContainer" class="row mt-2"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="in_stock" name="in_stock" value="1" {{ old('in_stock', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="in_stock">In stock</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#category_id').select2({ placeholder: 'Select category', width: '100%' });
    $('#brands').select2({ placeholder: 'Select brands', width: '100%' });

    const quill = new Quill('#editor', { theme: 'snow' });
    quill.on('text-change', function() {
        $('#description').val(quill.root.innerHTML);
    });

    $('#images').on('change', function(event) {
        const files = Array.from(event.target.files);
        const container = $('#previewContainer');
        container.empty();
        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = $('<div class="col-md-3 mb-2"></div>');
                const card = $('<div class="card"></div>');
                const img = $('<img class="card-img-top" style="height:140px;object-fit:cover;">');
                img.attr('src', e.target.result);
                const body = $('<div class="card-body p-2"></div>');
                const btn = $('<button type="button" class="btn btn-sm btn-danger w-100">Remove</button>');
                btn.on('click', function(){
                    const dt = new DataTransfer();
                    const input = document.getElementById('images');
                    const { files } = input;
                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) dt.items.add(files[i]);
                    }
                    input.files = dt.files;
                    col.remove();
                });
                body.append(btn);
                card.append(img).append(body);
                col.append(card);
                container.append(col);
            };
            reader.readAsDataURL(file);
        });
    });

    $('#productForm').validate({
        rules: { name: { required: true }, sku: { required: true }, category_id: { required: true } },
        submitHandler: function (form) { form.submit(); }
    });
});
</script>
@endpush


