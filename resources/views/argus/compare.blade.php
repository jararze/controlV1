<x-app-layout>
    <x-slot name="navigation">
        uploads
    </x-slot>

    <!-- Toolbar -->
    <div class="pb-6">
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-900">
                    Resultados de Comparación
                </h1>
            </div>
            <div>
                <form action="{{ route('argus.files.process.download') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Descargar Excel</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header py-5 flex-wrap">
                    <h3 class="card-title">Datos de Argus que no son viajes CBN</h3>
                </div>
                <div class="card-body">
                    @if ($result)
                        <table class="table table-auto table-border">
                            <thead>
                            <tr>
                                <th>Operación</th>
                                <th>Patente</th>
                                <th>Dia</th>
                                <th>Evento</th>
                                <th>Motorista</th>
                                <th>Hora Alarma</th>
                                <th>Velocidad</th>
                                <th>Latitud</th>
                                <th>Longitud</th>
                                <th>Evento ID</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($result as $row)
                                <tr>
                                    <td>{{ $row->operacion }}</td>
                                    <td>{{ $row->patente }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->dia)->toDateString() }}</td>
                                    <td>{{ $row->evento }}</td>
                                    <td>{{ $row->motorista }}</td>
                                    <td>{{ $row->hora_alarma }}</td>
                                    <td>{{ $row->velocidade }}</td>
                                    <td>{{ $row->latitude }}</td>
                                    <td>{{ $row->longitude }}</td>
                                    <td>{{ $row->event_id }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No se encontraron datos que no estén entre las fechas de Truck.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
