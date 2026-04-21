<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Huesped;

/**
 * MirDataValidator
 * =================
 * Validacion determinista (Nivel 1) de los datos de una Reserva antes de
 * enviarlos al MIR. No hace llamadas externas ni consulta IA: solo reglas
 * sintacticas y tablas estaticas (CP, provincias, algoritmo DNI/NIE, etc.).
 *
 * Proposito: cazar datos mal grabados por cliente/recepcion ANTES de enviar
 * el SOAP al MIR, para evitar emails de rechazo del Ministerio y tener una
 * sugerencia de correccion.
 *
 * Casos reales que motivan este servicio (abril 2026):
 *   - Cliente PEDRO JOSE FERREIRO FRANCO: direccion gallega
 *     ("RUA MONTE DOS CHAOS, BAR DE ABALO") pero codigo_postal=18017 (Granada)
 *     y provincia="BAR DE ABALO" (que no es una provincia).
 *   - Cliente MONTSERRAT DEL OLMO CABEZUDO: apellido1="DEL" (solo preposicion),
 *     apellido2="OLMO CABEZUDO".
 *
 * Salida de validar(): array de issues.
 *   [
 *     'severity' => 'error'|'warning',
 *     'entidad'  => 'cliente'|'huesped'|'reserva',
 *     'entidad_id' => int,
 *     'campo'    => string,
 *     'mensaje'  => string,
 *     'sugerencia' => string|null,
 *   ]
 *
 * Convencion: severity=error bloquea envio, warning se loggea y se deja pasar.
 *
 * NO integrado todavia en MIRService. Invocable desde tinker para smoke test:
 *   \App\Services\MirDataValidator::selfTest();
 */
class MirDataValidator
{
    /**
     * Preposiciones/articulos que NO pueden aparecer solos como primer apellido.
     * Todos normalizados (upper, sin acentos). La comparacion se hace tras
     * normalizar el valor del apellido.
     */
    private array $preposicionesApellido = [
        'DEL', 'DE', 'DE LA', 'DE LOS', 'DE LAS',
        'LA', 'LOS', 'LAS', 'EL', 'LE', 'LES',
        'VAN', 'VON', 'D', 'L',
        'DA', 'DO', 'DOS', 'DAS',
        'DI', 'DE LE', 'DU', 'DELLA', 'DELLO',
    ];

