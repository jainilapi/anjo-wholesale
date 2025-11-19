<?php

namespace App\Http\Controllers;

use App\Models\ProductAttributeVariant;
use App\Rules\DefaultSellingUnitRule;
use App\Models\ProductAdditionalUnit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariantImage;
use App\Models\ProductTierPricing;
use App\Rules\UnitHierarchyRule;
use App\Models\ProductAttribute;
use App\Models\ProductBaseUnit;
use App\Models\ProductSupplier;
use App\Models\ProductSubtitue;
use App\Models\ProductCategory;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Models\BrandProduct;
use App\Models\ProductImage;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;

class BundledProductController extends Controller
{
    private static array $productPriceCache = [];

    public static function view($product, $step, $type)
    {
        $product = Product::findOrFail($product->id);

        $bundleCatalog = ['simple' => [], 'variant' => []];
        $existingBundleSelections = [];

        if ($type === 'bundled' && $step == 2) {
            [$bundleCatalog, $existingBundleSelections] = self::prepareBundleResources($product);
        }

        $brand = BrandProduct::where('product_id', $product->id)->with('brand')->first();
        $images = ProductImage::where('product_id', $product->id)->get();

        $categories = ProductCategory::where('product_id', $product->id)
            ->with('category')
            ->get();
        $primaryCategory = $categories->firstWhere('is_primary', 1);
        $additionalCategories = $categories->where('is_primary', 0);        

        $reviewData = [
            'brand' => $brand->brand->name ?? 'N/A',
            'primaryImage' => $images->firstWhere('is_primary', 1),
            'secondaryImages' => $images->where('is_primary', 0),
            'inventorySettings' => [
                'track_inventory' => $product->track_inventory_for_all_variant,
                'allow_backorder' => $product->allow_backorder,
                'enable_auto_reorder' => $product->enable_auto_reorder_alerts,
            ],
            'primaryCategory' => $primaryCategory->category->name ?? 'N/A',
            'additionalCategories' => $additionalCategories,
            'seo' => [
                'title' => $product->seo_title,
                'description' => $product->seo_description,
                'feature' => $product->should_feature_on_home_page,
                'new' => $product->is_new_product,
                'best_seller' => $product->is_best_seller
            ],
        ];

        return view("products/{$type}/step-{$step}", compact(
            'product',
            'type',
            'step',
            'reviewData',
            'bundleCatalog',
            'existingBundleSelections'
        ));
    }

