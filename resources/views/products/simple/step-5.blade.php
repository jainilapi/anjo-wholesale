@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])
@section('product-content')
<div class="container py-4">
  <div class="card variant-card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <strong>Suppliers</strong>
      <button type="button" class="btn btn-sm btn-outline-primary btn-add-supplier">
        <i class="fa fa-plus"></i> Add Supplier
      </button>
    </div>
    <div class="card-body">
      <table class="table align-middle table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Supplier Name</th>
            <th>Supplier Contact Detail</th>
            <th>Supplier Country</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="simpleSuppliersBody"></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="supplierSelect" class="form-label">Select supplier</label>
          <select id="supplierSelect" class="form-select">
            <option value="">Select Supplier</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmAddSupplier">Add Supplier</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('product-js')
<script>
const allSuppliers = @json($suppliers);
let simpleSuppliers = @json($simpleProductSuppliers ?? []);

$(document).ready(function () {
  renderSuppliers();

  $(document).on('click', '.btn-add-supplier', function () {
    const used = simpleSuppliers.map(s => s.id);
    const available = allSuppliers.filter(s => !used.includes(s.id));
    const select = $('#supplierSelect');
    select.empty();
    if (available.length) {
      select.append(`<option value="">Select Supplier</option>`);
      available.forEach(s => select.append(`<option value="${s.id}">${s.name} - ${s.email}</option>`));
    } else {
      select.append(`<option value="">No more suppliers available</option>`);
    }
    $('#addSupplierModal').modal('show');
  });

  $('#confirmAddSupplier').on('click', function () {
    const selected = $('#supplierSelect').val();
    if (!selected) { alert('Please select a supplier.'); return; }
    if (simpleSuppliers.some(s => s.id == selected)) { alert('This supplier is already added.'); return; }
    const supplier = allSuppliers.find(s => s.id == selected);
    if (!supplier) return;
    simpleSuppliers.push(supplier);
    $('#simpleSuppliersBody').append(getSupplierRowHtml(supplier));
    $('#addSupplierModal').modal('hide');
  });

  $(document).on('click', '.remove-supplier', function () {
    const sid = $(this).data('supplier_id');
    const index = simpleSuppliers.findIndex(s => s.id == sid);
    if (index !== -1) simpleSuppliers.splice(index, 1);
    $(this).closest('tr').remove();
  });

  function getSupplierRowHtml(w) {
    return `
      <tr>
        <td>
          <input type="hidden" name="data[supplier_id][]" value="${w.id}" />
          <strong>${w.name}</strong>
        </td>
        <td>${w.phone_number} - (Email: ${w.email})</td>
        <td>${w.country_flag}</td>
        <td>
          <button data-supplier_id="${w.id}" type="button" class="remove-supplier btn btn-sm btn-outline-danger">Remove</button>
        </td>
      </tr>`;
  }

  function renderSuppliers() {
    const container = $('#simpleSuppliersBody');
    container.empty();
    const rows = (simpleSuppliers || []).map(w => getSupplierRowHtml(w)).join('');
    container.append(rows);
  }
});
</script>
@endpush