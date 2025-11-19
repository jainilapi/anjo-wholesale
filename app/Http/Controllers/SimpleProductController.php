<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\ProductCategory;
use App\Models\BrandProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\ProductBaseUnit;
use App\Models\ProductAdditionalUnit;
use App\Models\ProductTierPricing;
use App\Rules\UnitHierarchyRule;
use App\Rules\DefaultSellingUnitRule;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\ProductSupplier;
use App\Models\User;
use App\Models\ProductSubtitue;

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

        $unitHierarchy = $baseUnitsForAllV = $additionalUnitsForAllV = [];

        foreach ($product->variants as $thisVariant) {

            $baseUnitForV = ProductBaseUnit::where('product_id', $product->id)
                ->where('variant_id', $thisVariant->id)
                ->with('unit')
                ->first();

            $additionalUnitsForV = ProductAdditionalUnit::where('product_id', $product->id)
                ->where('variant_id', $thisVariant->id)
                ->with(['unit', 'parent'])
                ->orderBy('parent_id')
                ->get();

            $unitHierarchy[$thisVariant->id] = self::buildUnitHierarchy($additionalUnitsForV, $baseUnitForV);
            $baseUnitsForAllV[$thisVariant->id] = $baseUnitForV;
            $additionalUnitsForAllV[$thisVariant->id] = $additionalUnitsForV;
        }

        if ($step == 7) {
            $simpleSubstitutes = ProductSubtitue::where('product_id', $product->id)
                ->whereNull('variant_id')
                ->with(['substituteProduct:id,name', 'substituteVariant:id,name,sku'])
                ->get()
                ->map(function ($sub) {
                    $productName = $sub->substituteProduct->name ?? 'N/A';
                    $variantName = $sub->substituteVariant->name ?? 'N/A';
                    $variantSku = $sub->substituteVariant->sku ?? 'N/A';
                    return [
                        'substitute_variant_id' => $sub->source_variant_id,
                        'text_representation' => "{$productName} - {$variantName} (SKU: {$variantSku})"
                    ];
                });
        }

        $units = Unit::get();

        $warehouses = Warehouse::select('id', 'code', 'name', 'type')->toBase()->get();

        $variants = ProductVariant::where('product_id', $product->id)->get()->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'status' => 'No Data',
                'warehouses' => $variant->inventories()->with('warehouse')->get()->map(function ($location) {
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
                })->values()
            ];
        })->values();

        $variantsForSupplier = ProductVariant::where('product_id', $product->id)->get()->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'status' => 'No Data',
                'suppliers' => $variant->suppliers()->with(['supplier.country', 'variant'])->get()->map(function ($sup) {
                    return [
                        'id' => $sup->supplier_id ?? null,
                        'name' => $sup->supplier->name ?? 'N/A',
                        'phone_number' => '+' . ($sup->supplier->dial_code ?? '') . ' ' . ($sup->supplier->phone_number ?? 'N/A'),
                        'country_flag' => $sup->supplier->country->emoji ?? 'N/A',
                        'country_name' => $supplier->supplier->country->name ?? 'N/A',
                        'email' => $sup->supplier->email ?? 'N/A'
                    ];
                })->values()
            ];
        })->values();

        $suppliers = User::with('country')->whereHas('roles', function ($innerBuilder) {
            return $innerBuilder->where('id', 5);
        })->where('status', 1)->get()->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone_number' => $supplier->dial_code . ' ' . $supplier->phone_number,
                'country_flag' => $supplier->country->emoji ?? '',
                'country_name' => $supplier->country->name ?? '',
                'email' => $supplier->email
            ];
        })->values()->toArray();

        $variantsForSubstitutes = collect();
        $simpleSubstitutes = collect();

        $variantsForSubstitutes = ProductVariant::where('product_id', $product->id)
            ->with([
                'substitutes.substituteProduct:id,name',
                'substitutes.substituteVariant:id,name,sku'
            ])
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'substitutes' => $variant->substitutes->map(function ($sub) {
                        $productName = $sub->substituteProduct->name ?? 'N/A';
                        $variantName = $sub->substituteVariant->name ?? 'N/A';
                        $variantSku = $sub->substituteVariant->sku ?? 'N/A';
                        return [
                            'substitute_product_id' => $sub->source_product_id,
                            'substitute_variant_id' => $sub->source_variant_id,
                            'text_representation' => "{$productName} - {$variantName} (SKU: {$variantSku})"
                        ];
                    })
                ];
            });

        $simpleProductInventory = Inventory::where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->with('warehouse')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->warehouse_id,
                    'name' => ($item->warehouse->code ?? '') . ' - ' . ($item->warehouse->name ?? ''), 
                    'type' => $item->warehouse->type ?? 0, 
                    'qty' => $item->quantity,
                    'reorder' => $item->reorder_level,
                    'max' => $item->max_stock_level,
                    'notes' => $item->notes,
                    'lastUpdated' => $item->updated_at ? $item->updated_at->format('M d, Y H:i A') : '—',
                    'history' => []
                ];
            });

        $reviewData = [];

        $brand = BrandProduct::where('product_id', $product->id)->with('brand')->first();
        $images = ProductImage::where('product_id', $product->id)->get();
        
        $reviewVariants = ProductVariant::where('product_id', $product->id)
            ->with('attributes.attribute', 'variantImage')
            ->get();
        
        $tierPricings = ProductTierPricing::where('product_id', $product->id)
            ->with('variant:id,name')
            ->get();

        $allBaseUnits = ProductBaseUnit::where('product_id', $product->id)->with('unit')->get()->keyBy('id');
        $allAddUnits = ProductAdditionalUnit::where('product_id', $product->id)->with('unit')->get()->keyBy('id');

        $tierPricings->each(function($tp) use ($allBaseUnits, $allAddUnits) {
            $unitId = $tp->product_additional_unit_id;
            if ($allBaseUnits->has($unitId)) {
                $tp->unit_name = $allBaseUnits->get($unitId)->unit->title;
            } elseif ($allAddUnits->has($unitId)) {
                $tp->unit_name = $allAddUnits->get($unitId)->unit->title;
            } else {
                $tp->unit_name = 'N/A';
            }
        });
        
        $tierPricingsByVariant = $tierPricings->groupBy('variant.name');

        $categories = ProductCategory::where('product_id', $product->id)
            ->with('category')
            ->get();
        $primaryCategory = $categories->firstWhere('is_primary', 1);
        $additionalCategories = $categories->where('is_primary', 0);

        $reviewData = [
            'brand' => $brand->brand->name ?? 'N/A',
            'primaryImage' => $images->firstWhere('is_primary', 1),
            'secondaryImages' => $images->where('is_primary', 0),
            'variants' => $reviewVariants,
            'baseUnits' => $baseUnitsForAllV,
            'unitHierarchy' => $unitHierarchy,
            'tierPricings' => $tierPricingsByVariant,
            'inventorySettings' => [
                'track_inventory' => $product->track_inventory_for_all_variant,
                'allow_backorder' => $product->allow_backorder,
                'enable_auto_reorder' => $product->enable_auto_reorder_alerts,
            ],
            'inventoryLocations' => $variants,
            'suppliers' => $variantsForSupplier,
            'primaryCategory' => $primaryCategory->category->name ?? 'N/A',
            'additionalCategories' => $additionalCategories,
            'seo' => [
                'title' => $product->seo_title,
                'description' => $product->seo_description,
                'feature' => $product->should_feature_on_home_page,
                'new' => $product->is_new_product,
                'best_seller' => $product->is_best_seller,
            ],
            'substitutes' => $variantsForSubstitutes,
        ];

        $simpleProductSuppliers = ProductSupplier::where('product_id', $product->id)
        ->whereNull('product_variant_id')
        ->with(['supplier.country'])
        ->get()
        ->map(function ($item) {
            $sup = $item->supplier;
            return [
                'id' => $sup->id ?? 0,
                'name' => $sup->name ?? 'Unknown',
                'phone_number' => ($sup->dial_code ?? '') . ' ' . ($sup->phone_number ?? ''), 
                'country_flag' => $sup->country->emoji ?? '',
                'email' => $sup->email ?? 'N/A'
            ];
        });

        return view("products/{$type}/step-{$step}", compact(
            'product',
            'availableUnits',
            'baseUnit',
            'warehouses',
            'additionalUnits',
            'unitHierarchy',
            'step',
            'type',
            'units',
            'variants',
            'suppliers',
            'variantsForSupplier',
            'baseUnitsForAllV',
            'additionalUnitsForAllV',
            'variantsForSubstitutes',
            'reviewData', 'simpleSubstitutes',
            'simpleProductInventory',
            'simpleProductSuppliers'
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
                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(2), 'id' => encrypt($product->id)])
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
                $product = Product::findOrFail($id);

                $validated = $request->validate([
                    'data' => 'nullable|array',
                    'data.supplier_id' => 'nullable|array',
                    'data.supplier_id.*' => 'exists:users,id'
                ]);

                $toKeep = [];

                if (isset($validated['data']['supplier_id'])) {
                    foreach ($validated['data']['supplier_id'] as $supplier_id) {
                        $row = ProductSupplier::where('product_id', $product->id)
                            ->whereNull('product_variant_id')
                            ->where('supplier_id', $supplier_id)
                            ->first();

                        if (!$row) {
                            $row = ProductSupplier::create([
                                'product_id' => $product->id,
                                'product_variant_id' => null,
                                'supplier_id' => $supplier_id
                            ]);
                        }

                        $toKeep[] = $row->id;
                    }

                    if (empty($toKeep)) {
                        ProductSupplier::where('product_id', $product->id)->whereNull('product_variant_id')->delete();
                    } else {
                        ProductSupplier::where('product_id', $product->id)
                            ->whereNull('product_variant_id')
                            ->whereNotIn('id', $toKeep)
                            ->delete();
                    }
                } else {
                    ProductSupplier::where('product_id', $product->id)->whereNull('product_variant_id')->delete();
                }

                return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(6), 'id' => encrypt($product->id)])
                    ->with('success', 'Data saved successfully');

            case 6:

                try {
                    DB::beginTransaction();

                    $product = Product::findOrFail($id);

                    $product->update([
                        'should_feature_on_home_page' => $request->input('should_feature_on_home_page', 0),
                        'is_new_product' => $request->input('is_new_product', 0),
                        'is_best_seller' => $request->input('is_best_seller', 0),
                        'seo_title' => $request->input('seo_title'),
                        'seo_description' => $request->input('seo_description'),
                        'in_draft' => $request->input('action') === 'save_draft' ? 1 : 0,
                    ]);

                    ProductCategory::where('product_id', $product->id)->delete();

                    ProductCategory::create([
                        'product_id' => $product->id,
                        'category_id' => $request->input('primary_category') ?? 1,
                        'is_primary' => 1,
                    ]);

                    if ($request->has('additional_categories')) {
                        $additionalCategories = array_diff(
                            $request->input('additional_categories'),
                            [$request->input('primary_category')]
                        );

                        foreach ($additionalCategories as $categoryId) {
                            ProductCategory::create([
                                'product_id' => $product->id,
                                'category_id' => $categoryId,
                                'is_primary' => 0,
                            ]);
                        }
                    }

                    DB::commit();

                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(7), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Failed to save product: ' . $e->getMessage());
                }

            case 7:

                $product = Product::findOrFail($id);

                if ($request->ajax()) {
                    $request->validate([
                        'op' => 'required|string',
                        'term' => 'nullable|string',
                    ]);

                    if ($request->op === 'search-variants') {
                        $term = $request->input('term', '');

                        $variantsQuery = DB::table('product_variants as pv')
                            ->join('products as p', 'pv.product_id', '=', 'p.id')
                            ->select(
                                'pv.id',
                                'pv.name',
                                'pv.sku',
                                'p.name as product_name',
                                DB::raw("'variant' as type")
                            )
                            ->where('pv.product_id', '!=', $product->id)
                            ->where(function ($query) use ($term) {
                                $query->where('pv.name', 'LIKE', "%{$term}%")
                                      ->orWhere('pv.sku', 'LIKE', "%{$term}%");
                            });

                        $simpleProductsQuery = DB::table('products as p')
                            ->select(
                                'p.id',
                                'p.name',
                                'p.sku',
                                'p.name as product_name',
                                DB::raw("'simple' as type")
                            )
                            ->where('p.type', 'simple')
                            ->where('p.id', '!=', $product->id)
                            ->where(function ($query) use ($term) {
                                $query->where('p.name', 'LIKE', "%{$term}%")
                                      ->orWhere('p.sku', 'LIKE', "%{$term}%");
                            });

                        $combinedResults = $variantsQuery->union($simpleProductsQuery)->paginate(20);

                        $grouped = $combinedResults->getCollection()->groupBy('product_name');
                        $results = [];

                        foreach ($grouped as $productName => $groupItems) {
                            $children = $groupItems->map(function($item) {

                                $text = $item->type === 'simple'
                                    ? "{$item->name} (Simple Product - SKU: {$item->sku})"
                                    : "{$item->name} (SKU: {$item->sku})";

                                return [
                                    'id' => $item->id,
                                    'text' => $text,
                                ];
                            })->values();

                            if ($productName) {
                                $results[] = [
                                    'text' => $productName,
                                    'children' => $children
                                ];
                            }
                        }

                        return response()->json([
                            'results' => $results,
                            'pagination' => ['more' => $combinedResults->hasMorePages()]
                        ]);
                    }

                    return response()->json(['message' => 'Unknown AJAX operation'], 422);
                }

                $validated = $request->validate([
                    'substitutes' => 'nullable|array',
                    'substitutes.*' => 'required|integer|exists:product_variants,id',
                ], [
                    'substitutes.*.exists' => 'The selected substitute variant is invalid.'
                ]);

                try {
                    DB::beginTransaction();

                    ProductSubtitue::where('product_id', $product->id)
                        ->whereNull('variant_id')
                        ->delete();

                    $sourceVariantIds = $validated['substitutes'] ?? [];
                    $dataToInsert = [];

                    if (!empty($sourceVariantIds)) {
                        $validSourceVariants = ProductVariant::whereIn('id', $sourceVariantIds)
                            ->where('product_id', '!=', $product->id)
                            ->get(['id', 'product_id']);

                        foreach ($validSourceVariants as $sourceVariant) {
                            $dataToInsert[] = [
                                'product_id' => $product->id,
                                'variant_id' => null,
                                'source_product_id' => $sourceVariant->product_id,
                                'source_variant_id' => $sourceVariant->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    if (!empty($dataToInsert)) {
                        ProductSubtitue::insert($dataToInsert);
                    }

                    DB::commit();

                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(8), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Failed to save substitutes');
                }

            case 8:

                $product = Product::findOrFail($id);
                $product->update(['in_draft' => 0]);

                return redirect()->route('products.index')->with('success', 'Product setup completed successfully.');                

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
