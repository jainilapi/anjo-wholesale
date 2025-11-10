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
.parent-unit-display:focus {
    outline: none;
}
.parent-unit-display {
    border: none;
    color: grey;
    display: block;
}
</style>
@endpush

@section('product-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @foreach($product->variants as $variant)
                @php
                    $variantIndex = $loop->index;
                    $variantBaseUnit = $baseUnitsForAllV[$variant->id] ?? null;
                    $variantAdditionalUnits = $additionalUnitsForAllV[$variant->id] ?? collect();
                    $variantUnitHierarchy = $unitHierarchy[$variant->id] ?? [];
                @endphp

                <input type="hidden" name="variants[{{ $variantIndex }}][id]" value="{{ $variant->id }}">
                <div class="card variant-card mb-3" data-variant-index="{{ $variantIndex }}">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $variant->name }}</strong><br>
                                <small>SKU: {{ $variant->sku }} | Barcode: {{ $variant->barcode }}</small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#collapse{{ $variant->id }}">
                                    <i class="fa fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="collapse{{ $variant->id }}" class="collapse show">
                        <div class="card-body">
                            <div class="mb-4">
                                <label for="baseUnitSelect_{{ $variantIndex }}" class="form-label">Base Unit <span class="text-danger">*</span></label>
                                <select class="form-select base-unit-select" 
                                        id="baseUnitSelect_{{ $variantIndex }}" 
                                        name="variants[{{ $variantIndex }}][base_unit_id]" 
                                        data-variant="{{ $variantIndex }}"
                                        required>
                                    <option value="">Select Base Unit</option>
                                    @foreach($availableUnits as $unit)
                                        <option value="{{ $unit->id }}" 
                                            {{ $variantBaseUnit && $variantBaseUnit->unit_id == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Please select a base unit.
                                </div>
                                <div class="form-text">
                                    <div class="form-check form-switch d-inline-block ms-2">
                                        <input class="form-check-input default-selling-toggle" 
                                            type="checkbox" 
                                            id="baseUnitDefault_{{ $variantIndex }}" 
                                            name="variants[{{ $variantIndex }}][base_unit_is_default_selling]" 
                                            value="1"
                                            data-variant="{{ $variantIndex }}"
                                            {{ !$variantAdditionalUnits->where('is_default_selling_unit', 1)->count() ? 'checked' : '' }}>
                                        <label class="form-check-label" for="baseUnitDefault_{{ $variantIndex }}">
                                            Default Selling Unit
                                        </label>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 additional-units-section" id="additionalUnitsSection_{{ $variantIndex }}" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label">Additional Units</label>
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm add-unit-btn" 
                                            data-variant="{{ $variantIndex }}">
                                        <i class="fas fa-plus"></i> Add Unit
                                    </button>
                                </div>
                                
                                <div class="additional-units-container" id="additionalUnitsContainer_{{ $variantIndex }}">
                                    @if(isset($variantUnitHierarchy) && count($variantUnitHierarchy) > 0)
                                        @foreach($variantUnitHierarchy as $index => $unitData)
                                            <div class="unit-row mb-3 p-3 border rounded" 
                                                data-level="{{ $index }}" 
                                                data-index="{{ $index }}" 
                                                data-variant="{{ $variantIndex }}"
                                                data-unit-id="{{ $unitData['id'] ?? '' }}">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                                                        <select class="form-select unit-select" 
                                                                name="variants[{{ $variantIndex }}][additional_units][{{ $index }}][unit_id]" 
                                                                data-variant="{{ $variantIndex }}"
                                                                required>
                                                            <option value="">Select Unit</option>
                                                            @foreach($availableUnits as $unit)
                                                                <option value="{{ $unit->id }}" {{ ($unitData['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                                                                    {{ $unit->title }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select a unit.
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                        <input type="number" 
                                                            class="form-control quantity-input" 
                                                            name="variants[{{ $variantIndex }}][additional_units][{{ $index }}][quantity]" 
                                                            value="{{ $unitData['quantity'] ?? '' }}"
                                                            data-variant="{{ $variantIndex }}"
                                                            min="0.01" step="0.01" placeholder="1.00" required>
                                                        <div class="invalid-feedback">
                                                            Please enter a valid quantity.
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Per Parent Unit</label>
                                                        <input type="text" class="parent-unit-display" readonly 
                                                            value="{{ trim($unitData['parent_name'] ?? 'Select base unit first') }}">
                                                        <input type="hidden" class="parent-unit-id" 
                                                            name="variants[{{ $variantIndex }}][additional_units][{{ $index }}][parent_id]" 
                                                            value="{{ $unitData['parent_id'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check form-switch mt-4">
                                                            <input class="form-check-input default-selling-toggle" 
                                                                type="checkbox" 
                                                                name="variants[{{ $variantIndex }}][additional_units][{{ $index }}][is_default_selling_unit]" 
                                                                value="1"
                                                                id="defaultSelling_{{ $variantIndex }}_{{ $index }}"
                                                                data-variant="{{ $variantIndex }}"
                                                                {{ ($unitData['is_default_selling_unit'] ?? false) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="defaultSelling_{{ $variantIndex }}_{{ $index }}">
                                                                Default Selling Unit
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small class="text-muted"></small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <label class="form-label">&nbsp;</label>
                                                        <div>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger btn-sm remove-unit-btn" 
                                                                    data-variant="{{ $variantIndex }}"
                                                                    title="Remove Unit">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="conversion-formula mt-2">
                                                    <div class="d-flex align-items-center">
                                                        <small class="text-muted conversion-text fw-bold">
                                                            @if(isset($unitData['conversion_formula']))
                                                                <i class="fas fa-equals text-primary me-1"></i>{{ $unitData['conversion_formula'] }}
                                                            @else
                                                                <i class="fas fa-info-circle text-muted me-1"></i>Configure unit to see conversion
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <div class="conversion-details mt-1" style="display: none;">
                                                        <small class="text-info conversion-breakdown"></small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                
                                <div class="no-units-message text-muted text-center py-3" 
                                    id="noUnitsMessage_{{ $variantIndex }}" 
                                    style="display: none;">
                                    <i class="fas fa-info-circle"></i> No additional units configured. Click "Add Unit" to create hierarchical units.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('product-js')
<script>
    $(document).ready(function() {
        const maxVariants = {{ $product->variants()->count() }};
        
        for (let variantIndex = 0; variantIndex < maxVariants; variantIndex++) {
            initializeVariant(variantIndex);
        }

        $('#productStep1Form').on('submit', function(e) {
            let isValid = true;
            let validationErrors = [];
            
            clearAllValidationStates();
            
            // Validate all variants
            for (let variantIndex = 0; variantIndex < maxVariants; variantIndex++) {
                const variantErrors = validateVariant(variantIndex);
                if (variantErrors.length > 0) {
                    isValid = false;
                    validationErrors.push(`Variant ${variantIndex + 1}: ${variantErrors.join(', ')}`);
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                displayValidationErrors(validationErrors);
                scrollToFirstError();
                return false;
            }
            
            showSuccessMessage('Form validation passed. Submitting...');
        });
    });

    function initializeVariant(variantIndex) {
        initializeAllSelect2ForVariant(variantIndex);

        $(`#baseUnitSelect_${variantIndex}`).on('change', function() {
            validateBaseUnit(variantIndex);
            updateBaseUnitToggleState(variantIndex);
            updateAllParentUnitDisplays(variantIndex);
            updateAllConversionFormulas(variantIndex);
            validateNoDuplicateUnits(variantIndex);
            refreshAllSelect2ForVariant(variantIndex);
            performRealTimeValidation(variantIndex);
            toggleAdditionalUnitsSection(variantIndex);
        });
        
        $(`#baseUnitSelect_${variantIndex}`).on('select2:select', function(e) {
            const selectedData = e.params.data;
            showSuccessMessage(`Variant ${variantIndex + 1}: Base unit set to: ${selectedData.text}`);
        });

        $(`#baseUnitSelect_${variantIndex}`).on('blur', function() {
            validateBaseUnit(variantIndex);
        });

        $(`#baseUnitDefault_${variantIndex}`).on('change', function() {
            handleDefaultSellingToggle(this, variantIndex);
            performRealTimeValidation(variantIndex);
        });

        $(`.add-unit-btn[data-variant="${variantIndex}"]`).on('click', function() {
            addNewUnitRow(variantIndex);
        });

        initializeExistingUnitRows(variantIndex);
        
        toggleAdditionalUnitsSection(variantIndex);
        
        updateBaseUnitToggleState(variantIndex);
        updateDefaultSellingIndicators(variantIndex);
    }

    function validateVariant(variantIndex) {
        const errors = [];
        
        const baseValidation = validateBaseUnit(variantIndex);
        if (!baseValidation.isValid) {
            errors.push(...baseValidation.errors);
        }
        
        const hierarchyValidation = validateUnitHierarchy(variantIndex);
        if (!hierarchyValidation.isValid) {
            errors.push(...hierarchyValidation.errors);
        }
        
        const defaultSellingValidation = validateDefaultSellingUnit(variantIndex);
        if (!defaultSellingValidation.isValid) {
            errors.push(...defaultSellingValidation.errors);
        }
        
        const duplicateValidation = validateNoDuplicateUnits(variantIndex);
        if (!duplicateValidation.isValid) {
            errors.push(...duplicateValidation.errors);
        }
        
        const conversionValidation = validateConversionCalculations(variantIndex);
        if (!conversionValidation.isValid) {
            errors.push(...conversionValidation.errors);
        }
        
        return errors;
    }

    function validateBaseUnit(variantIndex) {
        const baseUnitSelect = $(`#baseUnitSelect_${variantIndex}`);
        const value = baseUnitSelect.val();
        const errors = [];
        
        if (!value || value === '') {
            errors.push('Base unit is required');
            baseUnitSelect.removeClass('is-valid').addClass('is-invalid');
            showFieldError(baseUnitSelect, 'Please select a base unit');
        } else {
            baseUnitSelect.removeClass('is-invalid').addClass('is-valid');
            clearFieldError(baseUnitSelect);
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function validateUnitSelection(selectElement) {
        const $select = $(selectElement);
        const value = $select.val();
        const errors = [];
        
        if (!value || value === '') {
            errors.push('Unit selection is required');
            $select.removeClass('is-valid').addClass('is-invalid');
            showFieldError($select, 'Please select a unit');
        } else {
            $select.removeClass('is-invalid').addClass('is-valid');
            clearFieldError($select);
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function validateQuantityInput(inputElement) {
        const $input = $(inputElement);
        const value = $input.val();
        const numValue = parseFloat(value);
        const errors = [];
        
        if (!value || value.trim() === '') {
            errors.push('Quantity is required');
            $input.removeClass('is-valid').addClass('is-invalid');
            showFieldError($input, 'Please enter a quantity');
        } else if (isNaN(numValue)) {
            errors.push('Quantity must be a valid number');
            $input.removeClass('is-valid').addClass('is-invalid');
            showFieldError($input, 'Please enter a valid number');
        } else if (numValue <= 0) {
            errors.push('Quantity must be greater than 0');
            $input.removeClass('is-valid').addClass('is-invalid');
            showFieldError($input, 'Quantity must be greater than 0');
        } else if (numValue > 999999) {
            errors.push('Quantity is too large (maximum: 999,999)');
            $input.removeClass('is-valid').addClass('is-invalid');
            showFieldError($input, 'Quantity is too large');
        } else {
            $input.removeClass('is-invalid').addClass('is-valid');
            clearFieldError($input);
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function showFieldError(element, message) {
        const $element = $(element);
        $element.addClass('is-invalid').removeClass('is-valid');
        
        let feedback = $element.siblings('.invalid-feedback');
        if (feedback.length === 0) {
            feedback = $('<div class="invalid-feedback"></div>');
            $element.after(feedback);
        }
        feedback.text(message).show();
    }

    function clearFieldError(element) {
        const $element = $(element);
        $element.removeClass('is-invalid');
        $element.siblings('.invalid-feedback').hide();
    }

    function clearAllValidationStates() {
        $('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $('.invalid-feedback').hide();
        $('.alert-danger, .alert-warning').remove();
    }

    function validateNoDuplicateUnits(variantIndex) {
        const errors = [];
        const selectedUnits = [];
        const baseUnitId = $(`#baseUnitSelect_${variantIndex}`).val();
        
        if (baseUnitId) {
            selectedUnits.push({
                id: baseUnitId,
                name: $(`#baseUnitSelect_${variantIndex} option:selected`).text(),
                element: $(`#baseUnitSelect_${variantIndex}`),
                type: 'base'
            });
        }
        
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function(index) {
            const row = $(this);
            const unitSelect = row.find('.unit-select');
            const unitId = unitSelect.val();
            
            if (unitId) {
                selectedUnits.push({
                    id: unitId,
                    name: unitSelect.find('option:selected').text(),
                    element: unitSelect,
                    type: 'additional',
                    level: index + 1
                });
            }
        });
        
        const unitCounts = {};
        selectedUnits.forEach(function(unit) {
            if (unitCounts[unit.id]) {
                unitCounts[unit.id].push(unit);
            } else {
                unitCounts[unit.id] = [unit];
            }
        });
        
        Object.keys(unitCounts).forEach(function(unitId) {
            const units = unitCounts[unitId];
            if (units.length > 1) {
                const unitName = units[0].name;
                errors.push(`Unit "${unitName}" is selected multiple times`);
                
                units.forEach(function(unit) {
                    showFieldError(unit.element, `Duplicate unit: ${unitName}`);
                });
            }
        });
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function validateConversionCalculations(variantIndex) {
        const errors = [];
        const rows = $(`#additionalUnitsContainer_${variantIndex} .unit-row`);
        
        rows.each(function(index) {
            const row = $(this);
            const level = parseInt(row.data('level'));
            const unitName = row.find('.unit-select option:selected').text();
            const quantity = parseFloat(row.find('.quantity-input').val());
            
            if (unitName && unitName !== 'Select Unit' && quantity) {
                const totalBaseUnits = calculateTotalBaseUnits(row, variantIndex);
                
                if (totalBaseUnits === null) {
                    errors.push(`Cannot calculate conversion for "${unitName}" - check parent unit configurations`);
                    row.find('.conversion-formula').addClass('formula-error');
                } else if (totalBaseUnits <= 0) {
                    errors.push(`Invalid conversion calculation for "${unitName}" - result must be positive`);
                    row.find('.conversion-formula').addClass('formula-error');
                } else if (totalBaseUnits > 1000000) {
                    errors.push(`Conversion for "${unitName}" results in extremely large numbers - please check quantities`);
                    row.find('.conversion-formula').addClass('formula-error');
                } else {
                    row.find('.conversion-formula').removeClass('formula-error').addClass('formula-success');
                }
            }
        });
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    function displayValidationErrors(errors) {
        if (errors.length === 0) return;
        
        $('.alert-danger').remove();
        
        const errorList = errors.map(error => `<li>${error}</li>`).join('');
        const alert = $(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors:</h6>
                <ul class="mb-0">${errorList}</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('.container-fluid').prepend(alert);
        
        setTimeout(function() {
            alert.fadeOut();
        }, 10000);
    }

    function scrollToFirstError() {
        const firstError = $('.is-invalid').first();
        if (firstError.length) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
            
            if (firstError.is('input, select')) {
                setTimeout(function() {
                    firstError.focus();
                }, 600);
            }
        }
    }

    function updateBaseUnitToggleState(variantIndex) {
        const baseUnitSelected = $(`#baseUnitSelect_${variantIndex}`).val() !== '';
        const baseUnitToggle = $(`#baseUnitDefault_${variantIndex}`);
        
        if (baseUnitSelected) {
            baseUnitToggle.prop('disabled', false);
            if (!hasDefaultSellingUnit(variantIndex)) {
                baseUnitToggle.prop('checked', true);
            }
        } else {
            baseUnitToggle.prop('disabled', true).prop('checked', false);
        }
    }

    function handleDefaultSellingToggle(toggleElement, variantIndex) {
        const $toggle = $(toggleElement);
        const isChecked = $toggle.prop('checked');
        
        if (isChecked) {
            $(`.variant-card[data-variant-index="${variantIndex}"] .default-selling-toggle`).not($toggle).each(function() {
                const $otherToggle = $(this);
                if ($otherToggle.prop('checked')) {
                    $otherToggle.prop('checked', false);
                    
                    const $otherLabel = $otherToggle.next('label');
                    $otherLabel.addClass('text-muted');
                    setTimeout(function() {
                        $otherLabel.removeClass('text-muted');
                    }, 1000);
                }
            });
            
            const $label = $toggle.next('label');
            $label.addClass('fw-bold text-primary');
            setTimeout(function() {
                $label.removeClass('fw-bold');
            }, 2000);
            
            const unitName = getToggleUnitName($toggle, variantIndex);
            showSuccessMessage(`Variant ${variantIndex + 1}: Default selling unit set to: ${unitName}`);
            
        } else {
            const baseUnitToggle = $(`#baseUnitDefault_${variantIndex}`);
            if (!hasAnyDefaultSellingUnit(variantIndex)) {
                baseUnitToggle.prop('checked', true);
                showSuccessMessage(`Variant ${variantIndex + 1}: Default selling unit reverted to base unit`);
            }
        }
        
        updateDefaultSellingIndicators(variantIndex);
    }
    
    function getToggleUnitName(toggleElement, variantIndex) {
        const $toggle = $(toggleElement);
        
        if ($toggle.attr('id') === `baseUnitDefault_${variantIndex}`) {
            const baseUnitText = $(`#baseUnitSelect_${variantIndex} option:selected`).text();
            return baseUnitText !== 'Select Base Unit' ? baseUnitText : 'Base Unit';
        } else {
            const $row = $toggle.closest('.unit-row');
            const unitText = $row.find('.unit-select option:selected').text();
            return unitText !== 'Select Unit' ? unitText : 'Additional Unit';
        }
    }
    
    function hasAnyDefaultSellingUnit(variantIndex) {
        return $(`.variant-card[data-variant-index="${variantIndex}"] .default-selling-toggle:checked`).length > 0;
    }
    
    function updateDefaultSellingIndicators(variantIndex) {
        $(`.variant-card[data-variant-index="${variantIndex}"] .default-selling-toggle`).each(function() {
            const $toggle = $(this);
            const $label = $toggle.next('label');
            const $row = $toggle.closest('.unit-row, .form-text');
            
            if ($toggle.prop('checked')) {
                $label.addClass('text-primary');
                $row.addClass('border-primary');
                
                if (!$label.find('.badge').length) {
                    $label.append(' <span class="badge bg-success ms-1">Active</span>');
                }
            } else {
                $label.removeClass('text-primary fw-bold');
                $row.removeClass('border-primary');
                
                $label.find('.badge').remove();
            }
        });
    }

    function initializeExistingUnitRows(variantIndex) {
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function(index) {
            const row = $(this);
            
            const unitSelect = row.find('.unit-select');
            if (unitSelect.length && !unitSelect.hasClass('select2-hidden-accessible')) {
                unitSelect.select2({
                    placeholder: 'Select Unit',
                    allowClear: true,
                    width: '100%'
                });
            }
            
            addRowEventHandlers(row, variantIndex);
            
            updateConversionFormula(row, variantIndex);
        });
        
        updateNoUnitsMessage(variantIndex);
    }

    function hasDefaultSellingUnit(variantIndex) {
        return hasAnyDefaultSellingUnit(variantIndex);
    }

    function showSuccessMessage(message, duration = 3000) {
        
    }

    function performRealTimeValidation(variantIndex) {
        const debouncedValidation = debounce(function() {
            const variantErrors = validateVariant(variantIndex);
            
            if (variantErrors.length === 0) {
                showValidationSuccess(variantIndex);
            } else {
                showValidationWarnings(variantErrors, variantIndex);
            }
        }, 500);
        
        debouncedValidation();
    }
    
    function showValidationSuccess(variantIndex) {
        $(`.variant-card[data-variant-index="${variantIndex}"] .alert-warning`).fadeOut();
        
        const submitBtn = $('button[type="submit"]');
        submitBtn.removeClass('btn-secondary').addClass('btn-primary');
    }
    
    function showValidationWarnings(errors, variantIndex) {
        if (errors.length === 0) return;
        
        const submitBtn = $('button[type="submit"]');
        submitBtn.removeClass('btn-primary').addClass('btn-secondary');
    }
    
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            
            if (callNow) func.apply(context, args);
        };
    }

    function toggleAdditionalUnitsSection(variantIndex) {
        const baseUnitSelected = $(`#baseUnitSelect_${variantIndex}`).val() !== '';
        const additionalUnitsSection = $(`#additionalUnitsSection_${variantIndex}`);
        
        if (baseUnitSelected) {
            additionalUnitsSection.show();
            updateNoUnitsMessage(variantIndex);
        } else {
            additionalUnitsSection.hide();
        }
    }

    function updateNoUnitsMessage(variantIndex) {
        const hasUnits = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).length > 0;
        const noUnitsMessage = $(`#noUnitsMessage_${variantIndex}`);
        
        if (hasUnits) {
            noUnitsMessage.hide();
        } else {
            noUnitsMessage.show();
        }
    }

    function addNewUnitRow(variantIndex) {
        const container = $(`#additionalUnitsContainer_${variantIndex}`);
        const currentRows = container.find('.unit-row').length;
        
        if (currentRows >= 5) {
            showErrorMessage(`Variant ${variantIndex + 1}: Maximum hierarchy depth reached (5 levels maximum)`);
            return;
        }
        
        const newIndex = currentRows;
        
        const newRow = createUnitRowTemplate(newIndex, variantIndex);
        container.append(newRow);
        
        initializeNewRowSelect2(newIndex, variantIndex);
        
        updateNoUnitsMessage(variantIndex);
        
        updateAllParentUnitDisplays(variantIndex);
        
        showSuccessMessage(`Variant ${variantIndex + 1}: Added new unit level ${newIndex + 1}`);
        
        setTimeout(function() {
            const newRow = $(`.unit-row[data-variant="${variantIndex}"][data-index="${newIndex}"]`);
            newRow.find('.unit-select').select2('open');
        }, 100);
    }

    function createUnitRowTemplate(index, variantIndex) {
        const level = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).length;
        const availableUnits = @json($availableUnits);
        
        let unitOptions = '<option value="">Select Unit</option>';
        availableUnits.forEach(function(unit) {
            unitOptions += `<option value="${unit.id}">${unit.title}</option>`;
        });
        
        const template = `
            <div class="unit-row mb-3 p-3 border rounded" data-level="${level}" data-index="${index}" data-variant="${variantIndex}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select unit-select" name="variants[${variantIndex}][additional_units][${index}][unit_id]" data-variant="${variantIndex}" required>
                            ${unitOptions}
                        </select>
                        <div class="invalid-feedback">
                            Please select a unit.
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input" 
                               name="variants[${variantIndex}][additional_units][${index}][quantity]" 
                               data-variant="${variantIndex}"
                               min="0.01" step="0.01" placeholder="1.00" required>
                        <div class="invalid-feedback">
                            Please enter a valid quantity.
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Per Parent Unit</label>
                        <input type="text" class="parent-unit-display" readonly 
                               placeholder="Select base unit first">
                        <input type="hidden" class="parent-unit-id" name="variants[${variantIndex}][additional_units][${index}][parent_id]">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input default-selling-toggle" type="checkbox" 
                                   name="variants[${variantIndex}][additional_units][${index}][is_default_selling_unit]" value="1"
                                   id="defaultSelling_${variantIndex}_${index}"
                                   data-variant="${variantIndex}">
                            <label class="form-check-label" for="defaultSelling_${variantIndex}_${index}">
                                Default Selling Unit
                            </label>
                        </div>
                        <div class="form-text">
                            <small class="text-muted"></small>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-unit-btn" 
                                    data-variant="${variantIndex}"
                                    title="Remove Unit">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="conversion-formula mt-2">
                    <div class="d-flex align-items-center">
                        <small class="text-muted conversion-text fw-bold"></small>
                    </div>
                    <div class="conversion-details mt-1" style="display: none;">
                        <small class="text-info conversion-breakdown"></small>
                    </div>
                </div>
            </div>
        `;
        
        return $(template);
    }

    function initializeNewRowSelect2(index, variantIndex) {
        const row = $(`.unit-row[data-variant="${variantIndex}"][data-index="${index}"]`);
        const unitSelect = row.find('.unit-select');
        
        unitSelect.select2({
            placeholder: 'Select Unit',
            allowClear: true,
            width: '100%',
            dropdownParent: row,
            templateResult: formatUnitOption,
            templateSelection: formatUnitSelection,
            escapeMarkup: function(markup) { return markup; }
        });
        
        addRowEventHandlers(row, variantIndex);
        
        unitSelect.on('select2:select', function(e) {
            const selectedData = e.params.data;
            showSuccessMessage(`Variant ${variantIndex + 1}: Selected unit: ${selectedData.text}`);
            $(this).trigger('change');
        });
        
        unitSelect.on('select2:clear', function(e) {
            showSuccessMessage(`Variant ${variantIndex + 1}: Unit selection cleared`);
            $(this).trigger('change');
        });
    }
    
    function formatUnitOption(unit) {
        if (!unit.id) {
            return unit.text;
        }
        
        const isUsed = isUnitAlreadySelected(unit.id);
        const usageClass = isUsed ? 'text-muted' : '';
        const usageIcon = isUsed ? '<i class="fas fa-exclamation-triangle text-primary me-1"></i>' : '';
        
        return $(`<span class="${usageClass}">${usageIcon}${unit.text}</span>`);
    }
    
    function formatUnitSelection(unit) {
        return unit.text;
    }
    
    function isUnitAlreadySelected(unitId) {
        let isUsed = false;
        
        if ($('#baseUnitSelect').val() == unitId) {
            isUsed = true;
        }
        
        $('.unit-select').each(function() {
            if ($(this).val() == unitId) {
                isUsed = true;
            }
        });
        
        return isUsed;
    }
    
    function initializeAllSelect2ForVariant(variantIndex) {
        $(`#baseUnitSelect_${variantIndex}`).select2({
            placeholder: 'Select Base Unit',
            allowClear: true,
            width: '100%',
            templateResult: formatUnitOption,
            templateSelection: formatUnitSelection,
            escapeMarkup: function(markup) { return markup; }
        });
        
        $(`.variant-card[data-variant-index="${variantIndex}"] .unit-select`).each(function(index) {
            const $select = $(this);
            if (!$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    placeholder: 'Select Unit',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $select.closest('.unit-row'),
                    templateResult: formatUnitOption,
                    templateSelection: formatUnitSelection,
                    escapeMarkup: function(markup) { return markup; }
                });
            }
        });
    }
    
    function refreshAllSelect2ForVariant(variantIndex) {
        $(`#baseUnitSelect_${variantIndex}`).select2('destroy').select2({
            placeholder: 'Select Base Unit',
            allowClear: true,
            width: '100%',
            templateResult: formatUnitOption,
            templateSelection: formatUnitSelection,
            escapeMarkup: function(markup) { return markup; }
        });
        
        $(`.variant-card[data-variant-index="${variantIndex}"] .unit-select`).each(function() {
            const $select = $(this);
            const currentValue = $select.val();
            const row = $select.closest('.unit-row');
            
            $select.select2('destroy').select2({
                placeholder: 'Select Unit',
                allowClear: true,
                width: '100%',
                dropdownParent: row,
                templateResult: formatUnitOption,
                templateSelection: formatUnitSelection,
                escapeMarkup: function(markup) { return markup; }
            });
            
            if (currentValue) {
                $select.val(currentValue).trigger('change.select2');
            }
        });
    }

    function addRowEventHandlers(row, variantIndex) {
        const unitSelect = row.find('.unit-select');
        const quantityInput = row.find('.quantity-input');
        const defaultToggle = row.find('.default-selling-toggle');
        const removeBtn = row.find('.remove-unit-btn');
        
        unitSelect.on('change', function() {
            const $select = $(this);
            const selectedText = $select.find('option:selected').text();
            
            validateUnitSelection(this);
            updateParentUnitDisplay(row, variantIndex);
            updateConversionFormula(row, variantIndex);
            updateConversionChain(row, variantIndex);
            updateAllParentUnitDisplays(variantIndex);
            updateAllConversionFormulas(variantIndex);
            validateNoDuplicateUnits(variantIndex);
            refreshAllSelect2ForVariant(variantIndex);
            
            if (selectedText && selectedText !== 'Select Unit') {
                showSuccessMessage(`Variant ${variantIndex + 1}: Unit selected: ${selectedText}`, 2000);
            }
            
            highlightAffectedFormulas(row, variantIndex);
        });
        
        unitSelect.on('blur', function() {
            validateUnitSelection(this);
        });
        
        quantityInput.on('input', function() {
            const input = this;
            const $input = $(input);
            
            validateQuantityInput(input);
            clearTimeout($input.data('debounceTimeout'));
            
            const formulaContainer = row.find('.conversion-formula');
            formulaContainer.addClass('formula-highlight');
            
            const timeout = setTimeout(function() {
                updateConversionFormula(row, variantIndex);
                updateConversionChain(row, variantIndex);
                validateConversionCalculations(variantIndex);
                updateAllParentUnitDisplays(variantIndex);
                
                formulaContainer.removeClass('formula-highlight');
                formulaContainer.addClass('formula-success');
                setTimeout(function() {
                    formulaContainer.removeClass('formula-success');
                }, 1000);
            }, 300);
            
            $input.data('debounceTimeout', timeout);
        });
        
        quantityInput.on('change', function() {
            const $input = $(this);
            clearTimeout($input.data('debounceTimeout'));
            
            validateQuantityInput(this);
            updateConversionFormula(row, variantIndex);
            updateConversionChain(row, variantIndex);
            validateConversionCalculations(variantIndex);
            updateAllParentUnitDisplays(variantIndex);
        });
        
        quantityInput.on('blur', function() {
            validateQuantityInput(this);
            updateConversionFormula(row, variantIndex);
            updateConversionChain(row, variantIndex);
        });
        
        quantityInput.on('keypress', function(e) {
            if ([46, 8, 9, 27, 13, 110, 190].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
        
        defaultToggle.on('change', function() {
            handleDefaultSellingToggle(this, variantIndex);
            validateDefaultSellingUnit(variantIndex);
            performRealTimeValidation(variantIndex);
        });
        
        removeBtn.on('click', function() {
            removeUnitRow(row, variantIndex);
        });
        
        row.on('mouseenter', function() {
            $(this).addClass('border-primary');
        }).on('mouseleave', function() {
            $(this).removeClass('border-primary');
        });
    }

    function highlightAffectedFormulas(changedRow, variantIndex) {
        const changedLevel = parseInt(changedRow.data('level'));
        
        const changedFormula = changedRow.find('.conversion-formula');
        changedFormula.addClass('formula-highlight');
        
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function() {
            const row = $(this);
            const level = parseInt(row.data('level'));
            
            if (level > changedLevel) {
                const formula = row.find('.conversion-formula');
                formula.addClass('formula-highlight');
                
                setTimeout(function() {
                    formula.removeClass('formula-highlight').addClass('formula-success');
                    setTimeout(function() {
                        formula.removeClass('formula-success');
                    }, 1000);
                }, 500);
            }
        });
        
        setTimeout(function() {
            changedFormula.removeClass('formula-highlight').addClass('formula-success');
            setTimeout(function() {
                changedFormula.removeClass('formula-success');
            }, 1000);
        }, 500);
    }

    function updateParentUnitDisplay(row, variantIndex) {
        const level = parseInt(row.data('level'));
        const parentDisplay = row.find('.parent-unit-display');
        const parentIdInput = row.find('.parent-unit-id');
        
        if (level === 0) {
            const baseUnitText = $(`#baseUnitSelect_${variantIndex} option:selected`).text();
            if (baseUnitText && baseUnitText !== 'Select Base Unit') {
                parentDisplay.val(baseUnitText.trim());
                parentIdInput.val('');
            } else {
                parentDisplay.val('Select base unit first');
                parentIdInput.val('');
            }
        } else {
            const previousRow = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).eq(level - 1);
            const previousUnitText = previousRow.find('.unit-select option:selected').text();
            const previousUnitId = previousRow.data('unit-id');
            
            if (previousUnitText && previousUnitText !== 'Select Unit') {
                parentDisplay.val(previousUnitText);
                parentIdInput.val(previousUnitId);
            } else {
                parentDisplay.val('Configure previous unit first');
                parentIdInput.val('');
            }
        }
    }

    function updateAllParentUnitDisplays(variantIndex) {
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function() {
            updateParentUnitDisplay($(this), variantIndex);
        });
    }

    function updateConversionFormula(row, variantIndex) {
        const unitText = row.find('.unit-select option:selected').text();
        const quantity = row.find('.quantity-input').val();
        const parentText = row.find('.parent-unit-display').val();
        const formulaElement = row.find('.conversion-text');
        const breakdownElement = row.find('.conversion-breakdown');
        const formulaContainer = row.find('.conversion-formula');
        const detailsContainer = row.find('.conversion-details');
        
        if (unitText && unitText !== 'Select Unit' && quantity && parentText && 
            parentText !== 'Select base unit first' && parentText !== 'Configure previous unit first') {
            
            const level = parseInt(row.data('level'));
            const quantityNum = parseFloat(quantity);
            
            if (isNaN(quantityNum) || quantityNum <= 0) {
                formulaElement.html('<i class="fas fa-exclamation-triangle text-primary me-1"></i>Invalid quantity - must be greater than 0');
                detailsContainer.hide();
                return;
            }
            
            let formula = `1 ${unitText} = ${quantityNum} ${parentText}`;
            let breakdown = '';
            
            if (level > 0) {
                const totalBaseUnits = calculateTotalBaseUnits(row, variantIndex);
                const baseUnitText = $(`#baseUnitSelect_${variantIndex} option:selected`).text();
                
                if (totalBaseUnits && totalBaseUnits > 0 && baseUnitText && baseUnitText !== 'Select Base Unit') {
                    formula += ` = ${totalBaseUnits} ${baseUnitText}`;
                    breakdown = buildConversionBreakdown(row, totalBaseUnits, baseUnitText, variantIndex);
                } else if (totalBaseUnits === null) {
                    formula += ' = <span class="text-primary">Configure parent units first</span>';
                }
            }
            
            formulaElement.html(`<i class="fas fa-equals text-primary me-1"></i>${formula}`);
            
            if (breakdown) {
                breakdownElement.html(`${breakdown}`);
                detailsContainer.show();
            } else {
                detailsContainer.hide();
            }
            
        } else {
            let message = '<i class="fas fa-info-circle text-muted me-1"></i>';
            if (!unitText || unitText === 'Select Unit') {
                message += 'Select a unit to begin';
            } else if (!quantity) {
                message += 'Enter quantity to see conversion';
            } else {
                message += 'Configure parent unit to see conversion';
            }
            
            detailsContainer.hide();
        }
    }
    
    function buildConversionBreakdown(row, totalBaseUnits, baseUnitText, variantIndex) {
        const level = parseInt(row.data('level'));
        const unitText = row.find('.unit-select option:selected').text();
        const quantity = parseFloat(row.find('.quantity-input').val());
        
        if (level === 0) return '';
        
        let breakdown = `1 ${unitText}`;
        let stepByStep = [];
        let runningTotal = quantity;
        
        stepByStep.push(`1 ${unitText} = ${quantity}`);
        
        for (let i = level - 1; i >= 0; i--) {
            const parentRow = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).eq(i);
            const parentQuantity = parseFloat(parentRow.find('.quantity-input').val());
            const parentUnitText = parentRow.find('.unit-select option:selected').text();
            
            if (parentQuantity && parentUnitText && parentUnitText !== 'Select Unit') {
                const previousTotal = runningTotal;
                runningTotal *= parentQuantity;
                
                if (i === 0) {
                    stepByStep.push(`${previousTotal} ${parentUnitText} × ${parentQuantity} = ${runningTotal} ${baseUnitText}`);
                } else {
                    const nextParentRow = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).eq(i - 1);
                    const nextParentUnitText = nextParentRow.find('.unit-select option:selected').text();
                    stepByStep.push(`${previousTotal} ${parentUnitText} × ${parentQuantity} = ${runningTotal} ${nextParentUnitText || 'units'}`);
                }
            }
        }
        
        if (level === 1) {
            breakdown = `${quantity} ${$(`#baseUnitSelect_${variantIndex} option:selected`).text()}`;
        } else {
            breakdown = stepByStep.join(' → ');
        }
        
        return breakdown;
    }
    
    function updateConversionChain(changedRow, variantIndex) {
        const changedLevel = parseInt(changedRow.data('level'));
        const allRows = $(`#additionalUnitsContainer_${variantIndex} .unit-row`);
        
        allRows.each(function(index) {
            const currentRow = $(this);
            const currentLevel = parseInt(currentRow.data('level'));
            
            if (currentLevel > changedLevel) {
                updateParentUnitDisplay(currentRow, variantIndex);
                updateConversionFormula(currentRow, variantIndex);
                highlightDependentUnit(currentRow, changedRow);
            }
        });
    }

    function highlightDependentUnit(dependentRow, parentRow) {
        const formulaContainer = dependentRow.find('.conversion-formula');
        formulaContainer.addClass('formula-highlight');
        
        setTimeout(function() {
            formulaContainer.removeClass('formula-highlight');
        }, 1000);
    }

    function calculateTotalBaseUnits(row, variantIndex) {
        const level = parseInt(row.data('level'));
        let total = parseFloat(row.find('.quantity-input').val()) || 0;
        
        if (total <= 0) return null;
        
        for (let i = level - 1; i >= 0; i--) {
            const parentRow = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).eq(i);
            const parentQuantity = parseFloat(parentRow.find('.quantity-input').val()) || 0;
            if (parentQuantity > 0) {
                total *= parentQuantity;
            } else {
                return null;
            }
        }
        
        return Math.round(total * 10000) / 10000;
    }

    function removeUnitRow(row, variantIndex) {
        const level = parseInt(row.data('level'));
        const totalRows = $(`#additionalUnitsContainer_${variantIndex} .unit-row`).length;
        
        if (level < totalRows - 1) {
            showErrorMessage(`Variant ${variantIndex + 1}: Cannot remove this unit as it has dependent child units. Remove child units first.`);
            return;
        }
        
        if (confirm('Are you sure you want to remove this unit?')) {
            const wasDefaultSelling = row.find('.default-selling-toggle').prop('checked');
            
            row.remove();
            
            if (wasDefaultSelling && !hasAnyDefaultSellingUnit(variantIndex)) {
                $(`#baseUnitDefault_${variantIndex}`).prop('checked', true);
                showSuccessMessage(`Variant ${variantIndex + 1}: Unit removed successfully. Default selling unit reverted to base unit.`);
            } else {
                showSuccessMessage(`Variant ${variantIndex + 1}: Unit removed successfully`);
            }
            
            reindexUnitRows(variantIndex);
            updateAllParentUnitDisplays(variantIndex);
            updateAllConversionFormulas(variantIndex);
            updateDefaultSellingIndicators(variantIndex);
            updateNoUnitsMessage(variantIndex);
        }
    }

    function reindexUnitRows(variantIndex) {
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function(index) {
            const row = $(this);
            
            row.attr('data-level', index);
            row.attr('data-index', index);
            
            row.find('select[name*="additional_units"]').attr('name', `variants[${variantIndex}][additional_units][${index}][unit_id]`);
            row.find('input[name*="additional_units"][name*="quantity"]').attr('name', `variants[${variantIndex}][additional_units][${index}][quantity]`);
            row.find('input[name*="additional_units"][name*="parent_id"]').attr('name', `variants[${variantIndex}][additional_units][${index}][parent_id]`);
            row.find('input[name*="additional_units"][name*="is_default_selling_unit"]').attr('name', `variants[${variantIndex}][additional_units][${index}][is_default_selling_unit]`);
        });
    }

    function updateAllConversionFormulas(variantIndex) {
        $(`#additionalUnitsContainer_${variantIndex} .unit-row`).each(function() {
            updateConversionFormula($(this), variantIndex);
        });
    }

    function showErrorMessage(message) {
        let alert = $('.alert-danger');
        if (alert.length === 0) {
            alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                     '<span class="alert-message"></span>' +
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                     '</div>');
            $('.container-fluid').prepend(alert);
        }
        
        alert.find('.alert-message').text(message);
        alert.show();
        
        setTimeout(function() {
            alert.fadeOut();
        }, 5000);
    }

    function validateUnitHierarchy(variantIndex) {
        const rows = $(`#additionalUnitsContainer_${variantIndex} .unit-row`);
        const errors = [];
        
        rows.each(function(index) {
            const row = $(this);
            const unitSelect = row.find('.unit-select');
            const quantityInput = row.find('.quantity-input');
            const level = index + 1;
            
            const unitValidation = validateUnitSelection(unitSelect[0]);
            if (!unitValidation.isValid) {
                errors.push(`Level ${level}: ${unitValidation.errors[0]}`);
            }
            
            const quantityValidation = validateQuantityInput(quantityInput[0]);
            if (!quantityValidation.isValid) {
                errors.push(`Level ${level}: ${quantityValidation.errors[0]}`);
            }
            
            if (index > 0) {
                const parentDisplay = row.find('.parent-unit-display').val();
                if (parentDisplay === 'Configure previous unit first' || 
                    parentDisplay === 'Select base unit first') {
                    errors.push(`Level ${level}: Previous unit must be configured first`);
                    showFieldError(row.find('.parent-unit-display'), 'Previous unit must be configured first');
                }
            }
            
            if (index >= 5) {
                errors.push(`Level ${level}: Maximum hierarchy depth exceeded (5 levels maximum)`);
            }
        });
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    function validateDefaultSellingUnit(variantIndex) {
        const checkedToggles = $(`.variant-card[data-variant-index="${variantIndex}"] .default-selling-toggle:checked`);
        const errors = [];
        
        if (checkedToggles.length === 0) {
            const baseUnitSelected = $(`#baseUnitSelect_${variantIndex}`).val();
            if (baseUnitSelected) {
                $(`#baseUnitDefault_${variantIndex}`).prop('checked', true);
                updateDefaultSellingIndicators(variantIndex);
            } else {
                errors.push('No default selling unit selected and no base unit configured');
            }
        } else if (checkedToggles.length === 1) {
            const checkedToggle = checkedToggles.first();
            
            if (checkedToggle.attr('id') === `baseUnitDefault_${variantIndex}`) {
                if (!$(`#baseUnitSelect_${variantIndex}`).val()) {
                    errors.push('Base unit must be selected to set as default selling unit');
                }
            } else {
                const row = checkedToggle.closest('.unit-row');
                const unitSelected = row.find('.unit-select').val();
                const quantity = row.find('.quantity-input').val();
                
                if (!unitSelected) {
                    errors.push('Cannot set unconfigured unit as default selling unit');
                }
                if (!quantity || parseFloat(quantity) <= 0) {
                    errors.push('Cannot set unit with invalid quantity as default selling unit');
                }
            }
        } else {
            errors.push('Multiple default selling units detected. Only one unit can be the default.');
            
            checkedToggles.slice(1).prop('checked', false);
            updateDefaultSellingIndicators(variantIndex);
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
</script>
@endpush('product-js')