    /**
     * Mapping prefijo CP (2 digitos) -> nombre oficial de provincia + aliases
     * que hemos visto en datos reales. La comparacion se hace con valores
     * normalizados (upper, sin acentos, sin espacios).
     *
     * El primer elemento del array de aliases es el nombre OFICIAL y se usa
     * como sugerencia cuando la provincia declarada no coincide.
     */
    private array $provincias = [
        '01' => ['Alava',    ['ALAVA', 'ARABA', 'ARABAALAVA', 'ALAVAARABA']],
        '02' => ['Albacete', ['ALBACETE']],
        '03' => ['Alicante', ['ALICANTE', 'ALACANT']],
        '04' => ['Almeria',  ['ALMERIA']],
        '05' => ['Avila',    ['AVILA']],
        '06' => ['Badajoz',  ['BADAJOZ']],
        '07' => ['Baleares', ['BALEARES', 'ILLESBALEARS', 'ISLASBALEARES']],
        '08' => ['Barcelona',['BARCELONA']],
        '09' => ['Burgos',   ['BURGOS']],
        '10' => ['Caceres',  ['CACERES']],
        '11' => ['Cadiz',    ['CADIZ']],
        '12' => ['Castellon',['CASTELLON', 'CASTELLO', 'CASTELLONDELAPLANA']],
        '13' => ['Ciudad Real', ['CIUDADREAL']],
        '14' => ['Cordoba',  ['CORDOBA']],
        '15' => ['A Coruna', ['ACORUNA', 'LACORUNA', 'CORUNA', 'LACORUNHA', 'ACORUNHA']],
        '16' => ['Cuenca',   ['CUENCA']],
        '17' => ['Girona',   ['GIRONA', 'GERONA']],
        '18' => ['Granada',  ['GRANADA']],
        '19' => ['Guadalajara', ['GUADALAJARA']],
        '20' => ['Gipuzkoa', ['GIPUZKOA', 'GUIPUZCOA']],
        '21' => ['Huelva',   ['HUELVA']],
        '22' => ['Huesca',   ['HUESCA']],
        '23' => ['Jaen',     ['JAEN']],
        '24' => ['Leon',     ['LEON']],
        '25' => ['Lleida',   ['LLEIDA', 'LERIDA']],
        '26' => ['La Rioja', ['LARIOJA', 'RIOJA']],
        '27' => ['Lugo',     ['LUGO']],
        '28' => ['Madrid',   ['MADRID']],
        '29' => ['Malaga',   ['MALAGA']],
        '30' => ['Murcia',   ['MURCIA']],
        '31' => ['Navarra',  ['NAVARRA', 'NAFARROA']],
        '32' => ['Ourense',  ['OURENSE', 'ORENSE']],
        '33' => ['Asturias', ['ASTURIAS', 'PRINCIPADODEASTURIAS']],
        '34' => ['Palencia', ['PALENCIA']],
        '35' => ['Las Palmas', ['LASPALMAS', 'LASPALMASDEGRANCANARIA']],
        '36' => ['Pontevedra', ['PONTEVEDRA']],
        '37' => ['Salamanca', ['SALAMANCA']],
        '38' => ['Santa Cruz de Tenerife', ['SANTACRUZDETENERIFE', 'TENERIFE', 'SCTENERIFE']],
        '39' => ['Cantabria', ['CANTABRIA', 'SANTANDER']],
        '40' => ['Segovia',  ['SEGOVIA']],
        '41' => ['Sevilla',  ['SEVILLA']],
        '42' => ['Soria',    ['SORIA']],
        '43' => ['Tarragona',['TARRAGONA']],
        '44' => ['Teruel',   ['TERUEL']],
        '45' => ['Toledo',   ['TOLEDO']],
        '46' => ['Valencia', ['VALENCIA', 'VALENCIAVALENCIA']],
        '47' => ['Valladolid', ['VALLADOLID']],
        '48' => ['Bizkaia',  ['BIZKAIA', 'VIZCAYA']],
        '49' => ['Zamora',   ['ZAMORA']],
        '50' => ['Zaragoza', ['ZARAGOZA']],
        '51' => ['Ceuta',    ['CEUTA']],
        '52' => ['Melilla',  ['MELILLA']],
    ];

    /**
     * Rangos de CP (inclusive) por prefijo provincial.
     * Duplicado (deliberado) del SpanishPostalCodeValidator para que este
     * servicio sea autocontenido. Si cambia uno, sincronizar el otro.
     */
    private array $provinceRanges = [
        '01' => [1001, 1528],   '02' => [2001, 2712],   '03' => [3001, 3828],
        '04' => [4001, 4867],   '05' => [5001, 5697],   '06' => [6001, 6939],
        '07' => [7001, 7860],   '08' => [8001, 8980],   '09' => [9001, 9613],
        '10' => [10001, 10990], '11' => [11001, 11690], '12' => [12001, 12609],
        '13' => [13001, 13779], '14' => [14001, 14960], '15' => [15001, 15994],
        '16' => [16001, 16891], '17' => [17001, 17869], '18' => [18001, 18890],
        '19' => [19001, 19292], '20' => [20001, 20810], '21' => [21001, 21892],
        '22' => [22001, 22889], '23' => [23001, 23790], '24' => [24001, 24994],
        '25' => [25001, 25795], '26' => [26001, 26589], '27' => [27001, 27892],
        '28' => [28001, 28991], '29' => [29001, 29793], '30' => [30001, 30896],
        '31' => [31001, 31891], '32' => [32001, 32890], '33' => [33001, 33993],
        '34' => [34001, 34889], '35' => [35001, 35660], '36' => [36001, 36992],
        '37' => [37001, 37893], '38' => [38001, 38916], '39' => [39001, 39880],
        '40' => [40001, 40593], '41' => [41001, 41940], '42' => [42001, 42368],
        '43' => [43001, 43886], '44' => [44001, 44479], '45' => [45001, 45919],
        '46' => [46001, 46988], '47' => [47001, 47883], '48' => [48001, 48860],
        '49' => [49001, 49883], '50' => [50001, 50840], '51' => [51001, 51005],
        '52' => [52001, 52006],
    ];

    private string $dniLetras = 'TRWAGMYFPDXBNJZSQVHLCKE';

    private array $paisEspanaAliases = ['ES', 'ESP', 'SPAIN', 'ESPANA', 'ESPAÑA'];

    /**
     * Punto de entrada. Valida Reserva + su Cliente + sus Huespedes.
     */
    public function validar(Reserva $reserva): array
    {
        $issues = [];

        $cliente = $reserva->cliente;
        $paisClienteFallback = '';
        if ($cliente) {
            $issues = array_merge($issues, $this->validarPersona($cliente, 'cliente', $cliente->id));
            // [2026-04-20] Nacionalidad del cliente como fallback para huespedes
            // sin pais rellenado (caso tipico: familia viajando, misma nacionalidad).
            $paisClienteFallback = trim((string) ($cliente->nacionalidad ?? $cliente->pais ?? ''));
        }

        // Huespedes
        $huespedes = Huesped::where('reserva_id', $reserva->id)->get();
        foreach ($huespedes as $h) {
            $issues = array_merge($issues, $this->validarPersona($h, 'huesped', $h->id, $paisClienteFallback));
        }

        return $issues;
    }

    /**
     * Valida una "persona" (Cliente o Huesped). Unifica el acceso a campos
     * porque los dos modelos usan nombres distintos (apellido1/primer_apellido).
     */
    private function validarPersona($persona, string $entidad, int $entidadId, string $paisFallback = ''): array
    {
        $issues = [];

        // Normalizar acceso a campos entre Cliente y Huesped
        if ($entidad === 'cliente') {
            $nombre = $persona->nombre ?? '';
            $ap1 = $persona->apellido1 ?? '';
            $ap2 = $persona->apellido2 ?? '';
            $dni = $persona->num_identificacion ?? '';
            $tipoDoc = $persona->tipo_documento ?? ($persona->tipo_documento_str ?? '');
            $pais = $persona->nacionalidad ?? ($persona->nacionalidadCode ?? '');
            $campoAp1 = 'apellido1';
            $campoAp2 = 'apellido2';
            $campoDni = 'num_identificacion';
        } else {
            $nombre = $persona->nombre ?? '';
            $ap1 = $persona->primer_apellido ?? '';
            $ap2 = $persona->segundo_apellido ?? '';
            $dni = $persona->numero_identificacion ?? '';
            $tipoDoc = $persona->tipo_documento ?? ($persona->tipo_documento_str ?? '');
            $pais = $persona->pais ?? ($persona->nacionalidad ?? ($persona->pais_iso3 ?? ''));
            $campoAp1 = 'primer_apellido';
            $campoAp2 = 'segundo_apellido';
            $campoDni = 'numero_identificacion';
        }

        // [2026-04-20] Si el huesped no tiene pais rellenado, heredar del
        // cliente principal. Es casi siempre correcto (familia viajando).
        if (trim((string) $pais) === '' && $paisFallback !== '') {
            $pais = $paisFallback;
        }

        $direccion = $persona->direccion ?? '';
        $cp = $persona->codigo_postal ?? '';
        $provincia = $persona->provincia ?? '';

        // F. Nombre vacio
        $issues = array_merge($issues, $this->validarNombreVacio($nombre, $entidad, $entidadId));

        // D. Apellido vacio o numerico
        $issues = array_merge($issues, $this->validarApellidoNoVacio($ap1, $entidad, $entidadId, $campoAp1));

        // A. Apellidos: preposiciones solas + primer apellido muy corto con ap2 con espacios
        $issues = array_merge($issues, $this->validarApellidoPreposicion($ap1, $ap2, $entidad, $entidadId, $campoAp1, $campoAp2));

        // B. CP <-> provincia coherente + C. CP dentro de rango
        $issues = array_merge($issues, $this->validarCodigoPostalProvincia($cp, $provincia, $pais, $entidad, $entidadId));

        // E. DNI/NIE formato + digito de control
        $issues = array_merge($issues, $this->validarDocumento($dni, $tipoDoc, $entidad, $entidadId, $campoDni, $pais));

        // G. Campos MIR con caracteres raros o longitud excesiva
        $issues = array_merge($issues, $this->validarCaracteresYLongitud($nombre, 'nombre', 60, $entidad, $entidadId));
        $issues = array_merge($issues, $this->validarCaracteresYLongitud($ap1, $campoAp1, 60, $entidad, $entidadId));
        $issues = array_merge($issues, $this->validarCaracteresYLongitud($ap2, $campoAp2, 60, $entidad, $entidadId));
        $issues = array_merge($issues, $this->validarCaracteresYLongitud($direccion, 'direccion', 100, $entidad, $entidadId));

        return $issues;
    }

