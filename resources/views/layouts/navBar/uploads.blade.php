<div
    class="flex items-stretch lg:fixed z-5 top-[--tw-header-height] start-[--tw-sidebar-width] end-5 h-[--tw-navbar-height] mx-5 lg:mx-0 bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark]"
    id="navbar">
    <div
        class="rounded-t-xl border border-gray-400 dark:border-gray-200 border-b-gray-300 dark:border-b-gray-200 bg-[--tw-content-bg] dark:bg-[--tw-content-bg-dark] flex items-stretch grow">
        <!-- Container -->
        <div class="container-fluid flex justify-between items-stretch gap-5">
            <div class="grid items-stretch">
                <div class="scrollable-x-auto flex items-stretch">
                    <div class="menu gap-5 lg:gap-7.5" data-menu="true">

                        <div
                            class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-gray-900 menu-item-here:border-b-gray-900">
                            <a class="menu-link gap-2.5" href="{{ route('uploads.index') }}"
                               tabindex="0">
                                <span
                                    class="menu-title text-nowrap text-sm text-gray-800 menu-item-active:text-gray-900 menu-item-active:font-medium menu-item-here:text-gray-900 menu-item-here:font-medium menu-item-show:text-gray-900 menu-link-hover:text-gray-900">Inicio</span>
                            </a>
                        </div>
                        <div
                            class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-gray-900 menu-item-here:border-b-gray-900"
                            data-menu-item-placement="bottom-start" data-menu-item-placement-rtl="bottom-end"
                            data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                            <div class="menu-link gap-1.5" tabindex="0">
                                <span
                                    class="menu-title text-nowrap text-sm text-gray-800 menu-item-active:text-gray-900 menu-item-active:font-medium menu-item-here:text-gray-900 menu-item-here:font-medium menu-item-show:text-gray-900 menu-link-hover:text-gray-900">Matriz</span>
                                <span class="menu-arrow"><i class="ki-filled ki-down text-2xs text-gray-500"></i></span>
                            </div>
                            <div class="menu-dropdown menu-default py-2 min-w-[200px]">
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.matriz') }}" tabindex="0">
                                        <span class="menu-title">Upload</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.matriz.index') }}" tabindex="0">
                                        <span class="menu-title">Lista</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div
                            class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-gray-900 menu-item-here:border-b-gray-900"
                            data-menu-item-placement="bottom-start" data-menu-item-placement-rtl="bottom-end"
                            data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                            <div class="menu-link gap-1.5" tabindex="0">
                                <span
                                    class="menu-title text-nowrap text-sm text-gray-800 menu-item-active:text-gray-900 menu-item-active:font-medium menu-item-here:text-gray-900 menu-item-here:font-medium menu-item-show:text-gray-900 menu-link-hover:text-gray-900">Truck</span>
                                <span class="menu-arrow"><i class="ki-filled ki-down text-2xs text-gray-500"></i></span>
                            </div>
                            <div class="menu-dropdown menu-default py-2 min-w-[200px]">
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.truck') }}" tabindex="0">
                                        <span class="menu-title">Upload</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.truck.index') }}" tabindex="0">
                                        <span class="menu-title">Lista</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div
                            class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-gray-900 menu-item-here:border-b-gray-900"
                            data-menu-item-placement="bottom-start" data-menu-item-placement-rtl="bottom-end"
                            data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                            <div class="menu-link gap-1.5" tabindex="0">
                                <span
                                    class="menu-title text-nowrap text-sm text-gray-800 menu-item-active:text-gray-900 menu-item-active:font-medium menu-item-here:text-gray-900 menu-item-here:font-medium menu-item-show:text-gray-900 menu-link-hover:text-gray-900">Argus</span>
                                <span class="menu-arrow"><i class="ki-filled ki-down text-2xs text-gray-500"></i></span>
                            </div>
                            <div class="menu-dropdown menu-default py-2 min-w-[200px]">
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.argus') }}" tabindex="0">
                                        <span class="menu-title">Upload</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ route('uploads.index.argus.index') }}" tabindex="0">
                                        <span class="menu-title">Lista</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @include('layouts.navBar.dates')

        </div>
        <!-- End of Container -->
    </div>
</div>
