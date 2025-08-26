<x-app-layout>
    <x-slot name="navigation">
        navBar
    </x-slot>

    <!-- Toolbar -->
    <div class="pb-6">
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-900">
                    Gestión de Reportes de Flota
                </h1>
            </div>
            <div class="flex items-center gap-1">
                <button class="btn btn-light btn-sm" onclick="actualizarEstadisticas()">
                    <i class="ki-filled ki-arrows-circle"></i>
                    Actualizar
                </button>
            </div>
        </div>
    </div>
    <!-- End of Toolbar -->

    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">

            <!-- Estadísticas Cards -->
            <!-- Estadísticas Cards - 12 cajitas en una fila -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-12 gap-3 lg:gap-5 items-stretch">
                <!-- 1. Total Excesos -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-danger-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-triangle text-danger text-sm"></i>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 mb-1" id="total-excesos">
                            {{ $estadisticas['total_excesos'] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-600">Excesos</div>
                    </div>
                </div>

                <!-- 2. Total Límites -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-warning-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-warning-2 text-warning text-sm"></i>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 mb-1" id="total-limites">
                            {{ $estadisticas['total_limites'] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-600">Límites</div>
                    </div>
                </div>

                <!-- 3. Total Conducción Fuera Horario -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-purple-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-time text-purple-600 text-sm"></i>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 mb-1" id="total-conduccion">
                            {{ $estadisticas['total_conduccion_fuera_horario'] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-600">Fuera Horario</div>
                    </div>
                </div>

                <!-- 4. Total General -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-primary-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-chart-line text-primary text-sm"></i>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 mb-1" id="total-general">
                            {{ ($estadisticas['total_excesos'] ?? 0) + ($estadisticas['total_limites'] ?? 0) + ($estadisticas['total_conduccion_fuera_horario'] ?? 0) }}
                        </div>
                        <div class="text-xs text-gray-600">Total</div>
                    </div>
                </div>

                <!-- 5. Último Exceso -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-success-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-calendar text-success text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="ultimo-exceso">
                            @if($estadisticas['ultimo_exceso'])
                                {{ $estadisticas['ultimo_exceso']->FECHA_EXCESO->format('d/m') ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-xs text-gray-600">Últ. Exceso</div>
                    </div>
                </div>

                <!-- 6. Último Límite -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-info-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-calendar-2 text-info text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="ultimo-limite">
                            @if($estadisticas['ultimo_limite'])
                                {{ $estadisticas['ultimo_limite']->FECHA_ALERTA->format('d/m') ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-xs text-gray-600">Últ. Límite</div>
                    </div>
                </div>

                <!-- 7. Última Conducción -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-purple-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-clock text-purple-600 text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="ultima-conduccion">
                            @if($estadisticas['ultima_conduccion_fuera_horario'])
                                {{ $estadisticas['ultima_conduccion_fuera_horario']->fecha_registro->format('d/m') ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-xs text-gray-600">Últ. Fuera Hor.</div>
                    </div>
                </div>

                <!-- 8. Estado Token -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-secondary-clarity rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-security-user text-secondary text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="estado-token">
                            @if($estadisticas['token_actual'])
                                @if($estadisticas['token_actual']->estaExpirado())
                                    <span class="badge badge-xs badge-danger">Exp</span>
                                @else
                                    <span class="badge badge-xs badge-success">OK</span>
                                @endif
                            @else
                                <span class="badge badge-xs badge-warning">N/A</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-600">Token</div>
                    </div>
                </div>

                <!-- 9. Tiempo Restante Token -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-orange-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-hourglass text-orange-600 text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="tiempo-restante">
                            {{ $estadisticas['token_actual']->tiempo_restante ?? 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-600">Tiempo Rest.</div>
                    </div>
                </div>

                <!-- 10. Último Batch -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-indigo-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-code text-indigo-600 text-sm"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="ultimo-batch">
                            @if(isset($estadisticas['ultimos_batches']) && count($estadisticas['ultimos_batches']) > 0)
                                {{ Str::limit($estadisticas['ultimos_batches'][0]['batch_id'], 6) }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-xs text-gray-600">Últ. Batch</div>
                    </div>
                </div>

                <!-- 11. Registros Hoy -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-emerald-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-calendar-tick text-emerald-600 text-sm"></i>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 mb-1" id="registros-hoy">
                            0
                        </div>
                        <div class="text-xs text-gray-600">Hoy</div>
                    </div>
                </div>

                <!-- 12. Estado Sistema -->
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="flex items-center justify-center size-10 bg-green-100 rounded-full mb-2 mx-auto">
                            <i class="ki-filled ki-check-circle text-green-600 text-sm" id="icono-sistema"></i>
                        </div>
                        <div class="text-xs font-medium text-gray-900 mb-1" id="estado-sistema">
                            <span class="badge badge-xs badge-success">OK</span>
                        </div>
                        <div class="text-xs text-gray-600">Sistema</div>
                    </div>
                </div>
            </div>

            <!-- Últimos Batches -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Últimas Descargas (Batches)</h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" id="tabla-batches">
                            <thead>
                            <tr>
                                <th class="min-w-[150px]">Fecha/Hora</th>
                                <th class="min-w-[120px]">Batch ID</th>
                                <th class="min-w-[80px]">Excesos</th>
                                <th class="min-w-[80px]">Límites</th>
                                <th class="min-w-[80px]">Fuera Horario</th>
                                <th class="min-w-[80px]">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($estadisticas['ultimos_batches'] ?? [] as $batch)
                                <tr>
                                    <td class="text-sm">{{ $batch['fecha_registro']->format('d/m/Y H:i:s') }}</td>
                                    <td><span class="text-2sm text-gray-600">{{ Str::limit($batch['batch_id'], 8) }}</span></td>
                                    <td><span class="badge badge-danger badge-outline">{{ $batch['excesos'] }}</span></td>
                                    <td><span class="badge badge-warning badge-outline">{{ $batch['limites'] }}</span></td>
                                    <td><span class="badge badge-purple badge-outline">{{ $batch['conduccion_fuera_horario'] }}</span></td>
                                    <td><span class="text-sm font-semibold">{{ $batch['total'] }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Formulario para obtener reportes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Obtener Reportes</h3>
                </div>
                <div class="card-body">
                    <!-- Pestañas -->
                    <div class="flex border-b border-gray-200 mb-5">
                        <button class="tab-btn active px-4 py-2 text-sm font-medium border-b-2 border-primary text-primary" onclick="cambiarTab('automatico')">
                            Automático
                        </button>
                        <button class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" onclick="cambiarTab('manual')">
                            Manual
                        </button>
                    </div>

                    <!-- Tab Automático -->
                    <div id="tab-automatico" class="tab-content">
                        <form id="form-reportes" class="grid gap-5">
                            <div class="grid lg:grid-cols-4 gap-5">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-28">Fecha Inicio</label>
                                    <input type="date" class="input" id="fecha_inicio" name="fecha_inicio"
                                           value="{{ Carbon\Carbon::yesterday()->format('Y-m-d') }}" required>
                                </div>

                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-28">Fecha Fin</label>
                                    <input type="date" class="input" id="fecha_fin" name="fecha_fin"
                                           value="{{ Carbon\Carbon::yesterday()->format('Y-m-d') }}" required>
                                </div>

                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-28">Token (Opcional)</label>
                                    <input type="text" class="input" id="nuevo_token"
                                           placeholder="Solo si necesitas actualizar">
                                </div>

                                <div class="flex items-end gap-2">
                                    <button type="button" class="btn btn-primary" onclick="verificarYObtenerReportes()">
                                        <i class="ki-filled ki-cloud-download"></i>
                                        Obtener Reportes
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="verificarDuplicadosOnly()">
                                        <i class="ki-filled ki-search"></i>
                                        Verificar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tab Manual -->
                    <div id="tab-manual" class="tab-content hidden">
                        <div class="grid gap-5">
                            <!-- Paso 1: Generar URLs -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-medium text-blue-900 mb-3">Paso 1: Generar URLs</h4>
                                <div class="grid lg:grid-cols-4 gap-3 mb-3">
                                    <div>
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" class="input input-sm" id="fecha_inicio_manual">
                                    </div>
                                    <div>
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" class="input input-sm" id="fecha_fin_manual">
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="form-label">Token</label>
                                        <input type="text" class="input input-sm" id="token_manual" placeholder="Pega tu token aquí">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="generarUrlsManual()">
                                    <i class="ki-filled ki-code"></i>
                                    Generar URLs
                                </button>
                            </div>

                            <!-- URLs Generadas -->
                            <div id="urls-generadas" class="hidden">
                                <div class="grid lg:grid-cols-2 gap-4">
                                    <!-- URL Excesos -->
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <h5 class="font-medium text-red-900 mb-2">URL Excesos</h5>
                                        <div class="flex gap-2 mb-2">
                                            <input type="text" id="url-excesos" readonly class="input input-sm flex-1 bg-gray-50 text-xs">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="abrirUrl('url-excesos')">
                                                <i class="ki-filled ki-external-link"></i>
                                                Abrir
                                            </button>
                                        </div>
                                        <p class="text-xs text-red-700">Haz click en "Abrir", copia el JSON y pégalo abajo</p>
                                    </div>

                                    <!-- URL Límites -->
                                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                        <h5 class="font-medium text-orange-900 mb-2">URL Límites</h5>
                                        <div class="flex gap-2 mb-2">
                                            <input type="text" id="url-limites" readonly class="input input-sm flex-1 bg-gray-50 text-xs">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="abrirUrl('url-limites')">
                                                <i class="ki-filled ki-external-link"></i>
                                                Abrir
                                            </button>
                                        </div>
                                        <p class="text-xs text-orange-700">Haz click en "Abrir", copia el JSON y pégalo abajo</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 2: Procesar JSON -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <h4 class="font-medium text-green-900 mb-3">Paso 2: Procesar JSON</h4>
                                <div class="grid lg:grid-cols-2 gap-4">
                                    <!-- Excesos JSON -->
                                    <div>
                                        <label class="form-label text-red-600">JSON Excesos</label>
                                        <textarea id="json-excesos" rows="6" class="input input-sm mb-2" placeholder="Pega aquí el JSON de excesos..."></textarea>
                                        <button type="button" class="btn btn-sm btn-danger w-full" onclick="procesarJsonManual('excesos')">
                                            <i class="ki-filled ki-check"></i>
                                            Procesar Excesos
                                        </button>
                                        <div id="resultado-excesos" class="mt-2 text-sm"></div>
                                    </div>

                                    <!-- Límites JSON -->
                                    <div>
                                        <label class="form-label text-orange-600">JSON Límites</label>
                                        <textarea id="json-limites" rows="6" class="input input-sm mb-2" placeholder="Pega aquí el JSON de límites..."></textarea>
                                        <button type="button" class="btn btn-sm btn-warning w-full" onclick="procesarJsonManual('limites')">
                                            <i class="ki-filled ki-check"></i>
                                            Procesar Límites
                                        </button>
                                        <div id="resultado-limites" class="mt-2 text-sm"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resultado Final -->
                            <div id="resultado-manual-final" class="hidden bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                                <h4 class="font-medium text-emerald-900 mb-2">Procesamiento Completado</h4>
                                <div id="resumen-manual" class="text-emerald-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="debug-urls-automatico" class="card hidden">
                <div class="card-header">
                    <h3 class="card-title">URLs Generadas (Debug)</h3>
                    <button class="btn btn-sm btn-light" onclick="ocultarDebugUrls()">
                        <i class="ki-filled ki-eye-slash"></i>
                        Ocultar
                    </button>
                </div>
                <div class="card-body">
                    <div class="grid lg:grid-cols-2 gap-4">
                        <!-- URL Excesos -->
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h5 class="font-medium text-red-900 mb-2">URL Excesos Generada</h5>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="debug-url-excesos" readonly class="input input-sm flex-1 bg-gray-50 text-xs">
                                <button type="button" class="btn btn-sm btn-danger" onclick="copiarUrl('debug-url-excesos')">
                                    <i class="ki-filled ki-copy"></i>
                                    Copiar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline btn-danger" onclick="abrirUrl('debug-url-excesos')">
                                    <i class="ki-filled ki-external-link"></i>
                                    Abrir
                                </button>
                            </div>
                        </div>

                        <!-- URL Límites -->
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <h5 class="font-medium text-orange-900 mb-2">URL Límites Generada</h5>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="debug-url-limites" readonly class="input input-sm flex-1 bg-gray-50 text-xs">
                                <button type="button" class="btn btn-sm btn-warning" onclick="copiarUrl('debug-url-limites')">
                                    <i class="ki-filled ki-copy"></i>
                                    Copiar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline btn-warning" onclick="abrirUrl('debug-url-limites')">
                                    <i class="ki-filled ki-external-link"></i>
                                    Abrir
                                </button>
                            </div>
                        </div>

                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h5 class="font-medium text-purple-900 mb-2">URL Conducción Fuera Horario</h5>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="debug-url-conduccion" readonly class="input input-sm flex-1 bg-gray-50 text-xs">
                                <button type="button" class="btn btn-sm btn-purple" onclick="copiarUrl('debug-url-conduccion')">
                                    <i class="ki-filled ki-copy"></i>
                                    Copiar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline btn-purple" onclick="abrirUrl('debug-url-conduccion')">
                                    <i class="ki-filled ki-external-link"></i>
                                    Abrir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional de debug -->
                    <div id="debug-info" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                        <h6 class="font-medium text-blue-900 mb-2">Información de Debug</h6>
                        <div id="debug-detalles" class="text-sm text-blue-700"></div>
                    </div>
                </div>
            </div>

            <!-- Token Management -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestión de Token</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-5">
                        <div class="grid lg:grid-cols-3 gap-5">
                            <div class="lg:col-span-2">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-32">Nuevo Token</label>
                                    <textarea class="input" id="token_actualizar" rows="3"
                                              placeholder="Pegar aquí el nuevo token completo si el actual expiró"></textarea>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2.5">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label">Fecha Expiración</label>
                                    <input type="datetime-local" class="input" id="fecha_expiracion">
                                </div>
                                <div class="flex gap-2.5">
                                    <button type="button" class="btn btn-warning btn-sm" onclick="actualizarToken()">
                                        <i class="ki-filled ki-key"></i>
                                        Actualizar
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="validarTokenActual()">
                                        <i class="ki-filled ki-shield-tick"></i>
                                        Validar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log de actividad -->
            <div class="card">
                <div class="card-header flex justify-between">
                    <h3 class="card-title">Log de Actividad</h3>
                    <button class="btn btn-sm btn-light" onclick="limpiarLog()">
                        <i class="ki-filled ki-eraser"></i>
                        Limpiar
                    </button>
                </div>
                <div class="card-body">
                    <div id="log-actividad" class="bg-gray-50 border border-gray-200 rounded p-4 h-64 overflow-y-auto font-mono text-sm">
                        <div class="text-gray-500">Sistema listo - Esperando actividad...</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End of Container -->

    <!-- Loading Modal ARREGLADO -->
    <div id="loadingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; text-align: center; max-width: 400px; width: 90%;">
            <div style="margin-bottom: 1rem;">
                <!-- Spinner CSS simple -->
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            </div>
            <div style="font-weight: 600; margin-bottom: 0.5rem; color: #333;" id="loading-text">
                Obteniendo reportes, por favor espere...
            </div>
            <div style="color: #666; font-size: 0.9rem;">
                Este proceso puede tomar varios segundos
            </div>
        </div>
    </div>

    <!-- Modal de Duplicados NUEVO -->
    <div id="duplicadosModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%;">
            <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                <div style="width: 40px; height: 40px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                    <span style="color: #d97706; font-size: 1.2rem;">⚠️</span>
                </div>
                <h3 style="margin: 0; color: #333; font-size: 1.2rem;">Registros Duplicados Detectados</h3>
            </div>

            <div id="duplicados-mensaje" style="margin-bottom: 1.5rem; color: #555; line-height: 1.5;"></div>

            <div id="duplicados-detalles" style="background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;"></div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalDuplicados()" style="padding: 0.5rem 1rem; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" onclick="forzarDescarga()" style="padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Descargar de Todas Formas
                </button>
            </div>
        </div>
    </div>

    <script>
        let datosFormulario = {};

        document.addEventListener('DOMContentLoaded', function() {
            // Configurar fechas por defecto (ayer)
            const ayer = new Date();
            ayer.setDate(ayer.getDate() - 1);
            const fechaAyer = ayer.toISOString().split('T')[0];

            document.getElementById('fecha_inicio').value = fechaAyer;
            document.getElementById('fecha_fin').value = fechaAyer;

            agregarLog('Sistema iniciado correctamente');
            agregarLog('Fechas configuradas para: ' + fechaAyer);

            document.getElementById('token_manual').addEventListener('paste', function(e) {
                setTimeout(() => {
                    let valor = this.value;
                    const valorLimpio = valor.trim()
                        .replace(/\s+/g, '')
                        .replace(/%3D/g, '=')
                        .replace(/\n/g, '')
                        .replace(/\r/g, '');

                    if (valor !== valorLimpio) {
                        this.value = valorLimpio;
                        agregarLog('Token limpiado automáticamente al pegar');
                    }
                }, 100);
            });
        });

        // NUEVA FUNCIÓN: Verificar duplicados antes de descargar
        function verificarYObtenerReportes() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const nuevoToken = document.getElementById('nuevo_token').value;

            if (!fechaInicio || !fechaFin) {
                mostrarAlerta('Por favor selecciona las fechas', 'warning');
                return;
            }

            // Guardar datos del formulario para uso posterior
            datosFormulario = {
                fecha_inicio: fechaInicio.trim(),
                fecha_fin: fechaFin.trim(),
                token: nuevoToken ? nuevoToken.trim() : ''
            };

            console.log('Datos guardados para posible descarga forzada:', datosFormulario);
            agregarLog('Verificando duplicados...');

            fetch('{{ route("reportes.verificar-duplicados") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.tiene_duplicados) {
                        // Mostrar modal de duplicados
                        mostrarModalDuplicados(data.mensaje, data.detalles);
                    } else {
                        // No hay duplicados, proceder con descarga normal
                        ejecutarDescarga(false);
                    }
                })
                .catch(error => {
                    agregarLog('Error verificando duplicados, procediendo con descarga...');
                    ejecutarDescarga(false);
                });
        }

        // NUEVA FUNCIÓN: Solo verificar duplicados sin descargar
        function verificarDuplicadosOnly() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                mostrarAlerta('Por favor selecciona las fechas', 'warning');
                return;
            }

            agregarLog('Verificando duplicados...');

            fetch('{{ route("reportes.verificar-duplicados") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.tiene_duplicados) {
                            mostrarAlerta(`Duplicados encontrados: ${data.mensaje}`, 'warning');
                            agregarLog(`⚠️ ${data.mensaje}`);
                        } else {
                            mostrarAlerta('No se encontraron duplicados. Puedes proceder con la descarga.', 'success');
                            agregarLog('✅ No hay duplicados para estas fechas');
                        }
                    }
                })
                .catch(error => {
                    mostrarAlerta('Error verificando duplicados', 'danger');
                    agregarLog('❌ Error verificando duplicados');
                });
        }

        // NUEVA FUNCIÓN: Mostrar modal de duplicados
        function mostrarModalDuplicados(mensaje, detalles) {
            document.getElementById('duplicados-mensaje').innerHTML = mensaje;

            let detallesHtml = `
                <strong>Detalles de registros existentes:</strong><br>
                • Excesos: ${detalles.excesos}<br>
                • Límites: ${detalles.limites}<br>
                • Total: ${detalles.total_registros}<br>
                • Rango: ${detalles.fecha_inicio} - ${detalles.fecha_fin}
            `;

            if (detalles.batches_existentes && detalles.batches_existentes.length > 0) {
                detallesHtml += '<br><br><strong>Batches existentes:</strong><br>';
                detalles.batches_existentes.forEach(batch => {
                    detallesHtml += `• ${batch.batch_id} - ${batch.fecha_registro}<br>`;
                });
            }

            document.getElementById('duplicados-detalles').innerHTML = detallesHtml;
            document.getElementById('duplicadosModal').style.display = 'block';

            agregarLog('⚠️ Duplicados detectados - mostrando opciones al usuario');
        }

        // NUEVA FUNCIÓN: Cerrar modal de duplicados
        function cerrarModalDuplicados() {
            document.getElementById('duplicadosModal').style.display = 'none';
            agregarLog('Modal de duplicados cerrado');
        }

        // NUEVA FUNCIÓN: Forzar descarga ignorando duplicados
        function forzarDescarga() {
            cerrarModalDuplicados();
            agregarLog('Usuario eligió forzar descarga...');

            // CORRECCIÓN: Validar que tenemos los datos necesarios
            if (!datosFormulario || !datosFormulario.fecha_inicio || !datosFormulario.fecha_fin) {
                console.error('No hay datos del formulario guardados para forzar descarga');
                agregarLog('Error: No hay datos guardados para forzar descarga');

                // Intentar obtener datos del formulario actual
                const fechaInicio = document.getElementById('fecha_inicio').value;
                const fechaFin = document.getElementById('fecha_fin').value;
                const nuevoToken = document.getElementById('nuevo_token').value;

                if (!fechaInicio || !fechaFin) {
                    mostrarAlerta('Error: No se pueden recuperar las fechas del formulario', 'danger');
                    return;
                }

                // Recrear datosFormulario
                datosFormulario = {
                    fecha_inicio: fechaInicio.trim(),
                    fecha_fin: fechaFin.trim(),
                    token: nuevoToken ? nuevoToken.trim() : ''
                };

                agregarLog('Datos del formulario recuperados del DOM');
            }

            console.log('Ejecutando descarga forzada con datos:', datosFormulario);
            ejecutarDescarga(true);
        }

        // FUNCIÓN MEJORADA: Ejecutar descarga con opción de forzar
        function ejecutarDescarga(forzar = false) {
            // CORRECCIÓN: Obtener datos de manera más robusta
            let fechaInicio, fechaFin, nuevoToken;

            if (forzar && datosFormulario && datosFormulario.fecha_inicio) {
                // Para descarga forzada, usar datos guardados
                fechaInicio = datosFormulario.fecha_inicio;
                fechaFin = datosFormulario.fecha_fin;
                nuevoToken = datosFormulario.token;
                console.log('Usando datos guardados para descarga forzada:', datosFormulario);
            } else {
                // Para descarga normal, obtener del DOM
                fechaInicio = document.getElementById('fecha_inicio').value;
                fechaFin = document.getElementById('fecha_fin').value;
                nuevoToken = document.getElementById('nuevo_token').value;
                console.log('Usando datos del DOM para descarga normal');
            }

            // DEBUG detallado
            console.log('=== DEBUG EJECUTAR DESCARGA ===');
            console.log('forzar:', forzar);
            console.log('fechaInicio:', fechaInicio, typeof fechaInicio);
            console.log('fechaFin:', fechaFin, typeof fechaFin);
            console.log('nuevoToken:', nuevoToken ? '[PRESENTE]' : '[VACÍO]');

            // Validación
            if (!fechaInicio || !fechaFin) {
                console.error('Fechas faltantes');
                mostrarAlerta('Error: Fechas requeridas', 'danger');
                return;
            }

            // Limpiar datos (eliminar espacios)
            fechaInicio = fechaInicio.trim();
            fechaFin = fechaFin.trim();
            nuevoToken = nuevoToken ? nuevoToken.trim() : '';

            // Validar formato de fecha
            if (!fechaInicio.match(/^\d{4}-\d{2}-\d{2}$/)) {
                console.error('Formato de fecha inicio inválido:', fechaInicio);
                mostrarAlerta('Formato de fecha inicio inválido', 'danger');
                return;
            }

            if (!fechaFin.match(/^\d{4}-\d{2}-\d{2}$/)) {
                console.error('Formato de fecha fin inválido:', fechaFin);
                mostrarAlerta('Formato de fecha fin inválido', 'danger');
                return;
            }

            // Mostrar modal de carga
            mostrarModalCarga();

            const textoModo = forzar ? 'forzada (ignorando duplicados)' : 'normal';
            document.getElementById('loading-text').textContent = `Iniciando descarga ${textoModo}...`;
            agregarLog(`Iniciando descarga ${textoModo}...`);
            agregarLog(`Rango: ${fechaInicio} a ${fechaFin}`);

            if (nuevoToken) {
                agregarLog('Usando nuevo token proporcionado');
            }

            // CORRECCIÓN: Construir parámetros de manera más explícita
            const parametros = new URLSearchParams();
            parametros.append('fecha_inicio', fechaInicio);
            parametros.append('fecha_fin', fechaFin);
            parametros.append('token', nuevoToken);

            // IMPORTANTE: Para forzar, enviar como string, no boolean
            if (forzar) {
                parametros.append('forzar', '1');
            } else {
                parametros.append('forzar', '0');
            }

            console.log('Parámetros finales:', parametros.toString());

            fetch('{{ route("reportes.obtener") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: parametros
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', [...response.headers.entries()]);
                    return response.text();
                })
                .then(responseText => {
                    console.log('Response text:', responseText);

                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (jsonError) {
                        console.error('Error parseando JSON:', jsonError);
                        console.log('Response que causó error:', responseText);
                        throw new Error('Respuesta del servidor no es JSON válido');
                    }

                    ocultarModalCarga();

                    if (data.success) {
                        const modoTexto = data.forzada ? ' (FORZADA)' : '';
                        mostrarAlerta(data.message + modoTexto, 'success');
                        agregarLog(`✅ ${data.message}`);
                        agregarLog(`📊 Excesos: ${data.data.excesos} registros`);
                        agregarLog(`📊 Límites: ${data.data.limites} registros`);
                        agregarLog(`📊 Fuera de horario: ${data.data.conduccion_fuera_horario} registros`);
                        agregarLog(`📖 Batch ID: ${data.data.batch_id.substring(0, 8)}...`);

                        if (data.data.debug_urls) {
                            mostrarDebugUrls(data.data.debug_urls, fechaInicio, fechaFin);
                            agregarLog('🔍 URLs de debug mostradas');
                        }

                        if (data.forzada) {
                            agregarLog('⚠️ DESCARGA FORZADA - Se ignoraron duplicados');
                        }

                        actualizarEstadisticas();
                        document.getElementById('nuevo_token').value = '';

                        // Limpiar datos guardados después de uso exitoso
                        datosFormulario = {};

                        setTimeout(() => {
                            mostrarResumenDescarga(data.data);
                        }, 1000);
                    } else if (data.duplicados_detectados) {
                        mostrarModalDuplicados(data.message, data.detalles_duplicados);
                    } else {
                        mostrarAlerta(data.message, 'danger');
                        agregarLog(`❌ Error: ${data.message}`);

                        // Mostrar errores de validación si existen
                        if (data.errors) {
                            console.error('Errores de validación:', data.errors);
                            Object.keys(data.errors).forEach(field => {
                                agregarLog(`❌ ${field}: ${data.errors[field].join(', ')}`);
                            });
                        }

                        if (data.debug_data) {
                            console.log('Debug data del servidor:', data.debug_data);
                        }
                    }
                })
                .catch(error => {
                    ocultarModalCarga();
                    console.error('Error en fetch:', error);
                    const mensaje = error.message || 'Error desconocido';
                    mostrarAlerta(`Error: ${mensaje}`, 'danger');
                    agregarLog(`❌ Error: ${mensaje}`);
                });
        }

        function mostrarDebugUrls(urls, fechaInicio, fechaFin) {
            document.getElementById('debug-url-excesos').value = urls.excesos;
            document.getElementById('debug-url-limites').value = urls.limites;

            if (urls.conduccion_fuera_horario && document.getElementById('debug-url-conduccion')) {
                document.getElementById('debug-url-conduccion').value = urls.conduccion_fuera_horario;
            }

            const fechaInicioObj = new Date(fechaInicio);
            const fechaFinObj = new Date(fechaFin);

            document.getElementById('debug-detalles').innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <strong>Fechas solicitadas:</strong><br>
                        • Inicio: ${fechaInicio} (${fechaInicioObj.toLocaleDateString('es-ES')})<br>
                        • Fin: ${fechaFin} (${fechaFinObj.toLocaleDateString('es-ES')})
                    </div>
                    <div>
                        <strong>URLs generadas:</strong><br>
                        • Excesos: ${urls.excesos.length > 100 ? urls.excesos.substring(0, 100) + '...' : urls.excesos}<br>
                        • Límites: ${urls.limites.length > 100 ? urls.limites.substring(0, 100) + '...' : urls.limites}
                    </div>
                </div>
            `;

            document.getElementById('debug-urls-automatico').classList.remove('hidden');

            // Auto-scroll hacia el debug
            setTimeout(() => {
                document.getElementById('debug-urls-automatico').scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, 500);
        }

        function ocultarDebugUrls() {
            document.getElementById('debug-urls-automatico').classList.add('hidden');
        }

        function copiarUrl(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            document.execCommand('copy');

            const tipo = inputId.includes('excesos') ? 'excesos' : 'límites';
            mostrarAlerta(`URL de ${tipo} copiada al portapapeles`, 'success');
            agregarLog(`📋 URL de ${tipo} copiada`);
        }

        // FUNCIONES AUXILIARES PARA EL MODAL ARREGLADO
        function mostrarModalCarga() {
            document.getElementById('loadingModal').style.display = 'block';
        }

        function ocultarModalCarga() {
            document.getElementById('loadingModal').style.display = 'none';
        }

        function actualizarToken() {
            const nuevoToken = document.getElementById('token_actualizar').value;
            const fechaExpiracion = document.getElementById('fecha_expiracion').value;

            if (!nuevoToken.trim()) {
                mostrarAlerta('Por favor ingresa un token', 'warning');
                return;
            }

            agregarLog('Validando nuevo token...');

            fetch('{{ route("reportes.actualizar-token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    token: nuevoToken,
                    fecha_expiracion: fechaExpiracion || ''
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAlerta(data.message, 'success');
                        agregarLog(`🔑 Token actualizado correctamente`);
                        if (fechaExpiracion) {
                            agregarLog(`⏰ Expira: ${new Date(fechaExpiracion).toLocaleString()}`);
                        }
                        actualizarEstadisticas();
                        document.getElementById('token_actualizar').value = '';
                        document.getElementById('fecha_expiracion').value = '';
                    } else {
                        mostrarAlerta(data.message, 'danger');
                        agregarLog(`❌ ${data.message}`);
                    }
                })
                .catch(error => {
                    const mensaje = error.message || 'Error al actualizar token';
                    mostrarAlerta(`Error: ${mensaje}`, 'danger');
                    agregarLog(`❌ Error: ${mensaje}`);
                });
        }

        function validarTokenActual() {
            agregarLog('Validando token actual...');

            fetch('{{ route("reportes.validar-token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    token: ''
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.valido) {
                        mostrarAlerta('Token actual es válido', 'success');
                        agregarLog(`✅ Token actual es válido`);
                    } else {
                        mostrarAlerta('Token actual inválido o expirado', 'warning');
                        agregarLog(`⚠️ Token actual inválido o expirado`);
                    }
                })
                .catch(error => {
                    mostrarAlerta('Error al validar token', 'danger');
                    agregarLog(`❌ Error al validar token`);
                });
        }

        function actualizarEstadisticas() {
            fetch('{{ route("reportes.ultimo") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const estadisticas = data.data;

                        // 1-4: Contadores principales
                        const totalExcesos = estadisticas.total_excesos || 0;
                        const totalLimites = estadisticas.total_limites || 0;
                        const totalConduccion = estadisticas.total_conduccion_fuera_horario || 0;
                        const totalGeneral = totalExcesos + totalLimites + totalConduccion;

                        document.getElementById('total-excesos').textContent = totalExcesos;
                        document.getElementById('total-limites').textContent = totalLimites;
                        document.getElementById('total-conduccion').textContent = totalConduccion;
                        document.getElementById('total-general').textContent = totalGeneral;

                        // 5-7: Últimas fechas (formato corto d/m)
                        document.getElementById('ultimo-exceso').textContent =
                            estadisticas.ultimo_exceso?.FECHA_EXCESO ?
                                new Date(estadisticas.ultimo_exceso.FECHA_EXCESO).toLocaleDateString('es-ES', {day: '2-digit', month: '2-digit'}) : 'N/A';

                        document.getElementById('ultimo-limite').textContent =
                            estadisticas.ultimo_limite?.FECHA_ALERTA ?
                                new Date(estadisticas.ultimo_limite.FECHA_ALERTA).toLocaleDateString('es-ES', {day: '2-digit', month: '2-digit'}) : 'N/A';

                        document.getElementById('ultima-conduccion').textContent =
                            estadisticas.ultima_conduccion_fuera_horario?.fecha_registro ?
                                new Date(estadisticas.ultima_conduccion_fuera_horario.fecha_registro).toLocaleDateString('es-ES', {day: '2-digit', month: '2-digit'}) : 'N/A';

                        // 8-9: Estado del token
                        const estadoTokenEl = document.getElementById('estado-token');
                        const tiempoRestanteEl = document.getElementById('tiempo-restante');

                        const token = estadisticas.token_actual;
                        if (token) {
                            if (token.esta_expirado) {
                                estadoTokenEl.innerHTML = '<span class="badge badge-xs badge-danger">Exp</span>';
                            } else {
                                estadoTokenEl.innerHTML = '<span class="badge badge-xs badge-success">OK</span>';
                            }
                            tiempoRestanteEl.textContent = token.tiempo_restante || 'N/A';
                        } else {
                            estadoTokenEl.innerHTML = '<span class="badge badge-xs badge-warning">N/A</span>';
                            tiempoRestanteEl.textContent = 'N/A';
                        }

                        // 10: Último batch
                        const ultimoBatchEl = document.getElementById('ultimo-batch');
                        if (estadisticas.ultimos_batches && estadisticas.ultimos_batches.length > 0) {
                            const batchId = estadisticas.ultimos_batches[0].batch_id;
                            ultimoBatchEl.textContent = batchId.substring(0, 6) + '..';
                        } else {
                            ultimoBatchEl.textContent = 'N/A';
                        }

                        // 11: Registros de hoy
                        calcularRegistrosHoy();

                        // 12: Estado del sistema
                        actualizarEstadoSistema(token);

                        // Actualizar tabla de batches
                        actualizarTablaBatches(estadisticas.ultimos_batches);

                        agregarLog('📊 Estadísticas actualizadas');
                    }
                })
                .catch(error => {
                    agregarLog('❌ Error al actualizar estadísticas');
                    // Marcar sistema como error
                    document.getElementById('estado-sistema').innerHTML = '<span class="badge badge-xs badge-danger">Error</span>';
                    document.getElementById('icono-sistema').className = 'ki-filled ki-cross-circle text-red-600 text-sm';
                });
        }

        function actualizarTablaBatches(batches) {
            const tbody = document.querySelector('#tabla-batches tbody');
            tbody.innerHTML = '';

            if (batches && batches.length > 0) {
                batches.forEach(batch => {
                    const fecha = new Date(batch.fecha_registro).toLocaleString('es-ES');
                    const batchId = batch.batch_id.substring(0, 8) + '...';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                    <td class="text-sm">${fecha}</td>
                    <td><span class="text-2sm text-gray-600">${batchId}</span></td>
                    <td><span class="badge badge-danger badge-outline">${batch.excesos}</span></td>
                    <td><span class="badge badge-warning badge-outline">${batch.limites}</span></td>
                    <td><span class="badge badge-purple badge-outline">${batch.conduccion_fuera_horario}</span></td>
                    <td><span class="text-sm font-semibold">${batch.total}</span></td>
                `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-500">No hay batches recientes</td></tr>';
            }
        }

        function calcularRegistrosHoy() {
            const hoy = new Date().toISOString().split('T')[0];

            // Esto requeriría un endpoint específico, por ahora usar placeholder
            fetch('{{ route("reportes.contar-hoy") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({fecha: hoy})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('registros-hoy').textContent = data.total || 0;
                    }
                })
                .catch(error => {
                    document.getElementById('registros-hoy').textContent = '0';
                });
        }

        // NUEVA FUNCIÓN: Actualizar estado del sistema
        function actualizarEstadoSistema(token) {
            const estadoEl = document.getElementById('estado-sistema');
            const iconoEl = document.getElementById('icono-sistema');

            let estado = 'OK';
            let clase = 'success';
            let icono = 'ki-check-circle text-green-600';

            // Verificar estado del token
            if (!token) {
                estado = 'Sin Token';
                clase = 'warning';
                icono = 'ki-warning-2 text-yellow-600';
            } else if (token.esta_expirado) {
                estado = 'Token Exp';
                clase = 'danger';
                icono = 'ki-cross-circle text-red-600';
            }

            // Verificar conectividad (basado en última actualización exitosa)
            const ahora = new Date();
            const ultimaActualizacion = localStorage.getItem('ultima_actualizacion');
            if (ultimaActualizacion) {
                const diffMinutos = (ahora - new Date(ultimaActualizacion)) / (1000 * 60);
                if (diffMinutos > 5) { // Más de 5 minutos sin actualizar
                    estado = 'Desconect';
                    clase = 'danger';
                    icono = 'ki-cross-circle text-red-600';
                }
            }

            estadoEl.innerHTML = `<span class="badge badge-xs badge-${clase}">${estado}</span>`;
            iconoEl.className = `ki-filled ${icono} text-sm`;

            // Guardar timestamp de esta actualización
            localStorage.setItem('ultima_actualizacion', ahora.toISOString());
        }

        function mostrarResumenDescarga(data) {
            const total = data.excesos + data.limites + (data.conduccion_fuera_horario || 0);
            if (total > 0) {
                agregarLog('📈 RESUMEN DE DESCARGA:');
                agregarLog(`   └── Excesos: ${data.excesos} registros`);
                agregarLog(`   └── Límites: ${data.limites} registros`);
                agregarLog(`   └── Fuera de horario: ${data.conduccion_fuera_horario || 0} registros`);
                agregarLog(`   └── Total: ${total} registros`);
                agregarLog(`   └── Batch: ${data.batch_id.substring(0, 8)}...`);
            }
        }

        function mostrarAlerta(mensaje, tipo) {
            // Crear toast notification simple
            const alertaDiv = document.createElement('div');
            const tipoColor = {
                'success': '#10b981',
                'danger': '#ef4444',
                'warning': '#f59e0b',
                'info': '#3b82f6'
            };

            alertaDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-left: 4px solid ${tipoColor[tipo] || '#6b7280'};
                padding: 1rem;
                border-radius: 4px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                max-width: 400px;
                word-wrap: break-word;
            `;

            alertaDiv.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">${tipo === 'success' ? 'Éxito' : 'Aviso'}</div>
                        <div style="color: #666; font-size: 0.9rem;">${mensaje}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #999;">×</button>
                </div>
            `;

            document.body.appendChild(alertaDiv);

            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (alertaDiv.parentNode) {
                    alertaDiv.remove();
                }
            }, 5000);
        }

        function agregarLog(mensaje) {
            const tiempo = new Date().toLocaleTimeString();
            const logElement = document.getElementById('log-actividad');
            const nuevoLog = document.createElement('div');
            nuevoLog.className = 'mb-1';
            nuevoLog.innerHTML = `<span class="text-gray-500">[${tiempo}]</span> ${mensaje}`;

            logElement.insertBefore(nuevoLog, logElement.firstChild);

            // Mantener solo los últimos 50 mensajes
            const logs = logElement.querySelectorAll('div');
            if (logs.length > 50) {
                logs[logs.length - 1].remove();
            }
        }

        function limpiarLog() {
            document.getElementById('log-actividad').innerHTML = '<div class="text-gray-500">Log limpiado - Esperando actividad...</div>';
            agregarLog('Log de actividad limpiado');
        }

        // ============= FUNCIONES PARA MODO MANUAL =============

        function cambiarTab(tab) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Mostrar tab seleccionado
            document.getElementById(`tab-${tab}`).classList.remove('hidden');

            // Actualizar estilos de botones
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            event.target.classList.add('active', 'border-primary', 'text-primary');
            event.target.classList.remove('border-transparent', 'text-gray-500');

            // Configurar fechas por defecto si es el primer uso
            if (tab === 'manual') {
                const ayer = new Date();
                ayer.setDate(ayer.getDate() - 1);
                const fechaAyer = ayer.toISOString().split('T')[0];

                if (!document.getElementById('fecha_inicio_manual').value) {
                    document.getElementById('fecha_inicio_manual').value = fechaAyer;
                    document.getElementById('fecha_fin_manual').value = fechaAyer;
                }
            }
        }

        function generarUrlsManual() {
            const url = debugGenerarUrls();
            if (url) {
                document.getElementById('url-excesos').value = url;
                document.getElementById('url-limites').value = url.replace('Excesos', 'Limites');
                document.getElementById('urls-generadas').classList.remove('hidden');
                agregarLog('URLs generadas - revisar consola para debug');
            } else {
                agregarLog('Error generando URLs - revisar consola');
            }
        }

        function construirParametrosManual(fechaInicio, fechaFin, token) {
            const tokenLimpio = token.trim()
                .replace(/\s+/g, '')
                .replace(/%3D/g, '=')
                .replace(/\n/g, '')
                .replace(/\r/g, '');

            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);

            // CORREGIDO: Usar el mismo formato que el servicio
            const añoInicio = inicio.getFullYear().toString().slice(-2); // Últimos 2 dígitos del año
            const mesInicio = (inicio.getMonth() + 1).toString().padStart(2, '0'); // Mes con 2 dígitos
            const mesi = añoInicio + mesInicio;

            const añoFin = fin.getFullYear().toString().slice(-2);
            const mesFin = (fin.getMonth() + 1).toString().padStart(2, '0');
            const mesf = añoFin + mesFin;

            console.log(`DEBUG MANUAL: ${fechaInicio} -> mesi=${mesi} (año: ${añoInicio}, mes: ${mesInicio})`);
            console.log(`DEBUG MANUAL: ${fechaFin} -> mesf=${mesf} (año: ${añoFin}, mes: ${mesFin})`);

            // NO usar URLSearchParams - construir manualmente
            const params = `E=${tokenLimpio}&T=0&IMEI=TODOS&mesi=${mesi}&diai=${inicio.getDate().toString().padStart(2, '0')}&horai=00&mini=00&mesf=${mesf}&diaf=${fin.getDate().toString().padStart(2, '0')}&horaf=23&minf=59&grupo=`;

            return params;
        }

        function abrirUrl(inputId) {
            const url = document.getElementById(inputId).value;
            if (url) {
                window.open(url, '_blank');
                agregarLog(`Abriendo URL: ${inputId.replace('url-', '')}`);
            }
        }

        function procesarJsonManual(tipo) {
            debugProcesarJson();
            const jsonText = document.getElementById(`json-${tipo}`).value.trim();
            const resultadoDiv = document.getElementById(`resultado-${tipo}`);

            if (!jsonText) {
                resultadoDiv.innerHTML = '<span class="text-red-600">Pega el JSON primero</span>';
                return;
            }

            try {
                const data = JSON.parse(jsonText);

                if (!data.data) {
                    throw new Error('El JSON no tiene el campo "data"');
                }

                agregarLog(`Procesando JSON ${tipo}...`);

                // Enviar al servidor
                fetch('{{ route("reportes.procesar-manual") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        data: data.data,
                        fecha_inicio: document.getElementById('fecha_inicio_manual').value,
                        fecha_fin: document.getElementById('fecha_fin_manual').value
                    })
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            resultadoDiv.innerHTML = `<span class="text-green-600">✅ ${result.registros} registros procesados</span>`;
                            agregarLog(`✅ ${tipo}: ${result.registros} registros guardados`);

                            // Mostrar resultado final si ambos están procesados
                            mostrarResultadoManualFinal();

                            // Actualizar estadísticas
                            actualizarEstadisticas();
                        } else {
                            resultadoDiv.innerHTML = `<span class="text-red-600">❌ Error: ${result.message}</span>`;
                            agregarLog(`❌ Error procesando ${tipo}: ${result.message}`);
                        }
                    })
                    .catch(error => {
                        resultadoDiv.innerHTML = `<span class="text-red-600">❌ Error de conexión</span>`;
                        agregarLog(`❌ Error de conexión procesando ${tipo}`);
                    });

            } catch (e) {
                resultadoDiv.innerHTML = '<span class="text-red-600">❌ JSON inválido</span>';
                agregarLog(`❌ JSON inválido para ${tipo}`);
            }
        }

        function mostrarResultadoManualFinal() {
            const resultadoExcesos = document.getElementById('resultado-excesos').textContent;
            const resultadoLimites = document.getElementById('resultado-limites').textContent;

            if (resultadoExcesos.includes('registros procesados') || resultadoLimites.includes('registros procesados')) {
                const finalDiv = document.getElementById('resultado-manual-final');
                const resumenDiv = document.getElementById('resumen-manual');

                finalDiv.classList.remove('hidden');
                resumenDiv.innerHTML = `
                    <p>Procesamiento manual completado:</p>
                    <ul class="list-disc list-inside mt-2">
                        <li>Excesos: ${resultadoExcesos}</li>
                        <li>Límites: ${resultadoLimites}</li>
                    </ul>
                `;
            }
        }

        // Actualizar estadísticas cada 30 segundos
        setInterval(actualizarEstadisticas, 30000);

        // Advertencia de token próximo a expirar (cada 5 minutos)
        setInterval(function() {
            const tiempoRestante = document.getElementById('tiempo-restante').textContent;

            if (tiempoRestante.includes('hora') && !tiempoRestante.includes('horas')) {
                if (!sessionStorage.getItem('token_warning_shown')) {
                    mostrarAlerta('⚠️ El token expira en menos de 1 hora', 'warning');
                    agregarLog('⚠️ ADVERTENCIA: Token expira pronto');
                    sessionStorage.setItem('token_warning_shown', 'true');
                }
            }

            const estadoToken = document.getElementById('estado-token').textContent;
            if (estadoToken.includes('Expirado')) {
                agregarLog('🔴 TOKEN EXPIRADO - Actualiza el token');
            }
        }, 300000); // 5 minutos

        function debugGenerarUrls() {
            const fechaInicio = document.getElementById('fecha_inicio_manual').value;
            const fechaFin = document.getElementById('fecha_fin_manual').value;
            const token = document.getElementById('token_manual').value;

            console.log('=== DEBUG GENERAR URLS MANUAL ===');
            console.log('Fecha inicio:', fechaInicio);
            console.log('Fecha fin:', fechaFin);
            console.log('Token original:', token);
            console.log('Token length:', token.length);

            const tokenLimpio = token.trim()
                .replace(/\s+/g, '')
                .replace(/%3D/g, '=')
                .replace(/\n/g, '')
                .replace(/\r/g, '');

            console.log('Token limpio:', tokenLimpio);
            console.log('¿Token cambió?', token !== tokenLimpio);

            // Usar la función corregida
            try {
                const params = construirParametrosManual(fechaInicio, fechaFin, tokenLimpio);
                console.log('Parámetros construidos:', params);

                const baseUrl = 'https://gestiondeflota.boltrack.net/reportes/';
                const urlCompleta = baseUrl + 'RP131BodyExcesos.rep?' + params;
                console.log('URL completa:', urlCompleta);

                agregarLog(`🔧 URLs manuales generadas para ${fechaInicio} - ${fechaFin}`);

                return urlCompleta;
            } catch (error) {
                console.error('Error construyendo URL:', error);
                agregarLog(`❌ Error generando URLs: ${error.message}`);
                return null;
            }
        }

        // Función para probar el endpoint manualmente
        function debugProcesarJson() {
            const tipo = 'excesos'; // Cambiar a 'limites' para probar límites
            const jsonText = document.getElementById(`json-${tipo}`).value.trim();

            console.log('=== DEBUG PROCESAR JSON ===');
            console.log('Tipo:', tipo);
            console.log('JSON text length:', jsonText.length);
            console.log('JSON primeros 200 chars:', jsonText.substring(0, 200));

            if (!jsonText) {
                console.error('No hay JSON');
                return;
            }

            try {
                const data = JSON.parse(jsonText);
                console.log('JSON parseado correctamente');
                console.log('Tiene campo data:', !!data.data);
                console.log('Contenido data:', data.data ? data.data.substring(0, 200) : 'No data');

                // Probar el fetch manualmente
                const payload = {
                    tipo: tipo,
                    data: data.data,
                    fecha_inicio: document.getElementById('fecha_inicio_manual').value,
                    fecha_fin: document.getElementById('fecha_fin_manual').value
                };

                console.log('Payload a enviar:', payload);

                fetch('{{ route("reportes.procesar-manual") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        return response.text(); // Cambiar a text() para ver qué devuelve
                    })
                    .then(text => {
                        console.log('Response text:', text);
                        try {
                            const json = JSON.parse(text);
                            console.log('Response JSON:', json);
                        } catch (e) {
                            console.error('Response no es JSON válido:', e);
                        }
                    })
                    .catch(error => {
                        console.error('Error en fetch:', error);
                    });

            } catch (e) {
                console.error('Error parseando JSON:', e);
            }
        }
    </script>
</x-app-layout>
