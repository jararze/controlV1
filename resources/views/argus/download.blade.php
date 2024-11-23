<table>
    <thead>
    <tr>
        <th>Operación</th>
        <th>Patente</th>
        <th>Dia</th>
        <th>Evento</th>
        <th>Motorista</th>
        <th>Hora Alarma</th>
        <th>Velocidad</th>
        <th>Latitud</th>
        <th>Longitud</th>
        <th>Evento ID</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result as $row)
        <tr>
            <td>{{ $row->operacion }}</td>
            <td>{{ $row->patente }}</td>
            <td>{{ \Carbon\Carbon::parse($row->dia)->toDateString() }}</td>
            <td>{{ $row->evento }}</td>
            <td>{{ $row->motorista }}</td>
            <td>{{ $row->hora_alarma }}</td>
            <td>{{ $row->velocidade }}</td>
            <td>{{ $row->latitude }}</td>
            <td>{{ $row->longitude }}</td>
            <td>{{ $row->event_id }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
