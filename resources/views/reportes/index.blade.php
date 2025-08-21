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
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 lg:gap-7.5 items-stretch">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-danger-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-triangle text-danger text-base"></i>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 mb-1" id="total-excesos">
                            {{ $estadisticas['total_excesos'] ?? 0 }}
                        </div>
                        <div class="text-2sm text-gray-600">Total Excesos</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-warning-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-warning-2 text-warning text-base"></i>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 mb-1" id="total-limites">
                            {{ $estadisticas['total_limites'] ?? 0 }}
                        </div>
                        <div class="text-2sm text-gray-600">Total Límites</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-success-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-calendar text-success text-base"></i>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-1" id="ultimo-exceso">
                            @if($estadisticas['ultimo_exceso'])
                                {{ $estadisticas['ultimo_exceso']->fecha_registro->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-2sm text-gray-600">Último Exceso</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-info-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-calendar-2 text-info text-base"></i>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-1" id="ultimo-limite">
                            @if($estadisticas['ultimo_limite'])
                                {{ $estadisticas['ultimo_limite']->fecha_registro->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="text-2sm text-gray-600">Último Límite</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-primary-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-security-user text-primary text-base"></i>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-1" id="estado-token">
                            @if($estadisticas['token_actual'])
                                @if($estadisticas['token_actual']->estaExpirado())
                                    <span class="badge badge-danger badge-outline">Expirado</span>
                                @else
                                    <span class="badge badge-success badge-outline">Activo</span>
                                @endif
                            @else
                                <span class="badge badge-warning badge-outline">Sin Token</span>
                            @endif
                        </div>
                        <div class="text-2sm text-gray-600">Estado Token</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <div class="flex items-center justify-center size-12 bg-secondary-clarity rounded-full mb-3 mx-auto">
                            <i class="ki-filled ki-time text-secondary text-base"></i>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-1" id="tiempo-restante">
                            {{ $estadisticas['token_actual']->tiempo_restante ?? 'N/A' }}
                        </div>
                        <div class="text-2sm text-gray-600">Tiempo Restante</div>
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

                                <div class="flex items-end">
                                    <button type="button" class="btn btn-primary" onclick="obtenerReportes()">
                                        <i class="ki-filled ki-cloud-download"></i>
                                        Obtener Reportes
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

    <!-- Loading Modal -->
    <div class="modal" id="loadingModal" data-modal="true" data-modal-backdrop-static="true">
        <div class="modal-content max-w-[400px] top-1/2 start-1/2 -translate-x-1/2 -translate-y-1/2">
            <div class="modal-body text-center p-10">
                <div class="flex justify-center mb-5">
                    <div class="spinner spinner-ring text-primary"></div>
                </div>
                <div class="text-gray-900 font-medium mb-2" id="loading-text">
                    Obteniendo reportes, por favor espere...
                </div>
                <div class="text-gray-600 text-sm">
                    Este proceso puede tomar varios segundos
                </div>
            </div>
        </div>
    </div>

    <script>
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

        function obtenerReportes() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const nuevoToken = document.getElementById('nuevo_token').value;

            if (!fechaInicio || !fechaFin) {
                mostrarAlerta('Por favor selecciona las fechas', 'warning');
                return;
            }

            // Mostrar modal de carga
            const modal = document.getElementById('loadingModal');
            modal.classList.add('open');

            document.getElementById('loading-text').textContent = 'Iniciando descarga de reportes...';
            agregarLog('Iniciando descarga de reportes...');
            agregarLog(`Rango: ${fechaInicio} a ${fechaFin}`);

            if (nuevoToken) {
                agregarLog('Usando nuevo token proporcionado');
            }

            fetch('{{ route("reportes.obtener") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    token: nuevoToken || ''
                })
            })
                .then(response => response.json())
                .then(data => {
                    modal.classList.remove('open');

                    if (data.success) {
                        mostrarAlerta(data.message, 'success');
                        agregarLog(`✅ ${data.message}`);
                        agregarLog(`📊 Excesos: ${data.data.excesos} registros`);
                        agregarLog(`📊 Límites: ${data.data.limites} registros`);
                        agregarLog(`📖 Batch ID: ${data.data.batch_id.substring(0, 8)}...`);

                        actualizarEstadisticas();
                        document.getElementById('nuevo_token').value = '';

                        setTimeout(() => {
                            mostrarResumenDescarga(data.data);
                        }, 1000);
                    } else {
                        mostrarAlerta(data.message, 'danger');
                        agregarLog(`❌ Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    modal.classList.remove('open');
                    const mensaje = error.message || 'Error desconocido';
                    mostrarAlerta(`Error: ${mensaje}`, 'danger');
                    agregarLog(`❌ Error: ${mensaje}`);
                });
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

                        // Actualizar contadores
                        document.getElementById('total-excesos').textContent = estadisticas.total_excesos || 0;
                        document.getElementById('total-limites').textContent = estadisticas.total_limites || 0;

                        // Actualizar fechas
                        document.getElementById('ultimo-exceso').textContent =
                            estadisticas.ultimo_exceso?.fecha_registro ?
                                new Date(estadisticas.ultimo_exceso.fecha_registro).toLocaleDateString('es-ES') : 'N/A';

                        document.getElementById('ultimo-limite').textContent =
                            estadisticas.ultimo_limite?.fecha_registro ?
                                new Date(estadisticas.ultimo_limite.fecha_registro).toLocaleDateString('es-ES') : 'N/A';

                        // Actualizar estado del token
                        const estadoTokenEl = document.getElementById('estado-token');
                        const tiempoRestanteEl = document.getElementById('tiempo-restante');

                        const token = estadisticas.token_actual;
                        if (token) {
                            if (token.esta_expirado) {
                                estadoTokenEl.innerHTML = '<span class="badge badge-danger badge-outline">Expirado</span>';
                            } else {
                                estadoTokenEl.innerHTML = '<span class="badge badge-success badge-outline">Activo</span>';
                            }
                            tiempoRestanteEl.textContent = token.tiempo_restante || 'N/A';
                        } else {
                            estadoTokenEl.innerHTML = '<span class="badge badge-warning badge-outline">Sin Token</span>';
                            tiempoRestanteEl.textContent = 'N/A';
                        }

                        // Actualizar tabla de batches
                        actualizarTablaBatches(estadisticas.ultimos_batches);

                        agregarLog('📊 Estadísticas actualizadas');
                    }
                })
                .catch(error => {
                    agregarLog('❌ Error al actualizar estadísticas');
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
                    <td><span class="text-sm font-semibold">${batch.total}</span></td>
                `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500">No hay batches recientes</td></tr>';
            }
        }

        function mostrarResumenDescarga(data) {
            const total = data.excesos + data.limites;
            if (total > 0) {
                agregarLog(`📈 RESUMEN DE DESCARGA:`);
                agregarLog(`   └── Excesos: ${data.excesos} registros`);
                agregarLog(`   └── Límites: ${data.limites} registros`);
                agregarLog(`   └── Total: ${total} registros`);
                agregarLog(`   └── Batch: ${data.batch_id.substring(0, 8)}...`);
            }
        }

        function mostrarAlerta(mensaje, tipo) {
            // Crear toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${tipo} alert-dismissible mb-5`;
            toast.innerHTML = `
            <div class="alert-icon">
                <i class="ki-filled ki-information-5"></i>
            </div>
            <div class="alert-wrapper">
                <div class="alert-title">${tipo === 'success' ? 'Éxito' : 'Aviso'}</div>
                <div class="alert-content">${mensaje}</div>
            </div>
            <button class="btn btn-sm btn-icon btn-light alert-close">
                <i class="ki-filled ki-cross"></i>
            </button>
        `;

            // Insertar al inicio del container
            const container = document.querySelector('.container-fluid');
            container.insertBefore(toast, container.firstChild);

            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);

            // Handle manual close
            toast.querySelector('.alert-close').addEventListener('click', () => {
                toast.remove();
            });
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

            const mesi = (inicio.getFullYear().toString().slice(-2)) + (inicio.getMonth() + 1).toString().padStart(2, '0');
            const mesf = (fin.getFullYear().toString().slice(-2)) + (fin.getMonth() + 1).toString().padStart(2, '0');

            // NO usar URLSearchParams - construir manualmente
            const params = `E=${tokenLimpio}&T=0&IMEI=&mesi=${mesi}&diai=${inicio.getDate().toString().padStart(2, '0')}&horai=00&mini=00&mesf=${mesf}&diaf=${fin.getDate().toString().padStart(2, '0')}&horaf=23&minf=59&grupo=`;

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

            console.log('=== DEBUG GENERAR URLS ===');
            console.log('Fecha inicio:', fechaInicio);
            console.log('Fecha fin:', fechaFin);
            console.log('Token original:', token);
            console.log('Token length:', token.length);
            console.log('Token bytes:', Array.from(token).map(char => char.charCodeAt(0)));

            const tokenLimpio = token.trim()
                .replace(/\s+/g, '')
                .replace(/%3D/g, '=')
                .replace(/\n/g, '')
                .replace(/\r/g, '');

            console.log('Token limpio:', tokenLimpio);
            console.log('¿Token cambió?', token !== tokenLimpio);

            // Probar construcción de parámetros
            try {
                const params = construirParametrosManual(fechaInicio, fechaFin, tokenLimpio);
                console.log('Parámetros construidos:', params);

                const baseUrl = 'https://gestiondeflota.boltrack.net/reportes/';
                const urlCompleta = baseUrl + 'RP131BodyExcesos.rep?' + params;
                console.log('URL completa:', urlCompleta);

                return urlCompleta;
            } catch (error) {
                console.error('Error construyendo URL:', error);
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
