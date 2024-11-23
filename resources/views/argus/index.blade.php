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
                    Selección de Archivos
                </h1>
            </div>
        </div>
        <!-- End of Container -->
    </div>
    <!-- End of Toolbar -->

    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">

            <!-- Mensajes de error -->
            @if ($errors->any())
                <div class="bg-red-500 text-white px-4 py-3 rounded relative mb-4">
                    <strong class="font-bold">Error!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Mensajes de éxito -->
            @if (session('success'))
                <div class="bg-green-500 text-white px-4 py-3 rounded relative mb-4">
                    <strong class="font-bold">Success!</strong>
                    <span class="block">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Mensajes de error generales -->
            @if (session('error'))
                <div class="bg-red-500 text-white px-4 py-3 rounded relative mb-4">
                    <strong class="font-bold">Error!</strong>
                    <span class="block">{{ session('error') }}</span>
                </div>
            @endif
            <form action="{{ route('argus.files.process') }}" method="POST">
                @csrf
                <!-- Tabla para seleccionar archivo de Truck -->
                <div class="card card-grid min-w-full">
                    <div class="card-header py-5 flex-wrap">
                        <h3 class="card-title">
                            Selecciona un archivo de Truck
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                <tr>
                                    <th class="w-[60px]">Seleccionar</th>
                                    <th>Nombre</th>
                                    <th>Fecha Registro</th>
                                    <th>Añadido hace</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($truckFiles as $truck)
                                    <tr>
                                        <td>
                                            <input type="radio" name="truck_file" value="{{ $truck->batch_id }}"
                                                   required>
                                        </td>
                                        <td>{{ $truck->file_name }}</td>
                                        <td>{{ $truck->fecha_registro ? $truck->fecha_registro->format('Y-m-d') : 'No disponible' }}</td>
                                        <td>{{ $truck->fecha_registro->diffForHumans() }}</td>
                                        <td>
                                            {!! $truck->final_status == 1
                                                ? '<span class="badge badge-success badge-outline rounded-[30px]">
                                                     <span class="size-1.5 rounded-full bg-success me-1.5"></span> Activo
                                                   </span>'
                                                : '<span class="badge badge-danger badge-outline rounded-[30px]">
                                                     <span class="size-1.5 rounded-full bg-danger me-1.5"></span> Inactivo
                                                   </span>'
                                            !!}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabla para seleccionar archivo de Argus -->
                <div class="card card-grid min-w-full">
                    <div class="card-header py-5 flex-wrap">
                        <h3 class="card-title">
                            Selecciona un archivo de Argus
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border">
                                <thead>
                                <tr>
                                    <th class="w-[60px]">Seleccionar</th>
                                    <th>Nombre</th>
                                    <th>Fecha Registro</th>
                                    <th>Añadido hace</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($argusFiles as $argus)
                                    <tr>
                                        <td>
                                            <input type="radio" name="argus_file" value="{{ $argus->batch_id }}"
                                                   required>
                                        </td>
                                        <td>{{ $argus->file_name }}</td>
                                        <td>{{ $argus->fecha_registro ? $argus->fecha_registro->format('Y-m-d') : 'No disponible' }}</td>
                                        <td>{{ $argus->fecha_registro->diffForHumans() }}</td>
                                        <td>
                                            {!! $argus->final_status == 1
                                                ? '<span class="badge badge-success badge-outline rounded-[30px]">
                                                     <span class="size-1.5 rounded-full bg-success me-1.5"></span> Activo
                                                   </span>'
                                                : '<span class="badge badge-danger badge-outline rounded-[30px]">
                                                     <span class="size-1.5 rounded-full bg-danger me-1.5"></span> Inactivo
                                                   </span>'
                                            !!}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botón de envío -->

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Validar y Procesar</button>
                </div>
            </form>

        </div>
    </div>
    <!-- End of Container -->
</x-app-layout>