    public static function store($request, $step, $id, $type = 'bundled')
    {
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
                        'type' => 'variable',
                        'in_draft' => 0,
                    ]);

                    BrandProduct::withTrashed()->where('product_id', $product->id)->delete();
                    BrandProduct::create([
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
                $product = Product::findOrFail($id);

                $payload = $request->input('bundle_items');
                $bundleItems = [];
                if ($payload) {
                    try {
                        $decoded = is_array($payload) ? $payload : json_decode($payload, true);
                        if (is_array($decoded)) {
                            $bundleItems = $decoded;
                        }
                    } catch (\Throwable $th) {
                    }
                }

                $request->merge(['_bundle_items' => $bundleItems]);

                $request->validate([
                    '_bundle_items' => 'required|array|min:1',
                    '_bundle_items.*.source_product_id' => 'required|integer|exists:products,id',
                    '_bundle_items.*.source_variant_id' => 'nullable|integer|exists:product_variants,id',
                    '_bundle_items.*.unit_type' => 'required|in:0,1',
                    '_bundle_items.*.unit_id' => 'required|integer',
                    '_bundle_items.*.quantity' => 'required|numeric|min:0.01',
                    'price_mode' => 'required|in:sum,fixed',
                    'fixed_price' => 'required_if:price_mode,fixed|numeric|min:0.01',
                    'discount_mode' => 'required|in:fixed,percentage',
                    'discount_value' => 'nullable|numeric|min:0',
                ]);

                if (empty($bundleItems)) {
                    return back()->withInput()->withErrors(['bundle_items' => 'Add at least one product or variant to the bundle.']);
                }

                $normalizedItems = [];
                $usedKeys = [];

                foreach ($bundleItems as $index => $row) {
                    $sourceProductId = (int) ($row['source_product_id'] ?? 0);
                    $variantId = $row['source_variant_id'] ?? null;
                    $variantId = $variantId === null || $variantId === '' ? null : (int) $variantId;
                    $unitType = (int) ($row['unit_type'] ?? -1);
                    $unitRowId = (int) ($row['unit_id'] ?? 0);
                    $quantity = (float) ($row['quantity'] ?? 0);

                    if ($sourceProductId === $product->id) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.source_product_id" => 'The bundle cannot include itself.']);
                    }

                    $key = $sourceProductId . ':' . ($variantId ?: '0');
                    if (isset($usedKeys[$key])) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.source_product_id" => 'Duplicate products or variants detected in bundle items.']);
                    }

                    $usedKeys[$key] = true;

                    $sourceProduct = Product::where('id', $sourceProductId)->whereIn('type', ['simple', 'variable'])->first();
                    if (!$sourceProduct) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.source_product_id" => 'Only simple or variable products can be bundled.']);
                    }

                    if ($variantId) {
                        $variant = ProductVariant::where('id', $variantId)->where('product_id', $sourceProductId)->first();
                        if (!$variant) {
                            return back()->withInput()->withErrors(["_bundle_items.$index.source_variant_id" => 'Variant is invalid for the selected product.']);
                        }
                    } else {
                        $variant = null;
                    }

                    if ($quantity <= 0) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.quantity" => 'Quantity must be greater than zero.']);
                    }

                    if ($unitType === 0) {
                        $unitRow = ProductBaseUnit::where('id', $unitRowId)->first();
                    } else {
                        $unitRow = ProductAdditionalUnit::where('id', $unitRowId)->first();
                    }

                    if (!$unitRow) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.unit_id" => 'Selected unit is invalid.']);
                    }

                    $unitVariantKey = (int) ($unitRow->variant_id ?? 0);
                    if ((int) $unitRow->product_id !== $sourceProductId || $unitVariantKey !== (int) ($variantId ?? 0)) {
                        return back()->withInput()->withErrors(["_bundle_items.$index.unit_id" => 'Unit does not belong to the selected product or variant.']);
                    }

                    $normalizedItems[] = [
                        'source_product_id' => $sourceProductId,
                        'source_variant_id' => $variantId,
                        'unit_type' => $unitType,
                        'unit_id' => $unitRowId,
                        'quantity' => $quantity,
                    ];
                }

                $priceMode = $request->input('price_mode', 'sum');
                $discountMode = $request->input('discount_mode', 'fixed');
                $discountValue = $request->input('discount_value');
                $discountValue = $discountValue === null || $discountValue === '' ? 0 : (float) $discountValue;

                if ($discountMode === 'percentage' && ($discountValue < 0 || $discountValue > 100)) {
                    return back()->withInput()->withErrors(['discount_value' => 'Percentage discount must be between 0 and 100.']);
                }

                $bundleTotal = self::calculateBundleSubtotal($normalizedItems);
                $fixedPrice = $priceMode === 'fixed' ? (float) $request->input('fixed_price', 0) : $bundleTotal;

                try {
                    DB::beginTransaction();

                    ProductBundle::where('product_id', $product->id)->delete();

                    foreach ($normalizedItems as $item) {
                        ProductBundle::create([
                            'product_id' => $product->id,
                            'source_product_id' => $item['source_product_id'],
                            'source_variant_id' => $item['source_variant_id'],
                            'quantity' => $item['quantity'],
                            'unit_type' => $item['unit_type'],
                            'unit_id' => $item['unit_id'],
                        ]);
                    }

                    $product->update([
                        'bundled_product_price_type' => $priceMode === 'sum' ? 0 : 1,
                        'bundled_product_fixed_price' => $fixedPrice,
                        'bundled_product_discount_type' => $discountMode === 'percentage' ? 0 : 1,
                        'bundled_product_discount' => $discountValue,
                        'in_draft' => 0,
                    ]);

                    DB::commit();

                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(3), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Failed to save bundle configuration.');
                }

            case 3:

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

                    return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(4), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Failed to save product: ' . $e->getMessage());
                }



            case 4:

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

    private static function prepareBundleResources(Product $product): array
    {
        $catalog = ['simple' => [], 'variant' => []];
        $existing = [];

        $simpleProducts = Product::select('id', 'name', 'sku', 'single_product_price')
            ->where('type', 'simple')
            ->where('id', '!=', $product->id)
            ->orderBy('name')
            ->get();

        $variableProducts = Product::select('id', 'name', 'sku', 'single_product_price')
            ->where('type', 'variable')
            ->where('id', '!=', $product->id)
            ->orderBy('name')
            ->get();

        if ($simpleProducts->isEmpty() && $variableProducts->isEmpty()) {
            return [$catalog, $existing];
        }

        $variantRecords = ProductVariant::select('id', 'product_id', 'name', 'sku')
            ->whereIn('product_id', $variableProducts->pluck('id'))
            ->with('product:id,name,single_product_price')
            ->orderBy('name')
            ->get();

        $allProductIds = $simpleProducts->pluck('id')->merge($variableProducts->pluck('id'))->unique()->values();

        if ($allProductIds->isEmpty()) {
            return [$catalog, $existing];
        }

        $baseCache = ProductBaseUnit::whereIn('product_id', $allProductIds)
            ->with('unit')
            ->get()
            ->groupBy(function ($unit) {
                return $unit->product_id . ':' . ($unit->variant_id ?: '0');
            });

        $additionalCache = ProductAdditionalUnit::whereIn('product_id', $allProductIds)
            ->with('unit')
            ->get()
            ->groupBy(function ($unit) {
                return $unit->product_id . ':' . ($unit->variant_id ?: '0');
            });

        $tierCache = ProductTierPricing::whereIn('product_id', $allProductIds)
            ->get()
            ->groupBy(function ($tier) {
                return $tier->product_id . ':' . ($tier->product_variant_id ?: '0') . ':' . $tier->product_additional_unit_id;
            });

        $catalog['simple'] = $simpleProducts->map(function ($item) use ($baseCache, $additionalCache, $tierCache) {
            $units = self::buildUnitList($item, null, $baseCache, $additionalCache, $tierCache);
            if (empty($units)) {
                return null;
            }

            return [
                'key' => 'product:' . $item->id,
                'product_id' => $item->id,
                'variant_id' => null,
                'label' => $item->name,
                'sku' => $item->sku,
                'product_name' => $item->name,
                'type' => 'simple',
                'units' => $units,
                'default_unit_id' => self::resolveDefaultUnitId($units),
            ];
        })->filter()->values()->toArray();

        $catalog['variant'] = $variantRecords->map(function ($variant) use ($baseCache, $additionalCache, $tierCache) {
            if (!$variant->product) {
                return null;
            }

            $units = self::buildUnitList($variant->product, $variant, $baseCache, $additionalCache, $tierCache);
            if (empty($units)) {
                return null;
            }

            return [
                'key' => 'variant:' . $variant->id,
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'label' => $variant->name,
                'sku' => $variant->sku,
                'product_name' => $variant->product->name,
                'type' => 'variable',
                'units' => $units,
                'default_unit_id' => self::resolveDefaultUnitId($units),
            ];
        })->filter()->values()->toArray();

        $existing = self::buildExistingBundleSelections($product, $catalog);

        return [$catalog, $existing];
    }

    private static function buildUnitList(Product $product, ?ProductVariant $variant, $baseCache, $additionalCache, $tierCache): array
    {
        $key = $product->id . ':' . ($variant?->id ?: '0');
        $units = [];
        $fallback = $product->single_product_price ?? self::getProductBasePrice($product->id);

        foreach ($baseCache->get($key, collect()) as $baseUnit) {
            $units[] = [
                'id' => $baseUnit->id,
                'unit_type' => 0,
                'unit_id' => $baseUnit->unit_id,
                'title' => $baseUnit->unit->title ?? 'N/A',
                'quantity' => 1,
                'price' => self::resolvePriceFromCache($tierCache, $product->id, $variant?->id, $baseUnit->id, $fallback),
                'is_default' => (bool) $baseUnit->is_default_selling_unit,
            ];
        }

        foreach ($additionalCache->get($key, collect()) as $additionalUnit) {
            $units[] = [
                'id' => $additionalUnit->id,
                'unit_type' => 1,
                'unit_id' => $additionalUnit->unit_id,
                'title' => $additionalUnit->unit->title ?? 'N/A',
                'quantity' => (float) $additionalUnit->quantity,
                'price' => self::resolvePriceFromCache($tierCache, $product->id, $variant?->id, $additionalUnit->id, $fallback),
                'is_default' => (bool) $additionalUnit->is_default_selling_unit,
            ];
        }

        return array_values(array_map(function ($unit) {
            $unit['price'] = (float) $unit['price'];
            return $unit;
        }, $units));
    }

    private static function resolvePriceFromCache($tierCache, int $productId, ?int $variantId, int $unitId, float $fallback): float
    {
        $key = $productId . ':' . ($variantId ?: '0') . ':' . $unitId;
        $tiers = $tierCache->get($key, collect());
        if ($tiers->isNotEmpty()) {
            $tier = $tiers->sortBy('min_qty')->first();
            if ($tier) {
                return (float) $tier->price_per_unit;
            }
        }

        return $fallback;
    }

    private static function resolveDefaultUnitId(array $units): ?int
    {
        foreach ($units as $unit) {
            if (!empty($unit['is_default'])) {
                return (int) $unit['id'];
            }
        }
        return $units[0]['id'] ?? null;
    }

    private static function buildExistingBundleSelections(Product $product, array $catalog): array
    {
        $lookup = collect(($catalog['simple'] ?? []))
            ->merge($catalog['variant'] ?? [])
            ->keyBy('key');

        return ProductBundle::where('product_id', $product->id)->get()->map(function ($bundle) use ($lookup) {
            $key = $bundle->source_variant_id ? 'variant:' . $bundle->source_variant_id : 'product:' . $bundle->source_product_id;
            if (!$lookup->has($key)) {
                return null;
            }

            return [
                'key' => $key,
                'quantity' => (float) $bundle->quantity,
                'unit_id' => (int) $bundle->unit_id,
                'unit_type' => (int) $bundle->unit_type,
            ];
        })->filter()->values()->toArray();
    }

    private static function calculateBundleSubtotal(array $items): float
    {
        $total = 0;
        foreach ($items as $item) {
            $price = self::resolveUnitPriceDirect($item['source_product_id'], $item['unit_id'], $item['source_variant_id']);
            $total += $price * $item['quantity'];
        }
        return round($total, 2);
    }

    private static function resolveUnitPriceDirect(int $productId, int $unitRowId, ?int $variantId = null): float
    {
        $query = ProductTierPricing::where('product_id', $productId)
            ->where('product_additional_unit_id', $unitRowId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $tier = $query->orderBy('min_qty')->first();
        if ($tier) {
            return (float) $tier->price_per_unit;
        }

        return self::getProductBasePrice($productId);
    }

    private static function getProductBasePrice(int $productId): float
    {
        if (!array_key_exists($productId, self::$productPriceCache)) {
            self::$productPriceCache[$productId] = (float) (Product::where('id', $productId)->value('single_product_price') ?? 0);
        }

        return self::$productPriceCache[$productId];
    }
}
