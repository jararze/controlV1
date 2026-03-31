<x-app-layout>
    <x-slot name="navigation">
        uploads
    </x-slot>

    <meta http-equiv="refresh" content="5">

    <div class="container-fluid py-10">
        <div class="flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-blue-500 mb-6"></div>
            <h2 class="text-xl font-semibold text-gray-200 mb-2">Procesando comparacion Argus...</h2>
            <p class="text-gray-400">Esta pagina se actualiza automaticamente cada 5 segundos.</p>
            <p class="text-gray-500 text-sm mt-2">Batch: {{ $batchId }}</p>
        </div>
    </div>
</x-app-layout>
