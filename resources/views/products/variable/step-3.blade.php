@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])
@section('product-content')
<div id="variantUnitsContainer" class="d-flex flex-column gap-3"></div>

<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="applyAllCheck">
            <label class="form-check-label" for="applyAllCheck">Apply same unit hierarchy to all variants</label>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="applyAllBtn">Apply to All</button>
    </div>
</div>

@endsection

@push('product-js')
<script>
$(function(){
    const stepUrl = "{{ route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(3), 'id' => encrypt($product->id)]) }}";

    function unitSelect($el){
        $el.select2({ theme:'bootstrap4', placeholder:'Select unit', allowClear:true, ajax:{ url: stepUrl, type:'POST', dataType:'json', delay:250, headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, data: params => ({ op:'unit-list', searchQuery: params.term, page: params.page||1 }), processResults: data => ({ results: data.items, pagination:{ more: data.pagination.more } }) } });
        return $el;
    }

    function renderItems(items){
        const $c = $('#variantUnitsContainer');
        $c.empty();
        items.forEach(function(v){
        const $card = $(`
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark">C</span>
                        <strong>${v.name || 'Variant'}</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#col_${v.id}">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="col_${v.id}" class="collapse show">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted small">Base Unit</div>
                                </div>
                                <div class="text-muted small">
                                    Default Selling Unit
                                    <div class="form-check form-switch d-inline-block ms-1">
                                        <input 
                                            disabled 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            ${v.additional_units?.find(a => a.is_default) ? 'checked' : ''}>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <div class="me-2" style="width:260px">
                                    <select class="form-select form-select-sm base-unit" data-id="${v.id}"></select>
                                </div>
                                <span class="text-muted small">Base selling unit</span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="text-muted small">Additional Units</div>
                        </div>
                        <div class="additional-wrap" data-id="${v.id}"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 add-unit" data-id="${v.id}">
                            + Add Unit
                        </button>
                    </div>
                </div>
            </div>
        `);

            const $select = unitSelect($card.find('select.base-unit'));
            if (v.base_unit) {
                const op = new Option(v.base_unit.title, v.base_unit.id, true, true); $select.append(op).trigger('change');
            }
            $select.on('select2:select', function(e){
                const unitId = e.params.data.id; const varId = $(this).data('id');
                $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'set-base', varient_id: varId, unit_id: unitId });
            });
            $select.on('select2:clear', function(){ const varId = $(this).data('id'); $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'set-base', varient_id: varId, unit_id: '' }); });

            const $wrap = $card.find('.additional-wrap');
            function addRow(row){
                const $r = $('<div class="border rounded p-3 mb-2" style="margin-left:0">\
                    <div class="row g-2 align-items-center">\
                        <div class="col-md-4">\
                            <div class="d-flex align-items-center">\
                                <span class="me-2" style="width:14px;height:14px;border-radius:50%;background:#888;display:inline-block"></span>\
                                <select class="form-select form-select-sm unit-select"></select>\
                            </div>\
                            <div class="text-muted small mt-1 conv-label"></div>\
                        </div>\
                        <div class="col-md-3"><input type="number" min="1" class="form-control form-control-sm qty" value="'+(row&&row.qty?row.qty: (row?1:12))+'"></div>\
                        <div class="col-md-3"><select class="form-select form-select-sm parent-select"><option value="">Per Base Unit</option></select></div>\
                        <div class="col-md-2 d-flex align-items-center justify-content-end">\
                            <div class="form-check form-switch me-2"><input class="form-check-input default-switch" type="checkbox" '+(row&&row.is_default?'checked':'')+'></div>\
                            <button type="button" class="btn btn-sm btn-outline-danger remove"><i class="fa fa-trash"></i></button>\
                        </div>\
                    </div>\
                </div>');
                if (row && row.id) { $r.attr('data-row-id', row.id); $r.data('row-id', row.id); }
                if (row && row.parent_id) { $r.attr('data-desired-parent', row.parent_id); $r.data('desired-parent', row.parent_id); }

                const $uSel = unitSelect($r.find('select.unit-select'));
                if (row && row.unit) { const op = new Option(row.unit.title, row.unit.id, true, true); $uSel.append(op).trigger('change'); }
                const $parentSel = $r.find('select.parent-select');
                function populateParents(){
                    $parentSel.html('<option value="">Per Base Unit</option>');
                    $wrap.find('.border').each(function(){ const lbl = $(this).find('select.unit-select option:selected').text(); const id = $(this).data('row-id'); if(id && id !== $r.data('row-id')) { $parentSel.append(new Option(lbl, id)); } });
                    const desired = $r.data('desired-parent'); if (desired) { $parentSel.val(desired); }
                }
                populateParents();

                function hasChild(parentId){ let found=false; $wrap.find('.border').each(function(){ if($(this).find('.parent-select').val()==parentId) found=true; }); return found; }

                function indentation(){
                    let indent = 0; let curParent = $parentSel.val();
                    while(curParent){ const $p = $wrap.find('.border[data-row-id="'+curParent+'"]'); if(!$p.length) break; indent += 20; curParent = $p.find('.parent-select').val(); }
                    $r.css('margin-left', indent+'px');
                }

                function chainLabel(){
                    const unitName = ($uSel.find('option:selected').text()||'');
                    let parts = ['1 '+unitName];
                    let total = 1;
                    let cursor = $r;
                    while(true){
                        const qty = parseFloat(cursor.find('.qty').val()||1);
                        const parentId = cursor.find('.parent-select').val();
                        if(parentId){
                            const $p = $wrap.find('.border[data-row-id="'+parentId+'"]');
                            const parentUnitName = $p.find('select.unit-select option:selected').text();
                            parts.push('= '+qty+' '+parentUnitName);
                            total *= qty;
                            cursor = $p;
                        } else {
                            const baseName = v.base_unit ? v.base_unit.title : '';
                            parts.push('= '+(total*1)+' '+baseName);
                            break;
                        }
                    }
                    return parts.join(' ');
                }

                function updateConv(){
                    const parentText = $parentSel.val() ? 'Per Parent Unit' : 'Per Base Unit';
                    $r.find('.conv-label').text(chainLabel());
                    $parentSel.find('option:first').text(parentText);
                    indentation();
                }
                $r.on('keyup change', '.qty, .unit-select, .parent-select', updateConv);
                updateConv();

                $r.on('click', '.remove', function(){
                    const id = $r.data('row-id');
                    if(id){ $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'delete-additional', id }, function(){ $r.remove(); }); } else { $r.remove(); }
                });

                $r.on('change', '.default-switch', function(){ const id = $r.data('row-id'); if(!id) { $(this).prop('checked', false); return; } $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'toggle-default', id, value: $(this).is(':checked')?1:0 }, function(){ $wrap.find('.default-switch').not($r.find('.default-switch')).prop('checked', false); }); });

                $uSel.on('select2:select', function(e){
                    if ($r.data('row-id')) return;
                    const parentId = $parentSel.val() || null;
                    if (parentId && hasChild(parentId)) { if(window.Swal){ Swal.fire('Only one child allowed','','warning'); } return; }
                    const payload = { _token:'{{ csrf_token() }}', op:'add-additional', varient_id: v.id, unit_id: e.params.data.id, parent_id: parentId, is_default: $r.find('.default-switch').is(':checked')?1:0 };
                    $.post(stepUrl, payload, function(res){ $r.attr('data-row-id', res.id); $r.data('row-id', res.id); populateParents(); updateConv(); });
                });

                $wrap.append($r);
                return $r;
            }

            (v.additional_units||[]).forEach(row => addRow(row));

            function refreshAll(){
                $wrap.find('.border').each(function(){
                    const $r = $(this); const $parentSel = $r.find('.parent-select');
                    $parentSel.html('<option value="">Per Base Unit</option>');
                    $wrap.find('.border').each(function(){ const id = $(this).data('row-id'); if (id && id !== $r.data('row-id')) { const lbl = $(this).find('select.unit-select option:selected').text(); $parentSel.append(new Option(lbl, id)); } });
                    const desired = $r.data('desired-parent'); if (desired) { $parentSel.val(desired); }
                    $r.find('.qty, .unit-select, .parent-select').trigger('change');
                });
            }
            refreshAll();

            $card.on('click', '.add-unit', function(){ addRow(); });

            $('#variantUnitsContainer').append($card);
        });
    }

    function load(){ $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'list' }, function(res){ renderItems(res.items||[]); }); }

    $('#applyAllBtn').on('click', function(){ if(!$('#applyAllCheck').is(':checked')) return; const firstCard = $('#variantUnitsContainer .card').first(); const sourceVarId = firstCard.find('.additional-wrap').data('id'); $.post(stepUrl, { _token:'{{ csrf_token() }}', op:'apply-hierarchy', source_varient_id: sourceVarId }, function(){ load(); }); });

    const initialItems = (function(){
        try { return JSON.parse(document.getElementById('initialVariantItems').textContent) || []; } catch(e){ return []; }
    })();
    if (initialItems.length) { renderItems(initialItems); } else { load(); }
});
</script>
<script type="application/json" id="initialVariantItems">@php
$items = [];
foreach(\App\Models\ProductVarient::where('product_id', $product->id)->get() as $v){
    $base = \App\Models\ProductBaseUnit::where('product_id', $product->id)->where('varient_id', $v->id)->first();
    $baseUnit = null;
    if ($base) { $u = \App\Models\Unit::find($base->unit_id); if ($u) { $baseUnit = ['id'=>$u->id,'title'=>$u->title]; } }
    $adds = \App\Models\ProductAdditionalUnit::where('product_id',$product->id)->where('varient_id',$v->id)->orderBy('id')->get();
    $additional = [];
    foreach($adds as $a){ $u = \App\Models\Unit::find($a->unit_id); $additional[] = ['id'=>$a->id,'unit'=>$u?['id'=>$u->id,'title'=>$u->title]:null,'parent_id'=>$a->parent_id,'is_default'=>(bool)$a->is_default_selling_unit]; }
    $items[] = ['id'=>$v->id,'name'=>$v->name,'base_unit'=>$baseUnit,'additional_units'=>$additional];
}
echo json_encode($items);
@endphp</script>
@endpush