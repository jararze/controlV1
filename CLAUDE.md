# Control Logistico v1

## Contexto del Proyecto

### Proposito General
Sistema de gestion de flota y logistica para CBN (Cerveceria Boliviana Nacional). Controla el ciclo completo de viajes de camiones entre 3 cervecerias (Santa Cruz, La Paz, Cochabamba): desde la planificacion (matriz), pasando por tracking GPS en tiempo real, hasta la validacion de telemetria (Argus) y generacion de reportes de cumplimiento.

### Flujos Principales

1. **Importacion de Datos por Archivos** (UploadsController + Queue Jobs)
   - Matriz/Planillas (`ProcessMatrixFile`) -> tabla `upload_matrix`
   - Trucks (`ProcessTruckFile` + `UltraFastTruck` service) -> tabla `trucks`
   - Argus/Telemetria (`ProcessArgusFile`) -> tabla `arguses`
   - Excesos de velocidad (`ProcessExcesoFile`) -> tabla `excesos`
   - Limites de conduccion (`ProcessLimiteFile`) -> tabla `limite`
   - Conduccion fuera de horario (importacion directa via `FileImportService`) -> tabla `conduccion_fuera_horario`

2. **Procesamiento Argus** (ArgusController)
   - Cruza alarmas Argus con viajes de camiones por patente + ventana de tiempo (hora_salida a hora_llegada)
   - Clasifica cada registro como "Viaje CBN" o "NO VIAJE CBN"
   - Escribe resultados en BD externa (`bajada_argus` via conexion `external_db`)
   - Exporta no-coincidencias a Excel

3. **Tracking en Tiempo Real** (TruckTrackingController + servicios)
   - Consulta ubicaciones GPS via Boltrack API
   - Verifica posicion contra geocercas con algoritmo ray-casting (jerarquia: DOCKS > TRACK AND TRACE > CBN > CIUDADES)
   - Calcula progreso de entrega (0-100%) segun geocerca alcanzada
   - Genera alertas por tiempo de espera: NORMAL (<4h), ATTENTION (4-8h), WARNING (8-48h), CRITICAL (>48h)

4. **Reportes de Flota** (ReporteFlotaController + ReporteFlotaService)
   - Descarga reportes RP131 (excesos/limites) y RP022 (conduccion fuera horario) desde API Boltrack
   - Escritura dual: BD local + BD externa
   - Deteccion de duplicados antes de insertar
   - Retry con escalacion de timeout (15s -> 30s -> 60s)

5. **ScoreCard** (ScoreCardController)
   - Consulta tabla `logistica_transporte` en BD externa
   - Metricas por patente, ruta, origen, destino, km recorridos

### Modelos y Relaciones Clave

```
BolTrackHeader (1) ---> (N) BolTrackBody          # Datos GPS del API
TruckTracking  (1) ---> (N) TruckTrackingHistory   # Historial de posiciones
Truck          (1) ---> (N) TruckHistory            # Cambios en datos de viaje (clave compuesta: planilla+patente+cod_producto)
BatchCall      (1) ---> (N) CallLog                 # Registro de llamadas
Matrix         (1) ---> (1) Driver                  # Vinculo por placa/patente

BajadaArgus    -> conexion external_db (sin timestamps, campo estado para clasificacion)
Geocerca       -> poligonos con metodo containsPoint() para deteccion de punto en geocerca
DepositoGeocercaMapping -> mapeo deposito a geocercas por jerarquia
```

- Solo `Driver` usa SoftDeletes
- `Truck` y `TruckHistory` tienen metodos `bulkInsert()` optimizados
- `Limite` y `Exceso` extraen lat/lng de un campo string `UBICACION` via accessors

### Servicios Externos Integrados

- **Boltrack API** (`gestiondeflota.boltrack.net/integracionapi`)
  - Ubicacion GPS de todos los vehiculos (`ultimaubicaciontodos`)
  - Reportes RP131 (excesos/limites) y RP022 (conduccion fuera horario)
  - Autenticacion por token en header
- **BD Externa** (conexion `external_db` en database.php)
  - Tabla `bajada_argus` para resultados de cruce Argus
  - Tabla `logistica_transporte` para ScoreCard
  - Escritura dual de conduccion_fuera_horario

### Procesos Complejos / No Obvios

- **ArgusController::processFiles()**: Carga todos los trucks en memoria, construye un indice por patente con ventanas de tiempo (inicio/fin), luego recorre las alarmas Argus en chunks de 500 comparando hora_alarma contra las ventanas. Tambien actualiza la BD externa en chunks con sentencias CASE de SQL.
- **Lock File Progress Tracking**: Los jobs de importacion crean archivos JSON en `storage/app/locks/` con progreso (processed_records, total_records, estimated_minutes). `JobStatusChecker` los lee para mostrar progreso en la UI.
- **ProcessExcesoFile**: Maneja archivos muy grandes (hasta 2GB RAM). Detecta tamano del archivo y usa procesamiento linea-a-linea con batches de 100 para archivos >5MB.
- **FileImportService**: Optimiza importacion por motor de BD: MySQL usa `LOAD DATA INFILE`, PostgreSQL usa `COPY`, SQLite cae a PHP puro.
- **DeliveryCalculatorService**: Calculo jerarquico de progreso: CIUDADES (25%) + CBN (25%) + TRACK AND TRACE (30%) + DOCKS (20%). Ajusta timezone UTC-4.
- **Geocercas**: Importadas desde Excel, almacenadas como arrays de coordenadas. Deteccion por ray-casting con cache de 1 hora.

### Puntos de Atencion / Deuda Tecnica

- **ArgusController** tiene logica de negocio muy pesada directamente en el controlador (deberia estar en un service)
- **ProcessExcesoFile** requiere hasta 2GB de RAM - posible punto de falla en servidores con recursos limitados
- **ReportController** y **TruckTrackingApiController** estan vacios (stubs sin implementar)
- **TrackingConfigRequest** y **ReportFilterRequest** tienen `authorize() return false` y reglas vacias - no funcionales
- **BoltrackUpdateController** tiene un token hardcodeado en el codigo (`bltrck2021_454fd3d`)
- Conexion a BD externa se usa en multiples controladores sin una capa de abstraccion unificada
- `$guarded = ""` (string vacio en vez de array) en el modelo Matrix - funciona pero es incorrecto segun convencion Laravel
- No hay tests de integracion para los flujos principales (solo tests de auth generados por Breeze)
