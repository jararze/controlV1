<div class="modal" data-modal="true" id="search_modal">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <i class="ki-filled ki-magnifier text-gray-700 text-xl">
            </i>
            <input class="input px-0 border-none bg-transparent shadow-none ms-2.5" name="query" placeholder="Tap to start search" type="text" value=""/>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross">
                </i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <div class="tabs justify-between px-5 mb-2.5" data-tabs="true">
                <div class="flex items-center gap-5">
                    <button class="tab py-5 active" data-tab-toggle="#search_modal_mixed">
                        Mixed
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_settings">
                        Settings
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_integrations">
                        Integrations
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_users">
                        Users
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_docs">
                        Docs
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_empty">
                        Empty
                    </button>
                    <button class="tab py-5" data-tab-toggle="#search_modal_no-results">
                        No Results
                    </button>
                </div>
                <div class="menu -mt-px" data-menu="true">
                    <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                        <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                            <i class="ki-filled ki-setting-2">
                            </i>
                        </button>
                        <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                            <div class="menu-item">
                                <a class="menu-link" href="#">
           <span class="menu-icon">
            <i class="ki-filled ki-document">
            </i>
           </span>
                                    <span class="menu-title">
            View
           </span>
                                </a>
                            </div>
                            <div class="menu-item" data-menu-item-offset="-15px, 0" data-menu-item-placement="right-start" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                                <div class="menu-link">
           <span class="menu-icon">
            <i class="ki-filled ki-notification-status">
            </i>
           </span>
                                    <span class="menu-title">
            Export
           </span>
                                    <span class="menu-arrow">
            <i class="ki-filled ki-right text-3xs rtl:transform rtl:rotate-180">
            </i>
           </span>
                                </div>
                                <div class="menu-dropdown menu-default w-full max-w-[175px]">
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo3/account/home/settings-sidebar.html">
             <span class="menu-icon">
              <i class="ki-filled ki-sms">
              </i>
             </span>
                                            <span class="menu-title">
              Email
             </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo3/account/home/settings-sidebar.html">
             <span class="menu-icon">
              <i class="ki-filled ki-message-notify">
              </i>
             </span>
                                            <span class="menu-title">
              SMS
             </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo3/account/home/settings-sidebar.html">
             <span class="menu-icon">
              <i class="ki-filled ki-notification-status">
              </i>
             </span>
                                            <span class="menu-title">
              Push
             </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="#">
           <span class="menu-icon">
            <i class="ki-filled ki-pencil">
            </i>
           </span>
                                    <span class="menu-title">
            Edit
           </span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="#">
           <span class="menu-icon">
            <i class="ki-filled ki-trash">
            </i>
           </span>
                                    <span class="menu-title">
            Delete
           </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scrollable-y-auto" data-scrollable="true" data-scrollable-max-height="auto" data-scrollable-offset="300px">
                <div class="" id="search_modal_mixed">
                    <div class="flex flex-col gap-2.5">
                        <div>
                            <div class="text-xs text-gray-600 font-medium pt-2.5 pb-1.5 ps-5">
                                Settings
                            </div>
                            <div class="menu menu-default p-0 flex-col">
                                <div class="menu-item">
                                    <a class="menu-link" href="#">
            <span class="menu-icon">
             <i class="ki-filled ki-badge">
             </i>
            </span>
                                        <span class="menu-title">
             Public Profile
            </span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="#">
            <span class="menu-icon">
             <i class="ki-filled ki-setting-2">
             </i>
            </span>
                                        <span class="menu-title">
             My Account
            </span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="#">
            <span class="menu-icon">
             <i class="ki-filled ki-message-programming">
             </i>
            </span>
                                        <span class="menu-title">
             Devs Forum
            </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="border-b border-b-gray-200">
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 font-medium pt-2.5 pb-1.5 ps-5">
                                Integrations
                            </div>
                            <div class="menu menu-default p-0 flex-col">
                                <div class="menu-item">
                                    <div class="menu-link flex items-center jistify-between gap-2">
                                        <div class="flex items-center grow gap-2">
                                            <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                                <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/jira.svg') }}"/>
                                            </div>
                                            <div class="flex flex-col gap-0.5">
                                                <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                                    Jira
                                                </a>
                                                <span class="text-2xs font-medium text-gray-600">
               Project management
              </span>
                                            </div>
                                        </div>
                                        <div class="flex justify-end shrink-0">
                                            <div class="flex -space-x-2">
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-4.png') }}"/>
                                                </div>
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-1.png') }}"/>
                                                </div>
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-2.png') }}"/>
                                                </div>
                                                <div class="flex">
               <span class="hover:z-5 relative inline-flex items-center justify-center shrink-0 rounded-full ring-1 font-semibold leading-none text-3xs size-6 text-success-inverse size-6 ring-success-light bg-success">
                +3
               </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-link flex items-center jistify-between gap-2">
                                        <div class="flex items-center grow gap-2">
                                            <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                                <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/inferno.svg') }}"/>
                                            </div>
                                            <div class="flex flex-col gap-0.5">
                                                <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                                    Inferno
                                                </a>
                                                <span class="text-2xs font-medium text-gray-600">
               Real-time photo sharing app
              </span>
                                            </div>
                                        </div>
                                        <div class="flex justify-end shrink-0">
                                            <div class="flex -space-x-2">
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-14.png') }}"/>
                                                </div>
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-12.png') }}"/>
                                                </div>
                                                <div class="flex">
                                                    <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-9.png') }}"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="border-b border-b-gray-200">
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 font-medium pt-2.5 pb-1.5 ps-5">
                                Users
                            </div>
                            <div class="menu menu-default p-0 flex-col">
                                <div class="grid gap-1">
                                    <div class="menu-item">
                                        <div class="menu-link flex justify-between gap-2">
                                            <div class="flex items-center gap-2.5">
                                                <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-3.png') }}"/>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                        Tyler Hero
                                                    </a>
                                                    <span class="text-2sm font-normal text-gray-500">
                tyler.hero@gmail.com connections
               </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2.5">
                                                <div class="badge badge-pill badge-outline badge-success gap-1.5">
               <span class="badge badge-dot badge-success size-1.5">
               </span>
                                                    In Office
                                                </div>
                                                <button class="btn btn-icon btn-light btn-clear btn-sm">
                                                    <i class="ki-filled ki-dots-vertical">
                                                    </i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="menu-item">
                                        <div class="menu-link flex justify-between gap-2">
                                            <div class="flex items-center gap-2.5">
                                                <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-1.png') }}"/>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                        Esther Howard
                                                    </a>
                                                    <span class="text-2sm font-normal text-gray-500">
                esther.howard@gmail.com connections
               </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2.5">
                                                <div class="badge badge-pill badge-outline badge-danger gap-1.5">
               <span class="badge badge-dot badge-danger size-1.5">
               </span>
                                                    On Leave
                                                </div>
                                                <button class="btn btn-icon btn-light btn-clear btn-sm">
                                                    <i class="ki-filled ki-dots-vertical">
                                                    </i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_settings">
                    <div class="menu menu-default p-0 flex-col">
                        <div class="text-xs text-gray-600 font-medium pt-2.5 ps-5 pb-1.5">
                            Shortcuts
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-home-2">
           </i>
          </span>
                                <span class="menu-title">
           Go to Dashboard
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-badge">
           </i>
          </span>
                                <span class="menu-title">
           Public Profile
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-profile-circle">
           </i>
          </span>
                                <span class="menu-title">
           My Profile
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-setting-2">
           </i>
          </span>
                                <span class="menu-title">
           My Account
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-message-programming">
           </i>
          </span>
                                <span class="menu-title">
           Devs Forum
          </span>
                            </a>
                        </div>
                        <div class="text-xs text-gray-600 font-medium pt-2.5 ps-5 pt-2.5 pb-1.5">
                            Actions
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-user">
           </i>
          </span>
                                <span class="menu-title">
           Create User
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-user-edit">
           </i>
          </span>
                                <span class="menu-title">
           Create Team
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-subtitle">
           </i>
          </span>
                                <span class="menu-title">
           Change Plan
          </span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#">
          <span class="menu-icon">
           <i class="ki-filled ki-setting">
           </i>
          </span>
                                <span class="menu-title">
           Setup Branding
          </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_integrations">
                    <div class="menu menu-default p-0 flex-col">
                        <div class="menu-item">
                            <div class="menu-link flex items-center jistify-between gap-2">
                                <div class="flex items-center grow gap-2">
                                    <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                        <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/jira.svg') }}"/>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                            Jira
                                        </a>
                                        <span class="text-2xs font-medium text-gray-600">
             Project management
            </span>
                                    </div>
                                </div>
                                <div class="flex justify-end shrink-0">
                                    <div class="flex -space-x-2">
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-4.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-1.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-2.png') }}"/>
                                        </div>
                                        <div class="flex">
             <span class="hover:z-5 relative inline-flex items-center justify-center shrink-0 rounded-full ring-1 font-semibold leading-none text-3xs size-6 text-success-inverse size-6 ring-success-light bg-success">
              +3
             </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item">
                            <div class="menu-link flex items-center jistify-between gap-2">
                                <div class="flex items-center grow gap-2">
                                    <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                        <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/inferno.svg') }}"/>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                            Inferno
                                        </a>
                                        <span class="text-2xs font-medium text-gray-600">
             Real-time photo sharing app
            </span>
                                    </div>
                                </div>
                                <div class="flex justify-end shrink-0">
                                    <div class="flex -space-x-2">
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-14.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-12.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-9.png') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item">
                            <div class="menu-link flex items-center jistify-between gap-2">
                                <div class="flex items-center grow gap-2">
                                    <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                        <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/evernote.svg') }}"/>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                            Evernote
                                        </a>
                                        <span class="text-2xs font-medium text-gray-600">
             Notes management app
            </span>
                                    </div>
                                </div>
                                <div class="flex justify-end shrink-0">
                                    <div class="flex -space-x-2">
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-6.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-3.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-1.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-8.png') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item">
                            <div class="menu-link flex items-center jistify-between gap-2">
                                <div class="flex items-center grow gap-2">
                                    <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                        <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/gitlab.svg') }}"/>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                            Gitlab
                                        </a>
                                        <span class="text-2xs font-medium text-gray-600">
             Notes management app
            </span>
                                    </div>
                                </div>
                                <div class="flex justify-end shrink-0">
                                    <div class="flex -space-x-2">
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-18.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-17.png') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item">
                            <div class="menu-link flex items-center jistify-between gap-2">
                                <div class="flex items-center grow gap-2">
                                    <div class="flex items-center justify-center size-10 shrink-0 rounded-full border border-gray-200 bg-gray-100">
                                        <img alt="" class="size-6 shrink-0" src="{{ asset('assets/media/brand-logos/google-webdev.svg') }}"/>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-2sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                            Google webdev
                                        </a>
                                        <span class="text-2xs font-medium text-gray-600">
             Building web expierences
            </span>
                                    </div>
                                </div>
                                <div class="flex justify-end shrink-0">
                                    <div class="flex -space-x-2">
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-14.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-20.png') }}"/>
                                        </div>
                                        <div class="flex">
                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-6" src="{{ asset('assets/media/avatars/300-21.png') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item px-4 pt-2">
                            <a class="btn btn-sm btn-light justify-center" href="#">
                                Go to Apps
                            </a>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_users">
                    <div class="menu menu-default p-0 flex-col">
                        <div class="grid gap-1">
                            <div class="menu-item">
                                <div class="menu-link flex justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-3.png') }}"/>
                                        <div class="flex flex-col">
                                            <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                Tyler Hero
                                            </a>
                                            <span class="text-2sm font-normal text-gray-500">
              tyler.hero@gmail.com connections
             </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="badge badge-pill badge-outline badge-success gap-1.5">
             <span class="badge badge-dot badge-success size-1.5">
             </span>
                                            In Office
                                        </div>
                                        <button class="btn btn-icon btn-light btn-clear btn-sm">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-1.png') }}"/>
                                        <div class="flex flex-col">
                                            <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                Esther Howard
                                            </a>
                                            <span class="text-2sm font-normal text-gray-500">
              esther.howard@gmail.com connections
             </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="badge badge-pill badge-outline badge-danger gap-1.5">
             <span class="badge badge-dot badge-danger size-1.5">
             </span>
                                            On Leave
                                        </div>
                                        <button class="btn btn-icon btn-light btn-clear btn-sm">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-11.png') }}"/>
                                        <div class="flex flex-col">
                                            <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                Jacob Jones
                                            </a>
                                            <span class="text-2sm font-normal text-gray-500">
              jacob.jones@gmail.com connections
             </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="badge badge-pill badge-outline badge-primary gap-1.5">
             <span class="badge badge-dot badge-primary size-1.5">
             </span>
                                            Remote
                                        </div>
                                        <button class="btn btn-icon btn-light btn-clear btn-sm">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-5.png') }}"/>
                                        <div class="flex flex-col">
                                            <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                TLeslie Alexander
                                            </a>
                                            <span class="text-2sm font-normal text-gray-500">
              leslie.alexander@gmail.com connections
             </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="badge badge-pill badge-outline badge-success gap-1.5">
             <span class="badge badge-dot badge-success size-1.5">
             </span>
                                            In Office
                                        </div>
                                        <button class="btn btn-icon btn-light btn-clear btn-sm">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <img alt="" class="rounded-full size-9 shrink-0" src="{{ asset('assets/media/avatars/300-2.png') }}"/>
                                        <div class="flex flex-col">
                                            <a class="text-sm font-semibold text-gray-900 hover:text-primary-active mb-px" href="#">
                                                Cody Fisher
                                            </a>
                                            <span class="text-2sm font-normal text-gray-500">
              cody.fisher@gmail.com connections
             </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="badge badge-pill badge-outline badge-primary gap-1.5">
             <span class="badge badge-dot badge-primary size-1.5">
             </span>
                                            Remote
                                        </div>
                                        <button class="btn btn-icon btn-light btn-clear btn-sm">
                                            <i class="ki-filled ki-dots-vertical">
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-item px-4 pt-2">
                                <a class="btn btn-sm btn-light justify-center" href="#">
                                    Go to Users
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_docs">
                    <div class="menu menu-default p-0 flex-col">
                        <div class="grid">
                            <div class="menu-item">
                                <div class="menu-link flex items-center">
                                    <div class="flex items-center grow gap-2.5">
                                        <img src="{{ asset('assets/media/file-types/pdf.svg') }}"/>
                                        <div class="flex flex-col">
             <span class="text-sm font-semibold text-gray-900 cursor-pointer hover:text-primary mb-px">
              Project-pitch.pdf
             </span>
                                            <span class="text-xs font-medium text-gray-500">
              4.7 MB 26 Sep 2024 3:20 PM
             </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-icon btn-light btn-clear btn-sm">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex items-center">
                                    <div class="flex items-center grow gap-2.5">
                                        <img src="{{ asset('assets/media/file-types/doc.svg') }}"/>
                                        <div class="flex flex-col">
             <span class="text-sm font-semibold text-gray-900 cursor-pointer hover:text-primary mb-px">
              Report-v1.docx
             </span>
                                            <span class="text-xs font-medium text-gray-500">
              2.3 MB 1 Oct 2024 12:00 PM
             </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-icon btn-light btn-clear btn-sm">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex items-center">
                                    <div class="flex items-center grow gap-2.5">
                                        <img src="{{ asset('assets/media/file-types/javascript.svg') }}"/>
                                        <div class="flex flex-col">
             <span class="text-sm font-semibold text-gray-900 cursor-pointer hover:text-primary mb-px">
              Framework-App.js
             </span>
                                            <span class="text-xs font-medium text-gray-500">
              0.8 MB 17 Oct 2024 6:46 PM
             </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-icon btn-light btn-clear btn-sm">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex items-center">
                                    <div class="flex items-center grow gap-2.5">
                                        <img src="{{ asset('assets/media/file-types/ai.svg') }}"/>
                                        <div class="flex flex-col">
             <span class="text-sm font-semibold text-gray-900 cursor-pointer hover:text-primary mb-px">
              Framework-App.js
             </span>
                                            <span class="text-xs font-medium text-gray-500">
              0.8 MB 17 Oct 2024 6:46 PM
             </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-icon btn-light btn-clear btn-sm">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item">
                                <div class="menu-link flex items-center">
                                    <div class="flex items-center grow gap-2.5">
                                        <img src="{{ asset('assets/media/file-types/php.svg') }}"/>
                                        <div class="flex flex-col">
             <span class="text-sm font-semibold text-gray-900 cursor-pointer hover:text-primary mb-px">
              appController.js
             </span>
                                            <span class="text-xs font-medium text-gray-500">
              0.1 MB 21 Nov 2024 3:20 PM
             </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-icon btn-light btn-clear btn-sm">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item px-4 pt-2.5">
                                <a class="btn btn-sm btn-light justify-center" href="#">
                                    Go to Users
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_empty">
                    <div class="flex flex-col text-center py-9 gap-5">
                        <div class="flex justify-center">
                            <img alt="image" class="dark:hidden max-h-[113px]" src="{{ asset('assets/media/illustrations/33.svg') }}"/>
                            <img alt="image" class="light:hidden max-h-[113px]" src="{{ asset('assets/media/illustrations/33-dark.svg') }}"/>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <h3 class="text-base font-semibold text-gray-900 text-center">
                                Looking for something..
                            </h3>
                            <span class="text-2sm font-medium text-center text-gray-600">
          Initiate your digital experience with
          <br>
           our intuitive dashboard
                                </br>
         </span>
                        </div>
                        <div class="flex justify-center">
                            <a class="btn btn-sm btn-light flex justify-center" href="#">
                                View Projects
                            </a>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="search_modal_no-results">
                    <div class="flex flex-col text-center py-9 gap-5">
                        <div class="flex justify-center">
                            <img alt="image" class="dark:hidden max-h-[113px]" src="{{ asset('assets/media/illustrations/33.svg') }}"/>
                            <img alt="image" class="light:hidden max-h-[113px]" src="{{ asset('assets/media/illustrations/33-dark.svg') }}"/>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <h3 class="text-base font-semibold text-gray-900 text-center">
                                No Results Found
                            </h3>
                            <span class="text-2sm font-medium text-center text-gray-600">
          Refine your query to discover relevant items
         </span>
                        </div>
                        <div class="flex justify-center">
                            <a class="btn btn-sm btn-light flex justify-center" href="#">
                                View Projects
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