    // -----------------------------------------------------------------
    // Validadores individuales
    // -----------------------------------------------------------------

    private function validarNombreVacio(string $nombre, string $entidad, int $id): array
    {
        if (trim($nombre) === '') {
            return [$this->issue('error', $entidad, $id, 'nombre', 'El nombre esta vacio', null)];
        }
        return [];
    }

    private function validarApellidoNoVacio(string $ap1, string $entidad, int $id, string $campo): array
    {
        $val = trim($ap1);
        if ($val === '') {
            return [$this->issue('error', $entidad, $id, $campo, 'El primer apellido esta vacio', null)];
        }
        if (preg_match('/^\d+$/', $val)) {
            return [$this->issue('error', $entidad, $id, $campo, "El primer apellido es solo numerico: '{$val}'", null)];
        }
        return [];
    }

    /**
     * A. Apellido1 no puede ser solo preposicion. Ademas: si ap1 es muy corto
     * (<= 2 chars no-prep) y ap2 contiene espacios, probablemente el partido
     * fue mal (p.ej. "DEL" "OLMO CABEZUDO" -> deberia ser "DEL OLMO" "CABEZUDO").
     */
    private function validarApellidoPreposicion(string $ap1, string $ap2, string $entidad, int $id, string $campoAp1, string $campoAp2): array
    {
        $issues = [];
        $norm = $this->normalizar($ap1);
        if ($norm === '') {
            return $issues; // ya lo cazo validarApellidoNoVacio
        }

        if (in_array($norm, array_map([$this, 'normalizar'], $this->preposicionesApellido), true)) {
            // Sugerencia: si ap2 tiene espacios, proponer "ap1 + primera palabra de ap2"
            $sug = null;
            $ap2Trim = trim($ap2);
            if ($ap2Trim !== '' && str_contains($ap2Trim, ' ')) {
                $parts = preg_split('/\s+/', $ap2Trim);
                $primeraPalabra = array_shift($parts);
                $restoAp2 = implode(' ', $parts);
                $sug = trim($ap1) . ' ' . $primeraPalabra . ' | ' . $campoAp2 . '=' . $restoAp2;
            }
            $issues[] = $this->issue(
                'error',
                $entidad,
                $id,
                $campoAp1,
                "El primer apellido es solo una preposicion/articulo: '{$ap1}'",
                $sug
            );
            return $issues;
        }

        // Warning: ap1 muy corto y ap2 con espacios (posible mal partido)
        if (mb_strlen(trim($ap1)) <= 2 && trim($ap2) !== '' && str_contains(trim($ap2), ' ')) {
            $issues[] = $this->issue(
                'warning',
                $entidad,
                $id,
                $campoAp1,
                "Primer apellido muy corto ('{$ap1}') y segundo contiene espacios ('{$ap2}'): posible mal partido",
                null
            );
        }

        return $issues;
    }

    /**
     * B + C. Valida CP, cruza con provincia declarada y rango provincial.
     * Solo aplica a Espana.
     */
    private function validarCodigoPostalProvincia(string $cp, string $provincia, string $pais, string $entidad, int $id): array
    {
        $issues = [];

        // Si el pais esta informado y no es Espana, no validamos (RD 933/2021
        // no requiere CP para viajeros extranjeros — solo dir completa, localidad, pais)
        if ($pais !== '') {
            $paisUpper = strtoupper(trim($pais));
            if (!in_array($paisUpper, $this->paisEspanaAliases, true)) {
                return $issues;
            }
        }

        $cp = trim($cp);
        $prov = trim($provincia);

        if ($cp === '') {
            // Otros campos ya cazan esto; no es asunto del CP<->prov
            return $issues;
        }

        // [2026-04-20] Heuristica "probablemente extranjero" cuando no hay pais
        // rellenado. Si el CP contiene letras o tiene formato claramente no
        // espanol, no bloqueamos — el RD 933/2021 no exige CP para extranjeros.
        if ($pais === '') {
            // CP con letras (ej. 'L4n0r5' canadiense, 'SW1A 1AA' UK, etc)
            if (!preg_match('/^\d+$/', $cp)) {
                $issues[] = $this->issue('warning', $entidad, $id, 'codigo_postal',
                    "Codigo postal '{$cp}' con letras (aparentemente extranjero, pais no rellenado)", null);
                return $issues;
            }
            // CP numerico de 5 digitos pero con prefijo provincial inexistente
            // (ej. '75031' checo, '90000' marroqui, '20000' tunecino)
            if (preg_match('/^\d{5}$/', $cp)) {
                $pref = substr($cp, 0, 2);
                if (!isset($this->provincias[$pref])) {
                    $issues[] = $this->issue('warning', $entidad, $id, 'codigo_postal',
                        "Codigo postal '{$cp}' con prefijo '{$pref}' no espanol (aparentemente extranjero, pais no rellenado)", null);
                    return $issues;
                }
            }
            // Si tiene longitud distinta de 5 sin letras, tambien asumimos extranjero
            if (!preg_match('/^\d{5}$/', $cp)) {
                $issues[] = $this->issue('warning', $entidad, $id, 'codigo_postal',
                    "Codigo postal '{$cp}' con longitud no espanola (aparentemente extranjero)", null);
                return $issues;
            }
        }

        if (!preg_match('/^\d{5}$/', $cp)) {
            $issues[] = $this->issue('error', $entidad, $id, 'codigo_postal', "Codigo postal '{$cp}' no tiene 5 digitos numericos", null);
            return $issues;
        }

        // [FIX 2026-04-19] Delegar al SpanishPostalCodeValidator que ya tiene
        // la blacklist empirica de CPs rechazados por MIR. Si ahi dice error,
        // lo devolvemos como bloqueante aqui tambien.
        try {
            $spv = app(\App\Services\SpanishPostalCodeValidator::class);
            $reasonSpv = $spv->getReason($cp, $pais);
            if ($reasonSpv !== null) {
                $issues[] = $this->issue('error', $entidad, $id, 'codigo_postal', $reasonSpv, null);
                // Continuamos con la coherencia provincia abajo — puede haber warnings adicionales
            }
        } catch (\Throwable $e) { /* ignora, continuamos con la validacion local */ }

        $prefix = substr($cp, 0, 2);
        if (!isset($this->provincias[$prefix])) {
            $issues[] = $this->issue('error', $entidad, $id, 'codigo_postal', "Codigo postal '{$cp}' tiene prefijo provincial inexistente ({$prefix})", null);
            return $issues;
        }

        [$oficial, $aliases] = $this->provincias[$prefix];

        // C. Rango provincial
        if (isset($this->provinceRanges[$prefix])) {
            $numeric = (int) $cp;
            [$min, $max] = $this->provinceRanges[$prefix];
            if ($numeric < $min || $numeric > $max) {
                $issues[] = $this->issue(
                    'error',
                    $entidad,
                    $id,
                    'codigo_postal',
                    "Codigo postal '{$cp}' fuera del rango de {$oficial} ({$min}-{$max})",
                    null
                );
            }
        }

        // B. Coherencia con provincia declarada
        if ($prov === '') {
            $issues[] = $this->issue(
                'warning',
                $entidad,
                $id,
                'provincia',
                "Provincia vacia con CP '{$cp}' (prefijo {$prefix})",
                $oficial
            );
        } else {
            $provNorm = $this->normalizar($prov);
            if (!in_array($provNorm, $aliases, true)) {
                $issues[] = $this->issue(
                    'error',
                    $entidad,
                    $id,
                    'provincia',
                    "Provincia declarada '{$prov}' no coincide con el prefijo CP {$prefix} ({$oficial})",
                    $oficial
                );
            }
        }

        return $issues;
    }

