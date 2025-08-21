<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrackingReportExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Patente',
            'Planilla',
            'Status',
            'Depósito Origen',
            'Depósito Destino',
            'Producto',
            'Código Producto',
            'Salida',
            'Fecha Salida',
            'Hora Salida',
            'Fecha Llegada',
            'Hora Llegada',
            'Latitud',
            'Longitud',
            'Velocidad (km/h)',
            'Timestamp API',
            'En Docks',
            'En Track & Trace',
            'En CBN',
            'En Ciudades',
            'Porcentaje Entrega',
            'Estado Entrega',
            'Tiempo Espera (min)',
            'Tiempo Espera (hrs)',
            'Estado Descarga',
            'Nivel de Alerta',
            'Inicio Espera',
            'Fecha Proceso'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '366092']]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'K' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
            'O' => NumberFormat::FORMAT_NUMBER_00,
            'U' => NumberFormat::FORMAT_NUMBER_00,
            'X' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
