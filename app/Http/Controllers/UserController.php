<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    protected $title = 'Users';
    protected $view = 'users.';

    protected static $excludeRoles = ['customer'];

    public function __construct()
    {
        $this->middleware('permission:users.index')->only(['index']);
        $this->middleware('permission:users.create')->only(['create']);
        $this->middleware('permission:users.store')->only(['store']);
        $this->middleware('permission:users.edit')->only(['edit']);
        $this->middleware('permission:users.update')->only(['update']);
        $this->middleware('permission:users.show')->only(['show']);
        $this->middleware('permission:users.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage users here';

        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    /**
     * return the json resource.
     */
    public function ajax()
    {
        $query = User::query()
        ->whereHas('roles', function ($builder) {
            $builder->whereNotIn('slug', self::$excludeRoles)
            ->whereIn('slug', Role::whereNotIn('slug', self::$excludeRoles)->pluck('slug')->toArray());
        });

        return datatables()
        ->eloquent($query)
        ->editColumn('phone_number', function ($row) {
            return '+' . $row->dial_code . ' ' . $row->phone_number;
        })
        ->addColumn('roles', function ($row) {
            $html = '';

            foreach ($row->roles->pluck('name')->toArray() as $role) {
                $html .= '<span class="badge bg-primary">'. $role .'</span>&nbsp;';
            }

            return $html;
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

            if (auth()?->user()?->isAdmin() || auth()->user()->can('users.edit')) {
                $html .= '<a href="' . route('users.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('users.destroy')) {
                if ($row->id != auth()->user()->id) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('users.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
            }

            if (auth()?->user()?->isAdmin() || auth()->user()->can('users.show')) {
                $html .= '<a href="' . route('users.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
            }

            return $html;
        })
        ->rawColumns(['status', 'roles', 'action'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New User';
        $roles = Role::whereNotIn('slug', self::$excludeRoles)->pluck('name', 'id');
        return view($this->view . 'create', compact('title', 'subTitle', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'dial_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20|unique:users,phone_number',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:6',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        \DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'status']);
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $data['added_by'] = auth()->user()->id;

            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('storage/users/profiles'), $filename);
                $data['profile'] = $filename;
            }

            $user = User::create($data);
            if ($request->has('roles') && is_array($request->roles)) {
                foreach ($request->roles as $roleId) {
                    $user->roles()->attach($roleId);
                }
            }

            \DB::commit();
            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('users.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'User Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit User';
        $roles = Role::whereNotIn('slug', self::$excludeRoles)->pluck('name', 'id');
        $userRoleIds = $user->roles->pluck('id')->toArray();
        return view($this->view . 'edit', compact('title', 'subTitle', 'user', 'roles', 'userRoleIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'dial_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $user->id,
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:6',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        \DB::beginTransaction();

        try {
                $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'status']);
                if ($request->filled('password')) {
                    $data['password'] = $request->password;
                }

                if ($request->hasFile('profile')) {
                    $file = $request->file('profile');
                    $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('storage/users/profiles'), $filename);
                    $data['profile'] = $filename;
                }

                $user->update($data);
                $user->roles()->detach();
                if ($request->has('roles') && is_array($request->roles)) {
                    foreach ($request->roles as $roleId) {
                        $user->roles()->attach($roleId);
                    }
                }

            \DB::commit();
            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('users.index')->with('error', 'Something Went Wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['success' => 'User deleted successfully.']);
    }
}
