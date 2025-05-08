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
                    Subir archivos
                </h1>
            </div>
            <div class="flex items-center gap-1">
                <a class="btn btn-light btn-sm" href="">
                    View Profile
                </a>
            </div>
        </div>
        <!-- End of Container -->
    </div>
    <!-- End of Toolbar -->
    <!-- Container -->
    <div class="container-fluid">
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5">


            <x-card-index-component icon="ki-setting" title="Matriz" subtitle="Carga de matriz" descrption="La matriz que llega dos veces al dia" href="{{ route('uploads.index.matriz') }}"  lista="{{ route('uploads.index.matriz.index') }}" />

            <x-card-index-component icon="ki-chart-line-up-2" title="Carga de archivo de truck" subtitle="Carga para la matrix principal de truck" descrption="Carga masiva de truck por mes para viajes" href="{{ route('uploads.index.truck') }}"  lista="{{ route('uploads.index.truck.index') }}" />

            <x-card-index-component icon="ki-design-1" title="Carga Argus" subtitle="Carga para eliminar viajes NO CBN" descrption="Carga para eliminar viajes NO CBN"  href="{{ route('uploads.index.argus') }}"  lista="{{ route('uploads.index.argus.index') }}" />

            <x-card-index-component icon="ki-design-1" title="Telemetria Argus" subtitle="Carga para eliminar viajes NO CBN" descrption="Carga para eliminar viajes NO CBN"  href="{{ route('uploads.argusReporte.conduccion') }}"  lista="{{ route('uploads.argusReporte.conduccion.list') }}" />



        </div>
    </div>
    <!-- End of Container -->
</x-app-layout>
