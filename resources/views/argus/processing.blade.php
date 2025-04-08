<x-app-layout>
    <x-slot name="navigation">
        uploads
    </x-slot>

    <!-- Toolbar -->
    <div class="pb-6">
        <!-- Container -->
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-200">
                    Archivos en Procesamiento
                </h1>
            </div>
        </div>
        <!-- End of Container -->
    </div>
    <!-- End of Toolbar -->

    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card bg-gray-800 border border-gray-700 shadow-lg">
                <div class="card-header py-5 flex-wrap border-b border-gray-700">
                    <h3 class="card-title text-gray-200">
                        Estado del Procesamiento
                    </h3>
                </div>

                <div class="card-body flex flex-col items-center justify-center py-8" id="processing-status">
                    <!-- Mensaje principal -->
                    <div class="text-center mb-6">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-blue-500 mb-4 mx-auto"></div>
                        <h2 class="text-xl font-semibold text-gray-200 mb-2">
                            Archivos en Procesamiento
                        </h2>
                        <p class="text-gray-400 max-w-lg mx-auto">
                            El sistema está procesando archivos. Esta operación puede tomar varios minutos dependiendo
                            del tamaño de los archivos.
                        </p>
                    </div>

                    <!-- Barra de progreso general -->
                    <div class="w-full max-w-xl mb-8">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-blue-400">Progreso General</span>
                            <span class="text-sm font-medium text-blue-400" id="progress-percentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full transition-all duration-500" id="progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="mt-2 text-sm text-gray-400 flex justify-between">
                            <span>Tiempo transcurrido: <span id="elapsed-time">0 minutos</span></span>
                            <span>Tiempo estimado: <span id="estimated-time">Calculando...</span></span>
                        </div>
                    </div>

                    <!-- Detalles de los archivos en procesamiento -->
                    <div class="w-full max-w-4xl" id="jobs-container">
                        <h3 class="font-medium text-gray-300 mb-3">Archivos en cola de procesamiento:</h3>
                        <div id="no-jobs-message" class="hidden py-4 text-center text-gray-400">
                            No hay archivos en procesamiento actualmente.
                        </div>
                        <div id="jobs-list" class="space-y-4">
                            <!-- Los jobs se añadirán aquí dinámicamente -->
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-8 flex space-x-4">
                        <button id="refreshButton" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors">
                            Actualizar Estado
                        </button>
                        <a href="{{ url()->previous() }}" class="px-4 py-2 border border-gray-600 text-gray-300 rounded hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors">
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->

    <!-- Template para los jobs -->
    <template id="job-template">
        <div class="job-item bg-gray-700 p-4 rounded-lg border border-gray-600 shadow-md">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-medium text-gray-200 job-type">Tipo de Archivo</h4>
                    <p class="text-sm text-gray-400 job-filename">nombre_del_archivo.csv</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full job-status bg-blue-900 text-blue-200">En progreso</span>
            </div>

            <div class="space-y-2">
                <!-- Barra de progreso individual -->
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-xs font-medium text-gray-300">Progreso</span>
                        <span class="text-xs font-medium text-gray-300 job-progress-text">0%</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2.5">
                        <div class="job-progress-bar bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Detalles del procesamiento -->
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mt-2">
                    <div>
                        <span class="text-gray-400">Inicio:</span>
                        <span class="ml-2 text-gray-300 job-started-at">-</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Fin estimado:</span>
                        <span class="ml-2 text-gray-300 job-estimated-end">-</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Registros:</span>
                        <span class="ml-2 text-gray-300"><span class="job-processed-records">0</span>/<span class="job-total-records">0</span></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Tiempo restante:</span>
                        <span class="ml-2 text-gray-300 job-remaining-time">Calculando...</span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- JavaScript para actualización de estado -->
    <script>
        // Variables para seguimiento de tiempo
        let startTime = new Date();
        let updateInterval;

        // Función para formatear fechas
        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' +
                date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        // Función para actualizar el estado
        function updateStatus() {
            fetch('/api/job-status')
                .then(response => response.json())
                .then(data => {
                    // Si no hay jobs corriendo, redirigir después de un breve retraso
                    if (!data.jobsRunning) {
                        clearInterval(updateInterval);
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('progress-percentage').textContent = '100%';

                        // Mostrar mensaje de finalización
                        const jobsContainer = document.getElementById('jobs-container');
                        jobsContainer.innerHTML = `
                            <div class="py-6 text-center">
                                <div class="text-green-500 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-200 mb-1">¡Procesamiento completado!</h3>
                                <p class="text-gray-400 mb-4">Todos los archivos han sido procesados correctamente.</p>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-400">Redirigiendo en 3 segundos...</p>
                                </div>
                            </div>
                        `;

                        // Redirigir después de 3 segundos
                        setTimeout(() => {
                            window.location.href = '/argus/files/select';
                        }, 3000);

                        return;
                    }

                    // Actualizar progreso general
                    const totalProgress = data.jobsInfo.total_progress || 0;
                    document.getElementById('progress-bar').style.width = `${totalProgress}%`;
                    document.getElementById('progress-percentage').textContent = `${totalProgress}%`;

                    // Calcular tiempo transcurrido
                    const elapsedMinutes = Math.floor((new Date() - startTime) / 60000);
                    document.getElementById('elapsed-time').textContent =
                        elapsedMinutes < 1 ? 'menos de un minuto' :
                            elapsedMinutes === 1 ? '1 minuto' :
                                `${elapsedMinutes} minutos`;

                    // Mostrar tiempo estimado
                    const estimatedMinutes = data.jobsInfo.overall_estimated_minutes || 0;
                    document.getElementById('estimated-time').textContent =
                        estimatedMinutes <= 0 ? 'Calculando...' :
                            data.jobsInfo.jobs.find(j => j.formatted_estimated_time)?.formatted_estimated_time || 'Finalizando...';

                    // Actualizar lista de jobs
                    updateJobsList(data.jobsInfo.jobs || []);
                })
                .catch(error => {
                    console.error('Error al obtener el estado:', error);
                });
        }

        // Función para actualizar la lista de jobs
        function updateJobsList(jobs) {
            const jobsList = document.getElementById('jobs-list');
            const noJobsMessage = document.getElementById('no-jobs-message');
            const template = document.getElementById('job-template');

            // Mostrar mensaje si no hay jobs
            if (jobs.length === 0) {
                jobsList.innerHTML = '';
                noJobsMessage.classList.remove('hidden');
                return;
            }

            // Ocultar mensaje y mostrar jobs
            noJobsMessage.classList.add('hidden');
            jobsList.innerHTML = '';

            // Añadir cada job a la lista
            jobs.forEach(job => {
                const jobElement = template.content.cloneNode(true);

                // Establecer tipo y nombre de archivo
                jobElement.querySelector('.job-type').textContent = job.job_type || 'Archivo';
                jobElement.querySelector('.job-filename').textContent = job.file_name || 'archivo.csv';

                // Establecer progreso
                const progressPercent = job.progress_percent || 0;
                jobElement.querySelector('.job-progress-text').textContent = `${progressPercent}%`;
                jobElement.querySelector('.job-progress-bar').style.width = `${progressPercent}%`;

                // Establecer información de tiempos
                jobElement.querySelector('.job-started-at').textContent = formatDateTime(job.started_at);
                jobElement.querySelector('.job-estimated-end').textContent = formatDateTime(job.estimated_end_time);

                // Establecer contadores de registros
                jobElement.querySelector('.job-processed-records').textContent = job.processed_records || 0;
                jobElement.querySelector('.job-total-records').textContent = job.total_records || '?';

                // Establecer tiempo restante
                jobElement.querySelector('.job-remaining-time').textContent =
                    job.formatted_estimated_time || 'Calculando...';

                // Establecer clase de estado
                const statusBadge = jobElement.querySelector('.job-status');
                if (job.potentially_stuck) {
                    statusBadge.textContent = 'Posible bloqueo';
                    statusBadge.classList.remove('bg-blue-900', 'text-blue-200');
                    statusBadge.classList.add('bg-yellow-900', 'text-yellow-200');
                } else if (progressPercent > 90) {
                    statusBadge.textContent = 'Finalizando';
                    statusBadge.classList.remove('bg-blue-900', 'text-blue-200');
                    statusBadge.classList.add('bg-green-900', 'text-green-200');
                }

                // Añadir el job a la lista
                jobsList.appendChild(jobElement);
            });
        }

        // Iniciar actualización automática al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Primera actualización inmediata
            updateStatus();

            // Actualización periódica cada 5 segundos
            updateInterval = setInterval(updateStatus, 5000);

            // Actualización manual al hacer clic en el botón
            document.getElementById('refreshButton').addEventListener('click', updateStatus);
        });
    </script>
</x-app-layout>
