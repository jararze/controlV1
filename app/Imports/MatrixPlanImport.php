<?php

namespace App\Imports;

use App\Models\Uploads\Matrix;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class MatrixPlanImport implements ToModel, WithMultipleSheets, WithHeadingRow, WithCalculatedFormulas
{
    protected $fileName;
    protected $batchId;
    protected $fecha_hora;

    public function __construct($fileName, $batchId, $fecha_hora)
    {
        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fecha_hora = $fecha_hora;
    }

    public function sheets(): array
    {
        return [
            "Base" => $this, // Indica que solo la hoja "BASE" será importada
        ];
    }


    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Matrix([
            'cod_origen' => $row['cod_origen'] ?? null,
            'dep_origen' => $row['dep_origen'] ?? null,
            'cod_destino' => $row['cod_des'] ?? null,
            'dep_destino' => $row['dep_des'] ?? null,
            'planilla' => $row['planilla'] ?? null,
            'nombre_fletero' => $row['nombre_fletero'] ?? null,
            'cod_cam' => $row['cod_cam'] ?? null,
            'patente' => $row['patente'] ?? null,
            'salida' => isset($row['salida']) ? $this->transformExcelDate($row['salida']) : null,
            'columna1' => $row['columna1'] ?? null,
            'status' => $row['status'] ?? null,
            'cod_prod' => $row['cod_prod'] ?? null,
            'producto' => $row['producto'] ?? null,
            'bultos' => $row['bultos'] ?? null,
            'tipo_producto' => $row['tipo_prod'] ?? null,
            'tipo_viaje' => $row['tipo_viaje'] ?? null,
            'hl' => $row['hl'] ?? null,
            'referencia' => $row['referencia'] ?? null,
            'eta' => isset($row['eta']) ? $this->transformExcelDate($row['eta']) : null,
            'obs_eta' => $row['obs_eta'] ?? null,
            'placa_real' => $row['placa_real'] ?? null,
            'eta_observacion' => isset($row['eta_observacion']) ? $this->transformExcelDate($row['eta_observacion']) : null,
            'comparacion_eta' => isset($row['comparacion_eta'])? $this->transformExcelDate($row['comparacion_eta']) : null,
            'comparacion_obs_eta' => $row['comparacion_obs_eta'] ?? null,
            'gps' => $row['gps'] ?? null,
            'coordenadas' => $row['coordenadas'] ?? null,
            'ultimo_reporte_gps' => isset($row['ultimo_reporte_gps']) ? $this->transformExcelDate($row['ultimo_reporte_gps']) : null,
            'ruta' => $row['ruta'] ?? null,
            'sla_dias' => $row['sla_dias'] ?? null,
            'tgt' => $row['tgt'] ?? null,
            'tmv_vs_sla' => $row['tmv_vs_sla'] ?? null,
            'duplicado' => $row['duplicado'] ?? null,
            'sku_agrupado' => $row['sku_agrupado'] ?? null,
            'marca' => $row['marca'] ?? null,
            'calibre' => $row['calibre'] ?? null,
            'clase' => $row['clase'] ?? null,
            'batch_id' => $this->batchId,
            'fecha_registro' => $this->fecha_hora,
            'file_name' => $this->fileName,
        ]);
    }

    private function transformExcelDate($value)
    {
        // Verifica si el valor es un número para convertirlo de formato serial de Excel
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
        }

        // Si el valor es #N/A, #REF! o cualquier otro error, lo convierte a null
        if (in_array($value, ['#N/A', '#REF!', '#VALUE!', '#DIV/0!', '#NAME?', '#NULL!'])) {
            return null;
        }

        return $value; // Si no es un número o error, se devuelve tal cual
    }
}
