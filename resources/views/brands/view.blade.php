@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Brand Details</div>
            <div class="card-body">
                <div class="mb-3"><strong>Name:</strong> {{ $brand->name }}</div>
                <div class="mb-3"><strong>Slug:</strong> {{ $brand->slug }}</div>
                <div class="mb-3"><strong>Status:</strong> {{ $brand->status ? 'Active' : 'Inactive' }}</div>
                <div class="mb-3"><strong>Description:</strong><div>{!! nl2br(e($brand->description)) !!}</div></div>
                <div class="mb-3">
                    <strong>Logo:</strong>
                    <div>
                        @if($brand->logo)
                        <img src="{{ asset('storage/'.$brand->logo) }}" style="height:80px;width:auto;object-fit:contain;">
                        @else
                        —
                        @endif
                    </div>
                </div>
                <a href="{{ route('brands.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection


