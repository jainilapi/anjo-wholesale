@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Category Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Name:</strong> {{ $category->name }}
                </div>
                <div class="mb-3">
                    <strong>Parent:</strong> {{ $category->parent?->name ?? '—' }}
                </div>
                <div class="mb-3">
                    <strong>Tags:</strong> {{ $category->tags ? implode(', ', $category->tags) : '—' }}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> {{ $category->status ? 'Active' : 'Inactive' }}
                </div>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div>{!! nl2br(e($category->description)) !!}</div>
                </div>
                <div class="mb-3">
                    <strong>Logo:</strong>
                    <img src="{{ $category->logo_url }}">
                </div>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection


