<x-app-layout>
    <x-slot name="navigation">
        uploads
    </x-slot>

    <!-- Toolbar -->
    <div class="pb-6">
        <!-- Container -->
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-900">
                    Lista Matriz plana
                </h1>
            </div>

        </div>
        <!-- End of Container -->
    </div>
    <!-- End of Toolbar -->
    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header py-5 flex-wrap">
                    <h3 class="card-title">
                        Listas
                    </h3>
                    <div class="flex gap-5">
                        <a class="btn btn-sm btn-primary" href="{{ route("uploads.index.matriz") }}">
                            Anadir matriz
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-page-size="10">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true" id="devices_table">
                                <thead>
                                <tr>
                                    <th class="w-[60px]">
                                        <input class="checkbox checkbox-sm" data-datatable-check="true"
                                               type="checkbox"/>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="sort asc"><span
                                                class="sort-label text-gray-700 text-2sm font-normal">Nombre</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="min-w-[165px]">
                                        <span class="sort"><span class="sort-label text-gray-700 text-2sm font-normal">Fecha Registro</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="min-w-[165px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 text-2sm font-normal"><span
                                                    class="pt-px" data-tooltip="true" data-tooltip-offset="0, 5"
                                                    data-tooltip-placement="left"><i
                                                        class="ki-filled ki-information-2 text-lg leading-none"></i><span
                                                        class="tooltip max-w-48">Time is based on your local timezone.</span></span>Anadido hace</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[60px]">
                                    </th>
                                    <th class="w-[60px]">
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($result as $matriz)
                                    <tr>
                                        <td>
                                            <input class="checkbox checkbox-sm" data-datatable-row-check="true"
                                                   type="checkbox" value="{{ $matriz->batch_id }}"/>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-4">
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="leading-none font-medium text-sm text-gray-900">{{ $matriz->file_name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-sm text-gray-800 font-normal">
                                            {{ $matriz->fecha_registro }}
                                        </td>
                                        <td class="text-sm text-gray-800 font-normal">
                                            {{ $matriz->fecha_registro->diffForHumans() }}
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-icon btn-clear btn-light" href="{{ route("uploads.index.matrix.work", $matriz->batch_id) }}">
                                                <i class="ki-filled ki-notepad-edit">
                                                </i>
                                            </a>
                                        </td>
                                        <td>
                                            <form action="{{ route('uploads.index.matrix.destroy', $matriz->batch_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-clear btn-light">
                                                    <i class="ki-filled ki-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach


                                </tbody>
                            </table>
                        </div>
                        <div
                            class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Show
                                <select class="select select-sm w-16" data-datatable-size="true" name="perpage">
                                </select>
                                per page
                            </div>
                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span data-datatable-info="true"></span>
                                <div class="pagination" data-datatable-pagination="true"></div>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="bg-green-500 border border-green-400 text-white px-4 py-3 rounded relative mb-4" role="alert">
                                <strong class="font-bold">Success!</strong>
                                <span class="block sm:inline">{{ session('success') }}</span>
                                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove();">
                                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <title>Close</title>
                                        <path d="M14.348 5.652a1 1 0 00-1.414 0L10 8.586 7.066 5.652a1 1 0 10-1.414 1.414L8.586 10l-2.934 2.934a1 1 0 101.414 1.414L10 11.414l2.934 2.934a1 1 0 001.414-1.414L11.414 10l2.934-2.934a1 1 0 000-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="bg-red-500 border border-red-400 text-white px-4 py-3 rounded relative mb-4" role="alert">
                                <strong class="font-bold">Error!</strong>
                                <span class="block sm:inline">{{ session('error') }}</span>
                                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove();">
                                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <title>Close</title>
                                        <path d="M14.348 5.652a1 1 0 00-1.414 0L10 8.586 7.066 5.652a1 1 0 10-1.414 1.414L8.586 10l-2.934 2.934a1 1 0 101.414 1.414L10 11.414l2.934 2.934a1 1 0 001.414-1.414L11.414 10l2.934-2.934a1 1 0 000-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        @endif



                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->

</x-app-layout>
