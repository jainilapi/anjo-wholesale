<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\BrandProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Unit;
use App\Models\ProductBaseUnit;
use App\Models\ProductAdditionalUnit;
use App\Rules\UnitHierarchyRule;
use App\Rules\DefaultSellingUnitRule;

class SimpleProductController extends Controller
{
    public static function view($product, $step, $type) {
        $product = Product::findOrFail($product->id);
        
        $availableUnits = Unit::select('id', 'title')->get();
        
        $baseUnit = ProductBaseUnit::where('product_id', $product->id)
            ->with('unit')
            ->first();
        
        $additionalUnits = ProductAdditionalUnit::where('product_id', $product->id)
            ->with(['unit', 'parent'])
            ->orderBy('parent_id')
            ->get();
        
        $unitHierarchy = self::buildUnitHierarchy($additionalUnits, $baseUnit);
        
        return view("products/{$type}/step-{$step}", compact(
            'product', 'availableUnits', 'baseUnit', 
            'additionalUnits', 'unitHierarchy', 'step', 'type'
        ));
    }

    public static function store($request, $step, $id, $type = 'simple') {
        switch ($step) {
            case 1:
                $request->validate([
                    'name' => 'required|string|max:255',
                    'brand_id' => 'required|integer|exists:brands,id',
                    'short_description' => 'required|string',
                    'long_description' => 'required|string',
                    'status' => 'nullable|boolean',
                    'tags' => 'nullable|array',
                    'primary_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                    'secondary_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                ]);

                DB::beginTransaction();
                try {
                    $product = Product::findOrFail($id);

                    $product->update([
                        'name' => $request->string('name'),
                        'short_description' => $request->input('short_description'),
                        'long_description' => $request->input('long_description'),
                        'status' => (bool) $request->input('status', false),
                        'tags' => $request->input('tags', []),
                        'type' => 'simple',
                        'in_draft' => 0,
                    ]);

                    BrandProduct::updateOrCreate([
                        'brand_id' => (int) $request->brand_id,
                        'product_id' => $product->id,
                    ]);

                    if ($request->hasFile('primary_image')) {
                        ProductImage::where('product_id', $product->id)->where('is_primary', 1)->delete();
                        $file = $request->file('primary_image')->store('products', 'public');
                        ProductImage::create([
                            'product_id' => $product->id,
                            'is_primary' => 1,
                            'file' => $file,
                        ]);
                    }

                    if ($request->hasFile('secondary_images')) {
                        foreach ($request->file('secondary_images') as $img) {
                            $path = $img->store('products', 'public');
                            ProductImage::create([
                                'product_id' => $product->id,
                                'is_primary' => 0,
                                'file' => $path,
                            ]);
                        }
                    }

                    DB::commit();
                    return redirect()->route('product-management', ['type' => encrypt('simple'), 'step' => encrypt(2), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Something went wrong');
                }
            case 2:
                return self::handleUnitConfigurationSubmission($request, $id, $type);

            case 3:

                break;
            case 4:

                break;
            case 5:

                break;
            case 6:

                break;
            default:
                abort(404);
                break;
        }
    }

    private static function buildUnitHierarchy($additionalUnits, $baseUnit)
    {
        $hierarchy = [];
        
        foreach ($additionalUnits as $unit) {
            $conversion = self::calculateConversion($unit, $baseUnit, $additionalUnits);
            $hierarchy[] = [
                'id' => $unit->id,
                'unit_name' => $unit->unit->title,
                'quantity' => $unit->quantity,
                'parent_name' => $unit->parent ? $unit->parent->unit->title : ($baseUnit ? $baseUnit->unit->title : ''),
                'conversion_formula' => $conversion['formula'],
                'total_base_units' => $conversion['total'],
                'is_default_selling_unit' => $unit->is_default_selling_unit
            ];
        }
        
        return $hierarchy;
    }

    private static function calculateConversion($unit, $baseUnit, $allUnits)
    {
        if (!$baseUnit) {
            return [
                'formula' => '',
                'total' => $unit->quantity
            ];
        }

        $formula = "1 {$unit->unit->title} = {$unit->quantity}";
        $total = $unit->quantity;
        
        $current = $unit;
        $chain = [];
        
        while ($current->parent_id) {
            $parent = $allUnits->firstWhere('id', $current->parent_id);
            if (!$parent) break;
            
            $chain[] = $parent;
            $total *= $parent->quantity;
            $current = $parent;
        }
        
        if (!empty($chain)) {
            $chainText = [];
            $runningTotal = $unit->quantity;
            
            foreach (array_reverse($chain) as $chainUnit) {
                $chainText[] = "{$runningTotal} {$chainUnit->unit->title}";
                $runningTotal *= $chainUnit->quantity;
            }
            $formula .= " " . implode(" = ", $chainText);
        }
        
        $formula .= " = {$total} {$baseUnit->unit->title}";
        
        return [
            'formula' => $formula,
            'total' => $total
        ];
    }

    private static function handleUnitConfigurationSubmission(Request $request, $id, $type)
    {
        $additionalUnits = $request->input('additional_units', []);
        $baseUnitId = $request->input('base_unit_id');
        $baseUnitIsDefault = $request->boolean('base_unit_is_default_selling');

        $request->validate([
            'base_unit_id' => 'required|integer|exists:units,id',
            'base_unit_is_default_selling' => 'nullable|boolean',
            'additional_units' => [
                'nullable',
                'array',
                'max:5',
                new UnitHierarchyRule($baseUnitId, $id),
            ],
            'additional_units.*.unit_id' => 'required|integer|exists:units,id',
            'additional_units.*.quantity' => 'required|numeric|min:0.01|max:999999',
            'additional_units.*.parent_id' => 'nullable|integer',
            'additional_units.*.is_default_selling_unit' => 'nullable|boolean',
            'default_selling_validation' => [
                new DefaultSellingUnitRule($baseUnitIsDefault, $additionalUnits)
            ],
        ], [
            'base_unit_id.required' => 'Base unit is required.',
            'base_unit_id.exists' => 'Selected base unit does not exist.',
            'additional_units.max' => 'Maximum 5 additional units are allowed.',
            'additional_units.*.unit_id.required' => 'Unit selection is required for all additional units.',
            'additional_units.*.unit_id.exists' => 'Selected unit does not exist.',
            'additional_units.*.quantity.required' => 'Quantity is required for all additional units.',
            'additional_units.*.quantity.min' => 'Quantity must be greater than 0.',
            'additional_units.*.quantity.max' => 'Quantity cannot exceed 999,999.',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            
            self::validateUnitConfigurationData($request, $additionalUnits);
            
            self::saveBaseUnit($product->id, $request);
            
            self::saveAdditionalUnits($product->id, $additionalUnits);
            
            DB::commit();
            
            return redirect()->route('product-management', [
                'type' => encrypt($type), 
                'step' => encrypt(3), 
                'id' => encrypt($product->id)
            ])->with('success', 'Unit configuration saved successfully');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Unit configuration validation failed', [
                'product_id' => $id,
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'Please fix the validation errors and try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unit configuration save failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('error', 'An error occurred while saving unit configuration: ' . $e->getMessage());
        }
    }

    private static function validateUnitConfigurationData(Request $request, array $additionalUnits)
    {
        $baseUnitId = $request->input('base_unit_id');
        $errors = [];
        
        self::validateBusinessRules($request, $additionalUnits, $errors);
        
        self::validateDataIntegrity($baseUnitId, $additionalUnits, $errors);
        
        self::validateSystemConstraints($additionalUnits, $errors);
        
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    private static function validateBusinessRules(Request $request, array $additionalUnits, array &$errors)
    {
        $baseUnitId = $request->input('base_unit_id');
        $allUnitIds = [$baseUnitId];
        
        foreach ($additionalUnits as $index => $unit) {
            if (in_array($unit['unit_id'], $allUnitIds)) {
                $errors["additional_units.{$index}.unit_id"] = 'This unit is already selected. Each unit can only be used once.';
            }
            $allUnitIds[] = $unit['unit_id'];
        }
        
        self::validateDefaultSellingUnitSelection($request, $additionalUnits, $errors);
        
        self::validateConversionCalculations($additionalUnits, $errors);
    }

    private static function validateDataIntegrity($baseUnitId, array $additionalUnits, array &$errors)
    {
        if (!Unit::where('id', $baseUnitId)->exists()) {
            $errors['base_unit_id'] = 'Selected base unit does not exist in the system.';
        }
        
        foreach ($additionalUnits as $index => $unit) {
            if (!Unit::where('id', $unit['unit_id'])->exists()) {
                $errors["additional_units.{$index}.unit_id"] = 'Selected unit does not exist in the system.';
            }
        }
        
        self::validateHierarchyConstraints($additionalUnits, $errors);
    }

    private static function validateSystemConstraints(array $additionalUnits, array &$errors)
    {
        if (count($additionalUnits) > 5) {
            $errors['additional_units'] = 'Maximum 5 additional units are allowed to prevent system complexity.';
        }
        
        foreach ($additionalUnits as $index => $unit) {
            $quantity = floatval($unit['quantity'] ?? 0);
            
            if ($quantity > 999999) {
                $errors["additional_units.{$index}.quantity"] = 'Quantity exceeds system maximum (999,999).';
            }
            
            if ($quantity < 0.01) {
                $errors["additional_units.{$index}.quantity"] = 'Quantity below system minimum (0.01).';
            }
        }
        
        self::validateNoCircularReferences($additionalUnits, $errors);
    }

    private static function validateNoCircularReferences(array $additionalUnits, array &$errors)
    {
        foreach ($additionalUnits as $index => $unit) {
            $parentId = $unit['parent_id'] ?? null;
            if (!$parentId) continue;
            
            $visited = [];
            $currentParentId = $parentId;
            
            while ($currentParentId) {
                if (in_array($currentParentId, $visited)) {
                    $errors["additional_units.{$index}.parent_id"] = 'Circular reference detected in unit hierarchy.';
                    break;
                }
                
                $visited[] = $currentParentId;
                
                $parentFound = false;
                foreach ($additionalUnits as $potentialParent) {
                    if (isset($potentialParent['id']) && $potentialParent['id'] == $currentParentId) {
                        $currentParentId = $potentialParent['parent_id'] ?? null;
                        $parentFound = true;
                        break;
                    }
                }
                
                if (!$parentFound) {
                    $dbParent = ProductAdditionalUnit::find($currentParentId);
                    $currentParentId = $dbParent ? $dbParent->parent_id : null;
                }
            }
        }
    }

    private static function validateHierarchyConstraints(array $additionalUnits, array &$errors)
    {
        if (empty($additionalUnits)) {
            return;
        }
        
        $parentCounts = [];
        $unitIds = [];
        
        foreach ($additionalUnits as $index => $unit) {
            $unitIds[$index] = $unit['unit_id'] ?? null;
            
            if (!empty($unit['parent_id'])) {
                $parentFound = false;
                for ($i = 0; $i < $index; $i++) {
                    if (isset($unitIds[$i]) && $unitIds[$i] == $unit['parent_id']) {
                        $parentFound = true;
                        break;
                    }
                }
                
                if (!$parentFound) {
                    $parentExists = ProductAdditionalUnit::where('id', $unit['parent_id'])->exists();
                    if (!$parentExists) {
                        $errors["additional_units.{$index}.parent_id"] = 'Invalid parent unit reference.';
                    }
                }
                
                $parentCounts[$unit['parent_id']] = ($parentCounts[$unit['parent_id']] ?? 0) + 1;
                
                if ($parentCounts[$unit['parent_id']] > 1) {
                    $errors["additional_units.{$index}.parent_id"] = 'Each parent unit can have only one child unit.';
                }
            } else {
                if ($index > 0) {
                    $errors["additional_units.{$index}.parent_id"] = 'This unit must reference a parent unit.';
                }
            }
        }
        
        for ($i = 1; $i < count($additionalUnits); $i++) {
            $currentUnit = $additionalUnits[$i];
            $expectedParentIndex = $i - 1;
            
            if (empty($currentUnit['parent_id'])) {
                continue;
            }
            
            $parentIsImmediate = false;
            if (isset($unitIds[$expectedParentIndex])) {
                $parentIsImmediate = ($currentUnit['parent_id'] == $unitIds[$expectedParentIndex]);
            }
            
            if (!$parentIsImmediate) {
                $parentExists = ProductAdditionalUnit::where('id', $currentUnit['parent_id'])->exists();
                if (!$parentExists) {
                    $errors["additional_units.{$i}.parent_id"] = 'Units must form a sequential hierarchy.';
                }
            }
        }
    }

    private static function validateDefaultSellingUnitSelection(Request $request, array $additionalUnits, array &$errors)
    {
        $defaultSellingCount = 0;
        $baseUnitIsDefault = $request->boolean('base_unit_is_default_selling');
        
        if ($baseUnitIsDefault) {
            $defaultSellingCount++;
        }
        
        foreach ($additionalUnits as $index => $unit) {
            if (!empty($unit['is_default_selling_unit'])) {
                $defaultSellingCount++;
            }
        }
        
        if ($defaultSellingCount === 0) {
            $request->merge(['base_unit_is_default_selling' => true]);
        } elseif ($defaultSellingCount > 1) {
            $errors['default_selling_unit'] = 'Only one unit can be set as the default selling unit.';
        }
    }

    private static function validateConversionCalculations(array $additionalUnits, array &$errors)
    {
        foreach ($additionalUnits as $index => $unit) {
            $quantity = floatval($unit['quantity'] ?? 0);
            
            if ($quantity <= 0) {
                continue;
            }
            
            $totalConversion = $quantity;
            $currentIndex = $index;
            
            while ($currentIndex > 0) {
                $parentUnit = $additionalUnits[$currentIndex - 1] ?? null;
                if (!$parentUnit) break;
                
                $parentQuantity = floatval($parentUnit['quantity'] ?? 0);
                if ($parentQuantity <= 0) {
                    $errors["additional_units.{$index}.quantity"] = 'Cannot calculate conversion - parent unit has invalid quantity.';
                    break;
                }
                
                $totalConversion *= $parentQuantity;
                $currentIndex--;
            }
            
            if ($totalConversion > 1000000) {
                $errors["additional_units.{$index}.quantity"] = 'Conversion results in extremely large numbers. Please check quantities.';
            }
            
            if ($totalConversion < 0.0001) {
                $errors["additional_units.{$index}.quantity"] = 'Conversion results in extremely small numbers. Please check quantities.';
            }
        }
    }

    private static function saveBaseUnit($productId, Request $request)
    {
        ProductBaseUnit::updateOrCreate(
            ['product_id' => $productId],
            [
                'unit_id' => $request->input('base_unit_id'),
                'varient_id' => null
            ]
        );
    }

    private static function saveAdditionalUnits($productId, array $additionalUnits)
    {
        ProductAdditionalUnit::where('product_id', $productId)->delete();
        
        $savedUnits = [];
        
        foreach ($additionalUnits as $index => $unitData) {
            $parentId = null;
            if ($index > 0 && isset($savedUnits[$index - 1])) {
                $parentId = $savedUnits[$index - 1]->id;
            }
            
            $unit = ProductAdditionalUnit::create([
                'product_id' => $productId,
                'varient_id' => null,
                'unit_id' => $unitData['unit_id'],
                'quantity' => $unitData['quantity'],
                'parent_id' => $parentId,
                'is_default_selling_unit' => !empty($unitData['is_default_selling_unit']),
            ]);
            
            $savedUnits[$index] = $unit;
        }
    }

    private static function validateUnitHierarchy($additionalUnits)
    {
        $parentCounts = [];
        
        foreach ($additionalUnits as $unit) {
            if (isset($unit['parent_id']) && $unit['parent_id']) {
                $parentCounts[$unit['parent_id']] = ($parentCounts[$unit['parent_id']] ?? 0) + 1;
                
                if ($parentCounts[$unit['parent_id']] > 1) {
                    throw new \Exception('Each parent unit can have only one child unit');
                }
            }
        }
        
        $unitIds = collect($additionalUnits)->pluck('id')->filter()->toArray();
        foreach ($additionalUnits as $unit) {
            if (isset($unit['parent_id']) && $unit['parent_id'] && !in_array($unit['parent_id'], $unitIds)) {
                $parentExists = ProductAdditionalUnit::where('id', $unit['parent_id'])->exists();
                if (!$parentExists) {
                    throw new \Exception('Invalid parent unit reference');
                }
            }
        }
    }
}
