@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])
@section('product-content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Brand *</label>
                        <select name="brand_id" id="brandSelect" class="form-select" data-placeholder="Select brand" required>
                            @php($selectedBrand = optional($product->brands->first())->id)
                            @if($selectedBrand)
                                <option value="{{ $selectedBrand }}" selected>{{ optional($product->brands->first())->name }}</option>
                            @endif
                        </select>
                        @error('brand_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Type *</label>
                        <div class="">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type_switch" id="typeSimple" value="simple" {{ $product->type === 'simple' ? 'checked' : '' }}>
                                <label class="form-check-label" for="typeSimple">Simple - Single product with no variations </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type_switch" id="typeVariable" value="variable" {{ $product->type === 'variable' ? 'checked' : '' }}>
                                <label class="form-check-label" for="typeVariable">Variable - Product with attributes & variants</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type_switch" id="typeBundled" value="bundled" {{ $product->type === 'bundled' ? 'checked' : '' }}>
                                <label class="form-check-label" for="typeBundled">Bundled - Multiple products sold together</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tags *</label>
                        <select name="tags[]" class="form-select" id="tags" multiple>
                            @php($selectedTags = old('tags', $product->tags ?? []))
                            @foreach($selectedTags as $tag)
                                <option value="{{ $tag }}"  selected>{{ $tag }}</option>
                            @endforeach
                        </select>
                        @error('tags')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Short Description *</label>
                        <div id="shortDescriptionEditor" class="form-control" style="min-height:120px;">{!! old('short_description', $product->short_description) !!}</div>
                        <input type="hidden" name="short_description" id="shortDescriptionInput" required>
                        @error('short_description')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Long Description *</label>
                        <div id="longDescriptionEditor" class="form-control" style="min-height:160px;">{!! old('long_description', $product->long_description) !!}</div>
                        <input type="hidden" name="long_description" id="longDescriptionInput" required>
                        @error('long_description')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Status *</label>
                        <div class="form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="status" value="1" {{ old('status', $product->status) ? 'checked' : '' }}>
                            <label class="form-check-label" for="statusSwitch">Active</label>
                        </div>
                        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <label class="form-label">Main Product Image *</label>
                    <input type="file" name="primary_image" id="primaryImage" class="form-control" accept="image/png,image/jpeg,image/webp" {{ $product->exists ? '' : 'required' }}>
                    @error('primary_image')<div class="text-danger small">{{ $message }}</div>@enderror
                    @php($existingPrimary = $product->primaryImage)
                    @if($existingPrimary)
                    <div class="mt-3" id="primaryPreview">
                        <img src="{{ $existingPrimary ? asset('storage/'.$existingPrimary->file) : '#' }}" alt="Preview" class="img-fluid rounded border" id="primaryPreviewImg" style="object-fit:cover;max-height:220px;width:100%">
                    </div>
                    @else
                    <div class="mt-3" id="primaryPreview" style="display:none">
                        <img src="#" alt="Preview" class="img-fluid rounded border" id="primaryPreviewImg" style="object-fit:cover;max-height:220px;width:100%">
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Secondary Images</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSecondary">Clear</button>
                    </div>
                    <input type="file" name="secondary_images[]" id="secondaryImages" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
                    @error('secondary_images')<div class="text-danger small">{{ $message }}</div>@enderror
                    <div class="mt-3">
                        <div id="secondaryGallery" class="d-flex flex-wrap gap-2" style="min-height:60px">
                            @foreach(($product->images()->where('is_primary',0)->get() ?? []) as $img)
                                <div class="position-relative border rounded existing-image" data-image-id="{{ $img->id }}" style="width:90px;height:90px" draggable="true">
                                    <img src="{{ asset('storage/'.$img->file) }}" class="w-100 h-100 rounded" style="object-fit:cover">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle rounded-circle remove-existing" style="width:20px;height:20px">&times;</button>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Drag to reorder. Click × to remove.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('product-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urls = {
        simple: "{{ route('product-management', ['type' => encrypt('simple'), 'step' => encrypt(1), 'id' => encrypt($product->id)]) }}",
        variable: "{{ route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(1), 'id' => encrypt($product->id)]) }}",
        bundled: "{{ route('product-management', ['type' => encrypt('bundled'), 'step' => encrypt(1), 'id' => encrypt($product->id)]) }}",
    };

    $('#productStep1Form').validate({
        rules: {
            name: {
                required: true
            },
            brand_id: {
                required: true
            },
            "tags[]": {
                required: true
            },
            primary_image: {
                required: @if($existingPrimary) false @else true @endif
            },
        },
        errorPlacement: function (error, element) {
            error.appendTo(element.parent("div"));
        }
    });

    document.querySelectorAll('input[name="type_switch"]').forEach(r => r.addEventListener('change', function(){
        const to = this.value;
        if (urls[to]) window.location.href = urls[to];
    }));

    $('#brandSelect').select2({
        theme: 'bootstrap4',
        placeholder: $(this).data('placeholder') || 'Select brand',
        allowClear: true,
        ajax: {
            url: '{{ route('brand-list') }}',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: function (params) {
                return { searchQuery: params.term, page: params.page || 1 };
            },
            processResults: function (data) { return { results: data.items, pagination: { more: data.pagination.more } }; }
        }
    });

    $('#tags').select2({
        theme: 'bootstrap4',
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Select or add tags',
    });

    const shortQ = new Quill('#shortDescriptionEditor', { theme: 'snow' });
    const longQ = new Quill('#longDescriptionEditor', { theme: 'snow' });

    document.getElementById('productStep1Form').addEventListener('submit', function(e){
        document.getElementById('shortDescriptionInput').value = shortQ.root.innerHTML.trim();
        document.getElementById('longDescriptionInput').value = longQ.root.innerHTML.trim();
    });

    const primaryInput = document.getElementById('primaryImage');
    const primaryPreview = document.getElementById('primaryPreview');
    const primaryPreviewImg = document.getElementById('primaryPreviewImg');
    
    primaryInput.addEventListener('change', function(){
        const file = this.files && this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { primaryPreviewImg.src = e.target.result; primaryPreview.style.display = 'block'; };
        reader.readAsDataURL(file);
    });

    const gallery = document.getElementById('secondaryGallery');
    const secondaryInput = document.getElementById('secondaryImages');
    const clearBtn = document.getElementById('clearSecondary');
    let items = [];

    function renderGallery() {
        gallery.innerHTML = '';
        items.forEach((item, index) => {
            const wrap = document.createElement('div');
            wrap.className = 'position-relative border rounded';
            wrap.style.width = '90px';
            wrap.style.height = '90px';
            wrap.draggable = true;
            wrap.dataset.index = index;

            const img = document.createElement('img');
            img.src = item.preview;
            img.className = 'w-100 h-100 rounded';
            img.style.objectFit = 'cover';
            wrap.appendChild(img);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle rounded-circle';
            btn.innerHTML = '&times;';
            btn.style.width = '20px';
            btn.style.height = '20px';
            btn.addEventListener('click', function(){
                items.splice(index, 1);
                const dt = new DataTransfer();
                items.forEach(i => dt.items.add(i.file));
                secondaryInput.files = dt.files;
                renderGallery();
            });
            wrap.appendChild(btn);

            wrap.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', index); });
            wrap.addEventListener('dragover', e => e.preventDefault());
            wrap.addEventListener('drop', e => {
                e.preventDefault();
                const from = parseInt(e.dataTransfer.getData('text/plain'));
                const to = index;
                const moved = items.splice(from, 1)[0];
                items.splice(to, 0, moved);
                const dt = new DataTransfer();
                items.forEach(i => dt.items.add(i.file));
                secondaryInput.files = dt.files;
                renderGallery();
            });

            gallery.appendChild(wrap);
        });
    }

    const deletedContainer = document.createElement('div');
    deletedContainer.id = 'deletedImagesContainer';
    document.getElementById('productStep1Form').appendChild(deletedContainer);

    document.querySelectorAll('#secondaryGallery .existing-image .remove-existing').forEach(btn => {
        btn.addEventListener('click', function(){
            const parent = this.closest('.existing-image');
            const imageId = parent.getAttribute('data-image-id');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Remove image?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, remove' }).then((result) => {
                    if (result.isConfirmed) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'deleted_image_ids[]';
                        input.value = imageId;
                        deletedContainer.appendChild(input);
                        parent.remove();
                    }
                });
            }
        });
    });

    secondaryInput.addEventListener('change', function(){
        items = [];
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => { items.push({ file, preview: e.target.result }); renderGallery(); };
            reader.readAsDataURL(file);
        });
    });

    clearBtn.addEventListener('click', function(){
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Clear all images?', text: 'This will remove all selected and existing secondary images', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, clear' }).then((result) => {
                if (result.isConfirmed) {
                    items = [];
                    gallery.querySelectorAll('.existing-image').forEach(el => {
                        const imageId = el.getAttribute('data-image-id');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'deleted_image_ids[]';
                        input.value = imageId;
                        deletedContainer.appendChild(input);
                        el.remove();
                    });
                    gallery.innerHTML = '';
                    secondaryInput.value = '';
                }
            });
        }
    });
});
</script>
@endpush