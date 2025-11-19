@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
    .bundle-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .bundle-placeholder {
        border: 1px dashed #ced4da;
        border-radius: .5rem;
        padding: 1.5rem;
        text-align: center;
        color: #6c757d;
    }
    .bundle-item-card + .bundle-item-card {
        margin-top: 1rem;
    }
    .bundle-unit-options {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1.25rem;
    }
    .bundle-unit-options .form-check {
        margin-bottom: 0;
        padding-left: 0;
    }
    .bundle-unit-options .form-check-input {
        margin-right: .35rem;
        margin-left: 0;
    }
    .bundle-item-actions {
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .bundle-qty-input {
        max-width: 140px;
    }
    .bundle-modal-step {
        transition: opacity .2s ease;
    }
    .bundle-modal-step.d-none {
        opacity: 0;
    }
    .bundle-type-option {
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        padding: 1rem;
        cursor: pointer;
    }
    .bundle-type-option input {
        margin-right: .5rem;
    }
    .bundle-type-option.active {
        border-color: #0d6efd;
        background-color: rgba(13,110,253,.05);
    }
</style>
@endpush

@php
    $priceMode = $product->bundled_product_price_type == 1 ? 'fixed' : 'sum';
    $discountMode = $product->bundled_product_discount_type == 0 ? 'percentage' : 'fixed';
    $fixedValue = $product->bundled_product_fixed_price ?? 0;
    $discountValue = $product->bundled_product_discount ?? 0;
    $hasBundleOptions = !empty($bundleCatalog['simple'] ?? []) || !empty($bundleCatalog['variant'] ?? []);
@endphp

@section('product-content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bundle-card-header">
                <div>
                    <h5 class="mb-0">Bundle Products</h5>
                    <small class="text-muted">Add simple products or variable product variants to this bundle</small>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bundleModal" id="openBundleModalBtn" {{ $hasBundleOptions ? '' : 'disabled' }}>+ Add Product</button>
            </div>
            <div class="card-body">
                @unless($hasBundleOptions)
                    <div class="alert alert-warning mb-3">No simple or variable products are available to include in this bundle.</div>
                @endunless
                <div id="bundleItems" class="bundle-items"></div>
                <input type="hidden" name="bundle_items" id="bundleItemsInput">
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Pricing</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="price_mode" id="priceModeSum" value="sum" {{ $priceMode === 'sum' ? 'checked' : '' }}>
                    <label class="form-check-label" for="priceModeSum">Sum of product prices</label>
                </div>
                <div class="mb-3 text-muted small">Current total: <span class="fw-semibold" id="bundleSumValue">0.00</span></div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="price_mode" id="priceModeFixed" value="fixed" {{ $priceMode === 'fixed' ? 'checked' : '' }}>
                    <label class="form-check-label" for="priceModeFixed">Fixed bundle price</label>
                </div>
                <input type="number" step="0.01" min="0" class="form-control" id="fixedPriceInput" name="fixed_price" value="{{ $priceMode === 'fixed' ? $fixedValue : '' }}" placeholder="Enter fixed price">
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Discount</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="discount_mode" id="discountFixed" value="fixed" {{ $discountMode === 'fixed' ? 'checked' : '' }}>
                        <label class="form-check-label" for="discountFixed">Fixed discount</label>
                    </div>
                    <input type="number" step="0.01" min="0" class="form-control mt-2" id="discountFixedInput" data-role="discount-value" data-mode="fixed" value="{{ $discountMode === 'fixed' ? $discountValue : '' }}" placeholder="Amount">
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="discount_mode" id="discountPercentage" value="percentage" {{ $discountMode === 'percentage' ? 'checked' : '' }}>
                        <label class="form-check-label" for="discountPercentage">Percentage discount</label>
                    </div>
                    <input type="number" step="0.01" min="0" max="100" class="form-control mt-2" id="discountPercentageInput" data-role="discount-value" data-mode="percentage" value="{{ $discountMode === 'percentage' ? $discountValue : '' }}" placeholder="%">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bundleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product to Bundle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bundle-modal-step" id="bundleModalStep1">
                    <p class="mb-3">Choose the type of product you want to add.</p>
                    <div class="d-flex flex-column gap-3">
                        <label class="bundle-type-option" data-type="simple">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="radio" name="bundle_type" value="simple">
                                <span class="form-check-label">Simple Product</span>
                            </div>
                            <small class="text-muted d-block mt-1">Add standalone products without variants.</small>
                        </label>
                        <label class="bundle-type-option" data-type="variant">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="radio" name="bundle_type" value="variant">
                                <span class="form-check-label">Variable Product Variant</span>
                            </div>
                            <small class="text-muted d-block mt-1">Pick a specific variant from variable products.</small>
                        </label>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary" id="bundleStepNext" {{ $hasBundleOptions ? '' : 'disabled' }}>Next</button>
                    </div>
                </div>
                <div class="bundle-modal-step d-none" id="bundleModalStep2">
                    <div class="mb-4" id="simpleSelectorWrapper">
                        <label class="form-label">Select Simple Product</label>
                        <select id="simpleProductSelect" class="form-select">
                            <option value="">Choose product</option>
                            @foreach($bundleCatalog['simple'] ?? [] as $item)
                                <option value="{{ $item['key'] }}">{{ $item['label'] }} @if(!empty($item['sku'])) ({{ $item['sku'] }}) @endif</option>
                            @endforeach
                        </select>
                        @if(empty($bundleCatalog['simple']))
                            <small class="text-muted d-block mt-2">No simple products available.</small>
                        @endif
                    </div>
                    <div class="mb-4 d-none" id="variantSelectorWrapper">
                        <label class="form-label">Select Product Variant</label>
                        <select id="variantProductSelect" class="form-select">
                            <option value="">Choose variant</option>
                            @foreach($bundleCatalog['variant'] ?? [] as $item)
                                <option value="{{ $item['key'] }}">{{ $item['product_name'] }} - {{ $item['label'] }} @if(!empty($item['sku'])) ({{ $item['sku'] }}) @endif</option>
                            @endforeach
                        </select>
                        @if(empty($bundleCatalog['variant']))
                            <small class="text-muted d-block mt-2">No variants available.</small>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="bundleStepBack">Back</button>
                        <button type="button" class="btn btn-primary" id="bundleAddBtn">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('product-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const catalog = @json($bundleCatalog);
    const existingSelections = @json($existingBundleSelections);
    const bundleItemsContainer = document.getElementById('bundleItems');
    const bundleItemsInput = document.getElementById('bundleItemsInput');
    const fixedPriceInput = document.getElementById('fixedPriceInput');
    const priceRadios = document.querySelectorAll('input[name="price_mode"]');
    const discountRadios = document.querySelectorAll('input[name="discount_mode"]');
    const discountInputs = document.querySelectorAll('[data-role="discount-value"]');
    const bundleSumValue = document.getElementById('bundleSumValue');
    const typeOptions = document.querySelectorAll('.bundle-type-option');
    const step1 = document.getElementById('bundleModalStep1');
    const step2 = document.getElementById('bundleModalStep2');
    const nextBtn = document.getElementById('bundleStepNext');
    const backBtn = document.getElementById('bundleStepBack');
    const addBtn = document.getElementById('bundleAddBtn');
    const simpleSelect = document.getElementById('simpleProductSelect');
    const variantSelect = document.getElementById('variantProductSelect');
    const simpleWrapper = document.getElementById('simpleSelectorWrapper');
    const variantWrapper = document.getElementById('variantSelectorWrapper');
    const modalEl = document.getElementById('bundleModal');

    $(variantSelect).select2({
        width: "100%",
        placeholder: 'Select a variant',
        dropdownParent: $('#bundleModal')
    });

    $(simpleProductSelect).select2({
        width: "100%",
        placeholder: 'Select a product',
        dropdownParent: $('#bundleModal')
    });

    const bundleMap = {};
    (catalog.simple || []).forEach(item => bundleMap[item.key] = item);
    (catalog.variant || []).forEach(item => bundleMap[item.key] = item);

    const bundleState = [];

    function escapeHtml(text) {
        if (typeof text !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatPrice(value) {
        const number = isNaN(value) ? 0 : Number(value);
        return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderBundleItems() {
        if (!bundleState.length) {
            bundleItemsContainer.innerHTML = '<div class="bundle-placeholder">No products added yet. Use the button above to include products in this bundle.</div>';
            return;
        }

        const html = bundleState.map(item => {
            const data = bundleMap[item.key];
            if (!data) return '';
            const unitOptions = (data.units || []).map(unit => {
                const isChecked = item.selectedUnitId === unit.id;
                const unitLabel = `${escapeHtml(unit.title || 'Unit')}${unit.quantity && unit.quantity !== 1 ? ' (x' + unit.quantity + ')' : ''}`;
                return `
                    <label class="form-check form-check-inline bundle-unit-option">
                        <input class="form-check-input bundle-unit-radio" type="radio" name="unit-${item.key.replace(/[^a-zA-Z0-9_-]/g, '_')}" value="${unit.id}" data-key="${item.key}" ${isChecked ? 'checked' : ''}>
                        <span>${unitLabel} - ${formatPrice(unit.price)}</span>
                    </label>
                `;
            }).join('');

            return `
                <div class="bundle-item-card card">
                    <div class="card-body" style="border: 1px solid #00000042;border-radius: 15px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge ${data.type === 'simple' ? 'bg-primary' : 'bg-success'} text-uppercase">${data.type === 'simple' ? 'Simple' : 'Variant'}</span>
                                    <h6 class="mb-0">${escapeHtml(data.product_name || data.label)}</h6>
                                </div>
                                <div class="text-muted small">SKU: ${escapeHtml(data.sku || 'N/A')}</div>
                                ${data.type === 'variable' ? `<div class="text-muted small">Variant: ${escapeHtml(data.label)}</div>` : ''}
                            </div>
                            <button type="button" class="btn btn-danger remove-bundle-item" data-key="${item.key}">Remove</button>
                        </div>
                        <div class="mt-3">
                            <div class="fw-semibold mb-2">Units</div>
                            <div class="bundle-unit-options">
                                ${unitOptions || '<span class="text-muted small">No units configured.</span>'}
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control bundle-qty-input" data-key="${item.key}" min="0.01" step="0.01" value="${item.quantity}">
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        bundleItemsContainer.innerHTML = html;
    }

    function updateBundleInput() {
        const payload = bundleState.map(item => {
            const data = bundleMap[item.key];
            if (!data) {
                return null;
            }
            const unit = (data.units || []).find(u => u.id === item.selectedUnitId);
            if (!unit) {
                return null;
            }
            return {
                source_product_id: data.product_id,
                source_variant_id: data.variant_id,
                unit_type: unit.unit_type,
                unit_id: unit.id,
                quantity: item.quantity
            };
        }).filter(Boolean);

        bundleItemsInput.value = payload.length ? JSON.stringify(payload) : '';
        updateBundleSum();
    }

    function updateBundleSum() {
        const total = bundleState.reduce((sum, item) => {
            const data = bundleMap[item.key];
            if (!data) return sum;
            const unit = (data.units || []).find(u => u.id === item.selectedUnitId);
            if (!unit) return sum;
            return sum + (Number(unit.price) || 0) * item.quantity;
        }, 0);

        const formatted = formatPrice(total);
        bundleSumValue.textContent = formatted;
        if (getSelectedPriceMode() === 'sum') {
            fixedPriceInput.value = formatted;
        }
    }

    function getSelectedPriceMode() {
        const checked = Array.from(priceRadios).find(radio => radio.checked);
        return checked ? checked.value : 'sum';
    }

    function syncPriceMode() {
        if (getSelectedPriceMode() === 'fixed') {
            fixedPriceInput.removeAttribute('disabled');
        } else {
            fixedPriceInput.setAttribute('disabled', 'disabled');
        }
    }

    function getDiscountMode() {
        const checked = Array.from(discountRadios).find(radio => radio.checked);
        return checked ? checked.value : 'fixed';
    }

    function syncDiscountInputs() {
        const mode = getDiscountMode();
        discountInputs.forEach(input => {
            if (input.dataset.mode === mode) {
                input.disabled = false;
                input.name = 'discount_value';
            } else {
                input.disabled = true;
                input.name = '';
            }
        });
    }

    function notify(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'info', text: message });
        } else {
            alert(message);
        }
    }

    function setStep(step) {
        if (step === 1) {
            step1.classList.remove('d-none');
            step2.classList.add('d-none');
        } else {
            step1.classList.add('d-none');
            step2.classList.remove('d-none');
        }
    }

    function getSelectedType() {
        const checked = document.querySelector('input[name="bundle_type"]:checked');
        return checked ? checked.value : null;
    }

    function resetModal() {
        document.querySelectorAll('input[name="bundle_type"]').forEach(input => input.checked = false);
        typeOptions.forEach(option => option.classList.remove('active'));
        simpleSelect.value = '';
        variantSelect.value = '';
        setStep(1);
    }

    typeOptions.forEach(option => {
        option.addEventListener('click', function () {
            const input = this.querySelector('input[name="bundle_type"]');
            if (!input.disabled) {
                input.checked = true;
                typeOptions.forEach(o => o.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    nextBtn.addEventListener('click', function () {
        const selected = getSelectedType();
        if (!selected) {
            notify('Please select a product type.');
            return;
        }
        simpleWrapper.classList.toggle('d-none', selected !== 'simple');
        variantWrapper.classList.toggle('d-none', selected !== 'variant');
        setStep(2);
    });

    backBtn.addEventListener('click', function () {
        setStep(1);
    });

    addBtn.addEventListener('click', function () {
        const type = getSelectedType();
        if (!type) {
            notify('Please select a product type.');
            return;
        }
        const select = type === 'simple' ? simpleSelect : variantSelect;
        const value = select ? select.value : '';
        if (!value) {
            notify('Please choose an item to add.');
            return;
        }
        addBundleItem(value);
    });

    function addBundleItem(key) {
        if (!bundleMap[key]) {
            notify('Selected item is no longer available.');
            return;
        }
        if (bundleState.find(item => item.key === key)) {
            notify('This product or variant is already in the bundle.');
            return;
        }
        const data = bundleMap[key];
        if (!data.units || !data.units.length) {
            notify('The selected item has no units configured.');
            return;
        }
        const defaultUnit = data.units.find(unit => unit.id === data.default_unit_id) || data.units[0];
        bundleState.push({
            key,
            productId: data.product_id,
            variantId: data.variant_id,
            quantity: 1,
            selectedUnitId: defaultUnit ? defaultUnit.id : null
        });
        renderBundleItems();
        updateBundleInput();
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
        resetModal();
    }

    bundleItemsContainer.addEventListener('change', function (event) {
        if (event.target.classList.contains('bundle-unit-radio')) {
            const key = event.target.dataset.key;
            const item = bundleState.find(row => row.key === key);
            if (item) {
                item.selectedUnitId = parseInt(event.target.value, 10);
                updateBundleInput();
            }
        }

        if (event.target.classList.contains('bundle-qty-input')) {
            const key = event.target.dataset.key;
            const item = bundleState.find(row => row.key === key);
            if (item) {
                const value = parseFloat(event.target.value);
                item.quantity = isNaN(value) || value <= 0 ? 1 : value;
                event.target.value = item.quantity;
                updateBundleInput();
            }
        }
    });

    bundleItemsContainer.addEventListener('click', function (event) {
        const target = event.target.closest('.remove-bundle-item');
        if (!target) return;
        const key = target.dataset.key;
        const index = bundleState.findIndex(item => item.key === key);
        if (index === -1) return;

        const removeItem = () => {
            bundleState.splice(index, 1);
            renderBundleItems();
            updateBundleInput();
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove item?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remove'
            }).then(result => {
                if (result.isConfirmed) {
                    removeItem();
                }
            });
        } else {
            if (confirm('Remove this item from the bundle?')) {
                removeItem();
            }
        }
    });

    priceRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            syncPriceMode();
            updateBundleSum();
        });
    });

    discountRadios.forEach(radio => {
        radio.addEventListener('change', syncDiscountInputs);
    });

    document.getElementById('productStep1Form').addEventListener('submit', function (event) {
        if (!bundleState.length) {
            event.preventDefault();
            notify('Add at least one product or variant to the bundle.');
            return;
        }
        updateBundleInput();
        syncDiscountInputs();
        syncPriceMode();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        resetModal();
    });

    syncPriceMode();
    syncDiscountInputs();
    renderBundleItems();
    updateBundleInput();

    existingSelections.forEach(selection => {
        const data = bundleMap[selection.key];
        if (!data || !data.units || !data.units.length) {
            return;
        }
        const unit = data.units.find(unit => unit.id === selection.unit_id) || data.units[0];
        bundleState.push({
            key: selection.key,
            productId: data.product_id,
            variantId: data.variant_id,
            quantity: selection.quantity || 1,
            selectedUnitId: unit ? unit.id : null
        });
    });

    renderBundleItems();
    updateBundleInput();
});
</script>
@endpush