@extends('layouts.app',['title' => $title, 'subTitle' => $subTitle,'datatable' => true, 'select2' => true, 'datepicker' => true])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('locations.create') }}" class="btn btn-primary float-end"> 
                    <i class="fa fa-plus"></i> Add New Location 
                </a>
            </div>
            <div class="card-body">
                <table id="datatables-reponsive" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Coordinates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>

    $(document).ready(function () {

        let dataTable = $('#datatables-reponsive').DataTable({
            pageLength : 10,
            searching: false,
            processing: true,
            serverSide: true,
            ajax: {
                "url": "{{ route(Request::route()->getName()) }}",
                "type": "GET"
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                },
                {
                    data: 'code',
                },
                {
                    data: 'name',
                },
                {
                    data: 'address',
                },
                {
                    data: 'location',
                },
                {
                    data: 'contact_number',
                },
                {
                    data: 'coordinates',
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                }
            ],
        });

        $(document).on('click', '#deleteRow', function () {
            let url = $(this).data('row-route');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.success, 'success');
                                dataTable.ajax.reload();
                            } else if (response.error) {
                                Swal.fire('Error', response.error, 'error');
                            }
                        },
                        error: function (xhr) {
                            let msg = 'An error occurred.';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            }
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });
    });

</script>
@endpush
