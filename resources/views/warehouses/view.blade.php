@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<style>
    #map {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .map-container {
        margin-top: 15px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Warehouse Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Code:</th>
                                <td>{{ $warehouse->code }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $warehouse->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $warehouse->email }}</td>
                            </tr>
                            <tr>
                                <th>Contact Number:</th>
                                <td>{{ $warehouse->contact_number }}</td>
                            </tr>
                            <tr>
                                <th>Fax:</th>
                                <td>{{ $warehouse->fax ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Address Line 1:</th>
                                <td>{{ $warehouse->address_line_1 }}</td>
                            </tr>
                            <tr>
                                <th>Address Line 2:</th>
                                <td>{{ $warehouse->address_line_2 ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>{{ $warehouse->country ? $warehouse->country->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>State:</th>
                                <td>{{ $warehouse->state ? $warehouse->state->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>City:</th>
                                <td>{{ $warehouse->city ? $warehouse->city->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Zipcode:</th>
                                <td>{{ $warehouse->zipcode }}</td>
                            </tr>
                            <tr>
                                <th>Coordinates:</th>
                                <td>
                                    @if($warehouse->latitude && $warehouse->longitude)
                                        {{ $warehouse->latitude }}, {{ $warehouse->longitude }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if($warehouse->latitude && $warehouse->longitude)
                        <div class="map-container">
                            <div id="map"></div>
                        </div>
                        @else
                        <div class="alert alert-info p-2">
                            <i class="fa fa-info-circle"></i> No map coordinates available for this warehouse.
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Back to List</a>
                    @if(auth()->user()->can('warehouses.edit'))
                        <a href="{{ route('warehouses.edit', encrypt($warehouse->id)) }}" class="btn btn-primary">Edit Warehouse</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@if($warehouse->latitude && $warehouse->longitude)
<script>
let map;
let marker;

function initMap() {
    const warehouseLat = {{ $warehouse->latitude }};
    const warehouseLng = {{ $warehouse->longitude }};
    
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: warehouseLat, lng: warehouseLng },
        zoom: 15,
    });

    marker = new google.maps.Marker({
        position: { lat: warehouseLat, lng: warehouseLng },
        map: map,
        title: 'Warehouse',
    });
}

window.initMap = initMap;
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key', 'YOUR_API_KEY') }}&callback=initMap"></script>
@endif
@endpush
