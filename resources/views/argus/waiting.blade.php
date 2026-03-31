<x-app-layout>
    <x-slot name="navigation">
        uploads
    </x-slot>

    <div class="container-fluid py-10">
        <div class="max-w-xl mx-auto">
            <h2 class="text-xl font-semibold text-gray-200 mb-6 text-center">Comparacion Argus</h2>

            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-400 mb-1">
                        <span id="progress-label">Iniciando...</span>
                        <span id="progress-pct">0%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div id="progress-bar" class="bg-blue-500 h-4 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <div class="flex justify-between text-sm text-gray-400">
                    <span id="records-count">0 / 0 registros</span>
                    <span id="elapsed-time">Tiempo: 0s</span>
                </div>

                <p class="text-gray-500 text-xs mt-4 text-center">Batch: {{ $batchId }}</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var batchId = @json($batchId);
            var startTime = Date.now();
            var bar = document.getElementById('progress-bar');
            var label = document.getElementById('progress-label');
            var pctEl = document.getElementById('progress-pct');
            var countEl = document.getElementById('records-count');
            var timeEl = document.getElementById('elapsed-time');

            function formatTime(ms) {
                var s = Math.floor(ms / 1000);
                if (s < 60) return s + 's';
                var m = Math.floor(s / 60);
                s = s % 60;
                return m + 'm ' + s + 's';
            }

            function updateElapsed() {
                timeEl.textContent = 'Tiempo: ' + formatTime(Date.now() - startTime);
            }

            function poll() {
                fetch('/argus/progress/' + batchId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var pct = data.pct || 0;
                    bar.style.width = pct + '%';
                    pctEl.textContent = pct + '%';
                    countEl.textContent = data.processed + ' / ' + data.total + ' registros';

                    if (data.total > 0 && !data.finished) {
                        label.textContent = 'Procesando registros...';
                    }

                    updateElapsed();

                    if (data.finished) {
                        label.textContent = 'Completado! Redirigiendo...';
                        bar.classList.remove('bg-blue-500');
                        bar.classList.add('bg-green-500');
                        setTimeout(function () {
                            window.location.href = '/argus/files/results?batch_id=' + batchId;
                        }, 1000);
                    } else {
                        setTimeout(poll, 2000);
                    }
                })
                .catch(function () {
                    setTimeout(poll, 3000);
                });
            }

            setInterval(updateElapsed, 1000);
            poll();
        })();
    </script>
</x-app-layout>
