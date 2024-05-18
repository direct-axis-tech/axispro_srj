@extends('layout.app')

@section('title', 'Issued Circulars')

@section('page')

<style>
    .modal-xl {
        max-width: 90%;
    }

    .modal-content {
        border-radius: 10px;
    }

    #circularViewer {
        background-color: #f5f5f5; 
        text-align: center;
        width:100%; 
        height:700px; 
        overflow-y: auto;
    }

</style>

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
                                    @if ($canAccess['VIEW'])
                                        <a target="_blank" href="#" class="btn btn-sm btn-info btn-view" data-id="{{ $list->id }}" title="View" >
                                            <span class="fas fa-eye"></span>
                                        </a>
                                    @endif
                                        
                                    @if ($canAccess['DOWNLOAD'])
                                        <a href="#" class="btn btn-sm btn-info btn-download" data-id="{{ $list->id }}" title="Download" >
                                            <span class="fas fa-download"></span>
                                        </a>
                                    @endif

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

    <div class="modal fade" id="viewCircularModal" tabindex="-1" role="dialog" aria-labelledby="viewCircularModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">View Circular</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="circularViewer"></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
@push('scripts')

<!-- Include PDF.js library from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js"></script>

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
        route.push('circular.view.secure.file','{{ rawRoute('circular.view.secure.file') }}');
        route.push('circular.download','{{ rawRoute('circular.download') }}');
        
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


        $('.btn-view').on('click', function(e) {
            e.preventDefault();
            var circularId = $(this).data('id');
            var modal = $('#viewCircularModal');
            var viewer = $('#circularViewer');
            viewer.html('');
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Initialize PDF.js
            var pdfjsLib = window['pdfjs-dist/build/pdf'];
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';

            $.ajax({
                method: "POST",
                url: route('circular.view.secure.file', { circular: circularId }),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                xhrFields: {
                    responseType: 'blob' // Important to handle binary data
                },
                success: function(response) {
                    var file = new Blob([response], { type: 'application/pdf' });
                    var fileURL = URL.createObjectURL(file);

                    pdfjsLib.getDocument(fileURL).promise.then(function(pdfDoc) {
                        var numPages = pdfDoc.numPages;
                        renderPagesInOrder(pdfDoc, viewer, 1, numPages);
                    }).catch(function(error) {
                        console.error('Error loading PDF:', error);
                        viewer.html('<div>Error loading PDF</div>');
                    });

                    modal.modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to load the PDF file."
                    });
                }
            });

        });

        function renderPagesInOrder(pdfDoc, viewer, currentPage, totalPages) {
            if (currentPage > totalPages) return; 

            pdfDoc.getPage(currentPage).then(function(page) {
                var scale = 1.5;
                var viewport = page.getViewport({ scale: scale });

                var canvas = document.createElement('canvas');
                var context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                var renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                page.render(renderContext).promise.then(function() {
                    viewer.append(canvas);
                    renderPagesInOrder(pdfDoc, viewer, currentPage + 1, totalPages); 
                });
            });
        }

        $('.btn-download').on('click', function(e) {
            e.preventDefault();
            var circularId = $(this).data('id');
            var downloadUrl = route('circular.download', { circular: circularId });

            window.location.href = downloadUrl;
        }); 

    });

</script>
@endpush