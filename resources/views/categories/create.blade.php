@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'select2' => true])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Add New Category</div>
            <div class="card-body">
                <form id="categoryForm" method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent</label>
                        <select class="form-select select2 @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">Select Parent</option>
                            @foreach($parents as $id => $name)
                                <option value="{{ $id }}" {{ old('parent_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label"> Image <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" required>
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="text-center">
                        <img id="previewImage" src="" alt="Image preview" class="img-fluid rounded shadow-sm d-none" style="max-height: 300px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <select class="form-select select2" id="tags" name="tags[]" multiple></select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Active</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Category</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
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

    const imageInput = document.getElementById('image');
    const previewImage = document.getElementById('previewImage');

    imageInput.addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImage.src = e.target.result;
          previewImage.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
      } else {
        previewImage.src = '';
        previewImage.classList.add('d-none');
      }
    });
    
    $('#parent_id').select2({ placeholder: 'Select parent', width: '100%' });
    $('#tags').select2({ tags: true, tokenSeparators: [','], width: '100%', placeholder: 'Add tags' });

    $('#categoryForm').validate({
        rules: { name: { required: true } },
        submitHandler: function (form) { form.submit(); }
    });
});
</script>
@endpush


