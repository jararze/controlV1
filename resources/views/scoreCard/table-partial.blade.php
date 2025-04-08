<!-- table-partial.blade.php -->
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
<div class="mt-5">
    {{ $logisticaData->links() }}
</div>
