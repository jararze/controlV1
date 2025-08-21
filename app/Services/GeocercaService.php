<?php

namespace App\Services;

use App\Models\Geocerca;
use App\Models\DepositoGeocercaMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class GeocercaService
{
    private array $geocercaHierarchy = ['DOCKS', 'TRACK AND TRACE', 'CBN', 'CIUDADES'];
    private string $cacheKey = 'geocercas_grouped';
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('truck-tracking.geocercas.cache_ttl', 3600);
    }

    /**
     * Obtiene geocercas agrupadas desde cache o BD
     */
    public function getGeocercasGrouped(): array
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            $grouped = Geocerca::active()
                ->orderBy('nombre_grupo')
                ->orderBy('nombre_geocerca')
                ->get()
                ->groupBy('nombre_grupo');

            $result = [];
            foreach ($grouped as $grupo => $geocercas) {
                $result[$grupo] = $geocercas->map(function ($geocerca) {
                    return [
                        'id' => $geocerca->id,
                        'id_geocerca' => $geocerca->id_geocerca,
                        'nombre' => $geocerca->nombre_geocerca,
                        'codigo' => $geocerca->codigo,
                        'puntos' => $geocerca->puntos,
                        'polygon' => $geocerca->puntos
                    ];
                })->toArray();
            }

            return $result;
        });
    }


    /**
     * Verifica en qué geocercas se encuentra un punto
     * Mantiene la misma lógica que Python
     */
    public function checkPointInGeocercas(float $lat, float $lng, ?string $depositoDestino = null): array
    {
        $result = [
            'DOCKS' => 'NO',
            'TRACK AND TRACE' => 'NO',
            'CBN' => 'NO',
            'CIUDADES' => 'NO'
        ];

        $geocercas = $this->getGeocercasGrouped();
        $targetGeocercas = [];

        // Si tenemos mapeo específico para el depósito, priorizarlo
        if ($depositoDestino) {
            $mapping = DepositoGeocercaMapping::getMappingForDeposito($depositoDestino);
            if ($mapping) {
                $targetGeocercas = $mapping;
            }
        }

        // Verificar en orden jerárquico
        foreach ($this->geocercaHierarchy as $grupo) {
            if (!isset($geocercas[$grupo])) continue;

            $targetName = $targetGeocercas[$grupo] ?? null;

            // Si tenemos geocerca específica para este depósito, verificar primero
            if ($targetName) {
                foreach ($geocercas[$grupo] as $geocerca) {
                    if ($this->isTargetGeocerca($geocerca['nombre'], $targetName)) {
                        if ($this->pointInGeocerca($lat, $lng, $geocerca['puntos'])) {
                            $result[$grupo] = "SI en {$geocerca['nombre']}";
                            break;
                        }
                    }
                }
            }

            // Si no encontramos la específica, buscar cualquiera en el grupo
            if ($result[$grupo] === 'NO') {
                foreach ($geocercas[$grupo] as $geocerca) {
                    if ($this->pointInGeocerca($lat, $lng, $geocerca['puntos'])) {
                        $result[$grupo] = "SI en {$geocerca['nombre']}";
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Importa geocercas desde Excel a base de datos
     * MIGRACIÓN DIRECTA DEL EXCEL ACTUAL
     */
    public function importFromExcel(string $filePath): bool
    {
        try {
            Log::info("Iniciando importación de geocercas desde: {$filePath}");

            $data = Excel::toArray([], $filePath);

            if (empty($data) || empty($data[0])) {
                Log::error("Archivo Excel vacío o no válido");
                return false;
            }

            $rows = $data[0];
            $headers = array_shift($rows); // Remover headers

            // Mapear columnas basado en tus datos
            $colMap = $this->mapExcelColumns($headers);

            if (!$colMap['valid']) {
                Log::error("No se encontraron las columnas requeridas en el Excel");
                Log::info("Headers encontrados: " . implode(', ', $headers));
                return false;
            }

            Log::info("Mapeado de columnas exitoso: " . json_encode($colMap));

            $processed = 0;
            $errors = 0;

            foreach ($rows as $row) {
                try {
                    // Extraer datos según tu estructura
                    $idGrupo = intval($row[$colMap['id_grupo']] ?? 0);
                    $nombreGrupo = trim($row[$colMap['nombre_grupo']] ?? '');
                    $idGeocerca = intval($row[$colMap['id_geocerca']] ?? 0);
                    $codigo = trim($row[$colMap['codigo']] ?? '');
                    $nombreGeocerca = trim($row[$colMap['nombre_geocerca']] ?? '');
                    $puntosStr = trim($row[$colMap['puntos']] ?? '');

                    // Validaciones
                    if (empty($nombreGrupo) || empty($nombreGeocerca) || empty($puntosStr)) {
                        continue;
                    }

                    // Parsear puntos (formato: "lat lng,lat lng,...")
                    $puntos = $this->parseGeocercaPoints($puntosStr);

                    if (empty($puntos)) {
                        Log::warning("No se pudieron parsear puntos para: {$nombreGeocerca}");
                        $errors++;
                        continue;
                    }

                    // Guardar o actualizar
                    Geocerca::updateOrCreate(
                        [
                            'id_grupo' => $idGrupo,
                            'id_geocerca' => $idGeocerca,
                            'nombre_geocerca' => $nombreGeocerca
                        ],
                        [
                            'nombre_grupo' => $nombreGrupo,
                            'codigo' => $codigo,
                            'puntos' => $puntos,
                            'puntos_raw' => $puntosStr,
                            'activa' => true
                        ]
                    );

                    $processed++;

                } catch (\Exception $e) {
                    Log::warning("Error procesando geocerca: {$e->getMessage()}");
                    $errors++;
                }
            }

            // Limpiar cache
            Cache::forget($this->cacheKey);

            Log::info("Importación completada: {$processed} procesadas, {$errors} errores");
            return true;

        } catch (\Exception $e) {
            Log::error("Error importando geocercas: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Mapea las columnas del Excel según tu estructura
     */
    private function mapExcelColumns(array $headers): array
    {
        $colMap = ['valid' => false];

        foreach ($headers as $index => $header) {
            $headerUpper = strtoupper(trim($header));

            if (str_contains($headerUpper, 'IDGRUPO')) {
                $colMap['id_grupo'] = $index;
            }
            if (str_contains($headerUpper, 'NOMBREGRUPO')) {
                $colMap['nombre_grupo'] = $index;
            }
            if (str_contains($headerUpper, 'IDGEOCERCA')) {
                $colMap['id_geocerca'] = $index;
            }
            if (str_contains($headerUpper, 'CODIGO')) {
                $colMap['codigo'] = $index;
            }
            if (str_contains($headerUpper, 'NOMBREGEOCERCA')) {
                $colMap['nombre_geocerca'] = $index;
            }
            if (str_contains($headerUpper, 'PUNTOS') && str_contains($headerUpper, 'GEOCERCA')) {
                $colMap['puntos'] = $index;
            }
        }

        // Verificar columnas esenciales
        $required = ['nombre_grupo', 'nombre_geocerca', 'puntos'];
        $colMap['valid'] = true;
        foreach ($required as $req) {
            if (!isset($colMap[$req])) {
                $colMap['valid'] = false;
                break;
            }
        }

        return $colMap;
    }

    /**
     * Parsea los puntos desde el formato del Excel
     * Formato: "-17.3291366 -66.1882108,-17.3296922 -66.1885685,..."
     */
    private function parseGeocercaPoints(string $puntosStr): array
    {
        try {
            $puntos = [];
            $coordenadas = explode(',', $puntosStr);

            foreach ($coordenadas as $coord) {
                $coord = trim($coord);
                if ($coord && str_contains($coord, ' ')) {
                    $parts = explode(' ', $coord);
                    if (count($parts) >= 2) {
                        $lat = floatval(trim($parts[0]));
                        $lng = floatval(trim($parts[1]));

                        // Validar que son coordenadas válidas
                        if ($lat !== 0.0 && $lng !== 0.0) {
                            $puntos[] = [$lat, $lng]; // [lat, lng] formato estándar
                        }
                    }
                }
            }

            return $puntos;

        } catch (\Exception $e) {
            Log::warning("Error parseando puntos: {$e->getMessage()}");
            return [];
        }
    }

    private function isTargetGeocerca(string $geocercaName, string $targetName): bool
    {
        $geocercaUpper = strtoupper($geocercaName);
        $targetUpper = strtoupper($targetName);

        return str_contains($geocercaUpper, $targetUpper) ||
            str_contains($targetUpper, $geocercaUpper);
    }

    private function pointInGeocerca(float $lat, float $lng, array $puntos): bool
    {
        if (empty($puntos) || count($puntos) < 3) {
            return false;
        }

        $vertices = count($puntos);
        $intersections = 0;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $puntos[$i][1]; // lng
            $yi = $puntos[$i][0]; // lat
            $xj = $puntos[$j][1]; // lng
            $yj = $puntos[$j][0]; // lat

            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $intersections++;
            }
        }

        return ($intersections % 2) === 1;
    }

    /**
     * Limpia el cache de geocercas
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }







}
