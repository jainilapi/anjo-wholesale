<?php

namespace App\Http\Controllers;

use App\Http\Controllers\VariableProductController;
use App\Http\Controllers\BundledProductController;
use App\Http\Controllers\SimpleProductController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\BrandProduct;
use App\Models\Category;
use App\Helpers\Helper;
use App\Models\Product;
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

        if ($type == 'simple' && !($step >= 1 && $step <= 8)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'variable' && !($step >= 1 && $step <= 9)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'bundled' && !($step >= 1 && $step <= 4)) {
            abort(404, $notFoundMessage);
        }

        if (empty($id)) {
            $product = Product::create([
                'name' => 'Untitled Product',
                'type' => $type
            ]);

            return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt($step), 'id' => encrypt($product->id)]);
        }

        $id = decrypt($id);
        $product = Product::find($id);
        $product->type = $type;
        $product->save();

        if ($request->method() == 'GET') {
            if ($type == 'simple') {
                return SimpleProductController::view($product, $step, $type);
            }

            if ($type == 'variable') {
                return VariableProductController::view($product, $step, $type);
            }

            if ($type == 'bundled') {
                return BundledProductController::view($product, $step, $type);
            }
        } else {
            if ($type == 'simple') {
                return SimpleProductController::store($request, $step, $id, $type);
            }

            if ($type == 'variable') {
                return VariableProductController::store($request, $step, $id);
            }

            if ($type == 'bundled') {
                return BundledProductController::store($request, $step, $id);
            }
        }
    }
}
