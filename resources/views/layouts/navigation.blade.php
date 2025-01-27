<div class="fixed w-[--tw-sidebar-width] lg:top-[--tw-header-height] top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 group py-3 lg:py-0"
     data-drawer="true" data-drawer-class="drawer drawer-start top-0 bottom-0" data-drawer-enable="true|lg:false"
     id="sidebar">
    <div class="flex grow shrink-0" id="sidebar_content">
        <div class="scrollable-y-auto grow gap-2.5 shrink-0 flex items-center flex-col" data-scrollable="true"
             data-scrollable-height="auto" data-scrollable-offset="0px" data-scrollable-wrappers="#sidebar_content">

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300 {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               data-tooltip="" data-tooltip-placement="right" href="{{ route('dashboard') }}">
                <span class="menu-icon">
                    <i class="ki-filled ki-chart-line-star"></i>
                </span>
                <span class="tooltip">Dashboard</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300 {{ request()->routeIs('argus.files.select') ? 'active' : '' }}"
               data-tooltip="" data-tooltip-placement="right" href="{{ route('argus.files.select') }}">
                <span class="menu-icon">
                    <i class="ki-filled ki-profile-circle"></i>
                </span>
                <span class="tooltip">Profile</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300 {{ request()->routeIs('uploads.index') ? 'active' : '' }}"
               data-tooltip="" data-tooltip-placement="right" href="{{ route('uploads.index') }}">
                <span class="menu-icon">
                    <i class="ki-filled ki-setting-2"></i>
                </span>
                <span class="tooltip">Upload Files</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300" {{ request()->routeIs('drivers.index') ? 'active' : '' }}
               data-tooltip="" data-tooltip-placement="right" href="{{ route('drivers.index') }}">
                <span class="menu-icon">
                    <i class="ki-filled ki-users"></i>
                </span>
                <span class="tooltip">Drivers</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="">
                <span class="menu-icon">
                    <i class="ki-filled ki-security-user"></i>
                </span>
                <span class="tooltip">Plans</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="">
                <span class="menu-icon">
                    <i class="ki-filled ki-messages"></i>
                </span>
                <span class="tooltip">Security Logs</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="">
                <span class="menu-icon">
                    <i class="ki-filled ki-shop"></i>
                </span>
                <span class="tooltip">Notifications</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="">
                <span class="menu-icon">
                    <i class="ki-filled ki-cheque"></i>
                </span>
                <span class="tooltip">ACL</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="">
                <span class="menu-icon">
                    <i class="ki-filled ki-code"></i>
                </span>
                <span class="tooltip">API Keys</span>
            </a>

            <a class="btn btn-icon btn-icon-lg rounded-full size-10 border border-transparent text-gray-600 hover:bg-light hover:text-primary hover:border-gray-300 [.active&]:bg-light [.active&]:text-primary [.active&]:border-gray-300"
               data-tooltip="" data-tooltip-placement="right" href="https://keenthemes.com/metronic/tailwind/docs/">
                <span class="menu-icon">
                    <i class="ki-filled ki-question"></i>
                </span>
                <span class="tooltip">Docs</span>
            </a>

        </div>
    </div>
</div>
