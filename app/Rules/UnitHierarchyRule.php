<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\ProductAdditionalUnit;

class UnitHierarchyRule implements ValidationRule
{
    private $baseUnitId;
    private $productId;

    public function __construct($baseUnitId, $productId = null)
    {
        $this->baseUnitId = $baseUnitId;
        $this->productId = $productId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        $errors = [];
        
        $this->validateNoDuplicateUnits($value, $errors);
        
        $this->validateHierarchyStructure($value, $errors);
        
        $this->validateParentChildRelationships($value, $errors);
        
        $this->validateConversionCalculations($value, $errors);
        
        if (!empty($errors)) {
            $fail(implode(' ', $errors));
        }
    }

    private function validateNoDuplicateUnits(array $units, array &$errors): void
    {
        $allUnitIds = [$this->baseUnitId];
        $duplicates = [];
        
        foreach ($units as $index => $unit) {
            $unitId = $unit['unit_id'] ?? null;
            if ($unitId && in_array($unitId, $allUnitIds)) {
                $duplicates[] = "Unit at position " . ($index + 1) . " is already selected";
            }
            if ($unitId) {
                $allUnitIds[] = $unitId;
            }
        }
        
        if (!empty($duplicates)) {
            $errors[] = "Duplicate units detected: " . implode(', ', $duplicates);
        }
    }

    private function validateHierarchyStructure(array $units, array &$errors): void
    {
        if (count($units) > 5) {
            $errors[] = "Maximum 5 additional units allowed";
        }
        
        for ($i = 0; $i < count($units); $i++) {
            $unit = $units[$i];
            
            if ($i === 0) {
                if (!empty($unit['parent_id'])) {
                    $parentExists = ProductAdditionalUnit::where('id', $unit['parent_id'])
                        ->where('product_id', $this->productId)
                        ->exists();
                    if (!$parentExists) {
                        $errors[] = "First additional unit has invalid parent reference";
                    }
                }
            } else {
                if (empty($unit['parent_id']) && $i != 1) {
                    $errors[] = "Unit at position " . ($i + 1) . " must reference a parent unit";
                }
            }
        }
    }

    private function validateParentChildRelationships(array $units, array &$errors): void
    {
        $parentCounts = [];
        
        foreach ($units as $index => $unit) {
            $parentId = $unit['parent_id'] ?? null;
            
            if ($parentId) {
                $parentCounts[$parentId] = ($parentCounts[$parentId] ?? 0) + 1;
                
                if ($parentCounts[$parentId] > 1) {
                    $errors[] = "Each parent unit can have only one child unit (violation at position " . ($index + 1) . ")";
                }
                
                if ($this->productId) {
                    $parentExists = ProductAdditionalUnit::where('id', $parentId)
                        ->where('product_id', $this->productId)
                        ->exists();
                    
                    if (!$parentExists && $index > 0) {
                        $parentFoundInSubmission = false;
                        for ($j = 0; $j < $index; $j++) {
                            if (isset($units[$j]['id']) && $units[$j]['id'] == $parentId) {
                                $parentFoundInSubmission = true;
                                break;
                            }
                        }
                        
                        if (!$parentFoundInSubmission) {
                            $errors[] = "Invalid parent reference at position " . ($index + 1);
                        }
                    }
                }
            }
        }
    }

    private function validateConversionCalculations(array $units, array &$errors): void
    {
        foreach ($units as $index => $unit) {
            $quantity = floatval($unit['quantity'] ?? 0);
            
            if ($quantity <= 0) {
                continue;
            }
            
            $totalConversion = $quantity;
            
            $currentIndex = $index;
            while ($currentIndex > 0) {
                $parentUnit = $units[$currentIndex - 1] ?? null;
                if (!$parentUnit) break;
                
                $parentQuantity = floatval($parentUnit['quantity'] ?? 0);
                if ($parentQuantity <= 0) break;
                
                $totalConversion *= $parentQuantity;
                $currentIndex--;
            }
            
            if ($totalConversion > 1000000) {
                $errors[] = "Unit at position " . ($index + 1) . " results in extremely large conversion factor";
            }
            
            if ($totalConversion < 0.0001) {
                $errors[] = "Unit at position " . ($index + 1) . " results in extremely small conversion factor";
            }
        }
    }
}