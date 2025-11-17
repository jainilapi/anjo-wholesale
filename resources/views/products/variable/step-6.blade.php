@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
.stepper {
  list-style: none;
  padding-left: 1rem;
  position: relative;
}
.stepper::before {
  content: "";
  position: absolute;
  top: 0;
  left: 12px;
  width: 2px;
  height: 100%;
  background: #dee2e6;
}
.step {
  position: relative;
  margin-bottom: 1rem;
  padding-left: 2rem;
}
.step::before {
  content: "";
  position: absolute;
  left: 4px;
  top: 4px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #0d6efd;
}
</style>
@endpush

@section('product-content')
<div class="container py-4">

  <div id="inventoryContainer"></div>

  <div class="mt-4 text-end">
  </div>
</div>

<div class="modal fade" id="addWarehouseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="warehouseSelect" class="form-label">Select supplier</label>
          <select id="warehouseSelect" class="form-select">
            <option value="">Select Supplier</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmAddWarehouse">Add Supplier</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('product-js')
<script>
const allWarehouses = @json($suppliers); 
const variants = @json($variantsForSupplier);
let activeVariantId = null;

$(document).ready(function () {
  renderVariants();


  $(document).on("click", ".btn-history", function () {
    const row = $(this).closest("tr");
    const historyRow = row.next(".warehouse-history");
    historyRow.find(".history").slideToggle();
  });

  $(document).on("click", ".btn-adjust", function () {
    const row = $(this).closest("tr");
    if (validateRow(row)) {
      alert("Stock adjusted successfully!");
    }
  });

  $(document).on('click', '.remove-supplier', function () {
    let that = this;
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            let deletableVid = $(that).data('variant_id');
            let deletableSid = $(that).data('supplier_id');

            let variant = variants.find(v => v.id == deletableVid);

            if (variant) {
                let supplierIndex = variant.suppliers.findIndex(s => s.id == deletableSid);

                if (supplierIndex !== -1) {
                    variant.suppliers.splice(supplierIndex, 1);
                }
            }
            
            $(that).closest("tr").remove();
        }
    });
  });

  $(document).on("click", ".btn-add-warehouse", function () {
    activeVariantId = $(this).data("variant-id");
    const variant = variants.find(v => v.id === activeVariantId);
    
    const usedSuppliers = variant.suppliers.map(w => w.id);
    
    const available = allWarehouses.filter(w => !usedSuppliers.includes(w.id));
    
    const select = $("#warehouseSelect");
    select.empty();
    
    if (available.length) {
      select.append(`<option value="">Select Supplier</option>`);
      available.forEach(w => select.append(`<option value="${w.id}">${w.name} - ${w.email}</option>`));
    } else {
      select.append(`<option value="">No more suppliers available</option>`);
    }

    $("#addWarehouseModal").modal("show");
  });

  $("#confirmAddWarehouse").click(function () {
    const selectedWarehouse = $("#warehouseSelect").val();
    if (!selectedWarehouse) {
      alert("Please select a supplier.");
      return;
    }

    const variant = variants.find(v => v.id === activeVariantId);
    
    if (variant.suppliers.some(w => w.id == selectedWarehouse)) {
      alert("This supplier is already added.");
      return;
    }

    let selectedWarehouseObject = allWarehouses.find(item => item.id == selectedWarehouse);
    
    if (selectedWarehouseObject) {
      variant.suppliers.push(selectedWarehouseObject);

      const newRowHtml = getSupplierRowHtml(variant, selectedWarehouseObject);
      
      const tableBody = $(`#collapse${activeVariantId}`).find("tbody");
      
      tableBody.append(newRowHtml);

      $("#addWarehouseModal").modal("hide");
      
    }

    return false;
  });

  function getSupplierRowHtml(variant, w) {
    return `
      <tr>
        <td>
          <input type="hidden" name="data[product_variant_id][]" value="${variant.id}" />
          <input type="hidden" name="data[supplier_id][]" value="${w.id}" />
          <strong>${w.name}</strong>
        </td>
        <td>${w.phone_number} - (Email: ${w.email})</td>
        <td>${w.country_flag}</td>
        <td>
          <button data-variant_id="${variant.id}" data-supplier_id="${w.id}" type="button" class="remove-supplier btn btn-sm btn-outline-danger">Remove</button>
        </td>
      </tr>`;
  }


  function validateRow(row) {
    const qty = parseFloat(row.find(".qty").val());
    const reorder = parseFloat(row.find(".reorder").val());
    const max = parseFloat(row.find(".max").val());
    let valid = true;

    row.find("input").removeClass("is-invalid");

    if (isNaN(qty) || qty < 0) {
      row.find(".qty").addClass("is-invalid");
      valid = false;
    }
    if (isNaN(reorder) || reorder < 0) {
      row.find(".reorder").addClass("is-invalid");
      valid = false;
    }
    if (isNaN(max) || max < 0 || reorder > max) {
      row.find(".max").addClass("is-invalid");
      valid = false;
    }

    return valid;
  }

  function renderVariants() {
    const container = $("#inventoryContainer");
    container.empty();

    variants.forEach((variant) => {
      const warehouseRows = variant.suppliers
        .map((w) => getSupplierRowHtml(variant, w))
        .join("");

      const card = `
        <div class="card variant-card mb-3">
          <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${variant.name}</strong><br>
                <small>SKU: ${variant.sku} | Barcode: ${variant.barcode}</small>
              </div>
              <div>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#collapse${variant.id}">
                  <i class="fa fa-chevron-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary btn-add-warehouse" data-variant-id="${variant.id}">
                  <i class="fa fa-plus"></i> Add Supplier
                </button>
              </div>
            </div>
          </div>

          <div id="collapse${variant.id}" class="collapse show">
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
                <tbody>
                  ${warehouseRows}
                </tbody>
              </table>
            </div>
          </div>
        </div>`;

      container.append(card);
    });
  }
});
</script>
@endpush