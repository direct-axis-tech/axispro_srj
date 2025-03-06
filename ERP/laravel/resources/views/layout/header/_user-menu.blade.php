<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px" data-kt-menu="true">
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <div class="menu-content d-flex align-items-center px-3">
            <!--begin::Avatar-->
            <div class="symbol symbol-50px me-5">
                <img alt="Logo" src="{{ auth()->user()->avatar_url }}"/>
            </div>
            <!--end::Avatar-->

            <!--begin::Username-->
            <div class="d-flex flex-column">
                <div class="fw-bolder d-flex align-items-center fs-5">
                    {{ auth()->user()->name }}
                </div>
                <a href="#" class="fw-bold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
            </div>
            <!--end::Username-->
        </div>
    </div>
    <!--end::Menu item-->

    <!--begin::Menu separator-->
    <div class="separator my-2"></div>
    <!--end::Menu separator-->

    <!--begin::Menu item-->
    <div class="menu-item px-5" data-kt-menu-trigger="hover" data-kt-menu-placement="left-start">
        <a href="#" class="menu-link px-5">
            <span class="menu-title position-relative">
                {{ __('Language') }}

                <span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
                    {{ __('English') }} <img class="w-15px h-15px rounded-1 ms-2" src="{{ media('flags/united-states.svg') }}" alt="United States Flag"/>
                </span>
            </span>
        </a>

        <!--begin::Menu sub-->
        <div class="menu-sub menu-sub-dropdown w-175px py-4">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <a href="#" class="menu-link d-flex px-5 active">
                    <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" src="{{ media('flags/united-states.svg') }}" alt="United States Flag"/>
                    </span>
                    {{ __('English') }}
                </a>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::Menu sub-->
    </div>
    <!--end::Menu item-->
    
    <!--begin::Menu item-->
    <div class="menu-item px-5">
        <a href="{{ erp_url('ERP/admin/change_current_user_password.php') }}" class="menu-link px-5">
            {{ __('Reset Password') }}
        </a>
    </div>
    <!--end::Menu item-->

    @if (authUser()->employee)    
    <!--begin::Menu item-->
    <div class="menu-item px-5">
        <a href="{{ route('employeeProfile.personal', ['employee' => authUser()->employee_id]) }}" class="menu-link px-5">
            {{ __('Profile') }}
        </a>
    </div>
    <!--end::Menu item-->
    @endif

    <!--begin::Menu item-->
    <div class="menu-item px-5 my-1">
        <a href="{{ erp_url('ERP/access/logout.php') }}" class="menu-link px-5">
            {{ __('Sign Out') }}
        </a>
    </div>
    <!--end::Menu item-->
</div>
<!--end::Menu-->
