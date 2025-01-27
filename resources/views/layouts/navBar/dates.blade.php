<div class="menu menu-default" data-menu="true">
    <div class="menu-item">
        <button class="menu-toggle btn btn-light btn-sm flex-nowrap" id="update-boltrack-button">
            <span class="hidden md:inline text-nowrap">Última actualización Movimiento Boltrack: <strong id="update-diff">Cargando...</strong></span>
            <span class="inline md:hidden text-nowrap">Sep, 2024</span>
        </button>
    </div>
</div>

<script>
    document.getElementById('update-boltrack-button').addEventListener('click', function () {
        // Perform AJAX call to update and fetch the latest time difference
        fetch('/boltrack/update', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                console.log('Actualizado:', data);
                // Update the UI with the new time difference
                const diff = data.diff ? `${data.diff} horas` : 'No disponible';
                document.getElementById('update-diff').innerText = diff;
            })
            .catch(error => {
                console.error('Error al actualizar:', error);
                alert('Ocurrió un error al intentar actualizar.');
            });
    });
</script>
