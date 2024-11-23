<x-app-layout>
    <x-slot name="navigation">
        navBar
    </x-slot>

    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- begin: grid -->
            <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                <div class="lg:col-span-2">
                    <div class="card h-full">
                        <div class="card-body flex flex-col place-content-center gap-5">
                            <div class="flex justify-center">
                                <img alt="image" class="dark:hidden max-h-[180px]" src="assets/media/illustrations/32.svg"/>
                                <img alt="image" class="light:hidden max-h-[180px]" src="assets/media/illustrations/32-dark.svg"/>
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-3 text-center">
                                    <h2 class="text-1.5xl font-semibold text-gray-900">
                                        Swift Setup for New Teams
                                    </h2>
                                    <p class="text-sm font-medium text-gray-700">
                                        Enhance team formation and management with easy-to-use tools for communication,
                                        <br/>
                                        task organization, and progress tracking, all in one place.
                                    </p>
                                </div>
                                <div class="flex justify-center">
                                    <a class="btn btn-dark" href="html/demo3/public-profile/teams.html">
                                        Create Team
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">
                                Highlights
                            </h3>
                            <div class="menu" data-menu="true">
                                <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-start" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                        <i class="ki-filled ki-dots-vertical">
                                        </i>
                                    </button>
                                    <div class="menu-dropdown menu-default w-full max-w-[200px]" data-menu-dismiss="true">
                                        <div class="menu-item">
                                            <a class="menu-link" href="html/demo3/account/activity.html">
                  <span class="menu-icon">
                   <i class="ki-filled ki-cloud-change">
                   </i>
                  </span>
                                                <span class="menu-title">
                   Activity
                  </span>
                                            </a>
                                        </div>
                                        <div class="menu-item">
                                            <a class="menu-link" data-modal-toggle="#share_profile_modal" href="#">
                  <span class="menu-icon">
                   <i class="ki-filled ki-share">
                   </i>
                  </span>
                                                <span class="menu-title">
                   Share
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
                   Notifications
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
                                            <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                  <span class="menu-icon">
                   <i class="ki-filled ki-dislike">
                   </i>
                  </span>
                                                <span class="menu-title">
                   Report
                  </span>
                                            </a>
                                        </div>
                                        <div class="menu-separator">
                                        </div>
                                        <div class="menu-item">
                                            <a class="menu-link" href="html/demo3/account/home/settings-enterprise.html">
                  <span class="menu-icon">
                   <i class="ki-filled ki-setting-3">
                   </i>
                  </span>
                                                <span class="menu-title">
                   Settings
                  </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                            <div class="flex flex-col gap-0.5">
              <span class="text-sm font-normal text-gray-700">
               All time sales
              </span>
                                <div class="flex items-center gap-2.5">
               <span class="text-3xl font-semibold text-gray-900">
                $295.7k
               </span>
                                    <span class="badge badge-outline badge-success badge-sm">
                +2.7%
               </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 mb-1.5">
                                <div class="bg-success h-2 w-full max-w-[60%] rounded-sm">
                                </div>
                                <div class="bg-brand h-2 w-full max-w-[25%] rounded-sm">
                                </div>
                                <div class="bg-info h-2 w-full max-w-[15%] rounded-sm">
                                </div>
                            </div>
                            <div class="flex items-center flex-wrap gap-4 mb-1">
                                <div class="flex items-center gap-1.5">
               <span class="badge badge-dot size-2 badge-success">
               </span>
                                    <span class="text-sm font-normal text-gray-800">
                Metronic
               </span>
                                </div>
                                <div class="flex items-center gap-1.5">
               <span class="badge badge-dot size-2 badge-danger">
               </span>
                                    <span class="text-sm font-normal text-gray-800">
                Bundle
               </span>
                                </div>
                                <div class="flex items-center gap-1.5">
               <span class="badge badge-dot size-2 badge-info">
               </span>
                                    <span class="text-sm font-normal text-gray-800">
                MetronicNest
               </span>
                                </div>
                            </div>
                            <div class="border-b border-gray-300">
                            </div>
                            <div class="grid gap-3">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-filled ki-shop text-base text-gray-500">
                                        </i>
                                        <span class="text-sm font-normal text-gray-900">
                 Online Store
                </span>
                                    </div>
                                    <div class="flex items-center text-sm font-medium text-gray-800 gap-6">
                <span class="lg:text-right">
                 $172k
                </span>
                                        <span class="lg:text-right">
                 <i class="ki-filled ki-arrow-up text-success">
                 </i>
                 3.9%
                </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-filled ki-facebook text-base text-gray-500">
                                        </i>
                                        <span class="text-sm font-normal text-gray-900">
                 Facebook
                </span>
                                    </div>
                                    <div class="flex items-center text-sm font-medium text-gray-800 gap-6">
                <span class="lg:text-right">
                 $85k
                </span>
                                        <span class="lg:text-right">
                 <i class="ki-filled ki-arrow-down text-danger">
                 </i>
                 0.7%
                </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-filled ki-instagram text-base text-gray-500">
                                        </i>
                                        <span class="text-sm font-normal text-gray-900">
                 Instagram
                </span>
                                    </div>
                                    <div class="flex items-center text-sm font-medium text-gray-800 gap-6">
                <span class="lg:text-right">
                 $36k
                </span>
                                        <span class="lg:text-right">
                 <i class="ki-filled ki-arrow-up text-success">
                 </i>
                 8.2%
                </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-filled ki-google text-base text-gray-500">
                                        </i>
                                        <span class="text-sm font-normal text-gray-900">
                 Google
                </span>
                                    </div>
                                    <div class="flex items-center text-sm font-medium text-gray-800 gap-6">
                <span class="lg:text-right">
                 $26k
                </span>
                                        <span class="lg:text-right">
                 <i class="ki-filled ki-arrow-up text-success">
                 </i>
                 8.2%
                </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-filled ki-shop text-base text-gray-500">
                                        </i>
                                        <span class="text-sm font-normal text-gray-900">
                 Retail
                </span>
                                    </div>
                                    <div class="flex items-center text-sm font-medium text-gray-800 gap-6">
                <span class="lg:text-right">
                 $7k
                </span>
                                        <span class="lg:text-right">
                 <i class="ki-filled ki-arrow-down text-danger">
                 </i>
                 0.7%
                </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
            <!-- begin: grid -->
            <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                <div class="lg:col-span-2">
                    <div class="grid">
                        <div class="card card-grid h-full min-w-full">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Teams
                                </h3>
                                <div class="input input-sm max-w-48">
                                    <i class="ki-filled ki-magnifier">
                                    </i>
                                    <input placeholder="Search Teams" type="text"/>
                                </div>
                            </div>
                            <div class="card-body">
                                <div data-datatable="true" data-datatable-page-size="5">
                                    <div class="scrollable-x-auto">
                                        <table class="table table-border" data-datatable-table="true">
                                            <thead>
                                            <tr>
                                                <th class="w-[60px]">
                                                    <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"/>
                                                </th>
                                                <th class="min-w-[280px]">
                    <span class="sort asc">
                     <span class="sort-label">
                      Team
                     </span>
                     <span class="sort-icon">
                     </span>
                    </span>
                                                </th>
                                                <th class="min-w-[135px]">
                    <span class="sort">
                     <span class="sort-label">
                      Rating
                     </span>
                     <span class="sort-icon">
                     </span>
                    </span>
                                                </th>
                                                <th class="min-w-[135px]">
                    <span class="sort">
                     <span class="sort-label">
                      Last Modified
                     </span>
                     <span class="sort-icon">
                     </span>
                    </span>
                                                </th>
                                                <th class="min-w-[135px]">
                    <span class="sort">
                     <span class="sort-label">
                      Members
                     </span>
                     <span class="sort-icon">
                     </span>
                    </span>
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="1"/>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-2">
                                                        <a class="leading-none font-medium text-sm text-gray-900 hover:text-primary" href="#">
                                                            Product Management
                                                        </a>
                                                        <span class="text-2sm text-gray-700 font-normal leading-3">
                      Product development & lifecycle
                     </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="rating">
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    21 Oct, 2024
                                                </td>
                                                <td>
                                                    <div class="flex -space-x-2">
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-4.png"/>
                                                        </div>
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-1.png"/>
                                                        </div>
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-2.png"/>
                                                        </div>
                                                        <div class="flex">
                      <span class="relative inline-flex items-center justify-center shrink-0 rounded-full ring-1 font-semibold leading-none text-3xs size-[30px] text-success-inverse ring-success-light bg-success">
                       +10
                      </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="2"/>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-2">
                                                        <a class="leading-none font-medium text-sm text-gray-900 hover:text-primary" href="#">
                                                            Marketing Team
                                                        </a>
                                                        <span class="text-2sm text-gray-700 font-normal leading-3">
                      Campaigns & market analysis
                     </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="rating">
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label indeterminate">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none" style="width: 50.0%">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    15 Oct, 2024
                                                </td>
                                                <td>
                                                    <div class="flex -space-x-2">
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-4.png"/>
                                                        </div>
                                                        <div class="flex">
                      <span class="hover:z-5 relative inline-flex items-center justify-center shrink-0 rounded-full ring-1 font-semibold leading-none text-3xs size-[30px] uppercase text-warning-inverse ring-warning-light bg-warning">
                       g
                      </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="3"/>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-2">
                                                        <a class="leading-none font-medium text-sm text-gray-900 hover:text-primary" href="#">
                                                            HR Department
                                                        </a>
                                                        <span class="text-2sm text-gray-700 font-normal leading-3">
                      Talent acquisition, employee welfare
                     </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="rating">
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    10 Oct, 2024
                                                </td>
                                                <td>
                                                    <div class="flex -space-x-2">
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-4.png"/>
                                                        </div>
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-1.png"/>
                                                        </div>
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-2.png"/>
                                                        </div>
                                                        <div class="flex">
                      <span class="relative inline-flex items-center justify-center shrink-0 rounded-full ring-1 font-semibold leading-none text-3xs size-[30px] text-info-inverse ring-info-light bg-info">
                       +A
                      </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="4"/>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-2">
                                                        <a class="leading-none font-medium text-sm text-gray-900 hover:text-primary" href="#">
                                                            Sales Division
                                                        </a>
                                                        <span class="text-2sm text-gray-700 font-normal leading-3">
                      Customer relations, sales strategy
                     </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="rating">
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                        <div class="rating-label checked">
                                                            <i class="rating-on ki-solid ki-star text-base leading-none">
                                                            </i>
                                                            <i class="rating-off ki-outline ki-star text-base leading-none">
                                                            </i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    05 Oct, 2024
                                                </td>
                                                <td>
                                                    <div class="flex -space-x-2">
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-24.png"/>
                                                        </div>
                                                        <div class="flex">
                                                            <img class="hover:z-5 relative shrink-0 rounded-full ring-1 ring-light-light size-[30px]" src="assets/media/avatars/300-7.png"/>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                                        <div class="flex items-center gap-2 order-2 md:order-1">
                                            Show
                                            <select class="select select-sm w-16" data-datatable-size="true" name="perpage">
                                            </select>
                                            per page
                                        </div>
                                        <div class="flex items-center gap-4 order-1 md:order-2">
                 <span data-datatable-info="true">
                 </span>
                                            <div class="pagination" data-datatable-pagination="true">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">
                                Block List
                            </h3>
                        </div>
                        <div class="card-body flex flex-col gap-5">
                            <div class="text-sm text-gray-800">
                                Users on the block list are unable to send chat requests or messages to you anymore, ever, or again
                            </div>
                            <div class="input-group">
                                <input class="input" placeholder="Block new user" type="text" value="">
                                <span class="btn btn-primary">
                Add
               </span>
                                </input>
                            </div>
                            <div class="flex flex-col gap-5">
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="">
                                            <img class="h-9 rounded-full" src="assets/media/avatars/gray/1.png"/>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <a class="flex items-center gap-1.5 leading-none font-medium text-sm text-gray-900 hover:text-primary" href="html/demo3/public-profile/teams.html">
                                                Esther Howard
                                            </a>
                                            <span class="text-2sm text-gray-700">
                  6 commits
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <a class="btn btn-sm btn-icon btn-clear btn-light" href="#">
                                            <i class="ki-filled ki-trash">
                                            </i>
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="">
                                            <img class="h-9 rounded-full" src="assets/media/avatars/gray/2.png"/>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <a class="flex items-center gap-1.5 leading-none font-medium text-sm text-gray-900 hover:text-primary" href="html/demo3/public-profile/teams.html">
                                                Tyler Hero
                                            </a>
                                            <span class="text-2sm text-gray-700">
                  29 commits
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <a class="btn btn-sm btn-icon btn-clear btn-light" href="#">
                                            <i class="ki-filled ki-trash">
                                            </i>
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="">
                                            <img class="h-9 rounded-full" src="assets/media/avatars/gray/3.png"/>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <a class="flex items-center gap-1.5 leading-none font-medium text-sm text-gray-900 hover:text-primary" href="html/demo3/public-profile/teams.html">
                                                Arlene McCoy
                                            </a>
                                            <span class="text-2sm text-gray-700">
                  34 commits
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <a class="btn btn-sm btn-icon btn-clear btn-light" href="#">
                                            <i class="ki-filled ki-trash">
                                            </i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
            <!-- begin: grid -->
            <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header" id="external_services_integrations">
                            <h3 class="card-title">
                                Integrations
                            </h3>
                        </div>
                        <div class="card-body grid gap-5 lg:gap-7.5 lg:py-7.5 py-5">
                            <div class="grid gap-5">
                                <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                    <div class="flex items-center flex-wrap gap-3.5">
                                        <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/google-webdev.svg"/>
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5">
                                                <a class="text-sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                                    Google web.dev
                                                </a>
                                                <a class="text-2sm font-medium text-gray-600 hover:text-primary-active" href="#">
                                                    webdev@webdevmail.com
                                                </a>
                                            </div>
                                            <span class="text-2sm font-medium text-gray-600">
                  Integrate for enhanced collaboration in web development.
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 lg:gap-5">
                                        <label class="switch switch-sm">
                                            <input checked="" type="checkbox" value="1"/>
                                        </label>
                                        <div class="btn btn-sm btn-icon btn-clear btn-light">
                                            <i class="ki-filled ki-setting-2">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                    <div class="flex items-center flex-wrap gap-3.5">
                                        <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/evernote.svg"/>
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5">
                                                <a class="text-sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                                    Evernote
                                                </a>
                                                <a class="text-2sm font-medium text-gray-600 hover:text-primary-active" href="#">
                                                    evernote@noteexample.com
                                                </a>
                                            </div>
                                            <span class="text-2sm font-medium text-gray-600">
                  Streamline cryptocurrency transactions securely and efficiently.
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 lg:gap-5">
                                        <label class="switch switch-sm">
                                            <input checked="" type="checkbox" value="1"/>
                                        </label>
                                        <div class="btn btn-sm btn-icon btn-clear btn-light">
                                            <i class="ki-filled ki-setting-2">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                    <div class="flex items-center flex-wrap gap-3.5">
                                        <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/inferno.svg"/>
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5">
                                                <a class="text-sm font-semibold text-gray-900 hover:text-primary-active" href="#">
                                                    Inferno
                                                </a>
                                                <a class="text-2sm font-medium text-gray-600 hover:text-primary-active" href="#">
                                                    inferno@dataexample.com
                                                </a>
                                            </div>
                                            <span class="text-2sm font-medium text-gray-600">
                  Robust email integration for data management.
                 </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 lg:gap-5">
                                        <label class="switch switch-sm">
                                            <input checked="" type="checkbox" value="1"/>
                                        </label>
                                        <div class="btn btn-sm btn-icon btn-clear btn-light">
                                            <i class="ki-filled ki-setting-2">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">
                                Manage your Data
                            </h3>
                        </div>
                        <div class="card-group flex items-center justify-between py-4 gap-2.5">
                            <div class="flex flex-col justify-center gap-1.5">
              <span class="leading-none font-medium text-sm text-gray-900">
               Download your data
              </span>
                                <span class="text-2sm text-gray-700">
               Add an extra layer of security.
              </span>
                            </div>
                            <a class="btn btn-sm btn-light btn-outline" href="#">
                                Start
                            </a>
                        </div>
                        <div class="card-group flex items-center justify-between py-4 gap-2.5">
                            <div class="flex flex-col justify-center gap-1.5">
              <span class="leading-none font-medium text-sm text-gray-900">
               Delete all of your data
              </span>
                                <span class="text-2sm text-gray-700">
               Instantly sign out all services.
              </span>
                            </div>
                            <a class="btn btn-sm btn-light btn-outline" href="#">
                                Delete
                            </a>
                        </div>
                        <div class="card-group flex items-center justify-between py-4 gap-2.5">
                            <div class="flex flex-col justify-center gap-1.5">
              <span class="leading-none font-medium text-sm text-gray-900">
               Auto Data Purge
              </span>
                                <span class="text-2sm text-gray-700">
               Toggle automatic deletion of old data.
              </span>
                            </div>
                            <div class="switch switch-sm">
                                <input name="param" type="checkbox" value="1"/>
                            </div>
                        </div>
                        <div class="card-group flex items-center justify-between py-4 gap-2.5">
                            <div class="flex flex-col justify-center gap-1.5">
              <span class="leading-none font-medium text-sm text-gray-900">
               Export your data
              </span>
                                <span class="text-2sm text-gray-700">
               Download a copy of your data
              </span>
                            </div>
                            <button class="btn btn-sm btn-icon btn-light btn-clear">
                                <svg fill="none" height="28" viewbox="0 0 28 28" width="28" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7.72779 15.7486C8.30339 16.3627 9.0987 16.7239 9.93985 16.7533H18.0297C18.8708 16.7239 19.6661 16.3627 20.2417 15.7486C20.8173 15.1346 21.1264 14.3176 21.1014 13.4763V10.537C21.1264 9.69631 20.8171 8.88004 20.2413 8.26703C19.6655 7.65402 18.8702 7.29426 18.0297 7.2666H9.93985C9.09929 7.29426 8.30398 7.65402 7.72819 8.26703C7.15241 8.88004 6.84312 9.69631 6.86811 10.537V13.4763C6.8431 14.3176 7.15218 15.1346 7.72779 15.7486Z" fill="#78829D" opacity="0.1">
                                    </path>
                                    <path d="M18.0735 16.7533C17.9385 16.7534 17.8089 16.7002 17.7128 16.6054C17.6167 16.5105 17.5619 16.3817 17.5601 16.2467C17.5618 16.1111 17.6165 15.9815 17.7124 15.8856C17.8083 15.7897 17.9378 15.7351 18.0735 15.7333C18.6512 15.7057 19.1945 15.4502 19.5843 15.0229C19.9742 14.5956 20.1788 14.0312 20.1534 13.4533V10.4933C20.177 9.91593 19.9718 9.35255 19.5823 8.92562C19.1928 8.49869 18.6506 8.24272 18.0735 8.21335H9.92678C9.34964 8.24272 8.80741 8.49869 8.41793 8.92562C8.02845 9.35255 7.8232 9.91593 7.84678 10.4933V13.4533C7.82143 14.0312 8.02608 14.5956 8.41591 15.0229C8.80575 15.4502 9.34902 15.7057 9.92678 15.7333C10.0624 15.7351 10.192 15.7897 10.2879 15.8856C10.3838 15.9815 10.4384 16.1111 10.4401 16.2467C10.4384 16.3817 10.3835 16.5105 10.2874 16.6054C10.1913 16.7002 10.0618 16.7534 9.92678 16.7533C9.07972 16.7238 8.27882 16.36 7.69917 15.7416C7.11952 15.1233 6.80826 14.3005 6.83345 13.4533V10.4933C6.80829 9.64679 7.11975 8.82479 7.69958 8.20747C8.27941 7.59015 9.08031 7.22787 9.92678 7.20001H18.0735C18.9199 7.22787 19.7208 7.59015 20.3007 8.20747C20.8805 8.82479 21.1919 9.64679 21.1668 10.4933V13.4533C21.192 14.3005 20.8807 15.1233 20.3011 15.7416C19.7214 16.36 18.9205 16.7238 18.0735 16.7533ZM13.4934 12.32V19.04L12.3801 17.9267C12.2846 17.8312 12.1551 17.7776 12.0201 17.7776C11.8851 17.7776 11.7556 17.8312 11.6601 17.9267C11.5646 18.0222 11.511 18.1517 11.511 18.2867C11.511 18.4217 11.5646 18.5512 11.6601 18.6467L13.6601 20.6467C13.7072 20.6942 13.7633 20.732 13.8251 20.7578C13.8869 20.7836 13.9532 20.7968 14.0201 20.7968C14.0871 20.7968 14.1534 20.7836 14.2151 20.7578C14.2769 20.732 14.333 20.6942 14.3801 20.6467L16.3801 18.6467C16.4274 18.5995 16.4647 18.5433 16.4899 18.4815C16.5151 18.4197 16.5276 18.3534 16.5268 18.2867C16.5275 18.186 16.4981 18.0873 16.4425 18.0034C16.3869 17.9194 16.3075 17.8539 16.2145 17.8153C16.1215 17.7767 16.0191 17.7666 15.9203 17.7865C15.8216 17.8063 15.731 17.8551 15.6601 17.9267L14.5468 19.04V12.32C14.5468 12.1856 14.4934 12.0568 14.3984 11.9617C14.3034 11.8667 14.1745 11.8133 14.0401 11.8133C13.9057 11.8133 13.7769 11.8667 13.6818 11.9617C13.5868 12.0568 13.5334 12.1856 13.5334 12.32H13.4934Z" fill="#78829D">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
        </div>
    </div>
</x-app-layout>
