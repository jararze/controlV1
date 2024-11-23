@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />
    <style>
        .dt-layout-row {
            padding: 0 30px !important;
        }
        .dt-layout-row.dt-layout-table {
            padding: 0 0px !important;
        }
    </style>
@endpush
@push('script')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script type="text/javascript" src="{{ asset("assets/js/calls/call.js") }}"></script>
@endpush
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Filtros
                        <a class="link" href="#">
                            Matriz
                        </a>
                    </h3>
                </div>
                <div class="card-body lg:py-7.5 grid gap-5 lg:gap-7.5 grid-cols-3">

                    <div
                        class="flex items-center justify-between grow border border-gray-200 rounded-xl gap-2 p-5 rtl:[background-position:-100%_44%] [background-position:220%_44%] bg-no-repeat bg-[length:650px] user-access-bg">
                        <div class="flex items-center gap-4">
                            <div class="relative size-[50px] shrink-0">
                                <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none" height="48"
                                     viewBox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                        fill=""></path>
                                    <path
                                        d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                        stroke=""></path>
                                </svg>
                                <div
                                    class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                    <i class="ki-filled ki-security-user text-xl text-brand"></i>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2.5">
                                    <a class="text-base font-medium text-gray-900 hover:text-primary-active" href="#">Vista
                                        Agrupada</a>
                                </div>
                                <div class="form-info text-gray-800 font-normal">
                                    En este acceso se podra ver
                                    <br>
                                    Todos los camiones agrupados por destino.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button class="btn btn-sm btn-dark">Ir a la vista</button>
                        </div>
                    </div>


                    <div
                        class="flex items-center justify-between grow border border-gray-200 rounded-xl gap-2 p-5 rtl:[background-position:-100%_44%] [background-position:220%_44%] bg-no-repeat bg-[length:650px] user-access-bg">
                        <div class="flex items-center gap-4">
                            <div class="relative size-[50px] shrink-0">
                                <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none" height="48"
                                     viewBox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                        fill=""></path>
                                    <path
                                        d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                        stroke=""></path>
                                </svg>
                                <div
                                    class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                    <i class="ki-filled ki-security-user text-xl text-brand"></i>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2.5">
                                    <a class="text-base font-medium text-gray-900 hover:text-primary-active" href="#">Vista
                                        para llamar</a>
                                </div>
                                <div class="form-info text-gray-800 font-normal">
                                    Esta vista es para llamar
                                    <br>
                                    Se despliega por tabla con todos los datos.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button class="btn btn-sm btn-dark">Ir a la vista</button>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between grow border border-gray-200 rounded-xl gap-2 p-5 rtl:[background-position:-100%_44%] [background-position:220%_44%] bg-no-repeat bg-[length:650px] user-access-bg">
                        <div class="flex items-center gap-4">
                            <div class="relative size-[50px] shrink-0">
                                <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none" height="48"
                                     viewBox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                        fill=""></path>
                                    <path
                                        d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                        stroke=""></path>
                                </svg>
                                <div
                                    class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                    <i class="ki-filled ki-security-user text-xl text-brand"></i>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2.5">
                                    <a class="text-base font-medium text-gray-900 hover:text-primary-active" href="#">Vista
                                        Agrupada</a>
                                </div>
                                <div class="form-info text-gray-800 font-normal">
                                    En este acceso se podra ver
                                    <br>
                                    Todos los camiones agrupados por destino.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button class="btn btn-sm btn-dark">Ir a la vista</button>
                        </div>
                    </div>


                </div>


                <div class="card-body ">


                    <style>
                        /* Ajuste de altura para el select de productos */
                        .select-multiple {
                            min-height: 100px; /* Ajusta la altura mínima del select múltiple */
                        }
                    </style>

                    <form action="{{ route('uploads.index.matrix.work', $batch_id) }}" method="GET"
                          class="grid grid-cols-1 lg:grid-cols-5 gap-5 py-5 lg:py-7.5">
                        @csrf

                        <!-- Filtro Origen -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Origen</span>
                            </div>
                            <div class="grow">
                                <select class="select" name="origen">
                                    <option value="">Seleccionar</option>
                                    @foreach($origenes as $origen)
                                        <option value="{{ $origen }}"
                                                @if(request('origen') == $origen) selected @endif>{{ $origen }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Filtro Destino -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Destino</span>
                            </div>
                            <div class="grow">
                                <select class="select" name="destino">
                                    <option value="">Seleccionar</option>
                                    @foreach($destinos as $destino)
                                        <option value="{{ $destino }}"
                                                @if(request('destino') == $destino) selected @endif>{{ $destino }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Filtro Tipo Producto -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Tipo Producto</span>
                            </div>
                            <div class="grow">
                                <select class="select" name="tipo_producto">
                                    <option value="">Seleccionar</option>
                                    @foreach($tipoViajes as $tipoViaje)
                                        <option value="{{ $tipoViaje }}"
                                                @if(request('tipo_producto') == $tipoViaje) selected @endif>{{ $tipoViaje }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Filtro Patente -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Patente</span>
                            </div>
                            <div class="grow">
                                <input type="text" class="input" name="patente" value="{{ request('patente') }}"
                                       placeholder="Buscar patente">
                            </div>
                        </div>

                        <!-- Filtro Conductor -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Conductor</span>
                            </div>
                            <div class="grow">
                                <input type="text" class="input" name="conductor" value="{{ request('conductor') }}"
                                       placeholder="Buscar conductor">
                            </div>
                        </div>

                        <!-- Filtro Producto (múltiple) -->
                        <div class="rounded-xl border p-4 flex flex-col gap-2.5 col-span-1 lg:col-span-2">
                            <div class="flex items-center gap-3.5">
                                <span class="leading-none font-medium text-sm text-gray-900">Producto</span>
                            </div>
                            <div class="grow">
                                <select class="select select-multiple" name="cod_prod[]" multiple>
                                    @foreach($productos as $cod => $producto)
                                        <option value="{{ $cod }}"
                                                @if(request('cod_prod') && in_array($cod, request('cod_prod'))) selected @endif>
                                            {{ $producto }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Botones Aplicar y Limpiar -->
                        <div class="flex flex-col space-y-3 col-span-1 lg:col-span-5 items-center mt-6">
                            <button type="submit"
                                    class="w-full lg:w-1/4 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-3 rounded-lg shadow-md transition duration-150 transform hover:scale-105">
                                Aplicar Filtros
                            </button>
                            <a href="{{ route('uploads.index.matrix.work', $batch_id) }}"
                               class="w-full lg:w-1/4 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-3 rounded-lg shadow-md transition duration-150 transform hover:scale-105 text-center">
                                Limpiar Filtros
                            </a>
                        </div>
                    </form>


                </div>
            </div>


            <div class="card card-grid min-w-full">
                <div class="card-header py-5 flex-wrap gap-2">
                    <h3 class="card-title">
                        Para llamar
                    </h3>
                    <div class="flex gap-6">
                        <label class="switch switch-sm">
                            <input class="order-2" name="check" type="checkbox" value="1"/>
                            <span class="switch-label order-1">Active Users</span>
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <div class="scrollable-x-auto">
                            <table class="table table-border"  id="members_table">
                                <thead>
                                <tr>
                                    <th class="min-w-[180px]">
                                        <span class="sort asc"><span
                                                class="sort-label text-gray-700 font-normal">Origen</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[180px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Destino</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Planilla</span><span
                                                class="sort-icon"></span></span></th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Patente</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span class="sort-label text-gray-700 font-normal">Tipo Producto</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span class="sort-label text-gray-700 font-normal">ETA</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">OBS ETA</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Conductor</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Celular</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Ol</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="sort"><span
                                                class="sort-label text-gray-700 font-normal">Celular OL</span><span
                                                class="sort-icon"></span></span>
                                    </th>
                                    <th class="w-[60px]">
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($results as $result)
                                    <tr
                                        data-planilla="{{ $result->planilla }}"
                                        data-apply-styles="{{ $result->apply_styles ? 'true' : 'false' }}"
                                        class="border-b">
                                        <td>
                                            {{ $result->dep_origen }}
                                        </td>
                                        <td>
                                            {{ $result->dep_destino }}
                                        </td>
                                        <td>
                                            {{ $result->planilla }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->patente }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->tipo_producto }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            @if (\Carbon\Carbon::hasFormat($result->eta, 'Y-m-d H:i:s'))
                                                {{ \Carbon\Carbon::parse($result->eta)->format('Y-m-d') }}
                                            @else
                                                {{ $result->eta }}
                                            @endif
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->obs_eta }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->driver ? $result->driver->conductor : 'No disponible' }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->driver ? $result->driver->celular_conductor : 'No disponible' }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->driver ? $result->driver->propietario : 'No disponible' }}
                                        </td>
                                        <td class="text-gray-800 font-normal">
                                            {{ $result->driver ? $result->driver->cel_propietario : 'No disponible' }}
                                        </td>
                                        <td>
                                            <div class="menu" data-menu="true">
                                                <div class="menu-item" data-menu-item-offset="0, 10px"
                                                     data-menu-item-placement="bottom-end"
                                                     data-menu-item-placement-rtl="bottom-start"
                                                     data-menu-item-toggle="dropdown"
                                                     data-menu-item-trigger="click|lg:click">
                                                    <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                        <i class="ki-filled ki-dots-vertical">
                                                        </i>
                                                    </button>
                                                    <div class="menu-dropdown menu-default w-full max-w-[175px]"
                                                         data-menu-dismiss="true">
                                                        <div class="menu-item">
                                                            <a class="menu-link"
                                                               href="#"
                                                               data-url="{{ route('work.matrix.call', ['patente' => $result->patente]) }}"
                                                               data-planilla="{{ $result->planilla }}"
                                                               data-batchid="{{ $batch_id }}"
                                                                {{--                                                               data-modal-toggle="#make_call"--}}
                                                            >
                                                                <span class="menu-icon"><i
                                                                        class="ki-filled ki-search-list"></i></span>
                                                                <span class="menu-title">Llamada</span>
                                                            </a>
                                                        </div>
                                                        <div class="menu-item">
                                                            <a class="menu-link" href="#">
                                                                <span class="menu-icon"><i
                                                                        class="ki-filled ki-file-up"></i></span>
                                                                <span class="menu-title">Ver Historico</span>
                                                            </a>
                                                        </div>
                                                        <div class="menu-separator">
                                                        </div>
                                                        <div class="menu-item">
                                                            <a class="menu-link" href="#">
                                                                <span class="menu-icon"><i
                                                                        class="ki-filled ki-pencil"></i></span>
                                                                <span class="menu-title">Marcar chofer erroneo</span>
                                                            </a>
                                                        </div>
                                                        <div class="menu-item">
                                                            <a class="menu-link" href="#">
                                                            <span class="menu-icon"><i
                                                                    class="ki-filled ki-copy"></i></span>
                                                                <span class="menu-title">Make a copy</span>
                                                            </a>
                                                        </div>
                                                        <div class="menu-separator">
                                                        </div>
                                                        <div class="menu-item">
                                                            <a class="menu-link" href="#">
                                                            <span class="menu-icon"><i
                                                                    class="ki-filled ki-trash"></i></span>
                                                                <span class="menu-title">Remove</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>


        </div>
    </div>
    <!-- End of Container -->

</x-app-layout>
