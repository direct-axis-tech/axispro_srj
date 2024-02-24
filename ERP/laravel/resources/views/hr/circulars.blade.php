@extends('layout.app')

@section('title', 'Manage Circular')

@section('page')

<div class="container-fluid">
    <div>
        <h1 class="d-inline-block my-10">Manage Circular</h1>
        <button type="button" data-bs-toggle="modal" data-bs-target="#circularModal" class="btn btn-primary float-end my-10">
            <span class="fa fa-plus mx-2"></span> Add Circular
        </button>
    </div>
    <div class="card mt-10">
        <div class="card-body">
            <div class="w-100 table-responsive">
                <table id="circular-table" class="table-striped table w-100 table-row-bordered g-3 text-nowrap thead-strong">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Entity Type</th>
                            <th>Entity</th>
                            <th>Memo</th>
                            <th>Circular Date</th>
                            <th>Issued By</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for adding Circular Modal -->
    <div class="modal fade" id="circularModal" tabindex="-1" aria-labelledby="circularModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="circularModalLabel">Add Circular</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="circularForm" name="circularForm" method="POST" action="{{ route('circulars.store') }}">
                    @csrf
                    <div class="modal-body p-5 bg-light">

                        <div class="form-group row mt-3">
                            <label class="col-sm-3 col-form-label" for="entity_type_id">Entity Type:</label>
                            <div class="col-sm-9">
                                <select name="entity_type_id" id="entity_type_id" class="form-control" data-control="select2" 
                                data-placeholder=" -- Select --" required >
                                    <option value=""> -- Select Entity Type -- </option>
                                    @foreach ($entityTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mt-3 user_entity d-none entities-div">
                            <label class="col-sm-3 col-form-label" for="user_id">User:</label>
                            <div class="col-sm-9">
                                <select name="user_id" id="user_id" class="form-control entities" data-control="select2" 
                                data-placeholder=" -- Select --" >
                                    <option value=""> -- Select User -- </option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mt-3 employee_entity d-none entities-div">
                            <label class="col-sm-3 col-form-label" for="employee_id">Employee:</label>
                            <div class="col-sm-9">
                                <select name="employee_id" id="employee_id" class="form-control entities" data-control="select2" 
                                data-placeholder=" -- Select --" >
                                    <option value=""> -- Select Employee -- </option>
                                    @foreach ($authorizedEmployees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->formatted_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mt-3 group_entity d-none entities-div">
                            <label class="col-sm-3 col-form-label" for="entity_group_id">Entity Group:</label>
                            <div class="col-sm-9">
                                <select name="entity_group_id" id="entity_group_id" class="form-control entities" data-control="select2" 
                                data-placeholder=" -- Select --" >
                                    <option value=""> -- Select Entity Group -- </option>
                                    @foreach ($entityGroups as $entityGroup)
                                        <option value="{{ $entityGroup->id }}">{{ $entityGroup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mt-3 access_role_entity d-none entities-div">
                            <label class="col-sm-3 col-form-label" for="access_role_id">Access Role:</label>
                            <div class="col-sm-9">
                                <select name="access_role_id" id="access_role_id" class="form-control entities" data-control="select2" 
                                data-placeholder=" -- Select --" >
                                    <option value=""> -- Select Access Role -- </option>
                                    @foreach ($accessRoles as $accessRole)
                                        <option value="{{ $accessRole->id }}">{{ $accessRole->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <label class="col-sm-3 col-form-label" for="circular_date">Circular Date:</label>
                            <div class="col-sm-9">
                                <input
                                    type="text"
                                    name="circular_date"
                                    id="circular_date"
                                    class="form-control"
                                    data-parsley-trigger-after-failure="change"
                                    data-parsley-date="{{ dateformat('momentJs') }}"
                                    data-control="bsDatepicker"
                                    data-dateformat="{{ dateformat('bsDatepicker') }}"
                                    data-date-today-btn="linked"
                                    value="{{ date(dateformat()) }}" required >
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <label class="col-sm-3 col-form-label" for="memo">Memo:</label>
                            <div class="col-sm-9">
                                <input type="text" name="memo" class="form-control" id="memo" placeholder="Memo" required >
                            </div>
                        </div>

                        <div class="form-group row p-10">
                            <div class="col">
                                <div
                                    class="dropzone parsley-indicator"
                                    id="attachment"
                                    data-parsley-files="1"
                                    data-parsley-control=""
                                    data-parsley-validate-if-empty="true"
                                    data-parsley-trigger-after-failure="mouseleave">
                                    <div class="dz-message justify-content-center py-5 py-xxl-15 needsclick">
                                        <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>
                                        <div class="ms-4 dropzone-preview">
                                            <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Drop file here or click to upload.</h3>
                                            <span class="fs-7 fw-bold text-gray-400">Only PDF files less than 2MB are allowed</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addCircularBtn" class="btn btn-primary">Add Circular</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Modal for listing acknowledgement status of users Modal -->
    <div class="modal fade" id="acknowledgeModal" tabindex="-1" aria-labelledby="acknowledgeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="acknowledgeModalLabel">Acknowledge Status</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-5 bg-light">
                    <div class="w-100 table-responsive">
                        <table id="acknowledge-table" class="table-striped table w-100 table-row-bordered g-3 text-nowrap thead-strong">
                            <thead>
                                <tr>
                                    <th>USERS</th>
                                    <th>STATUS</th>
                                    <th>ACKNOWLEDGED ON</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



</div>
@endsection
@push('scripts')

<script>

    var entityTypes = {
        USER: <?= App\Models\Entity::USER ?>,
        EMPLOYEE: <?= App\Models\Entity::EMPLOYEE ?>,
        GROUP: <?= App\Models\Entity::GROUP ?>,
        ACCESS_ROLE: <?= App\Models\Entity::ACCESS_ROLE ?>
    };

    $(document).ready(function() {
       
        $('#entity_type_id, #user_id, #employee_id, #entity_group_id, #access_role_id').select2({dropdownParent: $('#circularModal')});
        var ackTable = $('#acknowledge-table').DataTable();
        route.push('circulars.destroy', '{{ rawRoute('circulars.destroy') }}');
        route.push('file.view', '{{ rawRoute('file.view') }}');
        route.push('file.download','{{ rawRoute('file.download') }}');
        route.push('circular.getStatus','{{ rawRoute('circular.getStatus') }}');

        $('#entity_type_id').on('change', function() {

            var selectedType = $(this).val();
            $('.entities').val('').trigger('change');
            $('.entities-div').addClass('d-none');

            switch (selectedType) {
                case String(entityTypes.USER):
                    $('.user_entity').removeClass('d-none');
                    break;
                case String(entityTypes.EMPLOYEE):
                    $('.employee_entity').removeClass('d-none');
                    break;
                case String(entityTypes.GROUP):
                    $('.group_entity').removeClass('d-none');
                    break;
                case String(entityTypes.ACCESS_ROLE):
                    $('.access_role_entity').removeClass('d-none');
                    break;
                default:
                    $('.entities-div').addClass('d-none');
            }
        });

        const form = document.forms.namedItem('circularForm');
        const parsleyForm = $('#circularForm').parsley();
        let formData = null;

        const dropzone = new Dropzone('#attachment', {
            url: form.action,
            autoProcessQueue: false,
            addRemoveLinks: true,
            maxFiles: 1,
            maxFilesizes: 2,
            uploadMultiple: true,
            acceptedFiles: "application/pdf"
        });

        Parsley.addValidator('files', {
            messages: {en: 'Please select a file'},
            requirementType: 'integer',
            validate: function() {
                return dropzone.files.length > 0;
            }
        });

        parsleyForm.on('form:submit', (event) => {
            formData = new FormData(form);
            const file = dropzone.files[0];

            if (file.status == Dropzone.ERROR) {
                file.status = Dropzone.QUEUED;
            }
            setBusyState();
            dropzone.processQueue();
            return false;
        });

        // Dropzone sending event handler
        dropzone.on('sending', (file, xhr, _formData) => {
            for (const [key, value] of formData) {
                _formData.append(key, value);
            }
        });

        // Dropzone complete event handler
        dropzone.on('complete', () => {
            unsetBusyState();
            formData = null;
            $('#circularModal').modal('hide');
            table.ajax.reload();
        });

        // Dropzone error event handler
        dropzone.on('error', () => {
            unsetBusyState();
            defaultErrorHandler();
        });

        // Dropzone success event handler
        dropzone.on('success', () => {
            toastr.success("Success! Circular Successfully Created");
            form.reset();
        });

        // Reset form and Dropzone on modal close
        $('#circularModal').on('hidden.bs.modal', function () {
            $('#circularForm')[0].reset();
            $('#circularForm').parsley().reset();
            dropzone.removeAllFiles(true);
            $('#entity_type_id, #user_id, #employee_id, #entity_group_id, #access_role_id').val('').trigger('change');
        });

        // resets the form as well as any validation errors
        parsleyForm.$element.on('reset', () => {
            parsleyForm.reset();
            if (dropzone.files.length > 0) {
                dropzone.removeFile(dropzone.files[0]);
            }
        });

        var table = $('#circular-table').DataTable({
            ajax: ajaxRequest({
                url: '{{ route('api.dataTable.circulars') }}',
                method: 'post',
                eject: true,
            }),
            processing: true,
            serverSide: true,
            paging: true,
            ordering: true,
            order: [[ 4, 'desc' ]],
            rowId: 'id',
            columns: [
                {
                    data: 'reference',
                    title: 'Reference',
                    class: 'text-nowrap',
                },
                {
                    data: 'entity_type_name',
                    title: 'Entity Type',
                    class: 'text-nowrap',
                },
                {
                    data: 'entity_name',
                    title: 'Entity',
                    class: 'text-nowrap',
                },
                {
                    data: 'memo',
                    title: 'Memo',
                    class: 'text-nowrap',
                },
                {
                    data: 'formatted_circular_date',
                    title: 'Circular Date',
                    class: 'text-nowrap',
                },
                {
                    data: 'issued_by',
                    title: 'Issued By',
                    class: 'text-nowrap',
                },
                {
                    data: null,
                    defaultContent: '',
                    title: '',
                    width: '20px',
                    searchable: false,
                    orderable: false,
                    render: function (data) {
                        var actions = `<a target="_blank" href="${ route('file.view', { name: 'circular', file: data.file }) }" title="View" data-action="view" class="btn btn-sm btn-info btn-view m-1" ><span class="fas fa-eye"></span></a>`;
                        actions += `<a target="_blank" href="${ route('file.download', { type: 'circular', file: data.file }) }" title="Download" data-action="download" class="btn btn-sm btn-info btn-download m-1" ><span class="fas fa-download"></span></a>`;
                        actions += `<button data-action="acknowledgement-status" title="Acknowledgement Status" class="btn btn-sm btn-primary m-1" ><span class="fas fa-address-book"></span></button>`;
                        actions += `<button data-action="delete" title="Delete" class="btn btn-sm btn-danger btn-delete m-1" ><span class="fas fa-trash-alt"></span></button>`;
                        return actions;
                    },
                }
            ],
        });
              
        $('#circular-table').on('click', 'button[data-action="delete"]', function() {

            var data = table.row($(this).closest('tr')).data();
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete this '${data.reference}'. <br>This process cannot be reversed!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (!result.value) {
                    return;
                }
                ajaxRequest({
                    method: "POST",
                    url: route('circulars.destroy', { circular: data.id }),
                    data: {
                        _method: 'DELETE'
                    }
                }).done(function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.message,
                    });
                    table.ajax.reload();
                }).fail(defaultErrorHandler);
            })
        });


        $('#circular-table').on('click', 'button[data-action="acknowledgement-status"]', function() {
            var data = table.row($(this).closest('tr')).data();
            $('#acknowledge-table > tbody').html('');

            if (ackTable && ackTable !== 'undefined') {
                ackTable.destroy();
            }

            ackTable = $('#acknowledge-table').DataTable({
                ajax: ajaxRequest({
                    url: route('circular.getStatus', { circular : data.id }),
                    method: 'POST',
                    eject: true,
                }),
                processing: true,
                serverSide: true,
                paging: true,
                ordering: true,
                order: [[ 2, 'desc' ]],
                rowId: 'id',
                columns: [
                    {
                        data: 'notified_users',
                        title: 'USERS',
                        class: 'text-nowrap',
                    },
                    {
                        data: 'notification_status',
                        title: 'STATUS',
                        class: 'text-nowrap',
                    },
                    {
                        data: 'created_at',
                        title: 'ACKNOWLEDGED ON',
                        class: 'text-nowrap',
                    },
                    
                ],
                createdRow: function(row, data, dataIndex) {
                    // Add a red label to rows where notification_status is "Not Acknowledged"
                    if (data.notification_status === "Not Acknowledged") {
                        $('td', row).eq(1).addClass('text-danger font-weight-bold');
                    }
                }
            });

            $('#acknowledgeModal').modal('show');

        });

    });

</script>
@endpush