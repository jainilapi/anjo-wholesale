<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
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

        $product = Product::find($id);

        if ($request->method() == 'GET') {
            return view("products/{$type}/step-{$step}", compact('product'));
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
        
    }

    public function variable($request, $step, $id) {

    }

    public function bundled($request, $step, $id) {
        
    }
}
