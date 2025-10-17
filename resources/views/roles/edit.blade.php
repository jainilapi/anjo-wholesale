@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
<form method="POST" action="{{ route('roles.update', encrypt($role->id)) }}">
    @csrf
    @method('PUT')
        <div class="col-md-12">
        <div class="card">
            <div class="card-header"> {{  $subTitle  }} </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Role Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" @if($role->is_sytem_role == 1) readonly @else required @endif>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Select Permissions</div>
            <div class="card-body">
                
                <div class="row">
                    @forelse($permissions as $key => $permission)
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h3>
                                        <input type="checkbox" class="parent-checkbox" id="{{ Str::slug($key) }}">
                                        <label for="{{ Str::slug($key) }}"> {{  ucwords(str_replace('-', ' ', $key))  }} </label>
                                    </h3>
                                    @forelse($permission as $row)
                                    <input type="checkbox" name="permissions[]" data-parent="{{ Str::slug($key) }}" id="{{ $row->slug }}" value="{{ $row->id }}" @if(in_array($row->id, $existingPermissions)) checked @endif>
                                    <label for="{{ $row->slug }}">{{ $row->name }}</label> <br>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                    @endforelse
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary">Update Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>       
            </div>
        </div>
    </div>
    </form>    
</div>
@endsection 

@push('js')
<script>
    $(document).ready(function () {
        $('.parent-checkbox').on('change', function () {
            if ($(this).is(':checked')) {
                $(`[data-parent="${$(this).attr('id')}"]`).prop('checked', true);
            } else {
                $(`[data-parent="${$(this).attr('id')}"]`).prop('checked', false);
            }
        })                
    });
</script>
@endpush
