<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected $title = 'Warehouses';
    protected $view = 'warehouses.';

    public function __construct()
    {
        $this->middleware('permission:warehouses.index')->only(['index']);
        $this->middleware('permission:warehouses.create')->only(['create']);
        $this->middleware('permission:warehouses.store')->only(['store']);
        $this->middleware('permission:warehouses.edit')->only(['edit']);
        $this->middleware('permission:warehouses.update')->only(['update']);
        $this->middleware('permission:warehouses.show')->only(['show']);
        $this->middleware('permission:warehouses.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage warehouses here';

        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Warehouse::with(['country', 'state', 'city'])->w();

        return datatables()
        ->eloquent($query)
        ->addColumn('address', function ($row) {
            $address = $row->address_line_1;
            if ($row->address_line_2) {
                $address .= ', ' . $row->address_line_2;
            }
            return $address;
        })
        ->addColumn('location', function ($row) {
            $location = [];
            if ($row->city) $location[] = $row->city->name;
            if ($row->state) $location[] = $row->state->name;
            if ($row->country) $location[] = $row->country->name;
            return implode(', ', $location);
        })
        ->addColumn('coordinates', function ($row) {
            if ($row->latitude && $row->longitude) {
                return $row->latitude . ', ' . $row->longitude;
            }
            return 'N/A';
        })
        ->addColumn('action', function ($row) {
            $html = '';

            if (auth()?->user()?->isAdmin() || auth()->user()->can('warehouses.edit')) {
                $html .= '<a href="' . route('warehouses.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('warehouses.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('warehouses.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('warehouses.show')) {
                $html .= '<a href="' . route('warehouses.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }

            return $html;
        })
        ->rawColumns(['action'])
        ->addIndexColumn()
        ->toJson();
    }

    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Warehouse';
        $countries = Country::pluck('name', 'id');
        return view($this->view . 'create', compact('title', 'subTitle', 'countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'zipcode' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only([
                'code', 'name', 'address_line_1', 'address_line_2', 'country_id', 
                'state_id', 'city_id', 'zipcode', 'email', 'contact_number', 
                'fax', 'latitude', 'longitude'
            ]);

            Warehouse::create($data);

            DB::commit();
            return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('warehouses.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function show(string $id)
    {
        $warehouse = Warehouse::with(['country', 'state', 'city'])->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Warehouse Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'warehouse'));
    }

    public function edit(string $id)
    {
        $warehouse = Warehouse::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Warehouse';
        $countries = Country::pluck('name', 'id');
        return view($this->view . 'edit', compact('title', 'subTitle', 'warehouse', 'countries'));
    }

    public function update(Request $request, string $id)
    {
        $warehouse = Warehouse::findOrFail(decrypt($id));
        $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'zipcode' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only([
                'code', 'name', 'address_line_1', 'address_line_2', 'country_id', 
                'state_id', 'city_id', 'zipcode', 'email', 'contact_number', 
                'fax', 'latitude', 'longitude'
            ]);

            $warehouse->update($data);

            DB::commit();
            return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('warehouses.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function destroy(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return response()->json(['success' => 'Warehouse deleted successfully.']);
    }
}