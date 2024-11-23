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
                    Matriz plana
                </h1>
            </div>

        </div>
        <!-- End of Container -->
    </div>
    <!-- End of Toolbar -->
    <!-- Container -->
    <div class="container-fluid">
        <div class="grid gap-5 lg:gap-7.5 xl:w-[38.75rem] mx-auto">
            <div class="card pb-2.5">
                <div class="card-header" id="basic_settings">
                    <h3 class="card-title">
                        Subir matriz
                    </h3>
                </div>
                <form action="{{ route('uploads.index.matriz') }}" method="POST" enctype="multipart/form-data" class="card-body grid gap-5" x-data="{ fileName: '' }">
                    @csrf

                    <!-- Campo para subir archivo -->
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="form-label max-w-56">Archivo</label>
                        <div class="flex items-center justify-between flex-wrap grow gap-2.5">
                            <label class="bg-gray-700 text-white py-2 px-4 rounded cursor-pointer hover:bg-gray-800 transition">
                                <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required class="hidden" @change="fileName = $event.target.files[0].name">
                                Seleccionar archivo
                            </label>
                            <span x-text="fileName" class="text-gray-500"></span>
                        </div>
                    </div>

                    <!-- Campo de fecha y hora -->
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="form-label max-w-56">Fecha y Hora</label>
                        <input type="datetime-local" name="fecha_hora" class="input" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <!-- Botón de envío -->
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            Guardar cambios
                        </button>
                    </div>
                </form>

                @if (session('success'))
                    <div class="w-full mb-4 p-4 bg-green-500 text-white rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="w-full mb-4 p-4 bg-red-500 text-white rounded">
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End of Container -->
    <style>
        .custom-file-upload {
            display: inline-block;
            padding: 10px 20px;
            cursor: pointer;
            background-color: #4A5568; /* Color gris oscuro */
            color: #FFF;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .custom-file-upload:hover {
            background-color: #2D3748; /* Color gris más oscuro al pasar el mouse */
        }
        #fileInput {
            display: none; /* Oculta el input de archivo original */
        }
    </style>

</x-app-layout>
