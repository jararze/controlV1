<x-app-layout>
    <x-slot name="navigation">
        logistica
    </x-slot>

    <!-- Toolbar -->
    <div class="pb-6">
        <!-- Container -->
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-900">
                    Detalles de Logística
                </h1>
            </div>
            <div class="flex items-center gap-1">
                <a class="btn btn-light btn-sm" href="{{ route('scoreCard.index') }}">
                    Volver a la Lista
                </a>
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
                        Información Detallada - Registro #{{ $record->id ?? 'N/A' }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-gray-200 p-5">
                            <h4 class="text-gray-900 font-medium mb-4">Información del Vehículo</h4>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Patente</span>
                                    <span class="text-base font-medium">{{ $record->patente ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Tipo de Vehículo</span>
                                    <span class="text-base font-medium">{{ $record->tipo_vehiculo ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Capacidad</span>
                                    <span class="text-base font-medium">{{ $record->capacidad ?? 'No disponible' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-5">
                            <h4 class="text-gray-900 font-medium mb-4">Detalles del Transporte</h4>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Origen</span>
                                    <span class="text-base font-medium">{{ $record->origen ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Destino</span>
                                    <span class="text-base font-medium">{{ $record->destino ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Ruta</span>
                                    <span class="text-base font-medium">{{ $record->ruta ?? 'No disponible' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-5">
                            <h4 class="text-gray-900 font-medium mb-4">Métricas</h4>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Kilometraje</span>
                                    <span class="text-base font-medium">{{ $record->km_recorridos ?? '0' }} km</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Consumo Combustible</span>
                                    <span class="text-base font-medium">{{ $record->consumo_combustible ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Tiempo de Viaje</span>
                                    <span class="text-base font-medium">{{ $record->tiempo_viaje ?? 'No disponible' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-5">
                            <h4 class="text-gray-900 font-medium mb-4">Información Temporal</h4>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Fecha</span>
                                    <span class="text-base font-medium">
                                        {{ isset($record->fecha) ? \Carbon\Carbon::parse($record->fecha)->format('d/m/Y') : 'No disponible' }}
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Hora Salida</span>
                                    <span class="text-base font-medium">{{ $record->hora_salida ?? 'No disponible' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500">Hora Llegada</span>
                                    <span class="text-base font-medium">{{ $record->hora_llegada ?? 'No disponible' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios/Observaciones (Si existen) -->
                    @if(isset($record->observaciones) && !empty($record->observaciones))
                        <div class="mt-6 p-5 bg-gray-50 rounded-xl">
                            <h4 class="text-gray-900 font-medium mb-2">Observaciones</h4>
                            <p class="text-gray-700">{{ $record->observaciones }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</x-app-layout>
