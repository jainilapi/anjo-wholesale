@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
<div id="unitAccordion"></div>
@endsection

@push('product-js')
<script>
(() => {
  const csrf = '{{ csrf_token() }}';
  const unitStepUrl = "{{ route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(3), 'id' => encrypt($product->id)]) }}";

  /** ==============================
   *  Utility Functions
   * ============================== */
  const post = (data) => $.post(unitStepUrl, { _token: csrf, ...data });
  const icon = (cls) => `<i class="fa ${cls}"></i>`;

  function computeChainText(unit, unitsMap, baseLabel) {
    const parts = [`1 ${unit.unit.label}`];
    let multiplier = 1, cursor = unit;
    while (cursor.parent_id && unitsMap[cursor.parent_id]) {
      const parent = unitsMap[cursor.parent_id];
      multiplier *= (cursor.qty || 1);
      parts.push(`= ${cursor.qty || 1} ${parent.unit.label}`);
      cursor = parent;
    }
    parts.push(`= ${multiplier} ${baseLabel}`);
    return parts.join(' ');
  }

  /** ==============================
   *  Rendering Logic
   * ============================== */
  function renderUnitTree(units, parentId = null, parentName = '', depth = 0, unitsMap = {}, variantId = null, baseLabel='') {
    return units
      .filter(u => u.parent_id === parentId)
      .map(u => {
        
        const chainText = computeChainText(u, unitsMap, baseLabel);
        const hasChild = units.some(x => x.parent_id === u.id);
        const indent = depth * 25;

        return `
          <div class="card mb-2" style="margin-left:${indent}px">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-4">
                  <span class="badge bg-secondary">${u.unit.label}</span>
                </div>
                <div class="col-md-3">
                  <input class="form-control form-control-sm qty-input" 
                         data-id="${u.id}" type="number" min="1" 
                         value="${u.qty}" placeholder="Quantity">
                </div>
                <div class="col-md-3 small text-muted">Per ${parentName}</div>
                <div class="col-md-2 text-end d-flex align-items-center justify-content-end gap-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input default-toggle" 
                           data-id="${u.id}" data-variant="${variantId}" 
                           type="checkbox" ${u.is_default ? 'checked' : ''}>
                  </div>
                  <button type="button" class="btn btn-link text-danger p-0 unit-delete" data-id="${u.id}">
                    ${icon('fa-trash')}
                  </button>
                </div>
              </div>
              <div class="mt-2 small text-muted">${chainText}</div>
              <div class="ms-2 mt-2">
                ${!hasChild ? `<button type="button" class="btn btn-outline-secondary btn-sm add-unit" 
                               data-parent="${u.id}" data-variant="${variantId}">
                               + Add Unit</button>` : ''}
              </div>
              ${renderUnitTree(units, u.id, u.unit.label, depth + 1, unitsMap, variantId, baseLabel)}
            </div>
          </div>`;
      }).join('');
  }

  function renderVariantCard(v) {
    const unitsMap = Object.fromEntries((v.additional_units || []).map(x => [x.id, x]));
    return `
      <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <strong>${v.name}</strong>
          <button type="button" class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#var_${v.id}">
            ${icon('fa-chevron-down')}
          </button>
        </div>
        <div id="var_${v.id}" class="collapse show">
          <div class="card-body">
            <div class="mb-3">
              <div class="small text-muted mb-1">Base Unit</div>
              <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm base-unit-select" data-variant="${v.id}"></select>
                <span class="small text-muted">Base selling unit</span>
              </div>
            </div>
            <hr/>
            <div>
              <div class="small text-muted mb-2">Additional Units</div>
              ${renderUnitTree(v.additional_units || [], null, v.base_unit?.label || '', 0, unitsMap, v.id, v.base_unit?.label || '')}
              <button type="button" class="btn btn-outline-secondary btn-sm add-unit" data-parent="" data-variant="${v.id}">+ Add Unit</button>
            </div>
          </div>
        </div>
      </div>`;
  }

  function loadVariantsAndUnits() {
    post({ op: 'fetch-variants-units-tree' }).done(res => {
      const html = res.variants.map(renderVariantCard).join('');
      $('#unitAccordion').html(html);
      initBaseSelects(res.variants);
    });
  }

  /** ==============================
   *  Select2 & Modal Handling
   * ============================== */
  function initBaseSelects(variants) {
    $('.base-unit-select').each(function () {
      const variantId = $(this).data('variant');
      const current = variants.find(v => v.id === variantId)?.base_unit || null;
      $(this).select2({
        theme: 'bootstrap4',
        ajax: {
          url: unitStepUrl,
          type: 'POST',
          dataType: 'json',
          delay: 250,
          headers: { 'X-CSRF-TOKEN': csrf },
          data: params => ({ op: 'unit-list', searchQuery: params.term, page: params.page || 1 }),
          processResults: data => ({ results: data.items, pagination: { more: data.pagination.more } })
        }
      });
      if (current) {
        const option = new Option(current.label, current.id, true, true);
        $(this).append(option).trigger('change');
      }
    });
  }

  /** ==============================
   *  Event Bindings
   * ============================== */
  $(document)
    .on('select2:select', '.base-unit-select', function (e) {
      const variantId = $(this).data('variant');
      const unitId = e.params.data.id;
      post({ op: 'set-base', variant_id: variantId, unit_id: unitId }).done(() => loadVariantsAndUnits());
    })
    .on('click', '.add-unit', function () {
      const variantId = $(this).data('variant');
      const parentId = $(this).data('parent') || null;

      const modal = $(`
        <div class="modal fade" id="unitModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Add Unit</h5></div>
              <div class="modal-body">
                <select class="form-select form-select-sm unit-select"></select>
                <input class="form-control form-control-sm mt-2 qty-input" placeholder="Quantity" type="number" min="1">
                <div class="text-danger small error-msg mt-2"></div>
              </div>
              <div class="modal-footer">
                <button type="button" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" type="button" class="btn btn-primary save-unit">Add</button>
              </div>
            </div>
          </div>
        </div>`).appendTo('body');

      const modalEl = new bootstrap.Modal(modal[0]);
      modalEl.show();

      const unitSelect = modal.find('.unit-select');
      unitSelect.select2({
        theme: 'bootstrap4',
        dropdownParent: modal,
        ajax: {
          url: unitStepUrl,
          type: 'POST',
          dataType: 'json',
          delay: 250,
          headers: { 'X-CSRF-TOKEN': csrf },
          data: params => ({ op: 'unit-list', searchQuery: params.term, page: params.page || 1 }),
          processResults: data => ({ results: data.items, pagination: { more: data.pagination.more } })
        }
      });

      modal.find('.save-unit').on('click', () => {
        const uid = unitSelect.val();
        const qty = modal.find('.qty-input').val();
        const error = modal.find('.error-msg');
        if (!uid || !qty) return error.text('Unit and Qty required');
        post({ op: 'add-additional', variant_id: variantId, unit_id: uid, parent_id: parentId, qty }).done(res => {
          if (res.success) {
            modalEl.hide();
            modal.remove();
            loadVariantsAndUnits();
          } else {
            error.text(res.message || 'Unable to add.');
          }
        });
      });

      modal.on('hidden.bs.modal', () => modal.remove());
    })
    .on('input', '.qty-input', function () {
      post({ op: 'update-additional-qty', id: $(this).data('id'), qty: $(this).val() });
    })
    .on('change', '.default-toggle', function () {
      post({ op: 'toggle-default', id: $(this).data('id'), value: $(this).is(':checked') ? 1 : 0 });
    })
    .on('click', '.unit-delete', function () {
      if (confirm('Delete this unit and any children?')) {
        post({ op: 'delete-additional', id: $(this).data('id') }).done(() => loadVariantsAndUnits());
      }
    });

  /** ==============================
   *  Initialize
   * ============================== */
  $(() => loadVariantsAndUnits());
})();
</script>
@endpush
