<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\BrandProduct;
use App\Models\Category;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\ProductBaseUnit;
use App\Models\ProductAdditionalUnit;

class SimpleProductController extends Controller
{
    public static function index($request, $step, $id) {
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
                $product = Product::findOrFail($id);
                if ($request->ajax()) {
                    $request->validate(['op' => 'required|string']);

                    if ($request->op === 'unit-list') {
                        $query = \App\Models\Unit::query();
                        $searchQuery = $request->input('searchQuery','');
                        if($searchQuery) {
                            $query->where(function($q)use($searchQuery){
                                $q->where('title','like',"%$searchQuery%");
                            });
                        }
                        $units = $query->limit(10)->get();
                        $data = $units->map(fn($u)=>['id'=>$u->id,'text'=>$u->title]);
                        return response()->json(['items'=>$data,'pagination'=>['more'=>false]]);
                    }

                    if ($request->op === 'fetch-units-tree') {
                        $baseRow = \App\Models\ProductBaseUnit::where('product_id',$product->id)->whereNull('varient_id')->first();
                        $base = $baseRow ? [ 'id' => $baseRow->unit_id, 'label' => (\App\Models\Unit::find($baseRow->unit_id)?->title) ?? '' ] : null;
                        $adds = \App\Models\ProductAdditionalUnit::where('product_id',$product->id)->whereNull('varient_id')->orderBy('id')->get();
                        $unitMap = [];
                        foreach($adds as $add){
                            $unitMap[$add->id] = [
                                'id' => $add->id,
                                'unit_id' => $add->unit_id,
                                'qty' => $add->quantity,
                                'parent_id' => $add->parent_id,
                                'is_default' => (bool)$add->is_default_selling_unit,
                                'unit' => [ 'label' => (\App\Models\Unit::find($add->unit_id)?->title) ?? '' ],
                            ];
                        }
                        return response()->json(['base_unit'=>$base,'additional_units'=>array_values($unitMap)]);
                    }

                    if ($request->op === 'set-base') {
                        $request->validate(['unit_id'=>'required|integer|exists:units,id']);
                        \App\Models\ProductBaseUnit::updateOrCreate(
                            ['product_id'=>$product->id,'varient_id'=>null],
                            ['unit_id'=>(int)$request->unit_id]
                        );
                        return response()->json(['success'=>true]);
                    }

                    if ($request->op === 'add-additional') {
                        $request->validate([
                            'unit_id'=>'required|integer|exists:units,id',
                            'qty'=>'required|numeric|min:1',
                            'parent_id'=>'nullable|integer|exists:product_additional_units,id'
                        ]);
                        $parent = $request->parent_id ? \App\Models\ProductAdditionalUnit::find($request->parent_id) : null;
                        if($parent) {
                            $hasChild = \App\Models\ProductAdditionalUnit::where('parent_id',$parent->id)->exists();
                            if($hasChild) return response()->json(['success'=>false,'message'=>'This unit already has a child.']);
                        }
                        $row = \App\Models\ProductAdditionalUnit::create([
                            'product_id'=>$product->id,
                            'varient_id'=>null,
                            'unit_id'=>$request->unit_id,
                            'quantity'=>$request->qty,
                            'parent_id'=>$request->parent_id,
                            'is_default_selling_unit'=>0,
                        ]);
                        return response()->json(['success'=>true,'id'=>$row->id]);
                    }

                    if ($request->op === 'update-additional-qty') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id','qty'=>'required|numeric|min:1']);
                        $row = \App\Models\ProductAdditionalUnit::findOrFail($request->id);
                        $row->quantity = $request->qty;
                        $row->save();
                        return response()->json(['success'=>true]);
                    }

                    if ($request->op === 'update-additional-unit') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id','unit_id'=>'required|integer|exists:units,id']);
                        $row = \App\Models\ProductAdditionalUnit::findOrFail($request->id);
                        $row->unit_id = $request->unit_id;
                        $row->save();
                        return response()->json(['success'=>true]);
                    }

                    if ($request->op === 'delete-additional') {
                        $request->validate(['id'=>'required|integer|exists:product_additional_units,id']);
                        $toDelete = [$request->id];
                        $queue = [$request->id];
                        while($queue){
                            $par = array_shift($queue);
                            $chs = \App\Models\ProductAdditionalUnit::where('parent_id',$par)->pluck('id')->toArray();
                            foreach($chs as $cid){ $toDelete[]=$cid; $queue[]=$cid; }
                        }
                        \App\Models\ProductAdditionalUnit::whereIn('id',$toDelete)->delete();
                        return response()->json(['success'=>true]);
                    }

                    return response()->json(['message'=>'Unknown operation'], 422);
                }

                return redirect()->route('product-management', ['type' => encrypt('simple'), 'step' => encrypt(3), 'id' => encrypt($product->id)])
                    ->with('success', 'Data saved successfully');
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
}
