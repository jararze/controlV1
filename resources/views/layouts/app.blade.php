<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="true" data-theme-mode="light"
      dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>

    <link href="{{ asset("assets/media/app/favicon.ico") }}" rel="shortcut icon"/>
    <!-- Scripts -->
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body
    class="antialiased flex h-full text-base text-gray-700 [--tw-page-bg:#f6f6f6] [--tw-page-bg-dark:var(--tw-coal-200)] [--tw-content-bg:var(--tw-light)] [--tw-content-bg-dark:var(--tw-coal-500)] [--tw-content-scrollbar-color:#e8e8e8] [--tw-header-height:58px] [--tw-sidebar-width:58px] [--tw-navbar-height:56px] bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark] lg:overflow-hidden">
<!-- Theme Mode -->
<script>
    const defaultThemeMode = 'light'; // light|dark|system
    let themeMode;

    if (document.documentElement) {
        if (localStorage.getItem('theme')) {
            themeMode = localStorage.getItem('theme');
        } else if (document.documentElement.hasAttribute('data-theme-mode')) {
            themeMode = document.documentElement.getAttribute('data-theme-mode');
        } else {
            themeMode = defaultThemeMode;
        }

        if (themeMode === 'system') {
            themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.classList.add(themeMode);
    }
</script>
<!-- End of Theme Mode -->
<div class="flex grow">
    {{--            <!-- Page Heading -->--}}
    {{--            @isset($header)--}}
    {{--                <header class="bg-white dark:bg-gray-800 shadow">--}}
    {{--                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">--}}
    {{--                        {{ $header }}--}}
    {{--                    </div>--}}
    {{--                </header>--}}
    {{--            @endisset--}}

    @include('layouts.header')


    <div class="flex flex-col lg:flex-row grow pt-[--tw-header-height]">
        <!-- Sidebar -->

        @include('layouts.navigation')


        <!-- End of Sidebar -->
        <!-- Main -->
        <!-- Navbar -->
        @isset($navigation)
            @include('layouts.navBar.' . $navigation)
        @endisset
        <!-- End of Navbar -->
        <div
            class="flex grow rounded-b-xl bg-[--tw-content-bg] dark:bg-[--tw-content-bg-dark] border-x border-b border-gray-400 dark:border-gray-200 lg:mt-[--tw-navbar-height] mx-5 lg:ms-[--tw-sidebar-width] mb-5">
            <div
                class="flex flex-col grow lg:scrollable-y lg:[scrollbar-width:auto] lg:light:[--tw-scrollbar-thumb-color:var(--tw-content-scrollbar-color)] pt-7 lg:[&_.container-fluid]:pe-4"
                id="scrollable_content">
                <main class="grow" role="content">
                    <!-- Container -->
                    {{ $slot }}
                    <!-- End of Container -->
                </main>
                <!-- Footer -->
                @include('layouts.footer')
                <!-- End of Footer -->
            </div>
        </div>
        <!-- End of Main -->
        @include('layouts.searchModal')
        @include('layouts.calls.register')
    </div>
</div>


<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/widgets/general.js') }}"></script>

@stack('script')
</body>
</html>
