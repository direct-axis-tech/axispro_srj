@extends('layout.app')

@section('page')
<div class="container">
    <form action="{{ route('contract.maidReplacement.store') }}" method="post" id="maid_return_form" enctype="multipart/form-data">

        <div class="card mw-850px mx-auto">
            <div class="card-header">
                <div class="card-title">
                    <h2>Maid Replacement Request</h2>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <label for="contract_id" class="col-sm-2 col-form-label required">Contract</label>
                    <div class="col-sm-9">
                        <select required class="{{ class_names(['form-select', 'inactive-control' => $selectedContract]) }}"  name="contract_id" id="contract_id">
                            <option value="">-- Select Contract --</option>
                            @if ($selectedContract)
                            <option value="{{ $selectedContract->id }}" selected >{{ implode(' - ', array_filter([
                                $selectedContract->reference,
                                $selectedContract->maid->maid_ref,
                                $selectedContract->maid->name
                            ])) }}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <label for="customer" class="col-sm-2 col-form-label">Sponsor Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control-plaintext" id="customer" value="" readonly>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="maid" class="col-sm-2 col-form-label">Domestic Worker</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control-plaintext" id="maid" value="" readonly>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="category" class="col-sm-2 col-form-label">Category</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control-plaintext" id="category" value="" readonly>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="item" class="col-sm-2 col-form-label">Item</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control-plaintext" id="item" value="" readonly>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="contract_period" class="col-sm-2 col-form-label">Contract Period</label>
                    <div class="col-sm-9">
                        <div id="contract_period" class="input-group input-daterange">
                            <input
                                type="text"
                                class="form-control-plaintext px-4 w-125px text-start"
                                id="contract_from"
                                value=""
                                readonly>
                            <div class="input-group-text input-group-addon px-4 rounded-0 border-0 fw-normal text-inverse-light">to</div>
                            <input
                                type="text"
                                id="contract_till"
                                class="form-control-plaintext px-4 w-125px text-end"
                                value=""
                                readonly>
                        </div>
                        <small class="text-muted">Expiry Status: <span data-expiry-status></span></small>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="new_labour_id" class="col-sm-2 col-form-label required">Domestic Worker (NEW)</label>
                    <div class="col-sm-9">
                        <select class="form-select" name="new_labour_id" id="new_labour_id" required data-control="select2">
                            <option value="">-- select --</option>
                        </select>
                        <span data-error-message class="text-danger"></span>
                    </div>
                </div>

                <div class="row mb-2 ">
                    <label for="replace_date" class="col-sm-2 col-form-label required">Date Of Replacement</label>
                    <div class="col-sm-9">
                        <input
                            type="text"
                            required
                            data-provide="datepicker"
                            data-date-format="{{ getBSDatepickerDateFormat() }}"
                            data-date-clear-btn="true"
                            data-date-autoclose="true"
                            data-parsley-trigger-after-failure="change"
                            class="form-control"
                            name="replace_date"
                            id="replace_date"
                            placeholder="{{ getBSDatepickerDateFormat() }}"
                            value="">
                    </div>
                </div>

                <div class="row mb-2 ">
                    <label for="end_old_maid" class="col-sm-2 col-form-label required">End Old Maid</label>
                    <div class="col-sm-9">
                        <input
                            type="text"
                            required
                            data-provide="datepicker"
                            data-date-format="{{ getBSDatepickerDateFormat() }}"
                            data-date-clear-btn="true"
                            data-date-autoclose="true"
                            data-parsley-trigger-after-failure="change"
                            class="form-control"
                            name="end_old_maid"
                            id="end_old_maid"
                            placeholder="{{ getBSDatepickerDateFormat() }}"
                            value="">
                    </div>
                </div>

                <div class="row mb-2 ">
                    <label for="start_new_maid" class="col-sm-2 col-form-label required">Start New Maid</label>
                    <div class="col-sm-9">
                        <input
                            type="text"
                            required
                            data-provide="datepicker"
                            data-date-format="{{ getBSDatepickerDateFormat() }}"
                            data-date-clear-btn="true"
                            data-date-autoclose="true"
                            data-parsley-trigger-after-failure="change"
                            class="form-control"
                            name="start_new_maid"
                            id="start_new_maid"
                            placeholder="{{ getBSDatepickerDateFormat() }}"
                            value="">
                    </div>
                </div>

                <div class="row mb-5">
                    <label for="memo" class="col-sm-2 col-form-label">Notes</label>
                    <div class="col-sm-9">
                        <textarea
                            name="memo"
                            class="form-control"
                            id="memo"
                            cols="30"
                            rows="3"></textarea>
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="attachment_type" class="col-sm-2 col-form-label">Attachment Type</label>
                    <div class="col-sm-9">
                        <input
                            type="text"
                            name="attachment_type"
                            class="form-control"
                            id="attachment_type">
                    </div>
                </div>

                <div class="row mb-2">
                    <label for="attachment" class="col-sm-2 col-form-label">Attach File</label>
                    <div class="col-sm-9">
                        <input
                            type="file"
                            name="attachment"
                            data-parsley-max-file-size="2"
                            class="form-control"
                            id="attachment">
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    route.push('contract.show', '{{ rawRoute('contract.show') }}');

    const dateFormat = '{{ dateformat('momentJs') }}';
    var parsleyForm = $("#maid_return_form").parsley();
    var maidId = '';

    var selectedContract = @json($selectedContract);
    if(selectedContract){
        setTimeout(function () {
            $('#contract_id').val(selectedContract.id).change();
        }, 1000);
    }
    
    // initialize select2
    $('#contract_id').select2({
        placeholder: '-- select contract --',
        allowClear: true,
        ajax: {
            url: '{{ route('api.contract.maidReplacement.contractsSelect2') }}',
            dataType: 'json',
            delay: 250,
        }
    });

    $("#contract_id").change(function() {
        setData();
        ajaxRequest({
            method: 'get',
            url: route('contract.show', {contract: this.value}),
        }).done(function(response, textStatus, xhr) {
            if (!response.contract) {
                return defaultErrorHandler(xhr);
            }
            setData(response.contract);
        }).fail(defaultErrorHandler);
    });

    function setData(contract) {
        contract = contract || {};
        const contractFrom = moment(contract.contract_from);
        const contractTill = moment(contract.contract_till);

        $('#customer').val(contract.customer?.name || '');
        $('#category').val(contract.category?.description || '');
        $('#maid').val(contract.maid?.name || '');
        $('#item').val(contract.stock?.description || '');
        $('#contract_from').val(contractFrom.isValid() ? contractFrom.format(dateFormat) : '');   
        $('#contract_till').val(contractTill.isValid() ? contractTill.format(dateFormat) : '');
        $('[data-expiry-status]').text(contractTill.isValid() ? contractTill.fromNow() : 'NA');

        const maidId = contract.labour_id?.toString() || '-1';
        const newMaidSelect = document.querySelector('#new_labour_id');
        $(newMaidSelect).select2('destroy');
        
        // If the current maid in this contract is selected, unselect it
        if (newMaidSelect.value == maidId) {
            newMaidSelect.value = '';
        }
        
        // If there is already option for selecting current maid, remove it
        const currentMaidOption = newMaidSelect.querySelector(`option[value="${maidId}"]`);
        if (currentMaidOption) {
            newMaidSelect.options.remove(currentMaidOption.index);
        }

        // Initialize a new select2 without the current maid in it
        initializeLaboursSelect2('#new_labour_id', {
            except: [maidId],
            category_id: [
                '{{ \App\Models\Inventory\StockCategory::DWD_PACKAGETWO }}', 
                '{{ \App\Models\Inventory\StockCategory::DWD_PACKAGETHREE }}', 
                '{{ \App\Models\Inventory\StockCategory::DWD_PACKAGEFOUR }}'
            ],
        });
    }

    parsleyForm.on('form:submit', function() {
        var form = parsleyForm.element;
        var formData = new FormData(form);
        ajaxRequest({
            method: 'post',
            url: form.action,
            data: new FormData(form), 
            processData: false,
            contentType: false,
        }).done(function (response, textStatus, xhr) {
            if (!response.message) {
                return defaultErrorHandler(xhr);
            }

            toastr.success(response.message);
            form.reset();
        }).fail(defaultErrorHandler);

        return false;
    });

    // resets the form as well as any validation errors
    parsleyForm.$element.on('reset', () => {
        setData();
        parsleyForm.reset();
        $('#contract_id').val('').trigger('change.select2');
        $('#new_labour_id').val('').trigger('change.select2');
        $('[data-provide="datepicker"]').datepicker('update').trigger('changeDate').trigger('change');
    })
});
</script>
@endpush