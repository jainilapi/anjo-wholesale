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
use App\Models\ProductTierPricing;
use App\Rules\UnitHierarchyRule;
use App\Rules\DefaultSellingUnitRule;
use App\Models\Inventory;
use App\Models\Warehouse;

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
        
        $warehouses = Warehouse::select('id', 'code', 'name')->toBase()->get();
        
        $locations = Inventory::where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->with('warehouse')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->warehouse_id,
                    'code' => $location->warehouse->code ?? 'N/A',
                    'name' => $location->warehouse->name ?? 'N/A',
                    'qty' => $location->quantity,
                    'reorder' => $location->reorder_level,
                    'max' => $location->max_stock_level,
                    'notes' => $location->notes,
                    'lastUpdated' => date('M d, Y H:i A', strtotime($location->updated_at)),
                    'history' => []
                ];
            })->values();
        
        return view("products/{$type}/step-{$step}", compact(
            'product', 'availableUnits', 'baseUnit', 
            'additionalUnits', 'unitHierarchy', 'step', 'type',
            'warehouses', 'locations'
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
                $product = Product::findOrFail($id);
                $payload = $request->input('tier_pricings');
                $items = [];
                if ($payload) {
                    try {
                        $decoded = is_array($payload) ? $payload : json_decode($payload, true);
                        if (is_array($decoded)) {
                            $items = $decoded;
                        }
                    } catch (\Throwable $th) {
                    }
                }

                $request->merge(['_tier_items' => $items]);

                $request->validate([
                    '_tier_items' => 'nullable|array',
                    '_tier_items.*.product_variant_id' => 'nullable',
                    '_tier_items.*.product_additional_unit_id' => 'required|integer',
                    '_tier_items.*.min_qty' => 'required|numeric|min:1',
                    '_tier_items.*.max_qty' => 'nullable|numeric',
                    '_tier_items.*.price_per_unit' => 'required|numeric|min:0.01',
                    '_tier_items.*.discount_type' => 'required|in:0,1',
                    '_tier_items.*.discount_amount' => 'nullable|numeric|min:0',
                ]);

                if (empty($items)) {
                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(4), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                }

                foreach ($items as $index => $row) {
                    if (!empty($row['product_variant_id'])) {
                        return back()->withInput()->withErrors(["_tier_items.$index.product_variant_id" => 'Variant is not allowed for simple products']);
                    }

                    $unitRowId = $row['product_additional_unit_id'] ?? 0;
                    $belongs = ProductAdditionalUnit::where('product_id', $product->id)->where('id', $unitRowId)->exists()
                        || ProductBaseUnit::where('product_id', $product->id)->where('id', $unitRowId)->exists();
                    if (!$belongs) {
                        return back()->withInput()->withErrors(["_tier_items.$index.product_additional_unit_id" => 'Invalid unit selection for this product']);
                    }

                    if ((int)($row['discount_type'] ?? 1) === 1) {
                        $percent = (float)($row['discount_amount'] ?? 0);
                        if ($percent < 0 || $percent > 100) {
                            return back()->withInput()->withErrors(["_tier_items.$index.discount_amount" => 'Discount percentage must be between 0 and 100']);
                        }
                    }
                }

                $grouped = [];
                foreach ($items as $row) {
                    $key = '0-' . ($row['product_additional_unit_id'] ?? '0');
                    $grouped[$key][] = $row;
                }

                foreach ($grouped as $key => $rows) {
                    usort($rows, function ($a, $b) {
                        return ($a['min_qty'] ?? 0) <=> ($b['min_qty'] ?? 0);
                    });
                    $prevMax = 0;
                    foreach ($rows as $i => $r) {
                        $min = (float)($r['min_qty'] ?? 0);
                        $max = array_key_exists('max_qty', $r) && $r['max_qty'] !== null && $r['max_qty'] !== '' ? (float)$r['max_qty'] : null;
                        if ($min <= $prevMax) {
                            return back()->withInput()->withErrors(['tier_pricings' => 'Overlapping or unordered ranges detected.']);
                        }
                        if ($max !== null && $max < $min) {
                            return back()->withInput()->withErrors(['tier_pricings' => 'Max quantity must be greater than min quantity.']);
                        }
                        $prevMax = $max === null ? PHP_INT_MAX : $max;
                    }
                }

                try {
                    DB::beginTransaction();
                    ProductTierPricing::where('product_id', $product->id)->whereNull('product_variant_id')->delete();
                    foreach ($items as $r) {
                        ProductTierPricing::create([
                            'product_id' => $product->id,
                            'unit_type' => (int)$r['is_base_unit'],
                            'product_variant_id' => null,
                            'product_additional_unit_id' => (int)$r['product_additional_unit_id'],
                            'min_qty' => (float)$r['min_qty'],
                            'max_qty' => $r['max_qty'] === null || $r['max_qty'] === '' ? 0 : (float)$r['max_qty'],
                            'price_per_unit' => (float)$r['price_per_unit'],
                            'discount_type' => (int)$r['discount_type'],
                            'discount_amount' => (float)($r['discount_amount'] ?? 0),
                        ]);
                    }
                    DB::commit();
                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(4), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Failed to save pricing tiers');
                }

            case 4:
                $product = Product::findOrFail($id);

                $validated = $request->validate([
                    'data' => 'nullable|array',
                    'data.warehouse_id' => 'nullable|array',
                    'data.warehouse_id.*' => 'exists:warehouses,id',
                    'data.item_quantity' => 'nullable|array',
                    'data.item_quantity.*' => 'integer|min:0',
                    'data.item_reordering' => 'nullable|array',
                    'data.item_reordering.*' => 'integer|min:0',
                    'data.item_max' => 'nullable|array',
                    'data.item_max.*' => 'integer|min:0',
                    'data.item_notes' => 'nullable|array',
                    'data.item_notes.*' => 'string|max:255'
                ]);

                $product->track_inventory_for_all_variant = $request->track_inventory_for_all_variant == 'on' ? 1 : 0;
                $product->allow_backorder = $request->allow_backorder == 'on' ? 1 : 0;
                $product->enable_auto_reorder_alerts = $request->enable_auto_reorder_alerts == 'on' ? 1 : 0;
                $product->save();

                if (isset($validated['data']['warehouse_id'])) {
                    foreach ($validated['data']['warehouse_id'] as $index => $warehouse_id) {
                        $item_quantity = $validated['data']['item_quantity'][$index] ?? 0;
                        $item_reordering = $validated['data']['item_reordering'][$index] ?? 0;
                        $item_max = $validated['data']['item_max'][$index] ?? 0;
                        $item_notes = $validated['data']['item_notes'][$index] ?? null;

                        $inventory = Inventory::where('product_id', $product->id)
                            ->whereNull('product_variant_id')
                            ->where('warehouse_id', $warehouse_id)
                            ->first();

                        if ($inventory) {
                            $inventory->update([
                                'quantity' => $item_quantity,
                                'reorder_level' => $item_reordering,
                                'max_stock_level' => $item_max,
                                'notes' => $item_notes,
                            ]);
                        } else {
                            Inventory::create([
                                'product_id' => $product->id,
                                'product_variant_id' => null,
                                'warehouse_id' => $warehouse_id,
                                'quantity' => $item_quantity,
                                'reorder_level' => $item_reordering,
                                'max_stock_level' => $item_max,
                                'notes' => $item_notes,
                            ]);
                        }
                    }
                }

                return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(5), 'id' => encrypt($product->id)])
                    ->with('success', 'Data saved successfully');
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
                'unit_id' => $unit->unit->id,
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
                'variant_id' => null
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
                'variant_id' => null,
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
