@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])
@section('product-content')
    <div class="row g-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Variant Attributes</h6>
                    <div id="attributesWrapper" class="d-flex flex-column gap-3"></div>
                    <button type="button" class="btn btn-outline-secondary mt-2" id="addAttributeBtn">+ Add Attribute</button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Generated Variants</h6>
                        <small id="variantsCounter" class="text-muted"></small>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="variantsTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Variant Name</th>
                                    <th>SKU</th>
                                    <th>Barcode</th>
                                    <th>Attributes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" id="generateBarcodesBtn">Generate Barcodes</button>
                        <button type="button" class="btn btn-outline-dark" id="enableAllBtn">Enable All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
$attributes = \App\Models\ProductAttribute::where('product_id', $product->id)
    ->get()
    ->groupBy('title')
    ->map(fn($g) => $g->pluck('value')->toArray());
@endphp

@push('product-js')
<script>
const savedAttributes = @json($attributes ?? []);

$(function(){
    const stepUrl = "{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(2), 'id' => encrypt($product->id)]) }}";

    function buildAttribute(title = '', values = []){
        const id = 'attr_'+Math.random().toString(36).slice(2);
        const $row = $('<div class="border rounded p-3">\
            <div class="d-flex align-items-center mb-2">\
                <div class="form-check me-2">\
                    <input class="form-check-input" type="checkbox" checked>\
                </div>\
                <input type="text" class="form-control form-control-sm me-2" placeholder="Attribute title (e.g., Size)" style="max-width:220px">\
                <div class="flex-grow-1 me-2">\
                    <select multiple class="form-select form-select-sm values"></select>\
                </div>\
                <button type="button" class="btn btn-sm btn-link text-danger remove-attr"><i class="fa fa-trash"></i></button>\
            </div>\
        </div>');
        $row.find('input[type=text]').val(title);
        const $select = $row.find('select.values');
        $select.select2({ tags: true, theme: 'bootstrap4', tokenSeparators: [','], placeholder: 'Add value...' });
        (values||[]).forEach(v=>{ $select.append(new Option(v,v,true,true)).trigger('change'); });
        $row.on('click', '.remove-attr', function(){ $row.remove(); updateCount(); });
        $('#attributesWrapper').append($row);
        return $row;
    }

    $('#addAttributeBtn').on('click', function(){ buildAttribute(); });

    function collectAttributes(){
        const out = [];
        $('#attributesWrapper > div').each(function(){
            const enabled = $(this).find('input[type=checkbox]').is(':checked');
            if(!enabled) return;
            const title = $(this).find('input[type=text]').val().trim();
            const values = $(this).find('select.values').val() || [];
            if(title && values.length) out.push({ title, values });
        });
        return out;
    }

    function updateCount(){
        const attrs = collectAttributes();
        let total = 0;
        if(attrs.length){
            total = attrs.reduce((acc, a)=> acc * (a.values?.length||0), 1);
        }
        $('#variantsCounter').text(total ? (total+ ' variants will be created') : '');
    }

    $('#attributesWrapper').on('change keyup', 'input,select', updateCount);

    function renderVariants(items){
        const $tb = $('#variantsTable tbody');
        $tb.empty();
        items.forEach(function(it){
            const tr = $('<tr data-id="'+it.id+'">\
                <td>\
                    <div class="ratio ratio-1x1 border rounded position-relative" style="width:42px;">\
                        <img class="w-100 h-100 rounded" style="object-fit:cover" src="'+(it.image || '{{ asset('public/assets/images/image_0.png') }}')+'">\
                        <input type="file" accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 variant-image-input" title="">\
                    </div>\
                </td>\
                <td><input type="text" class="form-control form-control-sm inline name" value="'+(it.name||'')+'"></td>\
                <td style="max-width:170px"><input type="text" class="form-control form-control-sm inline sku" value="'+(it.sku||'')+'"></td>\
                <td style="max-width:170px"><input type="text" class="form-control form-control-sm inline barcode" value="'+(it.barcode||'')+'" placeholder="Enter barcode"></td>\
                <td>'+ (it.attributes||[]).map(v=>'<span class="badge bg-light text-dark me-1">'+v+'</span>').join('') +'</td>\
                <td>\
                    <div class="form-check form-switch">\
                        <input class="form-check-input inline status" type="checkbox" '+(it.status?'checked':'')+'>\
                    </div>\
                </td>\
                <td>\
                    <button type="button" class="btn btn-sm btn-outline-danger delete-variant"><i class="fa fa-trash"></i></button>\
                </td>\
            </tr>');
            $tb.append(tr);
        });
    }

    function generateVariants(){
        const attributes = collectAttributes();
        if(!attributes.length){
            if(window.Swal) Swal.fire('Attributes required','','warning');
            return;
        }
        $.ajax({
            url: stepUrl,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', op: 'generate', attributes },
            success: function(res){ renderVariants(res.items || []); updateCount(); },
            error: function(){ if(window.Swal) Swal.fire('Failed to generate','','error'); }
        });
    }

    $('#addAttributeBtn').after('<button type="button" class="btn btn-primary ms-2" id="generateBtn">Generate Variants</button>');
    $(document).on('click','#generateBtn', generateVariants);

    $(document).on('change','.inline.status', function(){
        const id = $(this).closest('tr').data('id');
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'inline', id, field:'status', value: $(this).is(':checked') ? 1 : 0 });
    });
    $(document).on('change','.inline.name', function(){
        const id = $(this).closest('tr').data('id');
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'inline', id, field:'name', value: $(this).val() });
    });
    $(document).on('change','.inline.sku', function(){
        const id = $(this).closest('tr').data('id');
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'inline', id, field:'sku', value: $(this).val() });
    });
    $(document).on('change','.inline.barcode', function(){
        const id = $(this).closest('tr').data('id');
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'inline', id, field:'barcode', value: $(this).val() });
    });

    $(document).on('click','.delete-variant', function(){
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const run = () => $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'delete', id }, function(){ $tr.remove(); });
        if(window.Swal) Swal.fire({title:'Delete variant?',icon:'warning',showCancelButton:true}).then(r=>{ if(r.isConfirmed) run(); }); else run();
    });

    $(document).on('change','.variant-image-input', function(){
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const file = this.files[0];
        if(!file) return;
        const fd = new FormData();
        fd.append('_token','{{ csrf_token() }}');
        fd.append('op','upload-image');
        fd.append('id', id);
        fd.append('file', file);
        $.ajax({ url: stepUrl, method: 'POST', data: fd, processData:false, contentType:false, success: function(res){ $tr.find('img').attr('src', res.url); }, error: function(){ if(window.Swal) Swal.fire('Upload failed','','error'); } });
    });

    $('#generateBarcodesBtn').on('click', function(){
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'generate-barcodes' }, function(){ refreshList(); });
    });

    function refreshList(){
        $.ajax({ url: stepUrl, method: 'POST', data: { _token:'{{ csrf_token() }}', op:'list' }, success: function(res){ renderVariants(res.items||[]); } });
    }

    $('#enableAllBtn').on('click', function(){
        $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'enable-all' }, function(){
            $('#variantsTable tbody .inline.status').prop('checked', true);
        });
    });

    if (Object.keys(savedAttributes).length) {
        for (const [title, values] of Object.entries(savedAttributes)) {
            buildAttribute(title, values);
        }
    } else {
        buildAttribute();
    }
    updateCount();
    refreshList();
});
</script>
@endpush