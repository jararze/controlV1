document.addEventListener('DOMContentLoaded', () => {

    let table = new DataTable('#members_table');

    // Apply styles to rows with `data-apply-styles="true"`
    document.querySelectorAll('tr[data-apply-styles="true"]').forEach((row) => {
        applyRowStyles(row);
    });

    // Inicializar modales
    KTModal.init();
    KTModal.createInstances();

    const modalEl = document.querySelector('#make_call');
    const modal = new KTModal(modalEl, {
        backdropClass: 'transition-all duration-300 fixed inset-0 bg-black opacity-75',
        backdrop: true,
        disableScroll: true,
        persistent: true
    });

    // Configuración de eventos globales
    document.addEventListener('click', (event) => {
        const target = event.target.closest('.menu-link');
        if (target) {
            event.preventDefault();

            // Pasar planilla al modal
            const planilla = target.getAttribute('data-planilla');
            const batchid = target.getAttribute('data-batchid');
            const modalPlanillaInput = document.getElementById('modal-planilla');
            const modalbatchidInput = document.getElementById('modal-batchid');
            if (modalPlanillaInput && modalbatchidInput) {
                modalPlanillaInput.value = planilla;
                modalbatchidInput.value = batchid;
            } else {
                console.error("No se encontró el input con id 'modal-planilla'.");
            }

            modal.show();
        }
    });

    // Configurar botón de cerrar modal
    document.querySelectorAll('[data-modal-dismiss]').forEach(button => {
        button.addEventListener('click', () => modal.hide());
    });

    // Configuración de dialers
    const setupDialer = (decrementId, incrementId, inputId) => {
        const decrementButton = document.getElementById(decrementId);
        const incrementButton = document.getElementById(incrementId);
        const input = document.getElementById(inputId);

        if (!decrementButton || !incrementButton || !input) {
            console.warn(`Dialer setup failed: Missing elements for IDs "${decrementId}", "${incrementId}", or "${inputId}".`);
            return; // Skip if any element is missing
        }

        decrementButton.addEventListener('click', () => {
             // Evitar valores negativos
            input.value = Math.max(0, parseInt(input.value, 10) - 1);
        });

        incrementButton.addEventListener('click', () => {
            input.value = parseInt(input.value, 10) + 1;
        });
    };

    setupDialer('decrement', 'increment', 'fuera_hora'); // Configurar dialer principal
    setupDialer('decrementDescarga', 'incrementDescarga', 'dialerDescarga'); // Configurar dialer de descarga

    // Manejo de condiciones de los selects
    const destinoSelect = document.getElementById('destino');
    const descargoSelect = document.getElementById('descargo');
    const tiempoEsperaDialer = document.getElementById('dialerDescarga');
    const decrementTiempoEspera = document.getElementById('decrementDescarga');
    const incrementTiempoEspera = document.getElementById('incrementDescarga');
    const dieselSelect = document.getElementById('diesel');
    const filaSelect = document.getElementById('fila');

    const toggleFields = (fields, isDisabled, values = {}) => {
        fields.forEach(field => {
            const element = document.getElementById(field.id);
            if (!element) {
                console.warn(`Element with ID "${field.id}" not found.`);
                return; // Skip if the element doesn't exist
            }
            if (isDisabled) {
                element.setAttribute('disabled', 'true');
                if (values[field.id] !== undefined) element.value = values[field.id];
            } else {
                element.removeAttribute('disabled');
            }
        });
    };

    const handleDestinoChange = () => {
        toggleFields(
            [
                { id: 'descargo' },
                { id: 'dialerDescarga' },
                { id: 'decrementDescarga' },
                { id: 'incrementDescarga' }
            ],
            destinoSelect.value === 'no',
            { descargo: '--', dialerDescarga: '0' }
        );
    };

    const handleFilaChange = () => {
        const isFilaSi = filaSelect.value === 'si';
        toggleFields(
            [
                { id: 'destino' },
                { id: 'descargo' },
                { id: 'dialerDescarga' },
                { id: 'decrementDescarga' },
                { id: 'incrementDescarga' },
                { id: 'diesel' }
            ],
            isFilaSi,
            { destino: 'no', descargo: '--', dialerDescarga: '0', status: 'no' }
        );
        if (!isFilaSi) handleDestinoChange();
    };

    // Manejar eventos "change"
    destinoSelect.addEventListener('change', handleDestinoChange);
    filaSelect.addEventListener('change', handleFilaChange);

    // Inicializar estado de los campos
    handleDestinoChange();
    handleFilaChange();


    const form = document.getElementById('call-form');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Guardando...';

        const formData = new FormData(form);

        // console.log('Datos del formulario:');
        // formData.forEach((value, key) => {
        //     console.log(`${key}: ${value}`);
        // });

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                const result = await response.json();
                alert('Formulario guardado con éxito!');

                const planilla = result.batch_call.planilla;
                console.log("planilla:" + planilla);
                const row = document.querySelector(`tr[data-planilla="${planilla}"]`);
                console.log("row:" + row);

                if (row) {

                    applyRowStyles(row);
                    // row.classList.add('!bg-green-400', '!text-gray-700', '!font-semibold');
                }


                form.reset();
                // Opcional: cerrar el modal
                // const modal = new KTModal(document.querySelector('#make_call'));
                modal.hide();
            } else {
                const error = await response.json();
                console.error('Errores de validación:', error.errors);

                // Mostrar errores en la consola
                for (const [field, messages] of Object.entries(error.errors)) {
                    console.log(`Campo: ${field}, Errores: ${messages.join(', ')}`);
                }

                // Opcional: mostrar errores en la interfaz
                alert('Error al guardar: ' + (error.message || 'Verifica los datos ingresados.'));
            }
        } catch (error) {
            console.error('Error en el envío:', error);
            alert('Ocurrió un error inesperado.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Guardar';
        }
    });

    function applyRowStyles(row) {
        const applyStyles = row.getAttribute('data-apply-styles') === 'true';
        if (applyStyles) {
            // Apply styles to the row (tr)
            row.style.backgroundColor = '#34D399'; // Green background
            row.style.color = '#FFFFFF'; // White text for contrast
            row.style.fontWeight = 'bold'; // Bold text
            row.style.borderRadius = '6px'; // Rounded corners
            row.style.transition = 'all 0.3s ease'; // Smooth transition
            row.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)'; // Light shadow
            row.style.padding = '0.5rem'; // Add padding for better spacing

            // Loop through all <td> elements in the row and modify their styles/classes
            const cells = row.querySelectorAll('td');
            cells.forEach((cell) => {
                // Remove the text-gray-800 class
                cell.classList.remove('text-gray-800');

                // Apply new styles or classes
                cell.style.color = '#FFFFFF'; // Make text white for better visibility
                cell.style.fontWeight = 'bold'; // Highlight the text
                cell.style.transition = 'all 0.3s ease'; // Smooth transition
            });

            // Optionally add hover effect for better interactivity
            row.addEventListener('mouseover', () => {
                row.style.backgroundColor = '#10B981'; // Darker green on hover
                row.style.boxShadow = '0 6px 12px rgba(0, 0, 0, 0.15)'; // Stronger shadow
            });

            row.addEventListener('mouseout', () => {
                row.style.backgroundColor = '#34D399'; // Restore initial color
                row.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)'; // Restore shadow
            });
        }
    }








});
