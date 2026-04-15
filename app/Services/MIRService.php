<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MIRService
{
    /**
     * Obtener la configuración de MIR desde settings
     */
    private function getConfig()
    {
        $config = [
            'codigo_arrendador' => Setting::get('mir_codigo_arrendador', Setting::get('mir_arrendador', '0000060524')),
            'codigo_establecimiento' => Setting::get('mir_codigo_establecimiento', '0000119330'),
            'usuario' => Setting::get('mir_usuario', 'B56927809WS'),
            'password' => Setting::get('mir_password', ''),
            'entorno' => Setting::get('mir_entorno', 'production'),
            'aplicacion' => Setting::get('mir_aplicacion', 'Hawkins Suite'),
        ];

        return $config;
    }

    /**
     * Obtener la URL del endpoint según el entorno
     */
    private function getEndpointUrl($entorno)
    {
        if ($entorno === 'production') {
            return 'https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion';
        }
        return 'https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion';
    }

    /**
     * Generar el XML interno para un parte de viajeros (PV).
     * Estructura según XSD altaParteHospedaje:
     * <ns2:peticion> -> <solicitud> -> <codigoEstablecimiento> + <comunicacion>[] -> <contrato> + <persona>[]
     */
    private function generarXMLReserva(Reserva $reserva, $codigoEstablecimiento)
    {
        $cliente = $reserva->cliente;

        if (!$cliente) {
            throw new \Exception('La reserva no tiene cliente asociado.');
        }

        // Validar que las fechas existan antes de parsear
        if (empty($reserva->fecha_entrada) || empty($reserva->fecha_salida)) {
            throw new \Exception('La reserva no tiene fechas de entrada/salida definidas.');
        }

        // Formatear fechas
        $fechaEntrada = \Carbon\Carbon::parse($reserva->fecha_entrada)->setTime(14, 0, 0);
        $fechaSalida = \Carbon\Carbon::parse($reserva->fecha_salida)->setTime(12, 0, 0);

        // Contador de personas: se calcula durante la generación del XML
        $personaCount = 1; // 1 = titular (siempre incluido)

        // Construir XML con namespace correcto
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<ns2:peticion xmlns:ns2="http://www.neg.hospedajes.mir.es/altaParteHospedaje">' . "\n";
        $xml .= '  <solicitud>' . "\n";
        $xml .= '    <codigoEstablecimiento>' . $this->esc($codigoEstablecimiento) . '</codigoEstablecimiento>' . "\n";
        $xml .= '    <comunicacion>' . "\n";

        // --- Contrato ---
        $xml .= '      <contrato>' . "\n";
        $xml .= '        <referencia>' . $this->esc($reserva->codigo_reserva) . '</referencia>' . "\n";
        $xml .= '        <fechaContrato>' . $fechaEntrada->format('Y-m-d') . '</fechaContrato>' . "\n";
        $xml .= '        <fechaEntrada>' . $fechaEntrada->format('Y-m-d\TH:i:s') . '</fechaEntrada>' . "\n";
        $xml .= '        <fechaSalida>' . $fechaSalida->format('Y-m-d\TH:i:s') . '</fechaSalida>' . "\n";
        $xml .= '        <numPersonas>__NUM_PERSONAS__</numPersonas>' . "\n";
        $xml .= '        <internet>' . ($this->esReservaOnline($reserva) ? 'true' : 'false') . '</internet>' . "\n";
        $xml .= '        <pago>' . "\n";
        $xml .= '          <tipoPago>' . $this->mapearTipoPago($reserva) . '</tipoPago>' . "\n";
        $xml .= '          <fechaPago>' . $fechaEntrada->format('Y-m-d') . '</fechaPago>' . "\n";
        $xml .= '        </pago>' . "\n";
        $xml .= '      </contrato>' . "\n";

        // --- Persona: Cliente principal (Titular) ---
        $dniCliente = $cliente->num_identificacion ?? null;
        if (empty($dniCliente)) {
            throw new \Exception('El cliente principal no tiene documento de identidad (num_identificacion). Es obligatorio para MIR.');
        }

        $xml .= $this->generarPersonaXML(
            rol: 'VI', // Viajero (PV solo admite rol VI)
            nombre: $cliente->nombre ?? '',
            apellido1: $cliente->apellido1 ?? '',
            apellido2: $cliente->apellido2 ?? '',
            tipoDocumento: $this->getTipoDocumentoMIR($dniCliente),
            numeroDocumento: $dniCliente,
            fechaNacimiento: $cliente->fecha_nacimiento,
            nacionalidad: $cliente->nacionalidad ?? 'ES',
            sexo: $cliente->sexo ?? $cliente->sexo_str ?? null,
            telefono: $cliente->telefono ?? $cliente->telefono_movil ?? '',
            correo: $cliente->email ?? $cliente->email_secundario ?? '',
            direccion: $cliente->direccion ?? '',
            codigoPostal: $cliente->codigo_postal ?? '',
            pais: $this->normalizarNacionalidad($cliente->nacionalidad ?? 'ES'),
            nombreMunicipio: $cliente->localidad ?? $cliente->municipio ?? '',
            numeroSoporte: $cliente->numero_soporte_documento ?? null,
            codigoMunicipio: $cliente->codigo_municipio_ine ?? null,
        );

        // --- Personas: Huéspedes adicionales ---
        // Excluir huéspedes con el mismo documento que el titular para evitar duplicados
        $documentosIncluidos = [strtoupper($dniCliente)];
        $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)->get();
        foreach ($huespedes as $huesped) {
            $apellido1 = $huesped->primer_apellido ?? $huesped->apellido1 ?? '';
            $apellido2 = $huesped->segundo_apellido ?? $huesped->apellido2 ?? '';

            if (empty($huesped->nombre) || empty($apellido1)) {
                Log::warning('Huésped con datos incompletos omitido del XML MIR', [
                    'huesped_id' => $huesped->id,
                ]);
                continue;
            }

            $dniHuesped = $huesped->numero_identificacion ?? null;
            if (empty($dniHuesped)) {
                throw new \Exception("El huésped {$huesped->nombre} {$apellido1} no tiene documento de identidad. Es obligatorio para MIR.");
            }

            // Evitar personas duplicadas (mismo documento)
            if (in_array(strtoupper($dniHuesped), $documentosIncluidos)) {
                Log::info('MIR: Huésped omitido por documento duplicado con titular', [
                    'huesped_id' => $huesped->id,
                    'documento' => $dniHuesped,
                ]);
                continue;
            }
            $documentosIncluidos[] = strtoupper($dniHuesped);
            $personaCount++;

            $xml .= $this->generarPersonaXML(
                rol: 'VI', // Viajero
                nombre: $huesped->nombre,
                apellido1: $apellido1,
                apellido2: $apellido2,
                tipoDocumento: $this->getTipoDocumentoMIR($dniHuesped),
                numeroDocumento: $dniHuesped,
                fechaNacimiento: $huesped->fecha_nacimiento,
                nacionalidad: $huesped->nacionalidad ?? 'ES',
                sexo: $huesped->sexo ?? $huesped->sexo_str ?? null,
                telefono: $huesped->telefono_movil ?? $huesped->telefono ?? '',
                correo: $huesped->email ?? '',
                direccion: $huesped->direccion ?? '',
                codigoPostal: $huesped->codigo_postal ?? '',
                pais: $this->normalizarNacionalidad($huesped->nacionalidad ?? 'ES'),
                nombreMunicipio: $huesped->localidad ?? $huesped->municipio ?? '',
                parentesco: $huesped->parentesco ?? null,
                numeroSoporte: $huesped->numero_soporte_documento ?? null,
                codigoMunicipio: $huesped->codigo_municipio_ine ?? null,
            );
        }

        $xml .= '    </comunicacion>' . "\n";
        $xml .= '  </solicitud>' . "\n";
        $xml .= '</ns2:peticion>';

        // Reemplazar placeholder con el número real de personas generadas
        $xml = str_replace('__NUM_PERSONAS__', (string) $personaCount, $xml);

        return $xml;
    }

    /**
     * Generar el bloque XML de una persona
     */
    private function generarPersonaXML(
        string $rol,
        string $nombre,
        string $apellido1,
        string $apellido2,
        string $tipoDocumento,
        string $numeroDocumento,
        ?string $fechaNacimiento,
        string $nacionalidad,
        ?string $sexo,
        string $telefono,
        string $correo,
        string $direccion,
        string $codigoPostal,
        string $pais,
        string $nombreMunicipio,
        ?string $parentesco = null,
        ?string $numeroSoporte = null,
        ?string $codigoMunicipio = null,
    ): string {
        $xml = '      <persona>' . "\n";
        $xml .= '        <rol>' . $this->esc($rol) . '</rol>' . "\n";
        $xml .= '        <nombre>' . $this->esc(mb_strtoupper($nombre)) . '</nombre>' . "\n";
        $xml .= '        <apellido1>' . $this->esc(mb_strtoupper($apellido1)) . '</apellido1>' . "\n";

        if (!empty($apellido2) && $apellido2 !== '.') {
            $xml .= '        <apellido2>' . $this->esc(mb_strtoupper($apellido2)) . '</apellido2>' . "\n";
        }

        $xml .= '        <tipoDocumento>' . $this->esc($tipoDocumento) . '</tipoDocumento>' . "\n";
        $xml .= '        <numeroDocumento>' . $this->esc(strtoupper($numeroDocumento)) . '</numeroDocumento>' . "\n";

        // Número de soporte del documento (obligatorio para NIF/NIE, va antes de fechaNacimiento)
        if (!empty($numeroSoporte)) {
            $xml .= '        <soporteDocumento>' . $this->esc(strtoupper($numeroSoporte)) . '</soporteDocumento>' . "\n";
        } elseif (in_array($tipoDocumento, ['NIF', 'NIE'])) {
            Log::warning('MIR: Falta número de soporte del documento para NIF/NIE', [
                'documento' => $numeroDocumento,
            ]);
            // No incluir placeholder - MIR lo rechazará y se verá el error claramente
        }

        if ($fechaNacimiento) {
            try {
                $xml .= '        <fechaNacimiento>' . \Carbon\Carbon::parse($fechaNacimiento)->format('Y-m-d') . '</fechaNacimiento>' . "\n";
            } catch (\Exception $e) {
                Log::warning('MIR: Fecha de nacimiento inválida, omitida', ['fecha' => $fechaNacimiento]);
            }
        }

        $nacionalidad3 = $this->normalizarNacionalidad($nacionalidad);
        $xml .= '        <nacionalidad>' . $nacionalidad3 . '</nacionalidad>' . "\n";
        $xml .= '        <sexo>' . $this->normalizarSexo($sexo) . '</sexo>' . "\n";

        // Dirección (obligatoria).
        // IMPORTANTE: el XSD de MIR tiene dos variantes segun el pais:
        //   - pais=ESP: permite cualquier combinacion de
        //     {direccionComplementaria, codigoMunicipio, nombreMunicipio, codigoPostal}
        //   - pais!=ESP (extranjero): EXIGE obligatoriamente <codigoPostal> antes del <pais>
        //     ("One of '{codigoPostal}' is expected").
        //
        // Si el cliente no tiene direccion completa, usamos fallback diferente
        // segun el caso para evitar el error 10118.
        $xml .= '        <direccion>' . "\n";
        $xml .= '          <direccion>' . $this->esc($direccion ?: 'NO FACILITADA') . '</direccion>' . "\n";

        $codMun = $codigoMunicipio ?: $this->obtenerCodigoMunicipioINE($nombreMunicipio, $codigoPostal, $pais);
        $tieneAlMenosUno = false;
        $tieneCodigoPostal = !empty($codigoPostal);

        if (!empty($codMun)) {
            $xml .= '          <codigoMunicipio>' . $this->esc($codMun) . '</codigoMunicipio>' . "\n";
            $tieneAlMenosUno = true;
        }
        if (!empty($nombreMunicipio)) {
            $xml .= '          <nombreMunicipio>' . $this->esc(mb_strtoupper($nombreMunicipio)) . '</nombreMunicipio>' . "\n";
            $tieneAlMenosUno = true;
        }
        if ($tieneCodigoPostal) {
            $xml .= '          <codigoPostal>' . $this->esc($codigoPostal) . '</codigoPostal>' . "\n";
            $tieneAlMenosUno = true;
        }

        // Fallbacks obligatorios para cumplir el XSD de MIR.
        // Para direcciones extranjeras, MIR exige SIEMPRE <codigoPostal>
        // (aunque haya municipio o nombreMunicipio). Lo forzamos si falta.
        if ($pais !== 'ESP' && !$tieneCodigoPostal) {
            // CP generico valido de 5 digitos cuando no lo conocemos.
            $xml .= '          <codigoPostal>00000</codigoPostal>' . "\n";
            $tieneAlMenosUno = true;
        } elseif (!$tieneAlMenosUno) {
            // Cliente espanol sin ningun dato de direccion: fallback neutro
            $xml .= '          <nombreMunicipio>NO FACILITADO</nombreMunicipio>' . "\n";
        }

        $xml .= '          <pais>' . $pais . '</pais>' . "\n";
        $xml .= '        </direccion>' . "\n";

        // Contacto (al menos teléfono o correo obligatorio)
        if (!empty($telefono)) {
            $telefonoLimpio = preg_replace('/[^\d+]/', '', $telefono);
            if (strlen($telefonoLimpio) > 0) {
                $xml .= '        <telefono>' . $this->esc(substr($telefonoLimpio, 0, 20)) . '</telefono>' . "\n";
            }
        }
        if (!empty($correo)) {
            $xml .= '        <correo>' . $this->esc($correo) . '</correo>' . "\n";
        }

        // Si no hay ni teléfono ni correo, poner un placeholder
        if (empty($telefono) && empty($correo)) {
            $xml .= '        <correo>nofacilitado@nofacilitado.com</correo>' . "\n";
        }

        // Parentesco (obligatorio para menores)
        if (!empty($parentesco)) {
            $xml .= '        <parentesco>' . $this->esc($parentesco) . '</parentesco>' . "\n";
        }

        $xml .= '      </persona>' . "\n";

        return $xml;
    }

    /**
     * Tipo de documento según formato MIR: NIF, NIE, PAS
     */
    private function getTipoDocumentoMIR($dni)
    {
        if (empty($dni)) return 'NIF';

        $dni = strtoupper(trim($dni));

        // NIE: empieza con X, Y o Z seguido de 7 dígitos y letra
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $dni)) {
            return 'NIE';
        }

        // NIF: 8 dígitos + letra
        if (preg_match('/^\d{8}[A-Z]$/', $dni)) {
            return 'NIF';
        }

        // Pasaporte para cualquier otro formato
        return 'PAS';
    }

    /**
     * Normalizar nacionalidad a ISO 3166-1 Alpha-3
     */
    private function normalizarNacionalidad($nacionalidad): string
    {
        if (empty($nacionalidad)) return 'ESP';

        $nac = strtoupper(trim($nacionalidad));

        // Ya es Alpha-3
        if (preg_match('/^[A-Z]{3}$/', $nac)) return $nac;

        // Mapa Alpha-2 → Alpha-3
        $mapa = [
            'ES' => 'ESP', 'FR' => 'FRA', 'GB' => 'GBR', 'DE' => 'DEU', 'IT' => 'ITA',
            'PT' => 'PRT', 'NL' => 'NLD', 'BE' => 'BEL', 'CH' => 'CHE', 'AT' => 'AUT',
            'US' => 'USA', 'MX' => 'MEX', 'AR' => 'ARG', 'BR' => 'BRA', 'CO' => 'COL',
            'CL' => 'CHL', 'PE' => 'PER', 'VE' => 'VEN', 'EC' => 'ECU', 'UY' => 'URY',
            'PL' => 'POL', 'RO' => 'ROU', 'RU' => 'RUS', 'UA' => 'UKR', 'CN' => 'CHN',
            'JP' => 'JPN', 'KR' => 'KOR', 'IN' => 'IND', 'MA' => 'MAR', 'DZ' => 'DZA',
            'SE' => 'SWE', 'NO' => 'NOR', 'DK' => 'DNK', 'FI' => 'FIN', 'IE' => 'IRL',
            'CZ' => 'CZE', 'HU' => 'HUN', 'GR' => 'GRC', 'BG' => 'BGR', 'HR' => 'HRV',
        ];

        if (isset($mapa[$nac])) return $mapa[$nac];

        // Texto
        if (stripos($nacionalidad, 'españa') !== false || stripos($nacionalidad, 'spain') !== false) return 'ESP';
        if (stripos($nacionalidad, 'franc') !== false) return 'FRA';
        if (stripos($nacionalidad, 'aleman') !== false || stripos($nacionalidad, 'german') !== false) return 'DEU';

        return 'ESP';
    }

    /**
     * Obtener código de municipio INE a partir de código postal y localidad.
     * Formato INE: 5 dígitos (2 provincia + 3 municipio).
     * Si el cliente/huésped tiene codigo_municipio_ine lo usa directamente.
     */
    private function obtenerCodigoMunicipioINE(?string $localidad, ?string $codigoPostal, string $pais): ?string
    {
        // Solo obligatorio para España
        if ($pais !== 'ESP') return null;

        // Mapa de localidades comunes → código INE
        $mapaLocalidades = [
            'ALGECIRAS' => '11004',
            'LA LINEA' => '11022', 'LA LÍNEA' => '11022', 'LA LINEA DE LA CONCEPCION' => '11022',
            'TARIFA' => '11035',
            'SAN ROQUE' => '11033',
            'LOS BARRIOS' => '11008',
            'CADIZ' => '11012', 'CÁDIZ' => '11012',
            'MALAGA' => '29067', 'MÁLAGA' => '29067',
            'SEVILLA' => '41091',
            'MADRID' => '28079',
            'BARCELONA' => '08019',
            'VALENCIA' => '46250',
            'GRANADA' => '18087',
            'MARBELLA' => '29069',
            'ESTEPONA' => '29051',
            'JEREZ' => '11020', 'JEREZ DE LA FRONTERA' => '11020',
            'CORDOBA' => '14021', 'CÓRDOBA' => '14021',
            'HUELVA' => '21041',
            'JAEN' => '23050', 'JAÉN' => '23050',
            'ALMERIA' => '04013', 'ALMERÍA' => '04013',
            'ZARAGOZA' => '50297',
            'BILBAO' => '48020',
            'PALMA' => '07040', 'PALMA DE MALLORCA' => '07040',
        ];

        if (!empty($localidad)) {
            $loc = mb_strtoupper(trim($localidad));
            if (isset($mapaLocalidades[$loc])) {
                return $mapaLocalidades[$loc];
            }
        }

        // Derivar provincia desde código postal (2 primeros dígitos)
        // y usar código genérico del municipio capital si no encontramos coincidencia exacta
        if (!empty($codigoPostal) && strlen($codigoPostal) === 5) {
            $provincia = substr($codigoPostal, 0, 2);
            // Retornar provincia + "001" como municipio genérico capital
            return $provincia . '001';
        }

        return null;
    }

    /**
     * Normalizar sexo: H (hombre), M (mujer)
     */
    private function normalizarSexo(?string $sexo): string
    {
        if (empty($sexo)) return 'H';

        $s = strtoupper(trim($sexo));

        if (in_array($s, ['H', 'HOMBRE', 'MALE', 'MASCULINO'])) return 'H';
        if (in_array($s, ['M', 'MUJER', 'FEMALE', 'FEMENINO', 'F'])) return 'M';

        // Si empieza con F, femenino
        if (str_starts_with($s, 'F')) return 'M';

        return 'H';
    }

    /**
     * Mapear tipo de pago según MIR
     */
    private function mapearTipoPago(Reserva $reserva): string
    {
        $origen = strtolower($reserva->origen ?? '');
        $tipoPago = strtolower($reserva->tipo_pago ?? '');

        if (str_contains($tipoPago, 'tarjeta') || str_contains($tipoPago, 'card')) return 'TARJT';
        if (str_contains($tipoPago, 'transfer')) return 'TRANS';
        if (str_contains($tipoPago, 'efectivo') || str_contains($tipoPago, 'cash')) return 'EFECT';
        if (str_contains($tipoPago, 'plataforma') || str_contains($tipoPago, 'platform')) return 'PLATF';

        // Si viene de una plataforma online (Booking, Airbnb), asumir plataforma
        if (str_contains($origen, 'booking') || str_contains($origen, 'airbnb') || str_contains($origen, 'channex')) {
            return 'PLATF';
        }

        // Por defecto, tarjeta
        return 'TARJT';
    }

    /**
     * Determinar si la reserva fue online
     */
    private function esReservaOnline(Reserva $reserva): bool
    {
        $origen = strtolower($reserva->origen ?? '');
        return str_contains($origen, 'booking') || str_contains($origen, 'airbnb')
            || str_contains($origen, 'web') || str_contains($origen, 'channex');
    }

    /**
     * Escapar HTML entities para XML
     */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Comprimir XML en ZIP y codificar en Base64
     */
    private function comprimirYCodificar($xml)
    {
        $tempBase = tempnam(sys_get_temp_dir(), 'mir_');
        if ($tempBase === false) {
            throw new \Exception('No se pudo crear archivo temporal para ZIP (disco lleno o sin permisos).');
        }
        $tempZip = $tempBase . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        // El nombre DEBE ser "solicitud.xml" según especificación MIR
        $zip->addFromString('solicitud.xml', $xml);
        $zip->close();

        $zipContent = file_get_contents($tempZip);
        $base64 = base64_encode($zipContent);

        @unlink($tempZip);

        return $base64;
    }

    /**
     * Construir el SOAP envelope completo
     */
    private function construirSOAPEnvelope(string $solicitudBase64, array $config, string $tipoOperacion = 'A'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.soap.servicios.hospedajes.mir.es/comunicacion">' . "\n";
        $xml .= '  <soapenv:Header/>' . "\n";
        $xml .= '  <soapenv:Body>' . "\n";
        $xml .= '    <com:comunicacionRequest>' . "\n";
        $xml .= '      <peticion>' . "\n";
        $xml .= '        <cabecera>' . "\n";
        $xml .= '          <codigoArrendador>' . $this->esc($config['codigo_arrendador']) . '</codigoArrendador>' . "\n";
        $xml .= '          <aplicacion>' . $this->esc($config['aplicacion']) . '</aplicacion>' . "\n";
        $xml .= '          <tipoOperacion>' . $this->esc($tipoOperacion) . '</tipoOperacion>' . "\n";
        $xml .= '          <tipoComunicacion>PV</tipoComunicacion>' . "\n";
        $xml .= '        </cabecera>' . "\n";
        $xml .= '        <solicitud>' . $solicitudBase64 . '</solicitud>' . "\n";
        $xml .= '      </peticion>' . "\n";
        $xml .= '    </com:comunicacionRequest>' . "\n";
        $xml .= '  </soapenv:Body>' . "\n";
        $xml .= '</soapenv:Envelope>';

        return $xml;
    }

    /**
     * Enviar reserva a MIR
     */
    public function enviarReserva(Reserva $reserva)
    {
        try {
            $config = $this->getConfig();

            if (empty($config['codigo_arrendador']) || empty($config['usuario']) || empty($config['password'])) {
                throw new \Exception('La configuración de MIR no está completa. Configure los datos en Configuración > MIR.');
            }

            // Cargar relaciones
            $reserva->load('apartamento.edificio', 'cliente');

            if (!$reserva->apartamento) {
                throw new \Exception('La reserva no tiene un apartamento asociado.');
            }

            // Obtener código de establecimiento
            $codigoEstablecimiento = $this->obtenerCodigoEstablecimiento($reserva, $config);

            if (empty($codigoEstablecimiento)) {
                throw new \Exception('No se pudo obtener el código de establecimiento.');
            }

            // Generar XML interno
            $xmlInterno = $this->generarXMLReserva($reserva, $codigoEstablecimiento);

            // Validar XML
            libxml_use_internal_errors(true);
            $xmlDoc = simplexml_load_string($xmlInterno);
            if ($xmlDoc === false) {
                $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
                libxml_clear_errors();
                throw new \Exception('XML mal formado: ' . implode('; ', $errors));
            }

            // Comprimir en ZIP y codificar en Base64
            $solicitudBase64 = $this->comprimirYCodificar($xmlInterno);

            // Construir SOAP envelope
            $requestXml = $this->construirSOAPEnvelope($solicitudBase64, $config);

            // Log en sandbox
            if ($config['entorno'] === 'sandbox') {
                $tempFile = storage_path('logs/mir_request_' . $reserva->id . '.xml');
                file_put_contents($tempFile, "=== XML INTERNO ===\n{$xmlInterno}\n\n=== SOAP REQUEST ===\n{$requestXml}");
                Log::info('MIR: XML guardado para debug', ['file' => $tempFile]);
            }

            // Enviar
            $endpoint = $this->getEndpointUrl($config['entorno']);
            $credentials = base64_encode($config['usuario'] . ':' . $config['password']);

            Log::info('MIR: Enviando reserva', [
                'reserva_id' => $reserva->id,
                'codigo_reserva' => $reserva->codigo_reserva,
                'endpoint' => $endpoint,
                'entorno' => $config['entorno'],
                'arrendador' => $config['codigo_arrendador'],
                'establecimiento' => $codigoEstablecimiento,
            ]);

            // Reintentar hasta 3 veces con 5 segundos de espera entre intentos
            $maxIntentos = 3;
            $response = null;
            $ultimoError = null;

            for ($intento = 1; $intento <= $maxIntentos; $intento++) {
                try {
                    $response = $this->enviarSOAP($endpoint, $requestXml, $credentials, $config['entorno']);
                    if ($response['http_code'] === 200) {
                        break; // Éxito
                    }
                    $ultimoError = "HTTP {$response['http_code']}";
                } catch (\Exception $e) {
                    $ultimoError = $e->getMessage();
                    $response = null;
                }

                if ($intento < $maxIntentos) {
                    Log::warning('MIR: Intento fallido, reintentando...', [
                        'reserva_id' => $reserva->id,
                        'intento' => $intento,
                        'error' => $ultimoError,
                    ]);
                    sleep(5);
                }
            }

            // Si todos los reintentos fallaron, enviar alerta por WhatsApp
            if (!$response || $response['http_code'] !== 200) {
                $this->enviarAlertaFalloMIR($reserva, $ultimoError);
            }

            if ($response) {
                Log::info('MIR: Respuesta recibida', [
                    'reserva_id' => $reserva->id,
                    'http_code' => $response['http_code'],
                    'body_length' => strlen($response['body']),
                    'body_preview' => substr($response['body'], 0, 500),
                    'intentos' => $intento,
                ]);

                return $this->procesarRespuesta($response);
            }

            // Sin respuesta tras todos los reintentos
            throw new \Exception("Fallo tras {$maxIntentos} intentos: {$ultimoError}");

        } catch (\Exception $e) {
            Log::error('MIR: Error al enviar reserva', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'estado' => 'error',
                'codigo_referencia' => null,
                'mensaje' => $e->getMessage(),
                'respuesta_completa' => null,
            ];
        }
    }

    /**
     * Obtener el código de establecimiento
     */
    private function obtenerCodigoEstablecimiento(Reserva $reserva, array $config): ?string
    {
        // En sandbox, priorizar configuración
        if ($config['entorno'] === 'sandbox' && !empty($config['codigo_establecimiento'])) {
            Log::info('MIR: Código establecimiento obtenido de configuración (sandbox)', [
                'codigo' => $config['codigo_establecimiento'],
            ]);
            return $config['codigo_establecimiento'];
        }

        // Desde apartamento
        $apt = $reserva->apartamento;
        if (!empty($apt->codigo_establecimiento)) {
            Log::info('MIR: Código establecimiento obtenido del apartamento', [
                'apartamento_id' => $apt->id,
                'codigo' => $apt->codigo_establecimiento,
            ]);
            return $apt->codigo_establecimiento;
        }

        // Desde edificio
        if ($apt->edificio && !empty($apt->edificio->codigo_establecimiento)) {
            Log::info('MIR: Código establecimiento obtenido del edificio', [
                'edificio_id' => $apt->edificio->id,
                'codigo' => $apt->edificio->codigo_establecimiento,
            ]);
            return $apt->edificio->codigo_establecimiento;
        }

        // Fallback a configuración
        if (!empty($config['codigo_establecimiento'])) {
            Log::info('MIR: Código establecimiento obtenido de configuración global (fallback)', [
                'codigo' => $config['codigo_establecimiento'],
            ]);
            return $config['codigo_establecimiento'];
        }

        // No se encontró en ningún nivel
        Log::error('MIR: No se encontró código de establecimiento en ningún nivel', [
            'reserva_id' => $reserva->id,
            'apartamento_id' => $reserva->apartamento_id,
            'edificio_id' => $reserva->apartamento?->edificio_id,
            'setting_value' => $config['codigo_establecimiento'] ?? null,
        ]);

        return null;
    }

    /**
     * Enviar alerta por WhatsApp cuando falla el envío a MIR tras todos los reintentos
     */
    private function enviarAlertaFalloMIR(Reserva $reserva, ?string $error): void
    {
        try {
            $whatsappService = app(WhatsappNotificationService::class);
            $mensaje = "ALERTA MIR: Fallo al enviar reserva #{$reserva->codigo_reserva} (ID: {$reserva->id}) tras 3 intentos. Error: " . ($error ?? 'desconocido');
            $whatsappService->sendToConfiguredRecipients($mensaje);
            Log::info('MIR: Alerta WhatsApp enviada por fallo en envío', [
                'reserva_id' => $reserva->id,
            ]);
        } catch (\Exception $e) {
            Log::error('MIR: No se pudo enviar alerta WhatsApp por fallo MIR', [
                'reserva_id' => $reserva->id,
                'error_whatsapp' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enviar SOAP request via cURL
     */
    private function enviarSOAP(string $endpoint, string $xml, string $credentials, string $entorno): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ""',
                'Accept: text/xml',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        // SSL: El servidor MIR del Ministerio del Interior usa certificados de la FNMT
        // (Fábrica Nacional de Moneda y Timbre) que no están en los CA bundles estándar.
        // Deshabilitamos la verificación del peer ya que los endpoints son hardcoded y de confianza.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Verificar hostname sí

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('Error de conexión: ' . $curlError);
        }

        return ['http_code' => $httpCode, 'body' => $body ?: ''];
    }

    /**
     * Procesar la respuesta SOAP del MIR
     */
    private function procesarRespuesta(array $response): array
    {
        $httpCode = $response['http_code'];
        $body = $response['body'];

        if ($httpCode === 401) {
            return [
                'success' => false,
                'estado' => 'error_auth',
                'codigo_referencia' => null,
                'mensaje' => 'Error de autenticación (401). Verifique usuario y contraseña de MIR.',
                'respuesta_completa' => $body,
            ];
        }

        if ($httpCode >= 500) {
            return [
                'success' => false,
                'estado' => 'error_servidor',
                'codigo_referencia' => null,
                'mensaje' => "Error del servidor MIR ({$httpCode}). " . substr($body, 0, 200),
                'respuesta_completa' => $body,
            ];
        }

        if ($httpCode === 200 && !empty($body)) {
            return $this->parsearRespuestaSOAP($body);
        }

        return [
            'success' => false,
            'estado' => 'error',
            'codigo_referencia' => null,
            'mensaje' => "Respuesta inesperada HTTP {$httpCode}",
            'respuesta_completa' => $body,
        ];
    }

    /**
     * Parsear respuesta SOAP XML del MIR
     */
    private function parsearRespuestaSOAP(string $body): array
    {
        try {
            // Limpiar namespaces para facilitar parseo
            $cleanBody = preg_replace('/(<\/?)[\w\-]+:/', '$1', $body);
            $cleanBody = preg_replace('/\s+xmlns(:[^=]*)?\s*=\s*"[^"]*"/', '', $cleanBody);
            $xml = simplexml_load_string($cleanBody);

            if ($xml === false) {
                // [FIX] NO asumir exito cuando MIR devuelve algo no parseable.
                // En muchos errores del XSD, MIR devuelve HTTP 200 con un body
                // de texto plano tipo "10118 Error en el formato de fichero xml..."
                // que no es XML. Antes lo marcabamos como enviado, ahora no.
                return [
                    'success' => false,
                    'estado' => 'error_xml',
                    'codigo_referencia' => null,
                    'mensaje' => 'Respuesta de MIR no parseable como XML (posible rechazo de schema). Revisar respuesta_completa.',
                    'respuesta_completa' => $body,
                ];
            }

            // Navegar la estructura SOAP: Envelope > Body > comunicacionResponse > respuesta
            $respuesta = null;
            $resultado = null;

            // Buscar en la estructura
            if (isset($xml->Body->comunicacionResponse->respuesta)) {
                $respuesta = $xml->Body->comunicacionResponse->respuesta;
            } elseif (isset($xml->Body->comunicacionResponse)) {
                $respuesta = $xml->Body->comunicacionResponse;
            }

            // Buscar respuesta SOAP fault (error serializado por MIR)
            if (isset($xml->Body->Fault)) {
                $faultString = (string) ($xml->Body->Fault->faultstring ?? 'SOAP Fault');
                $faultCode   = (string) ($xml->Body->Fault->faultcode   ?? '');
                return [
                    'success' => false,
                    'estado'  => 'error_soap_fault',
                    'codigo_referencia' => null,
                    'mensaje' => "SOAP Fault ({$faultCode}): {$faultString}",
                    'respuesta_completa' => $body,
                ];
            }

            // Extraer datos
            $codigo = null;
            $descripcion = '';
            $lote = '';
            $codigoEncontrado = false;

            if ($respuesta) {
                // Detectar si el elemento <codigo> existe REALMENTE (distinto de no encontrado)
                if (isset($respuesta->codigo)) {
                    $codigo = (int) $respuesta->codigo;
                    $codigoEncontrado = true;
                } elseif (isset($respuesta->respuesta->codigo)) {
                    $codigo = (int) $respuesta->respuesta->codigo;
                    $codigoEncontrado = true;
                }

                $descripcion = (string) ($respuesta->descripcion ?? $respuesta->respuesta->descripcion ?? '');
                $lote = (string) ($respuesta->lote ?? $respuesta->respuesta->lote ?? '');
            }

            // Buscar resultado de comunicaciones individuales
            if (isset($xml->Body->comunicacionResponse->resultado)) {
                $resultado = $xml->Body->comunicacionResponse->resultado;
            }

            // [FIX] Criterio estricto de exito: tres condiciones OBLIGATORIAS
            //   1) Se encontro el elemento <codigo> en la respuesta
            //   2) El valor de <codigo> es exactamente 0
            //   3) Hay un <lote> no vacio (MIR siempre lo devuelve al aceptar)
            // Si falta alguna -> es error. Esto evita marcar como "enviado" una
            // respuesta rara de MIR que en realidad rechaza la comunicacion.
            $success = $codigoEncontrado && $codigo === 0 && !empty($lote);

            // [FIX 10121] Caso especial: si la reserva ya fue enviada antes,
            // MIR devuelve codigo=10121 con el mensaje "Lote duplicado: XXX".
            // Extraemos el lote original y lo consideramos exito (enviado previamente).
            if ($codigoEncontrado && $codigo === 10121 && preg_match('/Lote duplicado:\s*([a-f0-9\-]+)/i', $descripcion, $m)) {
                return [
                    'success' => true,
                    'estado' => 'enviado',
                    'codigo_referencia' => $m[1],
                    'mensaje' => "Ya estaba enviada a MIR. Lote original: {$m[1]}",
                    'respuesta_completa' => $body,
                ];
            }

            if (!$success) {
                $mensaje = $descripcion ?: 'MIR no devolvio un lote de confirmacion (posible rechazo).';
                if ($codigoEncontrado && $codigo !== 0) {
                    $mensaje = "MIR rechazo la comunicacion (codigo {$codigo}): {$descripcion}";
                } elseif (!$codigoEncontrado) {
                    $mensaje = 'Respuesta MIR sin elemento <codigo>. ' . ($descripcion ?: 'Ver respuesta_completa.');
                } elseif (empty($lote)) {
                    $mensaje = 'MIR respondio codigo=0 pero sin <lote>. Se considera fallo para evitar falsos positivos.';
                }
                return [
                    'success' => false,
                    'estado' => 'error',
                    'codigo_referencia' => null,
                    'mensaje' => $mensaje,
                    'respuesta_completa' => $body,
                ];
            }

            return [
                'success' => true,
                'estado' => 'enviado',
                'codigo_referencia' => $lote,
                'mensaje' => $descripcion ?: "Enviado correctamente. Lote MIR: {$lote}",
                'respuesta_completa' => $body,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'estado' => 'error_parseo',
                'codigo_referencia' => null,
                'mensaje' => 'Error al parsear respuesta: ' . $e->getMessage(),
                'respuesta_completa' => $body,
            ];
        }
    }

    /**
     * Comprobar si una reserva tiene todos los datos necesarios para MIR.
     * Requisitos: cliente con DNI, al menos un huésped con DNI, código de establecimiento.
     */
    public function reservaListaParaMIR(Reserva $reserva): bool
    {
        $reserva->loadMissing(['cliente', 'apartamento.edificio']);

        // [FIX] Solo se considera "enviada" si MIR devolvio un lote de confirmacion.
        // Si mir_enviado=true pero mir_codigo_referencia esta vacio, en su momento
        // hubo un falso positivo (ver parsearRespuestaSOAP antes del fix): permitimos
        // el reintento para que vuelva a pasar por la logica correcta del parser nuevo.
        if ($reserva->mir_enviado && !empty($reserva->mir_codigo_referencia)) {
            return false;
        }

        // MIR desactivado para este edificio
        $edificio = $reserva->apartamento?->edificio ?? null;
        if ($edificio && !$edificio->mir_activo) {
            return false;
        }

        // Cliente con documento de identidad
        $cliente = $reserva->cliente;
        if (!$cliente || empty($cliente->num_identificacion)) {
            return false;
        }

        // Verificar soporte documento si es NIF/NIE
        $tipoDoc = $this->getTipoDocumentoMIR($cliente->num_identificacion ?? '');
        if (in_array($tipoDoc, ['NIF', 'NIE']) && empty($cliente->numero_soporte_documento)) {
            Log::info('MIR: Reserva no lista - falta número de soporte del documento (NIF/NIE)', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
            ]);
            return false;
        }

        // Apartamento con código de establecimiento (propio o del edificio)
        $apt = $reserva->apartamento;
        if (!$apt) return false;

        $codEst = $apt->codigo_establecimiento
            ?? ($apt->edificio->codigo_establecimiento ?? null);

        // Fallback a la configuración global
        if (empty($codEst)) {
            $codEst = Setting::get('mir_codigo_establecimiento');
        }

        if (empty($codEst)) return false;

        // Al menos un huésped con documento de identidad
        $huespedConDNI = \App\Models\Huesped::where('reserva_id', $reserva->id)
            ->whereNotNull('numero_identificacion')
            ->where('numero_identificacion', '!=', '')
            ->exists();

        if (!$huespedConDNI) return false;

        // Configuración MIR completa
        try {
            $config = $this->getConfig();
        } catch (\Exception $e) {
            return false;
        }
        if (empty($config['usuario']) || empty($config['password']) || empty($config['codigo_arrendador'])) {
            return false;
        }

        return true;
    }

    /**
     * Intentar enviar automáticamente una reserva a MIR.
     * Solo envía si tiene todos los datos necesarios y no se ha enviado antes.
     * Devuelve null si no se intentó (datos incompletos o ya enviada).
     */
    public function enviarSiLista(Reserva $reserva): ?array
    {
        if (!$this->reservaListaParaMIR($reserva)) {
            return null;
        }

        try {
            $resultado = $this->enviarReserva($reserva);

            // Actualizar campos MIR en la reserva
            $reserva->mir_enviado = $resultado['success'];
            $reserva->mir_estado = $resultado['estado'];
            $reserva->mir_respuesta = json_encode($resultado);
            $reserva->mir_fecha_envio = now();
            $reserva->mir_codigo_referencia = $resultado['codigo_referencia'] ?? null;
            $reserva->save();

            if ($resultado['success']) {
                Log::info('MIR auto-envío exitoso', [
                    'reserva_id' => $reserva->id,
                    'lote' => $resultado['codigo_referencia'],
                ]);
            } else {
                Log::warning('MIR auto-envío fallido', [
                    'reserva_id' => $reserva->id,
                    'error' => $resultado['mensaje'],
                ]);
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('MIR auto-envío excepción', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);

            $reserva->mir_estado = 'error';
            $reserva->mir_respuesta = json_encode(['error' => $e->getMessage()]);
            $reserva->mir_fecha_envio = now();
            $reserva->save();

            return [
                'success' => false,
                'estado' => 'error',
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Consultar el estado de uno o varios lotes en MIR (tipoOperacion=C).
     *
     * @param array $lotes Array de IDs de lote (strings UUID)
     * @return array Resultado con estado de cada lote
     */
    public function consultarLotes(array $lotes): array
    {
        try {
            $config = $this->getConfig();

            if (empty($config['usuario']) || empty($config['password']) || empty($config['codigo_arrendador'])) {
                throw new \Exception('Configuración MIR incompleta. Configure los datos en Ajustes > MIR.');
            }

            // Construir XML interno para consulta
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<lotes xmlns="http://www.neg.hospedajes.mir.es/consultarComunicacion">' . "\n";
            foreach ($lotes as $lote) {
                $xml .= '  <lote>' . $this->esc($lote) . '</lote>' . "\n";
            }
            $xml .= '</lotes>';

            // Comprimir y codificar
            $solicitudBase64 = $this->comprimirYCodificar($xml);

            // Construir SOAP envelope con tipoOperacion=C
            $requestXml = $this->construirSOAPEnvelope($solicitudBase64, $config, 'C');

            // Enviar
            $endpoint = $this->getEndpointUrl($config['entorno']);
            $credentials = base64_encode($config['usuario'] . ':' . $config['password']);

            Log::info('MIR: Consultando lotes', [
                'lotes' => $lotes,
                'endpoint' => $endpoint,
            ]);

            $response = $this->enviarSOAP($endpoint, $requestXml, $credentials, $config['entorno']);

            Log::info('MIR: Respuesta consulta', [
                'http_code' => $response['http_code'],
                'body_preview' => substr($response['body'], 0, 1000),
            ]);

            return $this->parsearRespuestaConsulta($response);

        } catch (\Exception $e) {
            Log::error('MIR: Error en consulta de lotes', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => $e->getMessage(),
                'lotes' => [],
            ];
        }
    }

    /**
     * Consultar el estado de las reservas enviadas hoy a MIR.
     */
    public function consultarEnviosHoy(): array
    {
        $reservas = Reserva::where('mir_enviado', true)
            ->whereDate('mir_fecha_envio', now()->toDateString())
            ->whereNotNull('mir_codigo_referencia')
            ->get();

        if ($reservas->isEmpty()) {
            return [
                'success' => true,
                'mensaje' => 'No hay envíos a MIR de hoy.',
                'reservas' => [],
                'lotes' => [],
            ];
        }

        $lotes = $reservas->pluck('mir_codigo_referencia')->unique()->values()->toArray();

        $resultado = $this->consultarLotes($lotes);
        $resultado['reservas'] = $reservas->map(function ($r) {
            return [
                'id' => $r->id,
                'codigo_reserva' => $r->codigo_reserva,
                'cliente' => $r->cliente ? ($r->cliente->nombre . ' ' . ($r->cliente->apellido1 ?? '')) : 'N/A',
                'lote' => $r->mir_codigo_referencia,
                'fecha_envio' => $r->mir_fecha_envio?->format('H:i:s'),
                'estado_local' => $r->mir_estado,
            ];
        })->toArray();

        return $resultado;
    }

    /**
     * Parsear respuesta de consulta de lotes
     */
    private function parsearRespuestaConsulta(array $response): array
    {
        $httpCode = $response['http_code'];
        $body = $response['body'];

        if ($httpCode === 401) {
            return ['success' => false, 'mensaje' => 'Error de autenticación (401)', 'lotes' => []];
        }

        if ($httpCode >= 500) {
            return ['success' => false, 'mensaje' => "Error del servidor ({$httpCode})", 'lotes' => []];
        }

        if ($httpCode !== 200 || empty($body)) {
            return ['success' => false, 'mensaje' => "Respuesta HTTP {$httpCode}", 'lotes' => []];
        }

        try {
            // Limpiar namespaces
            $cleanBody = preg_replace('/(<\/?)[\w\-]+:/', '$1', $body);
            $cleanBody = preg_replace('/\s+xmlns(:[^=]*)?\s*=\s*"[^"]*"/', '', $cleanBody);
            $xml = simplexml_load_string($cleanBody);

            if (!$xml) {
                return ['success' => true, 'mensaje' => 'Respuesta no parseable', 'lotes' => [], 'respuesta_completa' => $body];
            }

            $respuesta = $xml->Body->comunicacionResponse->respuesta ?? null;
            $codigo = $respuesta ? (int) ($respuesta->codigo ?? -1) : -1;
            $descripcion = $respuesta ? (string) ($respuesta->descripcion ?? '') : '';

            // Extraer resultados de comunicaciones individuales y del lote
            $resultados = [];
            $resultado = $xml->Body->comunicacionResponse->resultado ?? null;

            if ($resultado) {
                $loteInfo = [
                    'lote' => (string) ($resultado->lote ?? ''),
                    'tipo' => (string) ($resultado->tipoComunicacion ?? ''),
                    'fecha_peticion' => (string) ($resultado->fechaPeticion ?? ''),
                    'fecha_procesamiento' => (string) ($resultado->fechaProcesamiento ?? ''),
                    'codigo_estado' => (string) ($resultado->codigoEstado ?? ''),
                    'estado' => (string) ($resultado->descEstado ?? ''),
                    'usuario' => (string) ($resultado->nombreUsuario ?? ''),
                    'comunicaciones' => [],
                ];

                // Extraer comunicaciones individuales
                $resCom = $resultado->resultadoComunicaciones ?? null;
                if ($resCom) {
                    foreach ($resCom->children() as $com) {
                        $loteInfo['comunicaciones'][] = [
                            'orden' => (string) ($com->orden ?? ''),
                            'tipo_error' => (string) ($com->tipoError ?? ''),
                            'error' => (string) ($com->error ?? ''),
                            'codigo_comunicacion' => (string) ($com->codigoComunicacion ?? ''),
                        ];
                    }
                }

                $resultados[] = $loteInfo;
            }

            return [
                'success' => $codigo === 0 || $codigo === -1,
                'codigo' => $codigo,
                'mensaje' => $descripcion ?: 'OK',
                'lotes' => $resultados,
                'respuesta_completa' => $body,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'mensaje' => 'Error parseo: ' . $e->getMessage(), 'lotes' => [], 'respuesta_completa' => $body];
        }
    }
}
