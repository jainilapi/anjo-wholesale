<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

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
        $query = Product::with('category');

        return datatables()
        ->eloquent($query)
        ->addColumn('category_name', function ($row) {
            return $row->category?->name ?? '—';
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
                $html .= '<a href="' . route('products.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('products.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('products.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('products.show')) {
                $html .= '<a href="' . route('products.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }
            return $html;
        })
        ->rawColumns(['action', 'status_badge', 'stock_badge'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Product';
        $categories = Category::pluck('name', 'id');
        return view($this->view . 'create', compact('title', 'subTitle', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|boolean',
            'in_stock' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $uploadedImages[] = $path;
                }
            }

            $data = [
                'category_id' => $request->input('category_id'),
                'name' => $request->string('name'),
                'sku' => $request->string('sku'),
                'description' => $request->input('description'),
                'images' => $uploadedImages,
                'status' => (bool) $request->input('status', true),
                'in_stock' => (bool) $request->input('in_stock', true),
            ];

            Product::create($data);
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('products.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Product Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Product';
        $categories = Category::pluck('name', 'id');
        return view($this->view . 'edit', compact('title', 'subTitle', 'product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail(decrypt($id));
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|boolean',
            'in_stock' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $uploadedImages = $product->images ?? [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $uploadedImages[] = $path;
                }
            }

            $data = [
                'category_id' => $request->input('category_id'),
                'name' => $request->string('name'),
                'sku' => $request->string('sku'),
                'description' => $request->input('description'),
                'images' => $uploadedImages,
                'status' => (bool) $request->input('status', false),
                'in_stock' => (bool) $request->input('in_stock', false),
            ];

            $product->update($data);
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('products.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => 'Product deleted successfully.']);
    }
}