    /**
     * E. DNI/NIE formato + digito de control.
     * Si tipoDoc == pasaporte (o variantes), solo validamos que no este vacio.
     */
    private function validarDocumento(string $doc, string $tipoDoc, string $entidad, int $id, string $campo, string $pais = ''): array
    {
        $doc = strtoupper(trim($doc));
        $tipo = strtoupper(trim((string) $tipoDoc));

        if ($doc === '') {
            return [$this->issue('error', $entidad, $id, $campo, 'Documento identificativo vacio', null)];
        }

        // [2026-04-20] Si la nacionalidad es extranjera, ignoramos el tipo
        // declarado (la IA a veces clasifica mal los documentos extranjeros
        // como 'DNI') y tratamos cualquier doc como pasaporte/documento
        // extranjero — solo chequeo blando.
        $paisUpper = strtoupper(trim($pais));
        $esExtranjero = $paisUpper !== '' && !in_array($paisUpper, $this->paisEspanaAliases, true);
        if ($esExtranjero) {
            if (!preg_match('/^[A-Z0-9]{4,20}$/', $doc)) {
                return [$this->issue('warning', $entidad, $id, $campo, "Documento extranjero con formato raro: '{$doc}'", null)];
            }
            return [];
        }

        // Detectar pasaporte por tipo o forma (no empieza por X/Y/Z ni son 8 digitos)
        $esPasaporte = str_contains($tipo, 'PASAPORTE') || str_contains($tipo, 'PASSPORT') || $tipo === 'P';
        if ($esPasaporte) {
            // Pasaporte: solo chequeo blando
            if (!preg_match('/^[A-Z0-9]{5,15}$/', $doc)) {
                return [$this->issue('warning', $entidad, $id, $campo, "Pasaporte con formato raro: '{$doc}'", null)];
            }
            return [];
        }

        // DNI: 8 digitos + letra
        if (preg_match('/^(\d{8})([A-Z])$/', $doc, $m)) {
            $numero = (int) $m[1];
            $letra = $m[2];
            $esperada = $this->dniLetras[$numero % 23];
            if ($letra !== $esperada) {
                return [$this->issue(
                    'error',
                    $entidad,
                    $id,
                    $campo,
                    "DNI '{$doc}': la letra de control '{$letra}' no coincide (esperada '{$esperada}')",
                    str_pad((string) $numero, 8, '0', STR_PAD_LEFT) . $esperada
                )];
            }
            return [];
        }

        // NIE: [XYZ] + 7 digitos + letra
        if (preg_match('/^([XYZ])(\d{7})([A-Z])$/', $doc, $m)) {
            $prefijoMap = ['X' => '0', 'Y' => '1', 'Z' => '2'];
            $numero = (int) ($prefijoMap[$m[1]] . $m[2]);
            $letra = $m[3];
            $esperada = $this->dniLetras[$numero % 23];
            if ($letra !== $esperada) {
                return [$this->issue(
                    'error',
                    $entidad,
                    $id,
                    $campo,
                    "NIE '{$doc}': la letra de control '{$letra}' no coincide (esperada '{$esperada}')",
                    $m[1] . $m[2] . $esperada
                )];
            }
            return [];
        }

        // No encaja en DNI/NIE: si el tipoDoc sugiere DNI/NIE, error; si no, warning
        if (str_contains($tipo, 'DNI') || str_contains($tipo, 'NIE')) {
            return [$this->issue('error', $entidad, $id, $campo, "Documento '{$doc}' no tiene formato DNI (8 digitos + letra) ni NIE (X/Y/Z + 7 digitos + letra)", null)];
        }

        // TODO: soportar mas tipos de documento oficiales del MIR (carnet de
        // conducir europeo, documentos extranjeros, etc.) cuando tengamos la
        // tabla completa. De momento warning para no bloquear.
        return [$this->issue('warning', $entidad, $id, $campo, "Documento '{$doc}' con formato no reconocido y tipo '{$tipo}'", null)];
    }

