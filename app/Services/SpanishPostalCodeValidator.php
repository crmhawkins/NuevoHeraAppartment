<?php

namespace App\Services;

/**
 * Validador pragmatico de codigos postales espanoles.
 *
 * Estrategia (de cazar muchos typos sin falsos positivos):
 *  1. El CP debe tener exactamente 5 digitos numericos.
 *  2. Los 2 primeros digitos deben corresponder a una provincia valida (01-52).
 *  3. El numero completo debe caer dentro del rango general de esa provincia
 *     (los rangos documentados de Correos). Esto caza typos donde se invento
 *     el CP o se pusieron digitos altos impropios de la provincia.
 *  4. Blacklist de CPs concretos que MIR ha rechazado historicamente. Se
 *     amplia a mano cada vez que llega un email de rechazo del Ministerio.
 *
 * Limitaciones conocidas:
 *  - Spain tiene ~10.000 CPs validos distribuidos de forma dispersa dentro
 *    de cada rango provincial. Un CP puede estar dentro del rango de la
 *    provincia pero no existir como tal. La validacion por rango NO caza
 *    ese caso; para eso esta la blacklist empirica.
 *  - Si el cliente es extranjero (pais != ES/ESP) NO validamos, porque MIR
 *    tampoco usa el callejero espanol para esos.
 */
class SpanishPostalCodeValidator
{
    /**
     * Rangos CP (inclusive) por prefijo provincial (2 primeros digitos).
     * Fuente: tablas publicadas por Correos de rangos por provincia.
     */
    private array $provinceRanges = [
        '01' => [1001, 1528],   // Alava
        '02' => [2001, 2712],   // Albacete
        '03' => [3001, 3828],   // Alicante
        '04' => [4001, 4867],   // Almeria
        '05' => [5001, 5697],   // Avila
        '06' => [6001, 6939],   // Badajoz
        '07' => [7001, 7860],   // Baleares
        '08' => [8001, 8980],   // Barcelona
        '09' => [9001, 9613],   // Burgos
        '10' => [10001, 10990], // Caceres
        '11' => [11001, 11690], // Cadiz
        '12' => [12001, 12609], // Castellon
        '13' => [13001, 13779], // Ciudad Real
        '14' => [14001, 14960], // Cordoba
        '15' => [15001, 15994], // A Coruna
        '16' => [16001, 16891], // Cuenca
        '17' => [17001, 17869], // Girona
        '18' => [18001, 18890], // Granada
        '19' => [19001, 19292], // Guadalajara
        '20' => [20001, 20810], // Gipuzkoa
        '21' => [21001, 21892], // Huelva
        '22' => [22001, 22889], // Huesca
        '23' => [23001, 23790], // Jaen
        '24' => [24001, 24994], // Leon
        '25' => [25001, 25795], // Lleida
        '26' => [26001, 26589], // La Rioja
        '27' => [27001, 27892], // Lugo
        '28' => [28001, 28991], // Madrid
        '29' => [29001, 29793], // Malaga
        '30' => [30001, 30896], // Murcia
        '31' => [31001, 31891], // Navarra
        '32' => [32001, 32890], // Ourense
        '33' => [33001, 33993], // Asturias
        '34' => [34001, 34889], // Palencia
        '35' => [35001, 35660], // Las Palmas
        '36' => [36001, 36992], // Pontevedra
        '37' => [37001, 37893], // Salamanca
        '38' => [38001, 38916], // Santa Cruz de Tenerife
        '39' => [39001, 39880], // Cantabria
        '40' => [40001, 40593], // Segovia
        '41' => [41001, 41940], // Sevilla
        '42' => [42001, 42368], // Soria
        '43' => [43001, 43886], // Tarragona
        '44' => [44001, 44479], // Teruel
        '45' => [45001, 45919], // Toledo
        '46' => [46001, 46988], // Valencia
        '47' => [47001, 47883], // Valladolid
        '48' => [48001, 48860], // Bizkaia
        '49' => [49001, 49883], // Zamora
        '50' => [50001, 50840], // Zaragoza
        '51' => [51001, 51005], // Ceuta
        '52' => [52001, 52006], // Melilla
    ];

    /**
     * Blacklist de CPs que MIR ha rechazado previamente. Ampliala a medida
     * que lleguen nuevos emails de error "Codigo Postal: XXXXX no existe".
     */
    private array $blacklist = [
        '11070', // Algeciras/Cadiz - reportado por MIR 2026-04-16
        '18017', // Granada - reportado por MIR 2026-04-18 (pero era dir gallega, CP mal)
        '29661', // Malaga - reportado por MIR 2026-04-19 (reserva 6302, dir Avda Bonaire)
    ];

    /**
     * Palabras/codigos que identifican al pais como Espana.
     */
    private array $paisEspanaAliases = [
        'ES', 'ESP', 'SPAIN', 'ESPANA', 'ESPAÑA',
    ];

    /**
     * Devuelve null si el CP es valido (o no aplica validar), o un mensaje
     * descriptivo del motivo de invalidez.
     *
     * @param string|null $cp       Codigo postal a validar
     * @param string|null $pais     Nacionalidad o pais del cliente (ISO-2, ISO-3 o nombre)
     */
    public function getReason(?string $cp, ?string $pais = null): ?string
    {
        // Si el pais esta informado y no es Espana, no validamos
        if ($pais !== null && $pais !== '') {
            $paisUpper = strtoupper(trim($pais));
            if (!in_array($paisUpper, $this->paisEspanaAliases, true)) {
                return null;
            }
        }

        $cp = trim((string) $cp);

        // CP vacio: no es asunto de este validador (otras comprobaciones ya lo tratan)
        if ($cp === '') {
            return null;
        }

        if (!preg_match('/^\d{5}$/', $cp)) {
            return "Codigo postal '{$cp}' no tiene 5 digitos numericos";
        }

        if (in_array($cp, $this->blacklist, true)) {
            return "Codigo postal '{$cp}' rechazado previamente por MIR (no existe en el callejero)";
        }

        $prefix = substr($cp, 0, 2);
        if (!isset($this->provinceRanges[$prefix])) {
            return "Codigo postal '{$cp}' tiene prefijo provincial inexistente ({$prefix})";
        }

        $numeric = (int) $cp;
        [$min, $max] = $this->provinceRanges[$prefix];
        if ($numeric < $min || $numeric > $max) {
            return "Codigo postal '{$cp}' fuera del rango valido de la provincia {$prefix} ({$min}-{$max})";
        }

        return null;
    }

    public function isValid(?string $cp, ?string $pais = null): bool
    {
        return $this->getReason($cp, $pais) === null;
    }
}
