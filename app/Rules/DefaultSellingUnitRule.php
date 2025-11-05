<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DefaultSellingUnitRule implements ValidationRule
{
    private $baseUnitIsDefault;
    private $additionalUnits;

    public function __construct($baseUnitIsDefault, $additionalUnits = [])
    {
        $this->baseUnitIsDefault = $baseUnitIsDefault;
        $this->additionalUnits = $additionalUnits ?? [];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $defaultCount = 0;
        
        if ($this->baseUnitIsDefault) {
            $defaultCount++;
        }
        
        if (is_array($this->additionalUnits)) {
            foreach ($this->additionalUnits as $unit) {
                if (!empty($unit['is_default_selling_unit'])) {
                    $defaultCount++;
                }
            }
        }
        
        if ($defaultCount === 0) {
            return;
        } elseif ($defaultCount > 1) {
            $fail('Only one unit can be set as the default selling unit.');
        }
        
        if (!$this->baseUnitIsDefault && is_array($this->additionalUnits)) {
            foreach ($this->additionalUnits as $index => $unit) {
                if (!empty($unit['is_default_selling_unit'])) {
                    if (empty($unit['unit_id'])) {
                        $fail("Additional unit at position " . ($index + 1) . " cannot be default selling unit without selecting a unit.");
                    }
                    
                    $quantity = floatval($unit['quantity'] ?? 0);
                    if ($quantity <= 0) {
                        $fail("Additional unit at position " . ($index + 1) . " cannot be default selling unit without a valid quantity.");
                    }
                }
            }
        }
    }
}