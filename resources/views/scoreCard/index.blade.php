<x-app-layout>
    <!-- Toolbar -->
    <div class="pb-6">
        <!-- Container -->
        <div class="container-fluid flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center flex-wrap gap-1 lg:gap-5">
                <h1 class="font-medium text-lg text-gray-900">
                    Logística de Transporte
                </h1>
            </div>
            <div class="flex items-center gap-1">
                <a class="btn btn-light btn-sm" href="{{ route('dashboard') }}">
                    Volver al Dashboard
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
                        Datos de Logística y Transporte
                    </h3>
                    <div class="flex gap-5">
                        <input type="text" id="searchInput" class="input input-sm" placeholder="Buscar...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" id="logisticaTable">
                            <thead>
                            <tr>
                                <th class="min-w-[60px]">#</th>
                                <th class="min-w-[150px]">
                                    <span class="sort asc">
                                        <span class="sort-label text-gray-700 font-normal">Patente</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="sort">
                                        <span class="sort-label text-gray-700 font-normal">Ruta</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="sort">
                                        <span class="sort-label text-gray-700 font-normal">Origen</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="sort">
                                        <span class="sort-label text-gray-700 font-normal">Destino</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="sort">
                                        <span class="sort-label text-gray-700 font-normal">Km Recorridos</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="sort">
                                        <span class="sort-label text-gray-700 font-normal">Fecha</span>
                                        <span class="sort-icon"></span>
                                    </span>
                                </th>
                                <th class="min-w-[100px]">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($logisticaData as $index => $item)
                                <tr>
                                    <td>{{ ($logisticaData->currentPage() - 1) * $logisticaData->perPage() + $loop->iteration }}</td>
                                    <td>{{ $item->patente ?? 'N/A' }}</td>
                                    <td>{{ $item->ruta ?? 'N/A' }}</td>
                                    <td>{{ $item->origen ?? 'N/A' }}</td>
                                    <td>{{ $item->destino ?? 'N/A' }}</td>
                                    <td>{{ $item->km_recorridos ?? '0' }}</td>
                                    <td>{{ isset($item->fecha) ? \Carbon\Carbon::parse($item->fecha)->format('Y-m-d') : 'N/A' }}</td>
                                    <td>
                                        <div class="menu" data-menu="true">
                                            <div class="menu-item" data-menu-item-offset="0, 10px"
                                                 data-menu-item-placement="bottom-end"
                                                 data-menu-item-placement-rtl="bottom-start"
                                                 data-menu-item-toggle="dropdown"
                                                 data-menu-item-trigger="click|lg:click">
                                                <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                    <i class="ki-filled ki-dots-vertical"></i>
                                                </button>
                                                <div class="menu-dropdown menu-default w-full max-w-[175px]"
                                                     data-menu-dismiss="true">
                                                    <div class="menu-item">
                                                        <a class="menu-link" href="{{ route('scoreCard.show', $item->id ?? 0) }}">
                                                            <span class="menu-icon"><i class="ki-filled ki-search-list"></i></span>
                                                            <span class="menu-title">Ver Detalles</span>
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

                    <!-- Pagination -->
                    <div class="mt-5">
                        {{ $logisticaData->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Búsqueda con AJAX
                const searchInput = document.getElementById('searchInput');
                let timer;

                searchInput.addEventListener('keyup', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        const searchTerm = searchInput.value.trim();

                        if (searchTerm.length >= 2 || searchTerm.length === 0) {
                            // Construct URL with the search parameter
                            let url = new URL(window.location.href);
                            url.searchParams.set('search', searchTerm);

                            // Make AJAX request
                            fetch(url)
                                .then(response => response.text())
                                .then(html => {
                                    // Extract the table content from the response
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');

                                    // Replace the table content
                                    const tableBody = doc.querySelector('#logisticaTable tbody');
                                    if (tableBody) {
                                        document.querySelector('#logisticaTable tbody').innerHTML = tableBody.innerHTML;
                                    }

                                    // Replace pagination
                                    const pagination = doc.querySelector('.mt-5');
                                    if (pagination) {
                                        document.querySelector('.mt-5').innerHTML = pagination.innerHTML;
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                });
                        }
                    }, 500); // Delay for 500ms to avoid too many requests
                });

                // Sort functionality
                const sortElements = document.querySelectorAll('.sort');
                sortElements.forEach(function(element) {
                    element.addEventListener('click', function() {
                        const column = this.querySelector('.sort-label').textContent.trim().toLowerCase();
                        const isAsc = this.classList.contains('asc');

                        // Reset all sort classes
                        sortElements.forEach(el => {
                            el.classList.remove('asc', 'desc');
                        });

                        // Set new sort direction
                        this.classList.add(isAsc ? 'desc' : 'asc');

                        // Get current URL and add/update sort parameters
                        let url = new URL(window.location.href);
                        url.searchParams.set('sort', column);
                        url.searchParams.set('direction', isAsc ? 'desc' : 'asc');

                        // Redirect to the sorted URL
                        window.location.href = url.toString();
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
