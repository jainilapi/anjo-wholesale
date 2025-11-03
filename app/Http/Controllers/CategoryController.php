<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    protected $title = 'Categories';
    protected $view = 'categories.';

    public function __construct()
    {
        $this->middleware('permission:categories.index')->only(['index']);
        $this->middleware('permission:categories.create')->only(['create']);
        $this->middleware('permission:categories.store')->only(['store']);
        $this->middleware('permission:categories.edit')->only(['edit']);
        $this->middleware('permission:categories.update')->only(['update']);
        $this->middleware('permission:categories.show')->only(['show']);
        $this->middleware('permission:categories.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage categories here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Category::with('parent');

        return datatables()
        ->eloquent($query)
        ->addColumn('parent', function ($row) {
            return $row->parent?->name ?? '—';
        })
        ->addColumn('status_badge', function ($row) {
            return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
        })
        ->addColumn('action', function ($row) {
            $html = '';
            if (auth()?->user()?->isAdmin() || auth()->user()->can('categories.edit')) {
                $html .= '<a href="' . route('categories.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('categories.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('categories.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }
            if (auth()?->user()?->isAdmin() || auth()->user()->can('categories.show')) {
                $html .= '<a href="' . route('categories.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }
            return $html;
        })
        ->rawColumns(['action', 'status_badge'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Category';
        $parents = Category::pluck('name', 'id');
        return view($this->view . 'create', compact('title', 'subTitle', 'parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        DB::beginTransaction();
        try {

            $data = [
                'name' => $request->string('name'),
                'parent_id' => $request->input('parent_id'),
                'tags' => $request->input('tags', []),
                'description' => $request->input('description'),
                'status' => (bool) $request->input('status', true),
            ];

            $destinationPath = storage_path('app/public/categories');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $data['logo'] = 'IMG-' . date('YmdHis-') .  $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $data['logo']);
            }

            Category::create($data);
            DB::commit();
            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('categories.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::with('parent', 'children')->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Category Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $category = Category::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Category';
        $parents = Category::where('id', '!=', $category->id)->pluck('name', 'id');
        return view($this->view . 'edit', compact('title', 'subTitle', 'category', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $data = [
                'name' => $request->string('name'),
                'parent_id' => $request->input('parent_id'),
                'tags' => $request->input('tags', []),
                'description' => $request->input('description'),
                'status' => (bool) $request->input('status', false),
            ];

            $destinationPath = storage_path('app/public/categories');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $data['logo'] = 'IMG-' . date('YmdHis-') .  $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $data['logo']);
            }

            $category->update($data);
            DB::commit();
            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('categories.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['success' => 'Category deleted successfully.']);
    }
}
