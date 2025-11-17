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
                <h5 class="card-title mb-0">Location Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Code:</th>
                                <td>{{ $location->code }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $location->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $location->email }}</td>
                            </tr>
                            <tr>
                                <th>Contact Number:</th>
                                <td>{{ $location->contact_number }}</td>
                            </tr>
                            <tr>
                                <th>Fax:</th>
                                <td>{{ $location->fax ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Address Line 1:</th>
                                <td>{{ $location->address_line_1 }}</td>
                            </tr>
                            <tr>
                                <th>Address Line 2:</th>
                                <td>{{ $location->address_line_2 ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>{{ $location->country ? $location->country->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>State:</th>
                                <td>{{ $location->state ? $location->state->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>City:</th>
                                <td>{{ $location->city ? $location->city->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Zipcode:</th>
                                <td>{{ $location->zipcode }}</td>
                            </tr>
                            <tr>
                                <th>Coordinates:</th>
                                <td>
                                    @if($location->latitude && $location->longitude)
                                        {{ $location->latitude }}, {{ $location->longitude }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if($location->latitude && $location->longitude)
                        <div class="map-container">
                            <div id="map"></div>
                        </div>
                        @else
                        <div class="alert alert-info p-2">
                            <i class="fa fa-info-circle"></i> No map coordinates available for this location.
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('locations.index') }}" class="btn btn-secondary">Back to List</a>
                    @if(auth()->user()->can('locations.edit'))
                        <a href="{{ route('locations.edit', encrypt($location->id)) }}" class="btn btn-primary">Edit Location</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@if($location->latitude && $location->longitude)
<script>
let map;
let marker;

function initMap() {
    const lat = {{ $location->latitude }};
    const lng = {{ $location->longitude }};
    
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: lat, lng: lng },
        zoom: 15,
    });

    marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        title: 'Location',
    });
}

window.initMap = initMap;
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key', 'YOUR_API_KEY') }}&callback=initMap"></script>
@endif
@endpush
