<?php

namespace App\Console\Commands;

use App\Exports\ArgusExport;
use App\Models\Argus;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExportArgusCruce extends Command
{
    protected $signature = 'argus:export-cruce
                            {--year=2025 : Año}
                            {--month=5 : Mes (1-12)}
                            {--cod=85 : Código de depósito (origen o destino)}';

    protected $description = 'Exporta eventos Argus que matchean con viajes Truck filtrados por mes y depósito';

    public function handle(): int
    {
        $year  = (int) $this->option('year');
        $month = (int) $this->option('month');
        $cod   = (string) $this->option('cod');

        $inicioMes = Carbon::create($year, $month, 1)->startOfMonth();
        $finMes    = (clone $inicioMes)->endOfMonth();

        $this->info("Cargando trucks con cod={$cod} entre {$inicioMes->toDateString()} y {$finMes->toDateString()}...");

        // 1. Trucks filtrados (en su BD)
        $trucks = Truck::where(function ($q) use ($cod) {
            $q->where('cod', $cod)->orWhere('cod_destino', $cod);
        })
            ->whereBetween('fecha_salida', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->select([
                'planilla','patente','cod','deposito_origen',
                'cod_destino','deposito_destino',
                'fecha_salida','hora_salida','fecha_llegada','hora_llegada',
            ])
            ->get();

        $this->info("Trucks encontrados: {$trucks->count()}");
        if ($trucks->isEmpty()) {
            $this->warn("No hay trucks que cumplan el filtro.");
            return self::SUCCESS;
        }

        // 2. Indexar trucks (misma lógica que buildTrucksIndex del controller)
        $trucksIndexed = $this->buildTrucksIndex($trucks);
        $patentes = array_keys($trucksIndexed);

        // Ventana global para acotar la consulta a argus
        $minInicio = null; $maxFin = null;
        foreach ($trucksIndexed as $ventanas) {
            foreach ($ventanas as $v) {
                if ($v['inicio'] && (!$minInicio || $v['inicio']->lt($minInicio))) $minInicio = $v['inicio'];
                if ($v['fin']    && (!$maxFin    || $v['fin']->gt($maxFin)))       $maxFin    = $v['fin'];
            }
        }

        $this->info("Cargando argus de " . count($patentes) . " patentes entre {$minInicio} y {$maxFin}...");

        // 3. Argus (en SU BD — Laravel usa la conexión del modelo)
        $arguses = Argus::whereIn('patente', $patentes)
            ->whereBetween('hora_alarma', [$minInicio, $maxFin])
            ->select([
                'patente','hora_alarma','dia','evento','motorista',
                'velocidade','latitude','longitude','operacion','event_id',
            ])
            ->get();

        $this->info("Argus a evaluar: {$arguses->count()}");

        // 4. Filtrar los que SÍ matchean (misma lógica que rowHasMatch)
        $matched = $arguses->filter(
            fn ($a) => $this->rowHasMatch($a->patente, $a->hora_alarma, $trucksIndexed)
        )->values();

        $this->info("Argus con match: {$matched->count()}");
        if ($matched->isEmpty()) {
            $this->warn("Sin matches. No se genera Excel.");
            return self::SUCCESS;
        }

        // 5. Exportar
        $filename = "argus_cruce_{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "_cod{$cod}.xlsx";
        Excel::store(new ArgusExport($matched), $filename);

        $this->info("OK → storage/app/{$filename}");
        return self::SUCCESS;
    }

    private function buildTrucksIndex($trucks): array
    {
        $indexed = [];
        foreach ($trucks as $truck) {
            try {
                $fechaSalida  = $truck->fecha_salida  ? trim($truck->fecha_salida)  : null;
                $fechaLlegada = $truck->fecha_llegada ? trim($truck->fecha_llegada) : null;
                $horaSalida   = $truck->hora_salida   ? trim($truck->hora_salida)   : null;
                $horaLlegada  = $truck->hora_llegada  ? trim($truck->hora_llegada)  : null;

                if ($horaSalida) {
                    $c = Carbon::createFromTimeString($horaSalida);
                    $orig = $c->hour; $c->subHour();
                    if ($c->hour > $orig) $fechaSalida = Carbon::parse($fechaSalida)->subDay()->toDateString();
                    $horaSalida = $c->format('H:i:s');
                }
                if ($horaLlegada) {
                    $c = Carbon::createFromTimeString($horaLlegada);
                    $orig = $c->hour; $c->subHour();
                    if ($c->hour > $orig) $fechaLlegada = Carbon::parse($fechaLlegada)->subDay()->toDateString();
                    $horaLlegada = $c->format('H:i:s');
                }

                $inicio = $fechaSalida  ? Carbon::parse($fechaSalida)  : null;
                if ($inicio && $horaSalida)  $inicio->setTimeFromTimeString($horaSalida);

                $fin = $fechaLlegada ? Carbon::parse($fechaLlegada) : null;
                if ($fin && $horaLlegada)    $fin->setTimeFromTimeString($horaLlegada);

                $indexed[$truck->patente][] = ['inicio' => $inicio, 'fin' => $fin];
            } catch (\Exception $e) {
                Log::error("buildTrucksIndex: {$truck->patente} — {$e->getMessage()}");
            }
        }
        return $indexed;
    }

    private function rowHasMatch(string $patente, $horaAlarma, array $trucksIndexed): bool
    {
        if (!isset($trucksIndexed[$patente])) return false;
        try {
            $ts = Carbon::parse($horaAlarma);
            foreach ($trucksIndexed[$patente] as $v) {
                if ($v['inicio'] && $v['fin'] && $ts->between($v['inicio'], $v['fin'])) return true;
            }
        } catch (\Exception $e) {
            Log::error("rowHasMatch: {$patente} — {$e->getMessage()}");
        }
        return false;
    }
}
