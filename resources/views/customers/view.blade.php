@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Customer Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Name:</th>
                                <td>{{ $customer->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $customer->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone Number:</th>
                                <td>+{{ $customer->dial_code }} {{ $customer->phone_number }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>{{ $customer->country ? $customer->country->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>State:</th>
                                <td>{{ $customer->state ? $customer->state->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>City:</th>
                                <td>{{ $customer->city ? $customer->city->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($customer->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $customer->created_at->format('M d, Y H:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Updated At:</th>
                                <td>{{ $customer->updated_at->format('M d, Y H:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to List</a>
                    @if(auth()->user()->can('customers.edit'))
                        <a href="{{ route('customers.edit', encrypt($customer->id)) }}" class="btn btn-primary">Edit Customer</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
