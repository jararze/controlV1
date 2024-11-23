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
                            <a class="menu-link gap-2.5" href="{{ route('dashboard') }}"
                               tabindex="0">
                                <span
                                    class="menu-title text-nowrap text-sm text-gray-800 menu-item-active:text-gray-900 menu-item-active:font-medium menu-item-here:text-gray-900 menu-item-here:font-medium menu-item-show:text-gray-900 menu-link-hover:text-gray-900">Dashboard</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @include('layouts.navBar.dates')
        </div>
        <!-- End of Container -->
    </div>
</div>
