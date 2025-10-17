<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    protected $title = 'Brands';
    protected $view = 'brands.';

    public function __construct()
    {
        $this->middleware('permission:brands.index')->only(['index']);
        $this->middleware('permission:brands.create')->only(['create']);
        $this->middleware('permission:brands.store')->only(['store']);
        $this->middleware('permission:brands.edit')->only(['edit']);
        $this->middleware('permission:brands.update')->only(['update']);
        $this->middleware('permission:brands.show')->only(['show']);
        $this->middleware('permission:brands.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage brands here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Brand::query();

        return datatables()
        ->eloquent($query)
        ->addColumn('logo_img', function ($row) {
            if ($row->logo) {
                $src = asset('storage/' . $row->logo);
                return '<img src="'.$src.'" style="height:28px;width:28px;object-fit:contain;">';
            }
            return '—';
        })
        ->addColumn('status_badge', function ($row) {
            return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
        })
        ->addColumn('action', function ($row) {
            $html = '';
            if (auth()?->user()?->isAdmin() || auth()->user()->can('brands.edit')) {
                $html .= '<a href="' . route('brands.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('brands.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('brands.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('brands.show')) {
                $html .= '<a href="' . route('brands.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }
            return $html;
        })
        ->rawColumns(['action', 'status_badge', 'logo_img'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Brand';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $logo = null;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo')->store('brands', 'public');
            }

            $data = [
                'name' => $request->string('name'),
                'slug' => $request->string('slug'),
                'description' => $request->input('description'),
                'logo' => $logo,
                'status' => (bool) $request->input('status', true),
            ];

            Brand::create($data);
            DB::commit();
            return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('brands.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $brand = Brand::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Brand Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'brand'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $brand = Brand::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Brand';
        return view($this->view . 'edit', compact('title', 'subTitle', 'brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug,' . $brand->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $logo = $brand->logo;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo')->store('brands', 'public');
            }

            $data = [
                'name' => $request->string('name'),
                'slug' => $request->string('slug'),
                'description' => $request->input('description'),
                'logo' => $logo,
                'status' => (bool) $request->input('status', false),
            ];

            $brand->update($data);
            DB::commit();
            return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('brands.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return response()->json(['success' => 'Brand deleted successfully.']);
    }
}
