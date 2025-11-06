@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Unit Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="baseUnitSelect" class="form-label">Base Unit <span class="text-danger">*</span></label>
                        <select class="form-select" id="baseUnitSelect" name="base_unit_id" required>
                            <option value="">Select Base Unit</option>
                            @foreach($availableUnits as $unit)
                                <option value="{{ $unit->id }}" 
                                    {{ $baseUnit && $baseUnit->unit_id == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->title }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Please select a base unit.
                        </div>
                        <div class="form-text">
                            <div class="form-check form-switch d-inline-block ms-2">
                                <input class="form-check-input default-selling-toggle" type="checkbox" id="baseUnitDefault" 
                                        name="base_unit_is_default_selling" value="1"
                                        {{ !$additionalUnits->where('is_default_selling_unit', 1)->count() ? 'checked' : '' }}>
                                <label class="form-check-label" for="baseUnitDefault">
                                    Default Selling Unit
                                </label>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted"></small>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Units Section -->
                    <div class="mb-4" id="additionalUnitsSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label">Additional Units</label>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addUnitBtn">
                                <i class="fas fa-plus"></i> Add Unit
                            </button>
                        </div>
                        
                        <div id="additionalUnitsContainer">
                            @if($unitHierarchy ?? false)
                                @foreach($unitHierarchy as $index => $unitData)
                                    <div class="unit-row mb-3 p-3 border rounded" data-level="{{ $index }}" data-index="{{ $index }}" data-unit-id="{{ $unitData['id'] ?? '' }}">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                                                <select class="form-select unit-select" name="additional_units[{{ $index }}][unit_id]" required>
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
                                                <input type="number" class="form-control quantity-input" 
                                                        name="additional_units[{{ $index }}][quantity]" 
                                                        value="{{ $unitData['quantity'] ?? '' }}"
                                                        min="0.01" step="0.01" placeholder="1.00" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid quantity.
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Per Parent Unit</label>
                                                <input type="text" class="parent-unit-display" readonly 
                                                        value="{{ trim($unitData['parent_name'] ?? 'Select base unit first') }}">
                                                <input type="hidden" class="parent-unit-id" name="additional_units[{{ $index }}][parent_id]" 
                                                        value="{{ $unitData['parent_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch mt-4">
                                                    <input class="form-check-input default-selling-toggle" type="checkbox" 
                                                            name="additional_units[{{ $index }}][is_default_selling_unit]" value="1"
                                                            id="defaultSelling_{{ $index }}"
                                                            {{ ($unitData['is_default_selling_unit'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="defaultSelling_{{ $index }}">
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
                        
                        <div id="noUnitsMessage" class="text-muted text-center py-3" style="display: none;">
                            <i class="fas fa-info-circle"></i> No additional units configured. Click "Add Unit" to create hierarchical units.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('product-css')
<style>
    .parent-unit-display {
        border: none;
        color: grey;
        outline: none;
    }

    .conversion-formula {
        /* background-color: #f8f9fa;
        border-left: 3px solid #007bff; */
        padding: 8px 12px;
        border-radius: 4px;
        margin-top: 10px;
    }
    
    .conversion-text {
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        color: #495057 !important;
    }
    
    .conversion-breakdown {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        color: #6c757d !important;
        font-style: italic;
    }
    
    .unit-row {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6 !important;
    }
    
    .unit-row:hover {
        border-color: #007bff !important;
        box-shadow: 0 2px 4px rgba(0,123,255,0.1);
    }
    
    .conversion-formula .fas {
        font-size: 0.875rem;
    }
    
    .formula-highlight {
        background-color: #fff3cd;
        border-left-color: #ffc107;
    }
    
    .formula-error {
        background-color: #f8d7da;
        border-left-color: #dc3545;
    }
    
    .formula-success {
        background-color: #d1edff;
        border-left-color: #0dcaf0;
    }
    
    .default-selling-toggle:checked {
        background-color: #203c70;
        border-color: #203c70;
    }
    
    .default-selling-toggle:checked:focus {
        box-shadow: 0 0 0 0.25rem #5376b6ff;
    }
    
    .form-check-label .fas.fa-star {
        transition: all 0.2s ease;
    }
    
    .default-selling-toggle:checked + .form-check-label .fas.fa-star {
        color: #203c70 !important;
        text-shadow: 0 0 3px #5376b6ff;
    }
</style>
@endpush

@push('product-js')
<script>
    $(document).ready(function() {
        initializeAllSelect2();

        $('#productStep1Form').on('submit', function(e) {
            let isValid = true;
            let validationErrors = [];
            
            clearAllValidationStates();
            
            const baseUnitValidation = validateBaseUnit();
            if (!baseUnitValidation.isValid) {
                isValid = false;
                validationErrors.push(...baseUnitValidation.errors);
            }
            
            const hierarchyValidation = validateUnitHierarchy();
            if (!hierarchyValidation.isValid) {
                isValid = false;
                validationErrors.push(...hierarchyValidation.errors);
            }
            
            const defaultSellingValidation = validateDefaultSellingUnit();
            if (!defaultSellingValidation.isValid) {
                isValid = false;
                validationErrors.push(...defaultSellingValidation.errors);
            }
            
            const duplicateValidation = validateNoDuplicateUnits();
            if (!duplicateValidation.isValid) {
                isValid = false;
                validationErrors.push(...duplicateValidation.errors);
            }
            
            const conversionValidation = validateConversionCalculations();
            if (!conversionValidation.isValid) {
                isValid = false;
                validationErrors.push(...conversionValidation.errors);
            }
            
            if (!isValid) {
                e.preventDefault();
                displayValidationErrors(validationErrors);
                scrollToFirstError();
                return false;
            }
            
            showSuccessMessage('Form validation passed. Submitting...');
        });

        $('#baseUnitSelect').on('change', function() {
            validateBaseUnit();
            updateBaseUnitToggleState();
            updateAllParentUnitDisplays();
            updateAllConversionFormulas();
            validateNoDuplicateUnits();
            refreshAllSelect2();
            performRealTimeValidation();
        });
        
        $('#baseUnitSelect').on('select2:select', function(e) {
            const selectedData = e.params.data;
            showSuccessMessage(`Base unit set to: ${selectedData.text}`);
        });
        
        $('#baseUnitSelect').on('select2:clear', function(e) {
            showSuccessMessage('Base unit cleared');
        });

        $('#baseUnitSelect').on('blur', function() {
            validateBaseUnit();
        });

        $('#baseUnitDefault').on('change', function() {
            handleDefaultSellingToggle(this);
            performRealTimeValidation();
        });

        $('#addUnitBtn').on('click', function() {
            addNewUnitRow();
        });
        
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                if ($('#baseUnitSelect').val()) {
                    addNewUnitRow();
                } else {
                    showErrorMessage('Please select a base unit first');
                }
            }
        });

        updateBaseUnitToggleState();
        
        updateDefaultSellingIndicators();
        
        $('#baseUnitSelect').on('change', function() {
            toggleAdditionalUnitsSection();
        });
        
        toggleAdditionalUnitsSection();
        
        initializeExistingUnitRows();
    });

    function validateBaseUnit() {
        const baseUnitSelect = $('#baseUnitSelect');
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

    function showError(element, message) {
        const $element = $(element);
        $element.addClass('is-invalid');
        
        let feedback = $element.siblings('.invalid-feedback');
        if (feedback.length === 0) {
            feedback = $('<div class="invalid-feedback"></div>');
            $element.after(feedback);
        }
        feedback.text(message);
    }

    function clearError(element) {
        const $element = $(element);
        $element.removeClass('is-invalid').addClass('is-valid');
        $element.siblings('.invalid-feedback').text('');
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

    function validateNoDuplicateUnits() {
        const errors = [];
        const selectedUnits = [];
        const baseUnitId = $('#baseUnitSelect').val();
        
        if (baseUnitId) {
            selectedUnits.push({
                id: baseUnitId,
                name: $('#baseUnitSelect option:selected').text(),
                element: $('#baseUnitSelect'),
                type: 'base'
            });
        }
        
        $('#additionalUnitsContainer .unit-row').each(function(index) {
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

    function validateConversionCalculations() {
        const errors = [];
        const rows = $('#additionalUnitsContainer .unit-row');
        
        rows.each(function(index) {
            const row = $(this);
            const level = parseInt(row.data('level'));
            const unitName = row.find('.unit-select option:selected').text();
            const quantity = parseFloat(row.find('.quantity-input').val());
            
            if (unitName && unitName !== 'Select Unit' && quantity) {
                const totalBaseUnits = calculateTotalBaseUnits(row);
                
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
        
        //$('.card-body').prepend(alert);
        
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

    function updateBaseUnitToggleState() {
        const baseUnitSelected = $('#baseUnitSelect').val() !== '';
        const baseUnitToggle = $('#baseUnitDefault');
        
        if (baseUnitSelected) {
            baseUnitToggle.prop('disabled', false);
            if (!hasDefaultSellingUnit()) {
                baseUnitToggle.prop('checked', true);
            }
        } else {
            baseUnitToggle.prop('disabled', true).prop('checked', false);
        }
    }

    function handleDefaultSellingToggle(toggleElement) {
        const $toggle = $(toggleElement);
        const isChecked = $toggle.prop('checked');
        
        if (isChecked) {
            $('.default-selling-toggle').not($toggle).each(function() {
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
            
            const unitName = getToggleUnitName($toggle);
            showSuccessMessage(`Default selling unit set to: ${unitName}`);
            
        } else {
            const baseUnitToggle = $('#baseUnitDefault');
            if (!hasAnyDefaultSellingUnit()) {
                baseUnitToggle.prop('checked', true);
                showSuccessMessage('Default selling unit reverted to base unit');
            }
        }
        
        updateDefaultSellingIndicators();
    }
    
    function getToggleUnitName(toggleElement) {
        const $toggle = $(toggleElement);
        
        if ($toggle.attr('id') === 'baseUnitDefault') {
            const baseUnitText = $('#baseUnitSelect option:selected').text();
            return baseUnitText !== 'Select Base Unit' ? baseUnitText : 'Base Unit';
        } else {
            const $row = $toggle.closest('.unit-row');
            const unitText = $row.find('.unit-select option:selected').text();
            return unitText !== 'Select Unit' ? unitText : 'Additional Unit';
        }
    }
    
    function hasAnyDefaultSellingUnit() {
        return $('.default-selling-toggle:checked').length > 0;
    }
    
    function updateDefaultSellingIndicators() {
        $('.default-selling-toggle').each(function() {
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

    function initializeExistingUnitRows() {
        $('#additionalUnitsContainer .unit-row').each(function(index) {
            const row = $(this);
            
            const unitSelect = row.find('.unit-select');
            if (unitSelect.length && !unitSelect.hasClass('select2-hidden-accessible')) {
                unitSelect.select2({
                    placeholder: 'Select Unit',
                    allowClear: true,
                    width: '100%'
                });
            }
            
            addRowEventHandlers(row);
            
            updateConversionFormula(row);
        });
        
        updateNoUnitsMessage();
    }

    function hasDefaultSellingUnit() {
        return hasAnyDefaultSellingUnit();
    }

    function showSuccessMessage(message, duration = 3000) {
        let alert = $('.alert-success');
        if (alert.length === 0) {
            alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                     '<span class="alert-message"></span>' +
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                     '</div>');
            //$('.card-body').prepend(alert);
        }
        
        alert.find('.alert-message').text(message);
        alert.show();
        
        setTimeout(function() {
            alert.fadeOut();
        }, duration);
    }
    
    function highlightAffectedFormulas(changedRow) {
        const changedLevel = parseInt(changedRow.data('level'));
        
        const changedFormula = changedRow.find('.conversion-formula');
        changedFormula.addClass('formula-highlight');
        
        $('#additionalUnitsContainer .unit-row').each(function() {
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
    
    function performRealTimeValidation() {
        const debouncedValidation = debounce(function() {
            clearAllValidationStates();
            
            const baseValidation = validateBaseUnit();
            const hierarchyValidation = validateUnitHierarchy();
            const duplicateValidation = validateNoDuplicateUnits();
            const conversionValidation = validateConversionCalculations();
            const defaultSellingValidation = validateDefaultSellingUnit();
            
            const allValid = baseValidation.isValid && 
                           hierarchyValidation.isValid && 
                           duplicateValidation.isValid && 
                           conversionValidation.isValid && 
                           defaultSellingValidation.isValid;
            
            if (allValid) {
                showValidationSuccess();
            } else {
                const allErrors = [
                    ...baseValidation.errors,
                    ...hierarchyValidation.errors,
                    ...duplicateValidation.errors,
                    ...conversionValidation.errors,
                    ...defaultSellingValidation.errors
                ];
                showValidationWarnings(allErrors);
            }
        }, 500);
        
        debouncedValidation();
    }
    
    function showValidationSuccess() {
        $('.alert-warning').fadeOut();
        
        const submitBtn = $('button[type="submit"]');
        submitBtn.removeClass('btn-secondary').addClass('btn-primary');
        submitBtn.find('i').removeClass('fa-exclamation-triangle').addClass('fa-check');
    }
    
    function showValidationWarnings(errors) {
        if (errors.length === 0) return;
        
        const submitBtn = $('button[type="submit"]');
        submitBtn.removeClass('btn-primary').addClass('btn-secondary');
        submitBtn.find('i').removeClass('fa-check').addClass('fa-exclamation-triangle');
        
        let alert = $('.alert-warning');
        if (alert.length === 0) {
            alert = $(`
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Validation Warnings:</h6>
                    <ul class="mb-0 validation-warnings-list"></ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            //$('.card-body').prepend(alert);
        }
        
        const errorList = errors.slice(0, 3).map(error => `<li>${error}</li>`).join('');
        const moreErrors = errors.length > 3 ? `<li><em>... and ${errors.length - 3} more issues</em></li>` : '';
        
        alert.find('.validation-warnings-list').html(errorList + moreErrors);
        alert.show();
        
        setTimeout(function() {
            alert.fadeOut();
        }, 8000);
    }

    function toggleAdditionalUnitsSection() {
        const baseUnitSelected = $('#baseUnitSelect').val() !== '';
        const additionalUnitsSection = $('#additionalUnitsSection');
        
        if (baseUnitSelected) {
            additionalUnitsSection.show();
            updateNoUnitsMessage();
        } else {
            additionalUnitsSection.hide();
        }
    }

    function updateNoUnitsMessage() {
        const hasUnits = $('#additionalUnitsContainer .unit-row').length > 0;
        const noUnitsMessage = $('#noUnitsMessage');
        
        if (hasUnits) {
            noUnitsMessage.hide();
        } else {
            noUnitsMessage.show();
        }
    }

    function addNewUnitRow() {
        const container = $('#additionalUnitsContainer');
        const currentRows = container.find('.unit-row').length;
        
        if (currentRows >= 5) {
            showErrorMessage('Maximum hierarchy depth reached (5 levels maximum)');
            return;
        }
        
        const newIndex = currentRows;
        
        const newRow = createUnitRowTemplate(newIndex);
        container.append(newRow);
        
        initializeNewRowSelect2(newIndex);
        
        updateNoUnitsMessage();
        
        updateAllParentUnitDisplays();
        
        showSuccessMessage(`Added new unit level ${newIndex + 1}`);
        
        setTimeout(function() {
            const newRow = $(`.unit-row[data-index="${newIndex}"]`);
            newRow.find('.unit-select').select2('open');
        }, 100);
    }

    function createUnitRowTemplate(index) {
        const level = $('#additionalUnitsContainer .unit-row').length;
        const availableUnits = @json($availableUnits);
        
        let unitOptions = '<option value="">Select Unit</option>';
        availableUnits.forEach(function(unit) {
            unitOptions += `<option value="${unit.id}">${unit.title}</option>`;
        });
        
        const template = `
            <div class="unit-row mb-3 p-3 border rounded" data-level="${level}" data-index="${index}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select unit-select" name="additional_units[${index}][unit_id]" required>
                            ${unitOptions}
                        </select>
                        <div class="invalid-feedback">
                            Please select a unit.
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input" 
                               name="additional_units[${index}][quantity]" 
                               min="0.01" step="0.01" placeholder="1.00" required>
                        <div class="invalid-feedback">
                            Please enter a valid quantity.
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Per Parent Unit</label>
                        <input type="text" class="parent-unit-display" readonly 
                               placeholder="Select base unit first">
                        <input type="hidden" class="parent-unit-id" name="additional_units[${index}][parent_id]">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input default-selling-toggle" type="checkbox" 
                                   name="additional_units[${index}][is_default_selling_unit]" value="1"
                                   id="defaultSelling_${index}">
                            <label class="form-check-label" for="defaultSelling_${index}">
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

    function initializeNewRowSelect2(index) {
        const row = $(`.unit-row[data-index="${index}"]`);
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
        
        addRowEventHandlers(row);
        
        unitSelect.on('select2:select', function(e) {
            const selectedData = e.params.data;
            showSuccessMessage(`Selected unit: ${selectedData.text}`);
            
            $(this).trigger('change');
        });
        
        unitSelect.on('select2:clear', function(e) {
            showSuccessMessage('Unit selection cleared');
            
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
    
    function initializeAllSelect2() {
        $('#baseUnitSelect').select2({
            placeholder: 'Select Base Unit',
            allowClear: true,
            width: '100%',
            templateResult: formatUnitOption,
            templateSelection: formatUnitSelection,
            escapeMarkup: function(markup) { return markup; }
        });
        
        $('.unit-select').each(function(index) {
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
    
    function refreshAllSelect2() {
        $('#baseUnitSelect').select2('destroy').select2({
            placeholder: 'Select Base Unit',
            allowClear: true,
            width: '100%',
            templateResult: formatUnitOption,
            templateSelection: formatUnitSelection,
            escapeMarkup: function(markup) { return markup; }
        });
        
        $('.unit-select').each(function() {
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

    function addRowEventHandlers(row) {
        const unitSelect = row.find('.unit-select');
        const quantityInput = row.find('.quantity-input');
        const defaultToggle = row.find('.default-selling-toggle');
        const removeBtn = row.find('.remove-unit-btn');
        
        unitSelect.on('change', function() {
            const $select = $(this);
            const selectedText = $select.find('option:selected').text();
            
            validateUnitSelection(this);
            
            updateParentUnitDisplay(row);
            updateConversionFormula(row);
            updateConversionChain(row);
            
            updateAllParentUnitDisplays();
            updateAllConversionFormulas();
            
            validateNoDuplicateUnits();
            refreshAllSelect2();
            
            if (selectedText && selectedText !== 'Select Unit') {
                showSuccessMessage(`Unit selected: ${selectedText}`, 2000);
            }
            
            highlightAffectedFormulas(row);
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
                updateConversionFormula(row);
                
                updateConversionChain(row);
                
                validateConversionCalculations();
                
                updateAllParentUnitDisplays();
                
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
            updateConversionFormula(row);
            updateConversionChain(row);
            validateConversionCalculations();
            updateAllParentUnitDisplays();
        });
        
        quantityInput.on('blur', function() {
            validateQuantityInput(this);
            updateConversionFormula(row);
            updateConversionChain(row);
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
            handleDefaultSellingToggle(this);
            validateDefaultSellingUnit();
            performRealTimeValidation();
        });
        
        removeBtn.on('click', function() {
            removeUnitRow(row);
        });
        
        row.on('mouseenter', function() {
            $(this).addClass('border-primary');
        }).on('mouseleave', function() {
            $(this).removeClass('border-primary');
        });
    }
    
    function updateDependentFormulas(changedRow) {
        updateConversionChain(changedRow);
    }

    function updateParentUnitDisplay(row) {
        const level = parseInt(row.data('level'));
        const parentDisplay = row.find('.parent-unit-display');
        const parentIdInput = row.find('.parent-unit-id');
        
        if (level === 0) {
            const baseUnitText = $('#baseUnitSelect option:selected').text();
            if (baseUnitText && baseUnitText !== 'Select Base Unit') {
                parentDisplay.val(baseUnitText.trim());
                parentIdInput.val('');
            } else {
                parentDisplay.val('Select base unit first');
                parentIdInput.val('');
            }
        } else {
            const previousRow = $('#additionalUnitsContainer .unit-row').eq(level - 1);
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

    function updateAllParentUnitDisplays() {
        $('#additionalUnitsContainer .unit-row').each(function() {
            updateParentUnitDisplay($(this));
        });
    }

    function updateConversionFormula(row) {
        const unitText = row.find('.unit-select option:selected').text();
        const quantity = row.find('.quantity-input').val();
        const parentText = row.find('.parent-unit-display').val();
        const formulaElement = row.find('.conversion-text');
        const breakdownElement = row.find('.conversion-breakdown');
        const formulaContainer = row.find('.conversion-formula');
        const detailsContainer = row.find('.conversion-details');
        
        const isValid = validateAndHighlightFormula(row);
        
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
                const totalBaseUnits = calculateTotalBaseUnits(row);
                const baseUnitText = $('#baseUnitSelect option:selected').text();
                
                if (totalBaseUnits && totalBaseUnits > 0 && baseUnitText && baseUnitText !== 'Select Base Unit') {
                    formula += ` = ${totalBaseUnits} ${baseUnitText}`;
                    
                    breakdown = buildConversionBreakdown(row, totalBaseUnits, baseUnitText);
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
            
            // formulaElement.html(message);
            detailsContainer.hide();
        }
    }
    
    function buildConversionBreakdown(row, totalBaseUnits, baseUnitText) {
        const level = parseInt(row.data('level'));
        const unitText = row.find('.unit-select option:selected').text();
        const quantity = parseFloat(row.find('.quantity-input').val());
        
        if (level === 0) return '';
        
        let breakdown = `1 ${unitText}`;
        let stepByStep = [];
        let runningTotal = quantity;
        
        stepByStep.push(`1 ${unitText} = ${quantity}`);
        
        for (let i = level - 1; i >= 0; i--) {
            const parentRow = $('#additionalUnitsContainer .unit-row').eq(i);
            const parentQuantity = parseFloat(parentRow.find('.quantity-input').val());
            const parentUnitText = parentRow.find('.unit-select option:selected').text();
            
            if (parentQuantity && parentUnitText && parentUnitText !== 'Select Unit') {
                const previousTotal = runningTotal;
                runningTotal *= parentQuantity;
                
                if (i === 0) {
                    stepByStep.push(`${previousTotal} ${parentUnitText} × ${parentQuantity} = ${runningTotal} ${baseUnitText}`);
                } else {
                    const nextParentRow = $('#additionalUnitsContainer .unit-row').eq(i - 1);
                    const nextParentUnitText = nextParentRow.find('.unit-select option:selected').text();
                    stepByStep.push(`${previousTotal} ${parentUnitText} × ${parentQuantity} = ${runningTotal} ${nextParentUnitText || 'units'}`);
                }
            }
        }
        
        if (level === 1) {
            breakdown = `${quantity} ${$('#baseUnitSelect option:selected').text()}`;
        } else {
            breakdown = stepByStep.join(' → ');
        }
        
        return breakdown;
    }
    
    function calculateConversionChain(fromRow, toLevel) {
        const fromLevel = parseInt(fromRow.data('level'));
        const chain = [];
        
        if (fromLevel === toLevel) {
            return [{ level: fromLevel, multiplier: 1, unit: fromRow.find('.unit-select option:selected').text() }];
        }
        
        let currentLevel = fromLevel;
        let multiplier = 1;
        
        while (currentLevel !== toLevel) {
            const currentRow = $('#additionalUnitsContainer .unit-row').eq(currentLevel);
            const quantity = parseFloat(currentRow.find('.quantity-input').val()) || 1;
            const unitText = currentRow.find('.unit-select option:selected').text();
            
            chain.push({
                level: currentLevel,
                multiplier: multiplier,
                quantity: quantity,
                unit: unitText
            });
            
            if (currentLevel > toLevel) {
                multiplier /= quantity;
                currentLevel--;
            } else {
                multiplier *= quantity;
                currentLevel++;
            }
        }
        
        return chain;
    }
    
    function updateConversionChain(changedRow) {
        const changedLevel = parseInt(changedRow.data('level'));
        const allRows = $('#additionalUnitsContainer .unit-row');
        
        allRows.each(function(index) {
            const currentRow = $(this);
            const currentLevel = parseInt(currentRow.data('level'));
            
            if (currentLevel > changedLevel) {
                updateParentUnitDisplay(currentRow);
                updateConversionFormula(currentRow);
                
                highlightDependentUnit(currentRow, changedRow);
            }
        });
    }

    function highlightDependentUnit(dependentRow, parentRow) {
        const formulaContainer = dependentRow.find('.conversion-formula');
        
        formulaContainer.addClass('formula-highlight');
        
        setTimeout(function() {
            formulaContainer.removeClass('formula-highlight');
            validateAndHighlightFormula(dependentRow);
        }, 1000);
    }
    
    function validateConversionHierarchy() {
        const allRows = $('#additionalUnitsContainer .unit-row');
        let isValid = true;
        const errors = [];
        
        allRows.each(function(index) {
            const row = $(this);
            const level = parseInt(row.data('level'));
            const unitText = row.find('.unit-select option:selected').text();
            const quantity = parseFloat(row.find('.quantity-input').val());
            
            if (!unitText || unitText === 'Select Unit') {
                errors.push(`Level ${level + 1}: Unit must be selected`);
                isValid = false;
            }
            
            if (!quantity || quantity <= 0) {
                errors.push(`Level ${level + 1}: Quantity must be greater than 0`);
                isValid = false;
            }
            
            if (level > 0) {
                const parentRow = allRows.eq(level - 1);
                const parentUnitText = parentRow.find('.unit-select option:selected').text();
                const parentQuantity = parseFloat(parentRow.find('.quantity-input').val());
                
                if (!parentUnitText || parentUnitText === 'Select Unit') {
                    errors.push(`Level ${level + 1}: Parent unit (Level ${level}) must be configured first`);
                    isValid = false;
                }
                
                if (!parentQuantity || parentQuantity <= 0) {
                    errors.push(`Level ${level + 1}: Parent unit quantity (Level ${level}) must be configured first`);
                    isValid = false;
                }
            }
        });
        
        return { isValid, errors };
    }

    function calculateTotalBaseUnits(row) {
        const level = parseInt(row.data('level'));
        let total = parseFloat(row.find('.quantity-input').val()) || 0;
        
        if (total <= 0) return null;
        
        for (let i = level - 1; i >= 0; i--) {
            const parentRow = $('#additionalUnitsContainer .unit-row').eq(i);
            const parentQuantity = parseFloat(parentRow.find('.quantity-input').val()) || 0;
            if (parentQuantity > 0) {
                total *= parentQuantity;
            } else {
                return null;
            }
        }
        
        return Math.round(total * 10000) / 10000;
    }
    
    function getConversionRate(fromLevel, toLevel) {
        if (fromLevel === toLevel) return 1;
        
        let rate = 1;
        const startLevel = Math.min(fromLevel, toLevel);
        const endLevel = Math.max(fromLevel, toLevel);
        
        for (let i = startLevel; i < endLevel; i++) {
            const row = $('#additionalUnitsContainer .unit-row').eq(i);
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            if (quantity <= 0) return null;
            rate *= quantity;
        }
        
        return fromLevel > toLevel ? 1 / rate : rate;
    }
    
    function validateAndHighlightFormula(row) {
        const formulaContainer = row.find('.conversion-formula');
        const unitText = row.find('.unit-select option:selected').text();
        const quantity = parseFloat(row.find('.quantity-input').val());
        const parentText = row.find('.parent-unit-display').val();
        
        formulaContainer.removeClass('formula-highlight formula-error formula-success');
        
        if (!unitText || unitText === 'Select Unit') {
            formulaContainer.addClass('formula-highlight');
            return false;
        }
        
        if (!quantity || quantity <= 0) {
            formulaContainer.addClass('formula-error');
            return false;
        }
        
        if (!parentText || parentText.includes('Select') || parentText.includes('Configure')) {
            formulaContainer.addClass('formula-highlight');
            return false;
        }
        
        formulaContainer.addClass('formula-success');
        return true;
    }

    function removeUnitRow(row) {
        const level = parseInt(row.data('level'));
        const totalRows = $('#additionalUnitsContainer .unit-row').length;
        
        if (level < totalRows - 1) {
            showErrorMessage('Cannot remove this unit as it has dependent child units. Remove child units first.');
            return;
        }
        
        if (confirm('Are you sure you want to remove this unit?')) {
            const wasDefaultSelling = row.find('.default-selling-toggle').prop('checked');
            
            row.remove();
            
            if (wasDefaultSelling && !hasAnyDefaultSellingUnit()) {
                $('#baseUnitDefault').prop('checked', true);
                showSuccessMessage('Unit removed successfully. Default selling unit reverted to base unit.');
            } else {
                showSuccessMessage('Unit removed successfully');
            }
            
            reindexUnitRows();
            
            updateAllParentUnitDisplays();
            
            updateAllConversionFormulas();
            
            updateDefaultSellingIndicators();
            
            updateNoUnitsMessage();
        }
    }

    function reindexUnitRows() {
        $('#additionalUnitsContainer .unit-row').each(function(index) {
            const row = $(this);
            
            row.attr('data-level', index);
            row.attr('data-index', index);
            
            row.find('select[name*="additional_units"]').attr('name', `additional_units[${index}][unit_id]`);
            row.find('input[name*="additional_units"][name*="quantity"]').attr('name', `additional_units[${index}][quantity]`);
            row.find('input[name*="additional_units"][name*="parent_id"]').attr('name', `additional_units[${index}][parent_id]`);
            row.find('input[name*="additional_units"][name*="is_default_selling_unit"]').attr('name', `additional_units[${index}][is_default_selling_unit]`);
        });
    }

    function updateAllConversionFormulas() {
        $('#additionalUnitsContainer .unit-row').each(function() {
            updateConversionFormula($(this));
        });
    }

    function showErrorMessage(message) {
        let alert = $('.alert-danger');
        if (alert.length === 0) {
            alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                     '<span class="alert-message"></span>' +
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                     '</div>');
            //$('.card-body').prepend(alert);
        }
        
        alert.find('.alert-message').text(message);
        
        setTimeout(function() {
            alert.fadeOut();
        }, 5000);
    }

    function validateUnitHierarchy() {
        const rows = $('#additionalUnitsContainer .unit-row');
        const errors = [];
        
        const hierarchyValidation = validateConversionHierarchy();
        if (!hierarchyValidation.isValid) {
            errors.push(...hierarchyValidation.errors);
        }
        
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
        
        if (errors.length === 0 && rows.length > 0) {
            const chainValidation = validateCompleteConversionChain();
            if (!chainValidation.isValid) {
                errors.push('Conversion chain validation failed: ' + chainValidation.error);
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    function validateCompleteConversionChain() {
        const rows = $('#additionalUnitsContainer .unit-row');
        
        if (rows.length === 0) {
            return { isValid: true };
        }
        
        const topRow = rows.last();
        const totalBaseUnits = calculateTotalBaseUnits(topRow);
        
        if (totalBaseUnits === null || totalBaseUnits <= 0) {
            return { 
                isValid: false, 
                error: 'Cannot calculate complete conversion chain. Check all unit quantities.' 
            };
        }
        
        for (let i = 0; i < rows.length - 1; i++) {
            const currentRow = $(rows[i]);
            const nextRow = $(rows[i + 1]);
            
            const currentQuantity = parseFloat(currentRow.find('.quantity-input').val());
            const nextQuantity = parseFloat(nextRow.find('.quantity-input').val());
            
            if (!currentQuantity || !nextQuantity || currentQuantity <= 0 || nextQuantity <= 0) {
                return { 
                    isValid: false, 
                    error: `Invalid conversion between level ${i + 1} and level ${i + 2}` 
                };
            }
        }
        
        return { isValid: true };
    }
    
    function validateDefaultSellingUnit() {
        const checkedToggles = $('.default-selling-toggle:checked');
        const errors = [];
        
        if (checkedToggles.length === 0) {
            const baseUnitSelected = $('#baseUnitSelect').val();
            if (baseUnitSelected) {
                $('#baseUnitDefault').prop('checked', true);
                updateDefaultSellingIndicators();
            } else {
                errors.push('No default selling unit selected and no base unit configured');
            }
        } else if (checkedToggles.length === 1) {
            const checkedToggle = checkedToggles.first();
            
            if (checkedToggle.attr('id') === 'baseUnitDefault') {
                if (!$('#baseUnitSelect').val()) {
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
            updateDefaultSellingIndicators();
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
</script>
@endpush