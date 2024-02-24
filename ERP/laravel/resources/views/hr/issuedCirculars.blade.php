@extends('layout.app')

@section('title', 'Issued Circulars')

@section('page')

<div class="container-fluid">
    <div>
        <h1 class="d-inline-block my-10">Issued Circulars</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="issued-circular-form" method="GET">
                <div class="row mb-3">
                    
                    <div class="col-md-2">
                        <label for="reference" class="form-label">Reference No:</label>
                        <input type="text" name="reference" id="reference" class="form-control" placeholder="ABC12345TD" value="{{ (isset($userInputs['reference']) && !empty($userInputs['reference'])) ? $userInputs['reference'] : '' }}">
                    </div>

                    <div class="col-md-2">
                        <label for="circular_date_from" class="form-label">Circulars Date From:</label>
                        <input
                            type="text"
                            name="circular_date_from"
                            id="circular_date_from"
                            class="form-control"
                            data-parsley-trigger-after-failure="change"
                            data-parsley-date="{{ dateformat('momentJs') }}"
                            data-control="bsDatepicker"
                            data-dateformat="{{ dateformat('bsDatepicker') }}"
                            data-date-today-btn="linked"
                            placeholder="d-M-yyyy"
                            value="{{ (isset($userInputs['circular_date_from']) && $userInputs['circular_date_from'] != '') ? date(dateformat(), strtotime($userInputs['circular_date_from'])) : '' }}" >
                    </div>

                    <div class="col-md-2">
                        <label for="circular_date_to" class="form-label">Circulars Date To:</label>
                        <input
                            type="text"
                            name="circular_date_to"
                            id="circular_date_to"
                            class="form-control"
                            data-parsley-trigger-after-failure="change"
                            data-parsley-date="{{ dateformat('momentJs') }}"
                            data-control="bsDatepicker"
                            data-dateformat="{{ dateformat('bsDatepicker') }}"
                            data-date-today-btn="linked"
                            placeholder="d-M-yyyy"
                            value="{{ (isset($userInputs['circular_date_to']) && $userInputs['circular_date_to'] != '') ? date(dateformat(), strtotime($userInputs['circular_date_to'])) : '' }}" >
                    </div>

                    <div class="col-md-2 d-flex justify-content-center mt-8">
                        <button type="submit" class="btn btn-primary m-2">Filter</button>
                        <button type="reset" class="btn btn-secondary m-2">Reset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-10">
        <div class="card-body">
            <div class="w-100 table-responsive">
                <table id="issued-circular-table" class="table-striped table w-100 table-row-bordered g-3 text-nowrap thead-strong dataTable">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Reference</th>
                            <th>Memo</th>
                            <th>Circular Date</th>
                            <th>Issued By</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultList  as $key => $list)
                            <tr>
                                <td>{{ $resultList->firstItem() + $loop->index }}</td>
                                <td>{{ $list->reference }}</td>
                                <td>{{ $list->memo }}</td>
                                <td>{{ $list->formatted_circular_date }}</td>
                                <td>{{ $list->issued_by }}</td>
                                <td>
                                    <a target="_blank" href="{{ route('file.view', ['name' => 'circular', 'file' => $list->file]) }}" class="btn btn-sm btn-info btn-view" data-id="{{ $list->id }}" title="View" >
                                        <span class="fas fa-eye"></span>
                                    </a>
                                
                                    <a href="{{ route('file.download', ['type' => 'circular', 'file' => $list->file]) }}" class="btn btn-sm btn-info btn-download" data-id="{{ $list->id }}" title="Download" >
                                        <span class="fas fa-download"></span>
                                    </a>
                                    @if(empty($list->acknowledgement_id))
                                        <button data-action="acknowledge" title="Acknowledge" class="btn btn-sm btn-success" data-id="{{ $list->id }}" >
                                            <span class="fas fa-check"></span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-10">
                <p> Page {{ $resultList->currentPage() }} of {{ $resultList->lastPage() }} <br></p>
                <div class="pagination d-flex justify-around">
                    {{ $resultList->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')

<script>

    $(document).ready(function() {

        $('#issued-circular-form').on('reset', function() {
            setTimeout(() => {
                $('#reference').val('');
                $("input[data-control='bsDatepicker'], [data-control='bsDatepicker'] input").val('').trigger('change');
            })
        });
        
        $('#issued-circular-table').DataTable({
            paging: false, 
            info: false
        });

        route.push('circular.acknowledge','{{ rawRoute('circular.acknowledge') }}');
        
        $('#issued-circular-table').on('click', 'button[data-action="acknowledge"]', function() {
            var circularId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Acknowledge it!'
            }).then((result) => {

                if (!result.value) {
                    return;
                }

                ajaxRequest({
                    method: "POST",
                    url: route('circular.acknowledge', { circular: circularId }),
                    data: {}
                }).done(function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.message,
                    }).then((result) => {
                        window.location.href = window.location.pathname;
                    });
                }).fail(defaultErrorHandler);
            })
        });

    });

</script>
@endpush