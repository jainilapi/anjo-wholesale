<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class WarehouseLocationController extends Controller
{
    protected $title = 'Locations';
    protected $view = 'locations.';

    public function __construct()
    {
        $this->middleware('permission:locations.index')->only(['index']);
        $this->middleware('permission:locations.create')->only(['create']);
        $this->middleware('permission:locations.store')->only(['store']);
        $this->middleware('permission:locations.edit')->only(['edit']);
        $this->middleware('permission:locations.update')->only(['update']);
        $this->middleware('permission:locations.show')->only(['show']);
        $this->middleware('permission:locations.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage locations here';

        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Warehouse::l()->with(['country', 'state', 'city'])->w();

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

            if (auth()?->user()?->isAdmin() || auth()->user()->can('locations.edit')) {
                $html .= '<a href="' . route('locations.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('locations.destroy')) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('locations.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('locations.show')) {
                $html .= '<a href="' . route('locations.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Location';
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

            // Force type = 0 for Locations
            $data['type'] = 0;

            Warehouse::create($data);

            DB::commit();
            return redirect()->route('locations.index')->with('success', 'Location created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('locations.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function show(string $id)
    {
        $location = Warehouse::with(['country', 'state', 'city'])->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Location Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'location'));
    }

    public function edit(string $id)
    {
        $location = Warehouse::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Location';
        $countries = Country::pluck('name', 'id');
        return view($this->view . 'edit', compact('title', 'subTitle', 'location', 'countries'));
    }

    public function update(Request $request, string $id)
    {
        $location = Warehouse::findOrFail(decrypt($id));
        $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code,' . $location->id,
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

            // Ensure type remains 0
            $data['type'] = 0;

            $location->update($data);

            DB::commit();
            return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('locations.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function destroy(string $id)
    {
        $location = Warehouse::findOrFail($id);
        $location->delete();
        return response()->json(['success' => 'Location deleted successfully.']);
    }
}
