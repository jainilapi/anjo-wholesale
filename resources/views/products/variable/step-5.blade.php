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
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Global Inventory Settings</h4>
  </div>

  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" id="trackInventory" name="track_inventory_for_all_variant" @if($product->track_inventory_for_all_variant) checked @endif>
    <label class="form-check-label" for="trackInventory">Track inventory for all variants</label>
  </div>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" id="allowBackorders" name="allow_backorder" @if($product->allow_backorder) checked @endif>
    <label class="form-check-label" for="allowBackorders">Allow backorders</label>
  </div>
  <div class="form-check form-check-inline mb-4">
    <input class="form-check-input" type="checkbox" id="autoReorder" name="enable_auto_reorder_alerts" @if($product->enable_auto_reorder_alerts) checked @endif>
    <label class="form-check-label" for="autoReorder">Enable auto-reorder alerts</label>
  </div>

  <div id="inventoryContainer"></div>

  <div class="mt-4 text-end">
  </div>
</div>

<div class="modal fade" id="addWarehouseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Warehouse / Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="warehouseSelect" class="form-label">Select Warehouse / Location</label>
          <select id="warehouseSelect" class="form-select">
            <option value="">Select Warehouse / Location</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmAddWarehouse">Add</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('product-js')
<script>
const allWarehouses = @json($warehouses);
const variants = @json($variants);
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

  $(document).on("click", ".btn-add-warehouse", function () {
    activeVariantId = $(this).data("variant-id");
    const variant = variants.find(v => v.id === activeVariantId);
    
    const usedWarehouses = variant.warehouses.map(w => w.id);
    
    const available = allWarehouses.filter(w => !usedWarehouses.includes(w.id));
    
    const select = $("#warehouseSelect");
    select.empty();
    
    if (available.length) {
      select.append(`<option value="">Select Warehouse / Location</option>`);
      available.forEach(w => select.append(`<option value="${w.id}">${w.code} - ${w.name} (${w.type ? 'Warehouse' : 'Location'})</option>`));
    } else {
      select.append(`<option value="">No more warehouses / location available</option>`);
    }

    $("#addWarehouseModal").modal("show");
  });

  $("#confirmAddWarehouse").click(function () {
    const selectedWarehouse = $("#warehouseSelect").val();
    if (!selectedWarehouse) {
      alert("Please select a warehouse / location.");
      return;
    }

    const variant = variants.find(v => v.id === activeVariantId);
    
    if (variant.warehouses.some(w => w.id == selectedWarehouse)) { 
      alert("This warehouse / location is already added.");
      return;
    }

    let selectedWarehouseObject = allWarehouses.find(item => item.id == selectedWarehouse);
    
    if (selectedWarehouseObject) {
      
      const newWarehouse = {
        id: selectedWarehouseObject.id,
        name: `${selectedWarehouseObject.code} - ${selectedWarehouseObject.name}`,
        qty: 0,
        reorder: 0,
        max: 0,
        notes: "",
        lastUpdated: "—",
        history: []
      };
      
      variant.warehouses.push(newWarehouse);

      const newRowHtml = getWarehouseRowHtml(variant, newWarehouse);      
      const tableBody = $(`#collapse${activeVariantId}`).find("tbody");
      tableBody.append(newRowHtml);
      $("#addWarehouseModal").modal("hide");
    }

    return false;
  });

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

    if (isNaN(max) || max < 0 || (max > 0 && reorder > max)) { 
      row.find(".max").addClass("is-invalid");
      valid = false;
    }

    return valid;
  }

  function getWarehouseRowHtml(variant, w) {
    return `
      <tr>
        <td>
          <input type="hidden" name="data[product_variant_id][]" value="${variant.id}" />
          <input type="hidden" name="data[warehouse_id][]" value="${w.id}" />
          <strong>${w.name} (${w.type ? 'Warehouse' : 'Location'})</strong>
        </td>
        <td><input type="number" name="data[item_quantity][]" class="form-control qty" value="${w.qty}" /></td>
        <td><input type="number" name="data[item_reordering][]" class="form-control reorder" value="${w.reorder}" /></td>
        <td><input type="number" name="data[item_max][]" class="form-control max" value="${w.max}" /></td>
        <td><textarea class="form-control notes" name="data[item_notes][]" rows="1">${w.notes || ""}</textarea></td>
        <td class="text-end">
          <small class="text-muted d-block mb-1">Last updated: ${w.lastUpdated}</small>
          <button type="button" class="btn btn-sm btn-outline-primary btn-history">View History</button>
          <button type="button" class="btn btn-sm btn-outline-success btn-adjust">Adjust Stock</button>
        </td>
      </tr>
      <tr class="warehouse-history" style="display:none;">
        <td colspan="6">
          <div class="history mt-3">
            <ul class="stepper mb-0">
              ${
                w.history.length
                  ? w.history.map(h => `<li class="step">${h}</li>`).join("")
                  : "<li class='text-muted'>No history available</li>"
              }
            </ul>
          </div>
        </td>
      </tr>`;
  }

  function renderVariants() {
    const container = $("#inventoryContainer");
    container.empty();

    variants.forEach((variant) => {
      const warehouseRows = variant.warehouses
        .map((w) => getWarehouseRowHtml(variant, w))
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
                  <i class="fa fa-plus"></i> Add Warehouse / Location
                </button>
              </div>
            </div>
          </div>

          <div id="collapse${variant.id}" class="collapse show">
            <div class="card-body">
              <table class="table align-middle table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Warehouse</th>
                    <th width="12%">Quantity</th>
                    <th width="12%">Reorder</th>
                    <th width="12%">Max Stock</th>
                    <th>Notes</th>
                    <th width="25%" class="text-end">Actions</th>
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