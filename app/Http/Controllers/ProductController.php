<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\ProductImage;
use App\Models\BrandProduct;
use App\Models\Brand;

class ProductController extends Controller
{
    protected $title = 'Products';
    protected $view = 'products.';

    public function __construct()
    {
        $this->middleware('permission:products.index')->only(['index']);
        $this->middleware('permission:products.create')->only(['create']);
        $this->middleware('permission:products.store')->only(['store']);
        $this->middleware('permission:products.edit')->only(['edit']);
        $this->middleware('permission:products.update')->only(['update']);
        $this->middleware('permission:products.show')->only(['show']);
        $this->middleware('permission:products.destroy')->only(['destroy']);
        $this->middleware('permission:product-management')->only(['steps']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage products here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Product::query();

        return datatables()
        ->eloquent($query)
        ->addColumn('category_name', function ($row) {
            return $row->primaryCategory?->name ?? '—';
        })
        ->addColumn('status_badge', function ($row) {
            return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
        })
        ->addColumn('stock_badge', function ($row) {
            return $row->in_stock ? '<span class="badge bg-info">In stock</span>' : '<span class="badge bg-warning">Out of stock</span>';
        })
        ->addColumn('action', function ($row) {
            $html = '';
            if (auth()?->user()?->isAdmin() || auth()->user()->can('products.edit')) {
                $html .= '<a href="' . route('product-management', ['type' => encrypt($row->type), 'step' => encrypt(1), 'id' => encrypt($row->id)]) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            return $html;
        })
        ->editColumn('type', function ($row) {
            if ($row->type == 'simple') {
                return "Simple";
            } else if ($row->type == 'variable') {
                return "Variable";
            } else if ($row->type == 'bundled') {
                return "Bundled";
            } else {
                return "Unknown";
            }
        })
        ->rawColumns(['action', 'status_badge', 'stock_badge', 'type'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function steps(Request $request, $type = null, $step = null, $id = null)
    {
        $notFoundMessage = 'You are lost';

        if (empty($type) || empty($step) || !Helper::isValidEncryption($type) || !Helper::isValidEncryption($step)) {
            abort(404, $notFoundMessage);
        }

        $type = decrypt($type);

        if (!in_array($type, ['simple', 'variable', 'bundled'])) {
            abort(404, $notFoundMessage);
        }

        $step = decrypt($step);

        if ($type == 'simple' && !($step >= 1 && $step <= 6)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'variable' && !($step >= 1 && $step <= 7)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'bundled' && !($step >= 1 && $step <= 7)) {
            abort(404, $notFoundMessage);
        }

        if (empty($id)) {
            $product = Product::create([
                'name' => 'Untitled Product'
            ]);

            return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt($step), 'id' => encrypt($product->id)]);
        }

        $id = decrypt($id);
        $product = Product::find($id);

        if ($request->method() == 'GET') {
            return view("products/{$type}/step-{$step}", compact('product', 'step', 'type'));
        } else {
            if ($type == 'simple') {
                return $this->simple($request, $step, $id);
            }

            if ($type == 'variable') {
                return $this->variable($request, $step, $id);
            }

            if ($type == 'bundled') {
                return $this->bundled($request, $step, $id);
            }
        }
    }

    public function simple($request, $step, $id) {
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
                    return redirect()->route('product-management', ['type' => encrypt('simple'), 'step' => encrypt(2), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Something went wrong');
                }
            case 2:
                break;
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

    public function variable($request, $step, $id) {
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
                    return redirect()->route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(2), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Something went wrong');
                }
            case 2:
                $product = Product::findOrFail($id);
                if ($request->ajax()) {
                    $request->validate([
                        'op' => 'required|string',
                    ]);

                    if ($request->op === 'generate') {
                        $request->validate([
                            'attributes' => 'required|array|min:1',
                            'attributes.*.title' => 'required|string|max:120',
                            'attributes.*.values' => 'required|array|min:1',
                            'attributes.*.values.*' => 'required|string|max:120',
                        ]);

                        DB::beginTransaction();
                        try {
                            \App\Models\ProductAttribute::where('product_id', $product->id)->delete();
                            \App\Models\ProductAttributeVarient::where('product_id', $product->id)->delete();
                            \App\Models\ProductVarientImage::where('product_id', $product->id)->delete();
                            \App\Models\ProductVarient::where('product_id', $product->id)->delete();

                            $grouped = [];
                            
                            foreach ($request->input('attributes') as $attr) {
                                $title = trim($attr['title']);
                                $vals = array_values(array_filter(array_map('trim', $attr['values'])));
                                if (empty($vals)) continue;
                                $ids = [];
                                foreach ($vals as $val) {
                                    $a = \App\Models\ProductAttribute::create([
                                        'product_id' => $product->id,
                                        'title' => $title,
                                        'value' => $val,
                                    ]);
                                    $ids[] = $a->id;
                                }
                                if (!empty($ids)) $grouped[] = $ids;
                            }

                            if (empty($grouped)) {
                                DB::commit();
                                return response()->json(['items' => []]);
                            }

                            $combinations = [[]];
                            foreach ($grouped as $group) {
                                $new = [];
                                foreach ($combinations as $combo) {
                                    foreach ($group as $idAttr) {
                                        $tmp = $combo;
                                        $tmp[] = $idAttr;
                                        $new[] = $tmp;
                                    }
                                }
                                $combinations = $new;
                            }

                            $items = [];
                            $counter = 1;

                            foreach ($combinations as $set) {
                                $parts = [];
                                foreach ($set as $aid) {
                                    $a = \App\Models\ProductAttribute::find($aid);
                                    if ($a) $parts[] = $a->value;
                                }
                                $name = trim(($product->name ?: 'Product Name'). ' - ' . implode(' / ', $parts));
                                $skuSuffix = strtoupper(implode('-', array_map(function ($p) { return substr(preg_replace('/[^A-Za-z0-9]/','', $p), 0, 2); }, $parts)));
                                $sku = sprintf('PRD-%s-%03d', $skuSuffix ?: 'VAR', $counter);
                                $variant = \App\Models\ProductVarient::create([
                                    'product_id' => $product->id,
                                    'name' => $name,
                                    'sku' => $sku,
                                    'barcode' => null,
                                    'status' => 1,
                                ]);
                                foreach ($set as $aid) {
                                    \App\Models\ProductAttributeVarient::create([
                                        'product_id' => $product->id,
                                        'attribute_id' => $aid,
                                        'varient_id' => $variant->id,
                                    ]);
                                }
                                $items[] = [
                                    'id' => $variant->id,
                                    'name' => $variant->name,
                                    'sku' => $variant->sku,
                                    'barcode' => $variant->barcode,
                                    'status' => (bool) $variant->status,
                                    'attributes' => $parts,
                                    'image' => null,
                                ];
                                $counter++;
                            }

                            DB::commit();
                            return response()->json(['items' => $items]);
                        } catch (\Throwable $th) {
                            DB::rollBack();
                            return response()->json(['message' => 'Failed to generate variants'], 422);
                        }
                    }

                    if ($request->op === 'inline') {
                        $request->validate([
                            'id' => 'required|integer|exists:product_varients,id',
                            'field' => 'required|string|in:name,sku,barcode,status',
                            'value' => 'nullable',
                        ]);
                        $variant = \App\Models\ProductVarient::where('product_id', $product->id)->findOrFail($request->id);
                        if ($request->field === 'status') {
                            $variant->status = (int) !!$request->value;
                        } else {
                            $variant->{$request->field} = $request->value;
                        }
                        $variant->save();
                        return response()->json(['success' => true]);
                    }

                    if ($request->op === 'delete') {
                        $request->validate([
                            'id' => 'required|integer|exists:product_varients,id',
                        ]);
                        DB::beginTransaction();
                        try {
                            $variant = \App\Models\ProductVarient::where('product_id', $product->id)->findOrFail($request->id);
                            \App\Models\ProductAttributeVarient::where('product_id', $product->id)->where('varient_id', $variant->id)->delete();
                            \App\Models\ProductVarientImage::where('product_id', $product->id)->where('varient_id', $variant->id)->delete();
                            $variant->delete();
                            DB::commit();
                            return response()->json(['success' => true]);
                        } catch (\Throwable $th) {
                            DB::rollBack();
                            return response()->json(['message' => 'Unable to delete variant'], 422);
                        }
                    }

                    if ($request->op === 'upload-image') {
                        $request->validate([
                            'id' => 'required|integer|exists:product_varients,id',
                            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
                        ]);
                        $variant = \App\Models\ProductVarient::where('product_id', $product->id)->findOrFail($request->id);
                        $path = $request->file('file')->store('products/variants', 'public');
                        \App\Models\ProductVarientImage::where('product_id', $product->id)->where('varient_id', $variant->id)->where('is_primary',1)->delete();
                        $img = \App\Models\ProductVarientImage::create([
                            'product_id' => $product->id,
                            'varient_id' => $variant->id,
                            'is_primary' => 1,
                            'file' => $path,
                        ]);
                        return response()->json(['url' => asset('storage/'.$img->file)]);
                    }

                    if ($request->op === 'generate-barcodes') {
                        $variants = \App\Models\ProductVarient::where('product_id', $product->id)->get();
                        $i = 1;
                        foreach ($variants as $v) {
                            $v->barcode = sprintf('BC%06d', $product->id * 1000 + $i);
                            $v->save();
                            $i++;
                        }
                        return response()->json(['success' => true]);
                    }

                    if ($request->op === 'enable-all') {
                        \App\Models\ProductVarient::where('product_id', $product->id)->update(['status' => 1]);
                        return response()->json(['success' => true]);
                    }

                    if ($request->op === 'list') {
                        $variants = \App\Models\ProductVarient::where('product_id', $product->id)->get();
                        $items = [];
                        foreach ($variants as $v) {
                            $attrIds = \App\Models\ProductAttributeVarient::where('product_id', $product->id)->where('varient_id', $v->id)->pluck('attribute_id')->toArray();
                            $parts = [];
                            if (!empty($attrIds)) {
                                $parts = \App\Models\ProductAttribute::whereIn('id', $attrIds)->pluck('value')->toArray();
                            }
                            $img = \App\Models\ProductVarientImage::where('product_id', $product->id)->where('varient_id', $v->id)->where('is_primary',1)->first();
                            $items[] = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'sku' => $v->sku,
                                'barcode' => $v->barcode,
                                'status' => (bool)$v->status,
                                'attributes' => $parts,
                                'image' => $img ? asset('storage/'.$img->file) : null,
                            ];
                        }
                        return response()->json(['items' => $items]);
                    }

                    return response()->json(['message' => 'Unknown operation'], 422);
                }

                    return redirect()->route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(3), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
            case 3:
                $product = Product::findOrFail($id);
                if ($request->ajax()) {
                    $request->validate(['op' => 'required|string']);
                    if($request->op === 'fetch-variants-units-tree') {
                        $variants = \App\Models\ProductVarient::where('product_id', $product->id)->get();
                        $results = [];
                        foreach ($variants as $variant) {
                            $baseUnitRow = \App\Models\ProductBaseUnit::where('product_id', $product->id)->where('varient_id', $variant->id)->first();
                            $baseUnit = $baseUnitRow ? [
                                'id' => $baseUnitRow->unit_id,
                                'label' => (\App\Models\Unit::find($baseUnitRow->unit_id)?->title) ?? '',
                                'is_default' => true,
                            ] : null;
                            $adds = \App\Models\ProductAdditionalUnit::where('product_id',$product->id)->where('varient_id',$variant->id)->orderBy('id')->get();
                            $unitMap = [];
                            foreach($adds as $add) {
                                $unitMap[$add->id] = [
                                    'id' => $add->id,
                                    'unit_id' => $add->unit_id,
                                    'qty' => $add->quantity,
                                    'parent_id' => $add->parent_id,
                                    'is_default' => (bool)$add->is_default_selling_unit,
                                    'unit' => [
                                        'label' => (\App\Models\Unit::find($add->unit_id)?->title) ?? ''
                                    ],
                                ];
                            }
                            foreach ($unitMap as $uid => &$unit) { $unit['children'] = array_filter($unitMap, fn($x) => $x['parent_id']===$uid); }
                            unset($unit);
                            $results[] = [ 'id' => $variant->id, 'name' => $variant->name, 'base_unit' => $baseUnit, 'additional_units' => array_values($unitMap) ];
                        }
                        return response()->json(['variants'=>$results]);
                    }
                    if($request->op === 'set-base') {
                        $request->validate([
                            'variant_id' => 'required|integer|exists:product_varients,id',
                            'unit_id' => 'required|integer|exists:units,id',
                        ]);
                        $variant = \App\Models\ProductVarient::where('product_id',$product->id)->findOrFail($request->variant_id);
                        \App\Models\ProductBaseUnit::updateOrCreate(
                            ['product_id'=>$product->id,'varient_id'=>$variant->id],
                            ['unit_id'=>(int)$request->unit_id]
                        );
                        return response()->json(['success'=>true]);
                    }
                    if($request->op === 'unit-list') {
                        $query = \App\Models\Unit::query();
                        $searchQuery = $request->input('searchQuery','');
                        if($searchQuery) {
                            $query->where(function($q)use($searchQuery){$q->where('title','like',"%$searchQuery%") ->orWhere('text','like',"%$searchQuery% ");});}
                        $units = $query->limit(10)->get();
                        $data = $units->map(fn($u)=>['id'=>$u->id, 'text'=>$u->title]);
                        return response()->json(['items'=>$data, 'pagination'=>['more'=>false]]);
                    }
                    if($request->op === 'add-additional') {
                        $request->validate([
                            'variant_id'=>'required|integer|exists:product_varients,id',
                            'unit_id'=>'required|integer|exists:units,id',
                            'qty'=>'required|numeric|min:1',
                            'parent_id'=>'nullable|integer|exists:product_additional_units,id'
                        ]);
                        $variantId = $request->variant_id;
                        $parent = $request->parent_id ? \App\Models\ProductAdditionalUnit::find($request->parent_id) : null;
                        // Only one child allowed
                        if($parent) {
                            $hasChild = \App\Models\ProductAdditionalUnit::where('parent_id', $parent->id)->exists();
                            if($hasChild) return response()->json(['success'=>false, 'message'=>'This unit already has a child.']);
                        }
                        // Prevent same unit_id under same parent/branch for this variant
                        $branchIds = collect([$request->parent_id]);
                        while($branchIds->last()) {
                            $anc = \App\Models\ProductAdditionalUnit::find($branchIds->last());
                            $branchIds->push($anc?$anc->parent_id:null);
                        }
                        if(in_array($request->unit_id, $branchIds->toArray())) return response()->json(['success'=>false,'message'=>'Cyclic unit structure not allowed.']);
                        $row = \App\Models\ProductAdditionalUnit::create([
                            'product_id'=>$product->id,
                            'varient_id'=>$variantId,
                            'unit_id'=>$request->unit_id,
                            'quantity'=>$request->qty,
                            'parent_id'=>$request->parent_id,
                            'is_default_selling_unit'=>0,
                        ]);
                        return response()->json(['success'=>true]);
                    }
                    if($request->op === 'update-additional-qty') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id','qty'=>'required|numeric|min:1']);
                        $row = \App\Models\ProductAdditionalUnit::findOrFail($request->id);
                        $row->quantity = $request->quantity;
                        $row->save();
                        return response()->json(['success'=>true]);
                    }
                    if($request->op === 'toggle-default') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id','value'=>'required|boolean']);
                        $row = \App\Models\ProductAdditionalUnit::findOrFail($request->id);
                        // Only one default per variant
                        \App\Models\ProductAdditionalUnit::where('varient_id',$row->varient_id)->where('product_id',$row->product_id)->update(['is_default_selling_unit'=>0]);
                        $row->is_default_selling_unit = $request->value ? 1 : 0;
                        $row->save();
                        return response()->json(['success'=>true]);
                    }
                    if($request->op==='delete-additional') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id']);
                        // Delete this additional unit and any children recursively
                        $toDelete = [$request->id];
                        $queue = [$request->id];
                        while($queue) {
                            $par = array_shift($queue);
                            $chs = \App\Models\ProductAdditionalUnit::where('parent_id',$par)->pluck('id')->toArray();
                            foreach($chs as $cid) { $toDelete[]=$cid; $queue[]=$cid; }
                        }
                        \App\Models\ProductAdditionalUnit::whereIn('id',$toDelete)->delete();
                        return response()->json(['success'=>true]);
                    }
                }

                    return redirect()->route('product-management', ['type' => encrypt('variable'), 'step' => encrypt(4), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
            case 4:

        try {
            DB::beginTransaction();

            // Get or create product
            $product = Product::findOrFail($id);

            // Update product promotional and SEO settings
            $product->update([
                'should_feature_on_home_page' => $request->input('should_feature_on_home_page', 0),
                'is_new_product' => $request->input('is_new_product', 0),
                'is_best_seller' => $request->input('is_best_seller', 0),
                'seo_title' => $request->input('seo_title'),
                'seo_description' => $request->input('seo_description'),
                'in_draft' => $request->input('action') === 'save_draft' ? 1 : 0,
            ]);

            // Clear existing categories
            \App\Models\ProductCategory::where('product_id', $product->id)->delete();

            // Save primary category
            \App\Models\ProductCategory::create([
                'product_id' => $product->id,
                'category_id' => $request->input('primary_category'),
                'is_primary' => 1,
            ]);

            // Save additional categories
            if ($request->has('additional_categories')) {
                $additionalCategories = array_diff(
                    $request->input('additional_categories'),
                    [$request->input('primary_category')]
                );

                foreach ($additionalCategories as $categoryId) {
                    \App\Models\ProductCategory::create([
                        'product_id' => $product->id,
                        'category_id' => $categoryId,
                        'is_primary' => 0,
                    ]);
                }
            }

            DB::commit();

                return redirect()
                    ->route('products.index')
                    ->with('success', 'Data saved successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to save product: ' . $e->getMessage());
        }
            case 5:

                break;
            case 6:

                break;
            case 7:

                break;
            default:
                abort(404);
                break;
        }
    }

    public function bundled($request, $step, $id) {
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
                        'type' => 'bundled',
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
                    return redirect()->route('product-management', ['type' => encrypt('bundled'), 'step' => encrypt(2), 'id' => encrypt($product->id)])
                        ->with('success', 'Data saved successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Something went wrong');
                }
            case 2:

                break;
            case 3:

                break;
            case 4:

                break;
            case 5:

                break;
            case 6:

                break;
            case 7:

                break;
            default:
                abort(404);
                break;
        }        
    }

    public function deleteImage(Request $request)
    {
        $request->validate([
            'image_id' => 'required|integer|exists:product_images,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $image = ProductImage::where('id', $request->image_id)
            ->where('product_id', $request->product_id)
            ->firstOrFail();

        if ((int) $image->is_primary === 1) {
            return response()->json(['message' => 'Cannot delete primary image'], 422);
        }

        $image->delete();
        return response()->json(['success' => true]);
    }
}
