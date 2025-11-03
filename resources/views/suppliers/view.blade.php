@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Supplier Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Name:</th>
                                <td>{{ $supplier->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $supplier->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone Number:</th>
                                <td>+{{ $supplier->dial_code }} {{ $supplier->phone_number }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>{{ $supplier->country ? $supplier->country->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>State:</th>
                                <td>{{ $supplier->state ? $supplier->state->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>City:</th>
                                <td>{{ $supplier->city ? $supplier->city->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($supplier->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $supplier->created_at->format('M d, Y H:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Updated At:</th>
                                <td>{{ $supplier->updated_at->format('M d, Y H:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Back to List</a>
                    @if(auth()->user()->can('suppliers.edit'))
                        <a href="{{ route('suppliers.edit', encrypt($supplier->id)) }}" class="btn btn-primary">Edit Supplier</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
