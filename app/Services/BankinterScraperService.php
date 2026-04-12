<?php

namespace App\Services;

use App\Models\CategoriaGastos;
use App\Models\CategoriaIngresos;
use App\Models\DiarioCaja;
use App\Models\Gastos;
use App\Models\Ingresos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class BankinterScraperService
{
    private string $scraperDir;
    private string $storageDir;

    public function __construct()
    {
        $this->scraperDir = base_path('scripts/bankinter');
        $this->storageDir = storage_path('app/bankinter');

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    // =========================================================================
    // CONFIGURACION DE CUENTAS
    // =========================================================================

    public function obtenerConfigCuenta(string $alias): ?array
    {
        // 1) Fuente principal: tabla bankinter_credentials (gestionada desde el CRM)
        try {
            if (Schema::hasTable('bankinter_credentials')) {
                $cred = \App\Models\BankinterCredential::where('enabled', true)
                    ->where('alias', $alias)
                    ->first();

                if ($cred && !empty($cred->user) && !empty($cred->password)) {
                    return [
                        'user' => $cred->user,
                        'password' => $cred->password,
                        'iban' => $cred->iban,
                        'bank_id' => $cred->bank_id ?? 1,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Bankinter] Fallback a config: error leyendo bankinter_credentials', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Fallback: config/.env (retrocompatibilidad)
        $cuentas = config('services.bankinter.cuentas', []);

        if (!empty($cuentas) && isset($cuentas[$alias])) {
            return $cuentas[$alias];
        }

        if ($alias === 'default' || empty($cuentas)) {
            $user = config('services.bankinter.user');
            $password = config('services.bankinter.password');

            if ($user && $password) {
                return [
                    'user' => $user,
                    'password' => $password,
                    'iban' => config('services.bankinter.iban'),
                    'bank_id' => 1,
                ];
            }
        }

        return null;
    }

    public function listarCuentas(): array
    {
        // 1) Fuente principal: tabla bankinter_credentials
        try {
            if (Schema::hasTable('bankinter_credentials')) {
                $aliases = \App\Models\BankinterCredential::where('enabled', true)
                    ->orderBy('alias')
                    ->pluck('alias')
                    ->all();

                if (!empty($aliases)) {
                    return $aliases;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Bankinter] Fallback a config: error listando bankinter_credentials', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Fallback: config/.env (retrocompatibilidad)
        $cuentas = config('services.bankinter.cuentas', []);

        if (!empty($cuentas)) {
            // Solo devolver cuentas que tienen credenciales configuradas
            return array_keys(array_filter($cuentas, fn($c) => !empty($c['user']) && !empty($c['password'])));
        }

        if (config('services.bankinter.user')) {
            return ['default'];
        }

        return [];
    }

    // =========================================================================
    // BACKUP ANTES DE IMPORTAR
    // =========================================================================

    /**
     * Genera un backup ligero de la tesoreria actual (JSON comprimido).
     * Se sobreescribe cada dia — solo mantiene el ultimo estado.
     *
     * @return string|null  Ruta del backup o null si fallo
     */
    public function crearBackupTesoreria(): ?string
    {
        $backupDir = storage_path('app/bankinter/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            $backup = [
                'timestamp' => now()->toDateTimeString(),
                'ingresos_count' => Ingresos::count(),
                'gastos_count' => Gastos::count(),
                'diario_count' => DiarioCaja::count(),
                'hashes_count' => DB::table('hash_movimientos')->count(),
                'ingresos' => DB::table('ingresos')
                    ->select('id', 'categoria_id', 'bank_id', 'title', 'quantity', 'date', 'estado_id')
                    ->get()
                    ->toArray(),
                'gastos' => DB::table('gastos')
                    ->select('id', 'categoria_id', 'bank_id', 'title', 'quantity', 'date', 'estado_id')
                    ->whereNull('deleted_at')
                    ->get()
                    ->toArray(),
                'diario_caja' => DB::table('diario_caja')
                    ->select('id', 'gasto_id', 'ingreso_id', 'cuenta_id', 'asiento_contable', 'tipo', 'date', 'concepto', 'debe', 'haber')
                    ->get()
                    ->toArray(),
                'hashes' => DB::table('hash_movimientos')
                    ->select('id', 'hash', 'diario_caja_id')
                    ->get()
                    ->toArray(),
            ];

            // Sobreescribir el backup diario (un solo archivo, no acumula)
            $backupPath = $backupDir . '/tesoreria_backup.json.gz';

            $json = json_encode($backup, JSON_UNESCAPED_UNICODE);
            $compressed = gzencode($json, 9); // Maxima compresion

            file_put_contents($backupPath, $compressed);

            $sizeMB = round(strlen($compressed) / 1024 / 1024, 2);
            Log::info("[Bankinter] Backup tesoreria creado: {$backupPath} ({$sizeMB} MB)");

            return $backupPath;

        } catch (\Exception $e) {
            Log::error('[Bankinter] Error creando backup tesoreria', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Restaurar backup de tesoreria desde archivo JSON comprimido.
     * PELIGROSO: borra todos los datos actuales y los reemplaza.
     */
    public function restaurarBackupTesoreria(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            Log::error("[Bankinter] Backup no encontrado: {$backupPath}");
            return false;
        }

        try {
            $compressed = file_get_contents($backupPath);
            $json = gzdecode($compressed);
            $backup = json_decode($json, true);

            if (!$backup || !isset($backup['ingresos'])) {
                Log::error('[Bankinter] Backup corrupto o formato invalido');
                return false;
            }

            DB::transaction(function () use ($backup) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('diario_caja')->truncate();
                DB::table('ingresos')->truncate();
                DB::table('gastos')->truncate();
                DB::table('hash_movimientos')->truncate();

                foreach (array_chunk($backup['ingresos'], 500) as $chunk) {
                    DB::table('ingresos')->insert(array_map(fn($r) => (array)$r, $chunk));
                }
                foreach (array_chunk($backup['gastos'], 500) as $chunk) {
                    DB::table('gastos')->insert(array_map(fn($r) => (array)$r, $chunk));
                }
                foreach (array_chunk($backup['diario_caja'], 500) as $chunk) {
                    DB::table('diario_caja')->insert(array_map(fn($r) => (array)$r, $chunk));
                }
                foreach (array_chunk($backup['hashes'], 500) as $chunk) {
                    DB::table('hash_movimientos')->insert(array_map(fn($r) => (array)$r, $chunk));
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            });

            Log::info('[Bankinter] Backup restaurado correctamente', [
                'ingresos' => count($backup['ingresos']),
                'gastos' => count($backup['gastos']),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[Bankinter] Error restaurando backup', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // =========================================================================
    // DESCARGA (SCRAPER)
    // =========================================================================

    public function descargarMovimientos(string $cuentaAlias = 'default', ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $config = $this->obtenerConfigCuenta($cuentaAlias);

        if (!$config || empty($config['user']) || empty($config['password'])) {
            Log::error('[Bankinter] Credenciales no configuradas', ['cuenta' => $cuentaAlias]);
            return [
                'success' => false, 'file' => null,
                'error' => "Credenciales no configuradas para cuenta '{$cuentaAlias}'",
                'cuenta' => $cuentaAlias,
            ];
        }

        // Verificar dependencias npm
        if (!is_dir($this->scraperDir . '/node_modules')) {
            Log::info('[Bankinter] Instalando dependencias npm...');
            $this->runCommand("cd \"{$this->scraperDir}\" && npm install 2>&1");
            $this->runCommand("cd \"{$this->scraperDir}\" && npx playwright install chromium 2>&1");
        }

        $cmd = 'node ' . escapeshellarg($this->scraperDir . '/bankinter-scraper.js');
        $cmd .= ' --headless';
        $cmd .= ' --cuenta ' . escapeshellarg($cuentaAlias);
        $cmd .= ' --output-dir ' . escapeshellarg($this->storageDir);

        // [SEC-06] Solo pasar las variables estrictamente necesarias al proceso hijo
        $envVars = [
            'BANKINTER_USER' => $config['user'],
            'BANKINTER_PASSWORD' => $config['password'],
            'BANKINTER_CUENTA_ALIAS' => $cuentaAlias,
            'PATH' => getenv('PATH') ?: '',
            'HOME' => getenv('HOME') ?: getenv('USERPROFILE') ?: '',
            'APPDATA' => getenv('APPDATA') ?: '',
            'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: '',
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
        ];

        if (!empty($config['iban'])) {
            $envVars['BANKINTER_IBAN'] = $config['iban'];
        }

        $anthropicKey = config('services.anthropic.api_key');
        if ($anthropicKey) {
            $envVars['ANTHROPIC_API_KEY'] = $anthropicKey;
        }

        // [SEC-07] No loguear credenciales
        Log::info('[Bankinter] Ejecutando scraper...', [
            'cuenta' => $cuentaAlias,
        ]);

        $output = $this->runCommand($cmd, $envVars, 120);

        $result = $this->parseScraperOutput($output);
        $result['cuenta'] = $cuentaAlias;

        if ($result['success']) {
            Log::info('[Bankinter] Descarga exitosa', ['cuenta' => $cuentaAlias, 'file' => $result['file']]);
        } else {
            // [SEC-07] Sanitizar output antes de loguear
            $safeError = $this->sanitizeLogOutput($result['error'] ?? 'Error desconocido');
            Log::error('[Bankinter] Error en descarga', ['cuenta' => $cuentaAlias, 'error' => $safeError]);
        }

        return $result;
    }

    // =========================================================================
    // IMPORTACION (EXCEL → BD)
    // =========================================================================

    /**
     * Procesar Excel e importar movimientos a la BD.
     *
     * Correciones de la auditoria:
     * - [DUP-01] Contador de ocurrencias para transacciones identicas
     * - [DUP-02] number_format para hash determinista
     * - [OMIT-01] Deteccion dinamica de la fila de cabecera
     * - [OMIT-02] Manejo explicito de filas con importe 0
     * - [OMIT-04] Validacion de columnas minimas
     * - [RACE-01] File lock por cuenta + transaccion por fila
     * - [RACE-02] Asiento contable con lock
     */
    public function procesarExcel(string $filePath, int $bankId = 1): array
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => "Archivo no encontrado: {$filePath}"];
        }

        // [RACE-01] Lock exclusivo por cuenta para evitar procesamiento concurrente
        $lockFile = storage_path("app/bankinter/.lock_import_bank{$bankId}");
        $lockFp = fopen($lockFile, 'w');
        if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
            fclose($lockFp);
            return ['success' => false, 'error' => "Otra importacion en curso para bank_id={$bankId}"];
        }

        Log::info('[Bankinter] Procesando Excel', ['file' => $filePath, 'bank_id' => $bankId]);

        // Asegurar que el directorio temporal de Excel existe (evita "mkdir(): File exists" en Windows)
        $excelTempDir = storage_path('framework/cache/laravel-excel');
        if (!is_dir($excelTempDir)) {
            @mkdir($excelTempDir, 0755, true);
        }

        try {
            $data = Excel::toArray([], $filePath);
            $rows = $data[0] ?? [];
        } catch (\Exception $e) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return ['success' => false, 'error' => 'Error al leer Excel: ' . $e->getMessage()];
        }

        if (empty($rows)) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return ['success' => false, 'error' => 'El archivo Excel esta vacio'];
        }

        // [OMIT-01] Detectar dinamicamente la fila de cabecera
        $dataStartIndex = $this->detectarInicioData($rows);
        if ($dataStartIndex === null) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return ['success' => false, 'error' => 'No se encontro la cabecera del Excel (Fecha contable)'];
        }

        // [COL-MAP] Detectar columnas dinamicamente desde la fila de cabecera.
        // Bankinter puede cambiar el formato añadiendo/quitando columnas (p.ej. REF.12, REF.16).
        // Buscamos los nombres de columna en la fila anterior al inicio de datos.
        $headerRowIndex = $dataStartIndex - 1;
        $headerRow = $rows[$headerRowIndex] ?? [];
        $colMap = $this->detectarColumnasDesdeHeader($headerRow);
        Log::info('[Bankinter] Mapa de columnas detectado', $colMap);

        if ($colMap['debe'] === null || $colMap['haber'] === null || $colMap['saldo'] === null) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return ['success' => false, 'error' => 'No se encontraron las columnas DEBE/HABER/SALDO en la cabecera del Excel. Columnas detectadas: ' . json_encode($colMap)];
        }

        // Filtrar filas de datos validas
        $filteredRows = [];
        foreach ($rows as $index => $row) {
            if ($index < $dataStartIndex) continue;
            if (!isset($row[0]) || empty($row[0])) continue;
            if (!is_numeric($row[0])) continue;
            // Validar que la fila tiene suficientes columnas para llegar al saldo
            $minCols = max($colMap['debe'], $colMap['haber'], $colMap['saldo']) + 1;
            if (count($row) < $minCols) {
                Log::warning('[Bankinter] Fila con pocas columnas, saltando', [
                    'fila' => $index, 'columnas' => count($row), 'min_requerido' => $minCols,
                ]);
                continue;
            }
            $filteredRows[] = $row;
        }

        // Ordenar por fecha
        usort($filteredRows, fn($a, $b) => (float)$a[0] <=> (float)$b[0]);

        $procesados = 0;
        $duplicados = 0;
        $errores = 0;
        $ingresosCreados = 0;
        $gastosCreados = 0;
        $hashesHuerfanosEliminados = 0;
        $filasVacias = 0;
        $filasAnterioresFecha = 0;
        $detalleErrores = [];

        // [FECHA-MIN] Fecha minima de importacion. Movimientos anteriores se saltan.
        // Util para no pisar la tesoreria que ya esta cuadrada manualmente.
        $fechaMinima = config('services.bankinter.import_desde');
        if ($fechaMinima) {
            try {
                $fechaMinima = Carbon::parse($fechaMinima);
                Log::info('[Bankinter] Fecha minima de importacion: ' . $fechaMinima->format('Y-m-d'));
            } catch (\Exception $e) {
                Log::warning('[Bankinter] BANKINTER_IMPORT_DESDE invalida, se ignora: ' . config('services.bankinter.import_desde'));
                $fechaMinima = null;
            }
        }

        // [DUP-01] Contador de ocurrencias para transacciones identicas en el mismo archivo
        $hashOccurrences = [];

        foreach ($filteredRows as $index => $row) {
            // Parsear fecha
            try {
                $fechaContable = Carbon::createFromFormat(
                    'Y-m-d',
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[0])->format('Y-m-d')
                );
            } catch (\Exception $e) {
                $errores++;
                $detalleErrores[] = ['fila' => $index, 'error' => 'Fecha invalida: ' . ($row[0] ?? 'null')];
                continue;
            }

            // [FECHA-MIN] Saltar movimientos anteriores a la fecha minima
            if ($fechaMinima && $fechaContable->lt($fechaMinima)) {
                $filasAnterioresFecha++;
                continue;
            }

            $descripcion = trim((string)($row[$colMap['descripcion']] ?? ''));
            $debe = (float)($row[$colMap['debe']] ?? 0);
            $haber = (float)($row[$colMap['haber']] ?? 0);
            $saldo = (float)($row[$colMap['saldo']] ?? 0);

            // Extraer referencia bancaria (Ref.16) si existe en el mapeo de columnas
            $referenciaBancaria = null;
            if (isset($colMap['ref16']) && $colMap['ref16'] !== null) {
                $ref16Raw = trim((string)($row[$colMap['ref16']] ?? ''));
                // Limpiar prefijo "Ref.16: " si viene
                $referenciaBancaria = preg_replace('/^Ref\.?\s*16:?\s*/i', '', $ref16Raw);
                $referenciaBancaria = trim($referenciaBancaria) ?: null;
            }

            // [OMIT-02] Fila con importe 0 en ambos lados: registrar y saltar
            if ($haber == 0 && $debe == 0) {
                $filasVacias++;
                Log::debug('[Bankinter] Fila con debe=0 y haber=0, saltando', [
                    'fila' => $index, 'fecha' => $fechaContable->format('Y-m-d'), 'descripcion' => $descripcion,
                ]);
                continue;
            }

            // [DUP-02] Hash determinista con formato fijo de decimales
            $baseHash = $fechaContable->format('Y-m-d')
                . '|' . $descripcion
                . '|' . number_format($debe, 2, '.', '')
                . '|' . number_format($haber, 2, '.', '')
                . '|' . number_format($saldo, 2, '.', '');

            // [DUP-01] Contador de ocurrencia para transacciones identicas
            if (!isset($hashOccurrences[$baseHash])) {
                $hashOccurrences[$baseHash] = 0;
            }
            $hashOccurrences[$baseHash]++;
            $occurrence = $hashOccurrences[$baseHash];

            $hash = md5($baseHash . '|#' . $occurrence);

            // [RACE-01] Verificar duplicado dentro de transaccion con lock
            try {
                $resultado = DB::transaction(function () use (
                    $hash, $fechaContable, $descripcion, $debe, $haber, $saldo,
                    $bankId, &$duplicados, &$hashesHuerfanosEliminados,
                    &$ingresosCreados, &$gastosCreados
                ) {
                    // Lock para evitar race condition
                    $existingHash = DB::table('hash_movimientos')
                        ->where('hash', $hash)
                        ->lockForUpdate()
                        ->first();

                    if ($existingHash) {
                        // Verificar si el registro original existe
                        $registroExiste = ($haber > 0 && Ingresos::where('date', $fechaContable->format('Y-m-d'))
                                ->where('title', $descripcion)
                                ->where('quantity', $haber)
                                ->where('bank_id', $bankId)
                                ->exists())
                            || ($debe != 0 && Gastos::where('date', $fechaContable->format('Y-m-d'))
                                ->where('title', $descripcion)
                                ->where('quantity', $debe)
                                ->where('bank_id', $bankId)
                                ->exists());

                        if ($registroExiste) {
                            $duplicados++;
                            return 'duplicado';
                        }

                        // Hash huerfano
                        DB::table('hash_movimientos')->where('id', $existingHash->id)->delete();
                        $hashesHuerfanosEliminados++;
                    }

                    $categoriaIngreso = CategoriaIngresos::first();
                    $categoriaGasto = CategoriaGastos::first();
                    $diarioCajaId = null;

                    // Crear ingreso
                    if ($haber > 0) {
                        $ingresoData = [
                            'categoria_id' => $categoriaIngreso->id ?? 1,
                            'bank_id' => $bankId,
                            'title' => $descripcion,
                            'quantity' => $haber,
                            'date' => $fechaContable,
                            'estado_id' => 1,
                        ];

                        // Guardar referencia bancaria si existe la columna
                        if ($referenciaBancaria && \Illuminate\Support\Facades\Schema::hasColumn('ingresos', 'referencia_bancaria')) {
                            $ingresoData['referencia_bancaria'] = $referenciaBancaria;
                        }

                        // Auto-match: intentar vincular con una reserva por importe y canal
                        $reservaMatch = $this->intentarMatchReserva($descripcion, $haber, $fechaContable, $referenciaBancaria);
                        if ($reservaMatch && \Illuminate\Support\Facades\Schema::hasColumn('ingresos', 'reserva_id')) {
                            $ingresoData['reserva_id'] = $reservaMatch;

                            // Auto-marcar factura como cobrada si la reserva tiene factura pendiente
                            try {
                                $factura = \App\Models\Invoices::where('reserva_id', $reservaMatch)
                                    ->whereIn('invoice_status_id', [1, 3, 5])
                                    ->first();
                                if ($factura) {
                                    $factura->update([
                                        'invoice_status_id' => 6, // cobrada
                                        'fecha_cobro' => $fechaContable,
                                    ]);
                                    Log::info('[Bankinter] Factura auto-marcada como cobrada', [
                                        'factura_id' => $factura->id,
                                        'reference' => $factura->reference,
                                        'reserva_id' => $reservaMatch,
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('[Bankinter] Error auto-marcando factura', ['error' => $e->getMessage()]);
                            }
                        }

                        $ingreso = Ingresos::create($ingresoData);

                        $diario = DiarioCaja::create([
                            'asiento_contable' => $this->generarAsientoContableSafe(),
                            'cuenta_id' => 1,
                            'ingreso_id' => $ingreso->id,
                            'date' => $fechaContable,
                            'concepto' => $descripcion,
                            'haber' => $haber,
                            'tipo' => 'ingreso',
                            'estado_id' => 1,
                        ]);
                        $diarioCajaId = $diario->id;
                        $ingresosCreados++;
                    }

                    // Crear gasto
                    if ($debe != 0) {
                        $gasto = Gastos::create([
                            'categoria_id' => $categoriaGasto->id ?? 1,
                            'bank_id' => $bankId,
                            'title' => $descripcion,
                            'quantity' => $debe,
                            'date' => $fechaContable,
                            'estado_id' => 1,
                        ]);

                        $diario = DiarioCaja::create([
                            'asiento_contable' => $this->generarAsientoContableSafe(),
                            'cuenta_id' => 1,
                            'gasto_id' => $gasto->id,
                            'date' => $fechaContable,
                            'concepto' => $descripcion,
                            'debe' => $debe,
                            'tipo' => 'gasto',
                            'estado_id' => 1,
                        ]);
                        $diarioCajaId = $diario->id;
                        $gastosCreados++;
                    }

                    // Guardar hash
                    $insertHash = [
                        'hash' => $hash,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if (Schema::hasColumn('hash_movimientos', 'diario_caja_id') && $diarioCajaId) {
                        $insertHash['diario_caja_id'] = $diarioCajaId;
                    }
                    DB::table('hash_movimientos')->insert($insertHash);

                    return 'procesado';
                });

                if ($resultado === 'procesado') {
                    $procesados++;
                }

            } catch (\Exception $e) {
                $errores++;
                $detalleErrores[] = [
                    'fila' => $index,
                    'error' => $e->getMessage(),
                    'descripcion' => $descripcion,
                ];
                Log::error('[Bankinter] Error procesando fila', [
                    'fila' => $index, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Liberar lock
        flock($lockFp, LOCK_UN);
        fclose($lockFp);

        $resumen = [
            'success' => true,
            'total_filas' => count($filteredRows),
            'procesados' => $procesados,
            'duplicados' => $duplicados,
            'errores' => $errores,
            'ingresos_creados' => $ingresosCreados,
            'gastos_creados' => $gastosCreados,
            'hashes_huerfanos_eliminados' => $hashesHuerfanosEliminados,
            'filas_importe_cero' => $filasVacias,
            'filas_anteriores_fecha_minima' => $filasAnterioresFecha,
        ];

        if (!empty($detalleErrores)) {
            $resumen['detalle_errores'] = $detalleErrores;
        }

        Log::info('[Bankinter] Excel procesado', $resumen);

        return $resumen;
    }

    // =========================================================================
    // UTILIDADES INTERNAS
    // =========================================================================

    /**
     * [OMIT-01] Detectar dinamicamente la fila donde empiezan los datos.
     * Busca una fila con "fecha contable" y devuelve el indice de la siguiente fila.
     */
    /**
     * [COL-MAP] Detectar indices de columna dinamicamente desde la fila de cabecera.
     * Busca por nombre normalizado (sin tildes, case insensitive).
     * Si Bankinter añade/quita columnas intermedias (como REF.12, REF.16), esto lo detecta.
     */
    private function detectarColumnasDesdeHeader(array $headerRow): array
    {
        $map = [
            'fecha_contable' => null,
            'fecha_valor' => null,
            'descripcion' => 5, // fallback si no se encuentra
            'debe' => null,
            'haber' => null,
            'saldo' => null,
            'ref16' => null,
        ];

        foreach ($headerRow as $index => $cell) {
            if ($cell === null) continue;
            $norm = strtolower(trim(str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
                ['a', 'e', 'i', 'o', 'u', 'n'],
                (string) $cell
            )));

            if (str_contains($norm, 'fecha') && str_contains($norm, 'contable')) {
                $map['fecha_contable'] = $index;
            } elseif (str_contains($norm, 'fecha') && str_contains($norm, 'valor')) {
                $map['fecha_valor'] = $index;
            } elseif ($norm === 'descripcion' || str_contains($norm, 'descripci')) {
                $map['descripcion'] = $index;
            } elseif ($norm === 'debe') {
                $map['debe'] = $index;
            } elseif ($norm === 'haber') {
                $map['haber'] = $index;
            } elseif ($norm === 'saldo') {
                $map['saldo'] = $index;
            } elseif (str_contains($norm, 'ref') && str_contains($norm, '16')) {
                $map['ref16'] = $index;
            }
        }

        return $map;
    }

    private function detectarInicioData(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (!isset($row[0])) continue;
            $cellValue = strtolower(trim((string)$row[0]));
            if (str_contains($cellValue, 'fecha') && str_contains($cellValue, 'contable')) {
                return $index + 1;
            }
        }

        // Fallback: buscar la primera fila con un valor numerico en columna 0 (fecha Excel)
        foreach ($rows as $index => $row) {
            if ($index < 3) continue; // Saltar las primeras filas seguro
            if (isset($row[0]) && is_numeric($row[0]) && (float)$row[0] > 40000) {
                // Un serial de fecha Excel > 40000 equivale a ~2009+
                return $index;
            }
        }

        return null;
    }

    /**
     * [RACE-02] Generar asiento contable con lock para evitar duplicados.
     */
    private function generarAsientoContableSafe(): string
    {
        $year = date('Y');

        // Lock advisory para evitar numeros duplicados
        $lockResult = DB::select("SELECT GET_LOCK('asiento_contable_{$year}', 5) as locked");
        if (!$lockResult || !$lockResult[0]->locked) {
            Log::warning('[Bankinter] No se pudo obtener lock para asiento contable');
        }

        $ultimo = DiarioCaja::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $num = 1;
        if ($ultimo && $ultimo->asiento_contable) {
            $parts = explode('/', $ultimo->asiento_contable);
            $num = (int)($parts[0] ?? 0) + 1;
        }

        $asiento = str_pad($num, 4, '0', STR_PAD_LEFT) . '/' . $year;

        DB::select("SELECT RELEASE_LOCK('asiento_contable_{$year}')");

        return $asiento;
    }

    /**
     * [SEC-06] Ejecutar comando con solo las variables de entorno necesarias.
     */
    private function runCommand(string $cmd, array $envVars = [], int $timeoutSeconds = 60): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $envVars);

        if (!is_resource($process)) {
            return json_encode(['success' => false, 'error' => 'No se pudo ejecutar el comando']);
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $stderr = '';
        $startTime = time();

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) break;
            if ((time() - $startTime) > $timeoutSeconds) {
                proc_terminate($process, 9);
                return json_encode(['success' => false, 'error' => "Timeout ({$timeoutSeconds}s)"]);
            }

            $out = fread($pipes[1], 8192);
            $err = fread($pipes[2], 8192);
            if ($out) $output .= $out;
            if ($err) $stderr .= $err;

            if (!$out && !$err) usleep(100000);
        }

        $output .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $output . $stderr;
    }

    private function parseScraperOutput(string $output): array
    {
        if (preg_match('/\[RESULT_JSON\](.*?)\[\/RESULT_JSON\]/s', $output, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // [SEC-07] Sanitizar output antes de devolver
        return [
            'success' => false,
            'file' => null,
            'error' => 'No se pudo parsear resultado del scraper. Output: ' . $this->sanitizeLogOutput(substr($output, -500)),
        ];
    }

    /**
     * [SEC-07] Eliminar posibles credenciales del output antes de loguearlo.
     */
    private function sanitizeLogOutput(string $output): string
    {
        $patterns = [
            '/BANKINTER_USER=\S+/i' => 'BANKINTER_USER=***',
            '/BANKINTER_PASSWORD=\S+/i' => 'BANKINTER_PASSWORD=***',
            '/API_KEY=\S+/i' => 'API_KEY=***',
            '/x-api-key:\s*\S+/i' => 'x-api-key: ***',
            '/password["\s:=]+\S+/i' => 'password=***',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $output);
    }

    /**
     * Intentar vincular un ingreso bancario con una reserva.
     * Estrategia:
     *   1. Detectar canal (booking, airbnb, stripe, agoda) por la descripción
     *   2. Buscar reservas del mismo canal con importe compatible (±20% por comisiones)
     *   3. Priorizar por fecha más cercana a la fecha del movimiento
     *   4. Si hay UN solo match razonable, vincularlo
     *
     * @return int|null ID de la reserva vinculada, o null si no se pudo matchear
     */
    private function intentarMatchReserva(string $descripcion, float $importe, $fecha, ?string $referencia): ?int
    {
        $descLower = strtolower($descripcion);

        // Detectar canal del pago
        $origen = null;
        if (str_contains($descLower, 'booking.com') || str_contains($descLower, 'booking')) {
            $origen = 'BookingCom';
        } elseif (str_contains($descLower, 'airbnb')) {
            $origen = 'Airbnb';
        } elseif (str_contains($descLower, 'stripe')) {
            $origen = 'Web';
        } elseif (str_contains($descLower, 'agoda')) {
            $origen = 'Agoda';
        }

        if (!$origen) return null;

        $fechaCarbon = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        // Buscar reservas del mismo canal, no canceladas, sin cobro vinculado aún
        $query = \App\Models\Reserva::where('origen', $origen)
            ->where('estado_id', '!=', 4) // no canceladas
            ->where('fecha_entrada', '>=', $fechaCarbon->copy()->subDays(30))
            ->where('fecha_entrada', '<=', $fechaCarbon->copy()->addDays(15));

        // Si ya tenemos columna reserva_id, excluir las ya vinculadas
        if (\Illuminate\Support\Facades\Schema::hasColumn('ingresos', 'reserva_id')) {
            $yaVinculadas = Ingresos::whereNotNull('reserva_id')->pluck('reserva_id')->toArray();
            if (!empty($yaVinculadas)) {
                $query->whereNotIn('id', $yaVinculadas);
            }
        }

        $candidatas = $query->get(['id', 'codigo_reserva', 'precio', 'fecha_entrada', 'origen']);

        if ($candidatas->isEmpty()) return null;

        // Para Booking/Airbnb, el importe del banco es NET (después de comisión ~15-18%)
        // Precio reserva * (1 - comision) ≈ importe banco
        // Buscamos reservas donde precio * 0.78 <= importe <= precio * 0.92
        $matches = [];
        foreach ($candidatas as $reserva) {
            $precio = (float) $reserva->precio;
            if ($precio <= 0) continue;

            if ($origen === 'Web') {
                // Stripe: importe exacto (sin comisión de plataforma)
                if (abs($precio - $importe) < 1.0) {
                    $matches[] = $reserva;
                }
            } else {
                // Booking/Airbnb/Agoda: importe neto después de comisión (12-22%)
                $minEsperado = $precio * 0.78;
                $maxEsperado = $precio * 0.92;
                if ($importe >= $minEsperado && $importe <= $maxEsperado) {
                    $matches[] = $reserva;
                }
            }
        }

        // Si hay exactamente 1 match, vincularlo automáticamente
        if (count($matches) === 1) {
            Log::info('[Bankinter] Auto-match reserva', [
                'ingreso' => $importe,
                'reserva_id' => $matches[0]->id,
                'codigo' => $matches[0]->codigo_reserva,
                'precio_reserva' => $matches[0]->precio,
                'origen' => $origen,
            ]);
            return $matches[0]->id;
        }

        // Si hay múltiples, elegir la de fecha de entrada más cercana al movimiento
        if (count($matches) > 1) {
            usort($matches, function ($a, $b) use ($fechaCarbon) {
                $diffA = abs(Carbon::parse($a->fecha_entrada)->diffInDays($fechaCarbon));
                $diffB = abs(Carbon::parse($b->fecha_entrada)->diffInDays($fechaCarbon));
                return $diffA <=> $diffB;
            });
            Log::info('[Bankinter] Auto-match reserva (mejor de ' . count($matches) . ')', [
                'ingreso' => $importe,
                'reserva_id' => $matches[0]->id,
                'codigo' => $matches[0]->codigo_reserva,
            ]);
            return $matches[0]->id;
        }

        return null; // Sin match, se deja para conciliación manual
    }
}
