<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    protected $title = 'Suppliers';
    protected $view = 'suppliers.';

    public function __construct()
    {
        $this->middleware('permission:suppliers.index')->only(['index']);
        $this->middleware('permission:suppliers.create')->only(['create']);
        $this->middleware('permission:suppliers.store')->only(['store']);
        $this->middleware('permission:suppliers.edit')->only(['edit']);
        $this->middleware('permission:suppliers.update')->only(['update']);
        $this->middleware('permission:suppliers.show')->only(['show']);
        $this->middleware('permission:suppliers.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage suppliers here';

        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $supplierRole = Role::where('slug', 'supplier')->first();
        
        $query = User::query()
        ->whereHas('roles', function ($builder) use ($supplierRole) {
            $builder->where('id', $supplierRole->id);
        });

        return datatables()
        ->eloquent($query)
        ->editColumn('phone_number', function ($row) {
            return '+' . $row->dial_code . ' ' . $row->phone_number;
        })
        ->addColumn('location', function ($row) {
            $location = [];
            if ($row->city) $location[] = $row->city->name;
            if ($row->state) $location[] = $row->state->name;
            if ($row->country) $location[] = $row->country->name;
            return implode(', ', $location);
        })
        ->addColumn('status', function ($row) {
            if ($row->status) {
                return '<span class="badge bg-success"> Active </span>';
            } else {
                return '<span class="badge bg-danger"> InActive </span>';
            }
        })
        ->addColumn('action', function ($row) {
            $html = '';

            if (auth()?->user()?->isAdmin() || auth()->user()->can('suppliers.edit')) {
                $html .= '<a href="' . route('suppliers.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('suppliers.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('suppliers.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('suppliers.show')) {
                $html .= '<a href="' . route('suppliers.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }

            return $html;
        })
        ->rawColumns(['status', 'action'])
        ->addIndexColumn()
        ->toJson();
    }

    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Supplier';
        $countries = Country::pluck('name', 'id');
        return view($this->view . 'create', compact('title', 'subTitle', 'countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'dial_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20|unique:users,phone_number',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'status' => 'required|boolean',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'country_id', 'state_id', 'city_id', 'status', 'password']);
            $data['added_by'] = auth()->user()->id;

            $supplier = User::create($data);
            
            $supplierRole = Role::where('slug', 'supplier')->first();
            if ($supplierRole) {
                $supplier->roles()->attach($supplierRole->id);
            }

            DB::commit();
            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('suppliers.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function show(string $id)
    {
        $supplier = User::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Supplier Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'supplier'));
    }

    public function edit(string $id)
    {
        $supplier = User::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Supplier';
        $countries = Country::pluck('name', 'id');
        return view($this->view . 'edit', compact('title', 'subTitle', 'supplier', 'countries'));
    }

    public function update(Request $request, string $id)
    {
        $supplier = User::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $supplier->id,
            'dial_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $supplier->id,
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'country_id', 'state_id', 'city_id', 'status']);
            
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $supplier->update($data);

            DB::commit();
            return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('suppliers.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function destroy(string $id)
    {
        $supplier = User::findOrFail($id);
        $supplier->delete();
        return response()->json(['success' => 'Supplier deleted successfully.']);
    }
}