    /**
     * G. Caracteres extraños o longitud excesiva. Warning, no error.
     */
    private function validarCaracteresYLongitud(string $valor, string $campo, int $maxLen, string $entidad, int $id): array
    {
        $issues = [];
        if ($valor === '') {
            return $issues;
        }

        // Caracteres de control (\x00-\x1F) excepto nada (ni tabs ni newlines en estos campos)
        if (preg_match('/[\x00-\x1F\x7F]/', $valor)) {
            $issues[] = $this->issue('warning', $entidad, $id, $campo, "Campo '{$campo}' contiene caracteres de control", null);
        }

        // Emojis y simbolos unicode fuera del BMP basico "razonable".
        // Rango aproximado: emojis estan en U+1F300 en adelante (surrogate pairs en UTF-16,
        // pero en PHP/UTF-8 los detectamos con un regex de codepoints altos).
        if (preg_match('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}]/u', $valor)) {
            $issues[] = $this->issue('warning', $entidad, $id, $campo, "Campo '{$campo}' contiene emojis o simbolos no mapeables", null);
        }

        if (mb_strlen($valor) > $maxLen) {
            $issues[] = $this->issue(
                'warning',
                $entidad,
                $id,
                $campo,
                "Campo '{$campo}' longitud " . mb_strlen($valor) . " supera el maximo recomendado {$maxLen}",
                mb_substr($valor, 0, $maxLen)
            );
        }

        return $issues;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Normaliza para comparaciones case/acento-insensibles y sin espacios.
     */
    private function normalizar(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        // Quitar acentos
        $map = [
            'Á'=>'A','À'=>'A','Ä'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A',
            'É'=>'E','È'=>'E','Ë'=>'E','Ê'=>'E',
            'Í'=>'I','Ì'=>'I','Ï'=>'I','Î'=>'I',
            'Ó'=>'O','Ò'=>'O','Ö'=>'O','Ô'=>'O','Õ'=>'O',
            'Ú'=>'U','Ù'=>'U','Ü'=>'U','Û'=>'U',
            'Ñ'=>'N','Ç'=>'C',
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a','å'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n','ç'=>'c',
        ];
        $s = strtr($s, $map);
        $s = mb_strtoupper($s);
        // Quitar espacios para comparacion
        $s = preg_replace('/\s+/', '', $s);
        return $s;
    }

    private function issue(string $severity, string $entidad, int $id, string $campo, string $mensaje, ?string $sugerencia): array
    {
        return [
            'severity'   => $severity,
            'entidad'    => $entidad,
            'entidad_id' => $id,
            'campo'      => $campo,
            'mensaje'    => $mensaje,
            'sugerencia' => $sugerencia,
        ];
    }

    // =================================================================
    // Self-test (ejecutable desde tinker)
    // =================================================================

    /**
     * Smoke test con casos reales. No usa la BD: construye fakes en memoria.
     * Ejecutar:
     *   php artisan tinker
     *   >>> \App\Services\MirDataValidator::selfTest();
     */
    public static function selfTest(): void
    {
        $v = new self();

        echo "=== MirDataValidator selfTest ===\n\n";

        // ------ Test 1: Montserrat DEL OLMO CABEZUDO (ap1='DEL') ------
        $cliMontse = new Cliente();
        $cliMontse->id = 9001;
        $cliMontse->nombre = 'MONTSERRAT';
        $cliMontse->apellido1 = 'DEL';
        $cliMontse->apellido2 = 'OLMO CABEZUDO';
        $cliMontse->num_identificacion = '12345678Z';
        $cliMontse->tipo_documento = 'DNI';
        $cliMontse->nacionalidad = 'ESP';
        $cliMontse->codigo_postal = '28001';
        $cliMontse->provincia = 'Madrid';
        $cliMontse->direccion = 'C/ Inventada 1';

        $issues1 = $v->validarPersona($cliMontse, 'cliente', $cliMontse->id);
        echo "Test Montserrat (ap1='DEL'): " . count($issues1) . " issue(s)\n";
        self::printIssues($issues1);
        $hayErrorPrep = false;
        foreach ($issues1 as $i) {
            if ($i['severity'] === 'error' && str_contains($i['mensaje'], 'preposicion')) {
                $hayErrorPrep = true;
            }
        }
        echo $hayErrorPrep ? "  [OK] caza preposicion sola\n\n" : "  [FAIL] no se detecto preposicion\n\n";

        // ------ Test 2: Pedro Jose Ferreiro Franco (CP 18017 + prov BAR DE ABALO) ------
        $cliPedro = new Cliente();
        $cliPedro->id = 9002;
        $cliPedro->nombre = 'PEDRO JOSE';
        $cliPedro->apellido1 = 'FERREIRO';
        $cliPedro->apellido2 = 'FRANCO';
        $cliPedro->num_identificacion = '12345678Z';
        $cliPedro->tipo_documento = 'DNI';
        $cliPedro->nacionalidad = 'ESP';
        $cliPedro->codigo_postal = '18017';
        $cliPedro->provincia = 'BAR DE ABALO';
        $cliPedro->direccion = 'RUA MONTE DOS CHAOS, BAR DE ABALO';

        $issues2 = $v->validarPersona($cliPedro, 'cliente', $cliPedro->id);
        echo "Test Pedro Jose (CP 18017 / prov BAR DE ABALO): " . count($issues2) . " issue(s)\n";
        self::printIssues($issues2);
        $hayErrorProv = false;
        foreach ($issues2 as $i) {
            if ($i['severity'] === 'error' && $i['campo'] === 'provincia') {
                $hayErrorProv = true;
            }
        }
        echo $hayErrorProv ? "  [OK] caza incoherencia CP<->provincia\n\n" : "  [FAIL] no se detecto provincia mal\n\n";

        // ------ Test 3: Cliente OK ------
        $cliOk = new Cliente();
        $cliOk->id = 9003;
        $cliOk->nombre = 'ANA';
        $cliOk->apellido1 = 'GARCIA';
        $cliOk->apellido2 = 'LOPEZ';
        // DNI 00000000T es valido (0 % 23 = 0 -> T)
        $cliOk->num_identificacion = '00000000T';
        $cliOk->tipo_documento = 'DNI';
        $cliOk->nacionalidad = 'ESP';
        $cliOk->codigo_postal = '11204';
        $cliOk->provincia = 'Cadiz';
        $cliOk->direccion = 'Avda. Belgica 1';

        $issues3 = $v->validarPersona($cliOk, 'cliente', $cliOk->id);
        echo "Test Cliente OK: " . count($issues3) . " issue(s)\n";
        self::printIssues($issues3);
        $soloWarnings = true;
        foreach ($issues3 as $i) {
            if ($i['severity'] === 'error') { $soloWarnings = false; }
        }
        echo $soloWarnings ? "  [OK] sin errores\n\n" : "  [FAIL] hay error inesperado\n\n";

        // ------ Test 4: DNI con letra mal ------
        $cliDniMal = new Cliente();
        $cliDniMal->id = 9004;
        $cliDniMal->nombre = 'JUAN';
        $cliDniMal->apellido1 = 'PEREZ';
        $cliDniMal->apellido2 = 'SANCHEZ';
        $cliDniMal->num_identificacion = '12345678A'; // la buena es Z
        $cliDniMal->tipo_documento = 'DNI';
        $cliDniMal->nacionalidad = 'ESP';
        $cliDniMal->codigo_postal = '28001';
        $cliDniMal->provincia = 'Madrid';
        $cliDniMal->direccion = 'C/ Falsa 1';
        $issues4 = $v->validarPersona($cliDniMal, 'cliente', $cliDniMal->id);
        echo "Test DNI letra mal (12345678A): " . count($issues4) . " issue(s)\n";
        self::printIssues($issues4);
        $hayErrorDni = false;
        foreach ($issues4 as $i) {
            if ($i['severity'] === 'error' && str_contains($i['mensaje'], 'DNI')) {
                $hayErrorDni = true;
            }
        }
        echo $hayErrorDni ? "  [OK] caza letra de control mal\n\n" : "  [FAIL] no se detecto DNI mal\n\n";

        echo "=== Fin selfTest ===\n";
    }

    private static function printIssues(array $issues): void
    {
        foreach ($issues as $i) {
            $sug = $i['sugerencia'] !== null ? " (sugerencia: {$i['sugerencia']})" : '';
            echo "  [{$i['severity']}] {$i['entidad']}#{$i['entidad_id']}.{$i['campo']}: {$i['mensaje']}{$sug}\n";
        }
    }
}
