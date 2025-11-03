@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')

    <div class="row g-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Base Unit</h6>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <select id="baseUnitSelect" class="form-select" data-placeholder="Select base unit"></select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Additional Units</h6>
                        <button type="button" class="btn btn-outline-secondary" id="addRootAdditional">+ Add Additional Unit</button>
                    </div>
                    <div id="additionalUnitsWrapper" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('product-js')
<script>
$(document).ready(function () {
    const stepUrl = "{{ route('product-management', ['type' => encrypt('simple'), 'step' => encrypt(2), 'id' => encrypt($product->id)]) }}";
    const csrf = '{{ csrf_token() }}';
    const $wrapper = $('#additionalUnitsWrapper');

    function initSelect2($el){
        $el.select2({
            theme: 'bootstrap4',
            placeholder: $el.data('placeholder') || 'Select',
            allowClear: true,
            ajax: {
                url: stepUrl,
                type: 'POST',
                dataType: 'json',
                delay: 200,
                data: function(params){ return { _token: csrf, op: 'unit-list', searchQuery: params.term, page: params.page || 1 }; },
                processResults: function (data) { return { results: data.items, pagination: { more: data.pagination.more } }; }
            }
        });
    }

    function pluralize(label, qty){
        if(!label) return '';
        if(parseFloat(qty) === 1) return label;
        if(/s$/i.test(label)) return label;
        return label + 's';
    }

    function buildRow(node, map){
        const id = node.id;
        const $row = $('<div class="border rounded p-3" data-id="'+id+'">\
            <div class="row g-2 align-items-center">\
                <div class="col-md-4">\
                    <select class="form-select unit-select" data-placeholder="Select unit"></select>\
                </div>\
                <div class="col-md-2">\
                    <input type="number" min="1" step="1" class="form-control qty-input" value="'+(node.qty||1)+'">\
                </div>\
                <div class="col-md-4">\
                    <div class="small text-muted conv-label"></div>\
                </div>\
                <div class="col-md-2 d-flex gap-2 justify-content-end">\
                    <button type="button" class="btn btn-sm btn-outline-primary add-child">Add Child</button>\
                    <button type="button" class="btn btn-sm btn-outline-danger delete-node">Delete</button>\
                </div>\
            </div>\
        </div>');
        const $sel = $row.find('select.unit-select');
        initSelect2($sel);
        if(node.unit_id && node.unit && node.unit.label){
            const opt = new Option(node.unit.label, node.unit_id, true, true);
            $sel.append(opt).trigger('change');
        }
        $row.find('.add-child').on('click', function(){
            const hasChild = Object.values(map).some(x => x.parent_id === id);
            if(hasChild) return;
            openCreateDialog(id);
        });
        $row.find('.delete-node').on('click', function(){
            $.post(stepUrl, { _token: csrf, op: 'delete-additional', id: id }, function(){ refreshTree(); });
        });
        $sel.on('change', function(){
            const val = $(this).val();
            if(!val) return;
            $.post(stepUrl, { _token: csrf, op: 'update-additional-unit', id: id, unit_id: val }, function(){ refreshTree(); });
        });
        $row.find('.qty-input').on('change keyup', function(){
            let v = parseFloat($(this).val()||1);
            if(!v || v < 1) v = 1;
            $(this).val(v);
            $.post(stepUrl, { _token: csrf, op: 'update-additional-qty', id: id, qty: v }, function(){ refreshTree(); });
        });
        return $row;
    }

    function computeChain(node, base, map){
        const baseLabel = base?.label || '';
        const left = '1 ' + pluralize(node.unit?.label || '', 1);
        const terms = [];
        if(!node.parent_id){
            const qty = parseFloat(node.qty||1);
            terms.push(qty + ' ' + pluralize(baseLabel, qty));
            return left + ' = ' + terms.join(' = ');
        }
        const ancestors = [];
        let cur = map[node.parent_id] || null;
        while(cur){ ancestors.push(cur); cur = cur.parent_id ? map[cur.parent_id] : null; }
        let cumulative = parseFloat(node.qty||1);
        terms.push(cumulative + ' ' + pluralize(ancestors[0]?.unit?.label || baseLabel, cumulative));
        for(let i=1;i<ancestors.length;i++){
            cumulative = cumulative * parseFloat(ancestors[i-1].qty||1);
            terms.push(cumulative + ' ' + pluralize(ancestors[i]?.unit?.label || baseLabel, cumulative));
        }
        const topQty = parseFloat(ancestors[ancestors.length-1]?.qty||1);
        const toBase = cumulative * topQty;
        terms.push(toBase + ' ' + pluralize(baseLabel, toBase));
        return left + ' = ' + terms.join(' = ');
    }

    function renderTree(data){
        $wrapper.empty();
        const base = data.base_unit;
        const nodes = data.additional_units || [];
        const map = {};
        nodes.forEach(n => { map[n.id] = n; });
        const roots = nodes.filter(n => !n.parent_id);
        roots.forEach(function(r){
            const $row = buildRow(r, map);
            $wrapper.append($row);
        });
        nodes.forEach(function(n){
            const $row = $wrapper.find('[data-id='+n.id+']');
            const label = computeChain(n, base, map);
            $row.find('.conv-label').text(label);
        });
    }

    function refreshTree(){
        $.post(stepUrl, { _token: csrf, op: 'fetch-units-tree' }, function(res){ renderTree(res); });
    }

    function openCreateDialog(parentId){
        const $dlg = $('<div class="row g-2 align-items-center mb-3">\
            <div class="col-md-4">\
                <select class="form-select" id="newUnitSelect" data-placeholder="Select unit"></select>\
            </div>\
            <div class="col-md-2">\
                <input type="number" min="1" step="1" class="form-control" id="newQty" value="1">\
            </div>\
            <div class="col-md-3">\
                <button type="button" class="btn btn-primary" id="confirmAdd">Add</button>\
                <button type="button" class="btn btn-light ms-2" id="cancelAdd">Cancel</button>\
            </div>\
        </div>');
        initSelect2($dlg.find('#newUnitSelect'));
        $wrapper.prepend($dlg);
        $dlg.on('click', '#cancelAdd', function(){ $dlg.remove(); });
        $dlg.on('click', '#confirmAdd', function(){
            const unitId = $dlg.find('#newUnitSelect').val();
            const qty = parseFloat($dlg.find('#newQty').val()||1);
            if(!unitId || !qty || qty < 1) return;
            $.post(stepUrl, { _token: csrf, op: 'add-additional', unit_id: unitId, qty: qty, parent_id: parentId || '' }, function(){
                $dlg.remove();
                refreshTree();
            });
        });
    }

    $('#addRootAdditional').on('click', function(){ openCreateDialog(null); });

    $('#baseUnitSelect').each(function(){ initSelect2($(this)); });
    $('#baseUnitSelect').on('change', function(){
        const val = $(this).val();
        if(!val) return;
        $.post(stepUrl, { _token: csrf, op: 'set-base', unit_id: val }, function(){ refreshTree(); });
    });

    $.post(stepUrl, { _token: csrf, op: 'fetch-units-tree' }, function(res){
        if(res.base_unit){
            const opt = new Option(res.base_unit.label, res.base_unit.id, true, true);
            $('#baseUnitSelect').append(opt).trigger('change');
        }
        renderTree(res);
    });
});
</script>
@endpush
