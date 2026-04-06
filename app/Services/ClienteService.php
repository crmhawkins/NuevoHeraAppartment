<?php

namespace App\Services;

use App\Models\Cliente; // Asegúrate de importar tu modelo Cliente
use Carbon\Carbon;
use libphonenumber\PhoneNumberUtil;
use Exception;

class ClienteService
{
    /**
     * Añadir idioma del cliente por ID
     *
     * @param int $id
     * @return array
     */
    public function getIdiomaClienteID($id)
    {
        // Obtener la fecha de hoy
        $hoy = Carbon::now();

        // Obtener el cliente por el ID
        $cliente = Cliente::find($id);

        // Validar si la nacionalidad del cliente es NULL
        if ($cliente && $cliente->idioma == null) {
            // Generar la instancia del Package de Phone
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Convertir el código del teléfono a código ISO del país
            try {
                if($cliente->telefono != null){
                    $phoneNumber = $phoneUtil->parse($cliente->telefono, "ZZ");
                    $codigoPaisISO = $phoneUtil->getRegionCodeForNumber($phoneNumber);
                    $countryCode = $phoneNumber->getCountryCode();
                    $countryCodeToIso = $this->getCountryCodeToIso();
                    $codigoPaisISO = $countryCodeToIso[$countryCode] ?? null;

                } else {
                    return [
                        'status' => '500',
                        'mensaje' => "Telefono es NULL"
                    ];
                }
            } catch (\libphonenumber\NumberParseException $e) {
                // Devolver la operación con un status 500 y mensaje de error
                return [
                    'status' => '500',
                    'mensaje' => $e->getMessage()
                ];
            }

            // Realizar una solicitud a una API para obtener el idioma
            $url = "https://restcountries.com/v3.1/alpha/" . $codigoPaisISO;
            $datosPais = file_get_contents($url);
            $infoPais = json_decode($datosPais, true);
            // Obtener del array de idioma el código del país y enviarlo a ChatGPT
            $reponseIdioma = $this->addIdiomaCliente($infoPais[0]['cioc']);

            // Obtener del array de idioma el código del país y enviarlo a ChatGPT
            $reponsePais = $this->addPaisCliente($infoPais[0]['cioc']);

            // Establecer la nacionalidad y guardar el cliente
            $cliente->nacionalidad = $reponsePais;
            $cliente->idioma = $reponseIdioma;
            $cliente->save();

            // Devolver la operación con un status 200
            return [
                'status' => '200',
            ];
        }

        return [
            'status' => '400',
            'mensaje' => 'Cliente no encontrado o ya tiene nacionalidad definida.'
        ];
    }


    function getCountryCodeToIso() {
        return [
            1 => 'US', // Estados Unidos, Canadá
            7 => 'RU', // Rusia, Kazajistán
            20 => 'EG', // Egipto
            27 => 'ZA', // Sudáfrica
            30 => 'GR', // Grecia
            31 => 'NL', // Países Bajos
            32 => 'BE', // Bélgica
            33 => 'FR', // Francia
            34 => 'ES', // España
            36 => 'HU', // Hungría
            39 => 'IT', // Italia
            40 => 'RO', // Rumania
            41 => 'CH', // Suiza
            43 => 'AT', // Austria
            44 => 'GB', // Reino Unido
            45 => 'DK', // Dinamarca
            46 => 'SE', // Suecia
            47 => 'NO', // Noruega
            48 => 'PL', // Polonia
            49 => 'DE', // Alemania
            51 => 'PE', // Perú
            52 => 'MX', // México
            53 => 'CU', // Cuba
            54 => 'AR', // Argentina
            55 => 'BR', // Brasil
            56 => 'CL', // Chile
            57 => 'CO', // Colombia
            58 => 'VE', // Venezuela
            60 => 'MY', // Malasia
            61 => 'AU', // Australia
            62 => 'ID', // Indonesia
            63 => 'PH', // Filipinas
            64 => 'NZ', // Nueva Zelanda
            65 => 'SG', // Singapur
            66 => 'TH', // Tailandia
            81 => 'JP', // Japón
            82 => 'KR', // Corea del Sur
            84 => 'VN', // Vietnam
            86 => 'CN', // China
            90 => 'TR', // Turquía
            91 => 'IN', // India
            92 => 'PK', // Pakistán
            93 => 'AF', // Afganistán
            94 => 'LK', // Sri Lanka
            95 => 'MM', // Myanmar
            98 => 'IR', // Irán
            211 => 'SS', // Sudán del Sur
            212 => 'MA', // Marruecos
            213 => 'DZ', // Argelia
            216 => 'TN', // Túnez
            218 => 'LY', // Libia
            220 => 'GM', // Gambia
            221 => 'SN', // Senegal
            222 => 'MR', // Mauritania
            223 => 'ML', // Malí
            224 => 'GN', // Guinea
            225 => 'CI', // Costa de Marfil
            226 => 'BF', // Burkina Faso
            227 => 'NE', // Níger
            228 => 'TG', // Togo
            229 => 'BJ', // Benín
            230 => 'MU', // Mauricio
            231 => 'LR', // Liberia
            232 => 'SL', // Sierra Leona
            233 => 'GH', // Ghana
            234 => 'NG', // Nigeria
            235 => 'TD', // Chad
            236 => 'CF', // República Centroafricana
            237 => 'CM', // Camerún
            238 => 'CV', // Cabo Verde
            239 => 'ST', // Santo Tomé y Príncipe
            240 => 'GQ', // Guinea Ecuatorial
            241 => 'GA', // Gabón
            242 => 'CG', // Congo-Brazzaville
            243 => 'CD', // Congo-Kinshasa
            244 => 'AO', // Angola
            245 => 'GW', // Guinea-Bissau
            246 => 'IO', // Territorio Británico del Océano Índico
            247 => 'AC', // Isla Ascensión
            248 => 'SC', // Seychelles
            249 => 'SD', // Sudán
            250 => 'RW', // Ruanda
            251 => 'ET', // Etiopía
            252 => 'SO', // Somalia
            253 => 'DJ', // Yibuti
            254 => 'KE', // Kenia
            255 => 'TZ', // Tanzania
            256 => 'UG', // Uganda
            257 => 'BI', // Burundi
            258 => 'MZ', // Mozambique
            259 => 'ZM', // Zanzíbar (obsoleto, ahora parte de Tanzania)
            260 => 'ZM', // Zambia
            261 => 'MG', // Madagascar
            262 => 'RE', // Reunión
            263 => 'ZW', // Zimbabue
            264 => 'NA', // Namibia
            265 => 'MW', // Malaui
            266 => 'LS', // Lesoto
            267 => 'BW', // Botsuana
            268 => 'SZ', // Suazilandia
            269 => 'KM', // Comoras
            290 => 'SH', // Santa Helena
            291 => 'ER', // Eritrea
            292 => 'AW', // Aruba
            293 => 'FO', // Islas Feroe
            294 => 'GL', // Groenlandia
            295 => 'GI', // Gibraltar
            297 => 'AW', // Aruba
            298 => 'FO', // Islas Feroe
            299 => 'GL', // Groenlandia
            350 => 'GI', // Gibraltar
            351 => 'PT', // Portugal
            352 => 'LU', // Luxemburgo
            353 => 'IE', // Irlanda
            354 => 'IS', // Islandia
            355 => 'AL', // Albania
            356 => 'MT', // Malta
            357 => 'CY', // Chipre
            358 => 'FI', // Finlandia
            359 => 'BG', // Bulgaria
            370 => 'LT', // Lituania
            371 => 'LV', // Letonia
            372 => 'EE', // Estonia
            373 => 'MD', // Moldavia
            374 => 'AM', // Armenia
            375 => 'BY', // Bielorrusia
            376 => 'AD', // Andorra
            377 => 'MC', // Mónaco
            378 => 'SM', // San Marino
            379 => 'VA', // Ciudad del Vaticano
            380 => 'UA', // Ucrania
            381 => 'RS', // Serbia
            382 => 'ME', // Montenegro
            383 => 'XK', // Kosovo
            384 => 'SI', // Eslovenia
            385 => 'HR', // Croacia
            386 => 'SI', // Eslovenia
            387 => 'BA', // Bosnia y Herzegovina
            388 => 'EU', // Unión Europea
            389 => 'MK', // Macedonia del Norte
            420 => 'CZ', // República Checa
            421 => 'SK', // Eslovaquia
            423 => 'LI', // Liechtenstein
            500 => 'FK', // Islas Malvinas
            501 => 'BZ', // Belice
            502 => 'GT', // Guatemala
            503 => 'SV', // El Salvador
            504 => 'HN', // Honduras
            505 => 'NI', // Nicaragua
            506 => 'CR', // Costa Rica
            507 => 'PA', // Panamá
            508 => 'PM', // San Pedro y Miquelón
            509 => 'HT', // Haití
            590 => 'GP', // Guadalupe
            591 => 'BO', // Bolivia
            592 => 'GY', // Guyana
            593 => 'EC', // Ecuador
            594 => 'GF', // Guayana Francesa
            595 => 'PY', // Paraguay
            596 => 'MQ', // Martinica
            597 => 'SR', // Surinam
            598 => 'UY', // Uruguay
            599 => 'CW', // Curazao
            670 => 'TL', // Timor Oriental
            671 => 'GU', // Guam (obsoleto, ahora usa 1-671)
            672 => 'NF', // Islas Norfolk
            673 => 'BN', // Brunei
            674 => 'NR', // Nauru
            675 => 'PG', // Papúa Nueva Guinea
            676 => 'TO', // Tonga
            677 => 'SB', // Islas Salomón
            678 => 'VU', // Vanuatu
            679 => 'FJ', // Fiyi
            680 => 'PW', // Palau
            681 => 'WF', // Wallis y Futuna
            682 => 'CK', // Islas Cook
            683 => 'NU', // Niue
            685 => 'WS', // Samoa
            686 => 'KI', // Kiribati
            687 => 'NC', // Nueva Caledonia
            688 => 'TV', // Tuvalu
            689 => 'PF', // Polinesia Francesa
            690 => 'TK', // Tokelau
            691 => 'FM', // Micronesia
            692 => 'MH', // Islas Marshall
            800 => '001', // Universal Personal Telecommunications services
            808 => '001', // International shared cost service (ISCS)
            850 => 'KP', // Corea del Norte
            852 => 'HK', // Hong Kong
            853 => 'MO', // Macao
            855 => 'KH', // Camboya
            856 => 'LA', // Laos
            870 => '001', // Inmarsat SNAC
            878 => '001', // Universal Personal Telecommunications
            880 => 'BD', // Bangladesh
            881 => '001', // Mobile Satellite System
            882 => '001', // International Networks
            883 => '001', // International Networks
            886 => 'TW', // Taiwán
            888 => '001', // Disaster Relief
            960 => 'MV', // Maldivas
            961 => 'LB', // Líbano
            962 => 'JO', // Jordania
            963 => 'SY', // Siria
            964 => 'IQ', // Irak
            965 => 'KW', // Kuwait
            966 => 'SA', // Arabia Saudita
            967 => 'YE', // Yemen
            968 => 'OM', // Omán
            970 => 'PS', // Palestina
            971 => 'AE', // Emiratos Árabes Unidos
            972 => 'IL', // Israel
            973 => 'BH', // Baréin
            974 => 'QA', // Catar
            975 => 'BT', // Bután
            976 => 'MN', // Mongolia
            977 => 'NP', // Nepal
            978 => '001', // Universal Access Number
            979 => '001', // International Premium Rate Service (IPRS)
            991 => '001', // International Telecommunications Public Correspondence Service trial (ITPCS)
            992 => 'TJ', // Tayikistán
            993 => 'TM', // Turkmenistán
            994 => 'AZ', // Azerbaiyán
            995 => 'GE', // Georgia
            996 => 'KG', // Kirguistán
            998 => 'UZ', // Uzbekistán
        ];
    }
    

    /**
     * Consultar a ChatGPT el idioma basado en el código de país
     *
     * @param string $codigo
     * @return string
     * @throws Exception
     */
    public function addIdiomaCliente($codigo)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');

        // Configurar los parámetros de la solicitud
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $data = [
            "messages" => [
                ["role" => "user", "content" => 'podrias decirme en una palabra el idioma de este codigo de pais, no me digas nada mas que el idioma y no pongas punto final: ' . $codigo,]
            ],
            "model" => "gpt-4",
            "temperature" => 0,
            "max_tokens" => 200,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
            "stop" => ["_END"]
        ];

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("Error en la solicitud cURL: " . $error);
        }

        curl_close($curl);

        // Procesar la respuesta
        $response_data = json_decode($response, true);
        return $response_data['choices'][0]['message']['content'];
    }

    public function addPaisCliente($codigo)
    {
        $paises = array("Afganistán","Albania","Alemania","Andorra","Angola","Antigua y Barbuda","Arabia Saudita","Argelia","Argentina","Armenia","Australia","Austria","Azerbaiyán","Bahamas","Bangladés","Barbados","Baréin","Bélgica","Belice","Benín","Bielorrusia","Birmania","Bolivia","Bosnia y Herzegovina","Botsuana","Brasil","Brunéi","Bulgaria","Burkina Faso","Burundi","Bután","Cabo Verde","Camboya","Camerún","Canadá","Catar","Chad","Chile","China","Chipre","Ciudad del Vaticano","Colombia","Comoras","Corea del Norte","Corea del Sur","Costa de Marfil","Costa Rica","Croacia","Cuba","Dinamarca","Dominica","Ecuador","Egipto","El Salvador","Emiratos Árabes Unidos","Eritrea","Eslovaquia","Eslovenia","España","Estados Unidos","Estonia","Etiopía","Filipinas","Finlandia","Fiyi","Francia","Gabón","Gambia","Georgia","Ghana","Granada","Grecia","Guatemala","Guyana","Guinea","Guinea ecuatorial","Guinea-Bisáu","Haití","Honduras","Hungría","India","Indonesia","Irak","Irán","Irlanda","Islandia","Islas Marshall","Islas Salomón","Israel","Italia","Jamaica","Japón","Jordania","Kazajistán","Kenia","Kirguistán","Kiribati","Kuwait","Laos","Lesoto","Letonia","Líbano","Liberia","Libia","Liechtenstein","Lituania","Luxemburgo","Madagascar","Malasia","Malaui","Maldivas","Malí","Malta","Marruecos","Mauricio","Mauritania","México","Micronesia","Moldavia","Mónaco","Mongolia","Montenegro","Mozambique","Namibia","Nauru","Nepal","Nicaragua","Níger","Nigeria","Noruega","Nueva Zelanda","Omán","Países Bajos","Pakistán","Palaos","Palestina","Panamá","Papúa Nueva Guinea","Paraguay","Perú","Polonia","Portugal","Reino Unido","República Centroafricana","República Checa","República de Macedonia","República del Congo","República Democrática del Congo","República Dominicana","República Sudafricana","Ruanda","Rumanía","Rusia","Samoa","San Cristóbal y Nieves","San Marino","San Vicente y las Granadinas","Santa Lucía","Santo Tomé y Príncipe","Senegal","Serbia","Seychelles","Sierra Leona","Singapur","Siria","Somalia","Sri Lanka","Suazilandia","Sudán","Sudán del Sur","Suecia","Suiza","Surinam","Tailandia","Tanzania","Tayikistán","Timor Oriental","Togo","Tonga","Trinidad y Tobago","Túnez","Turkmenistán","Turquía","Tuvalu","Ucrania","Uganda","Uruguay","Uzbekistán","Vanuatu","Venezuela","Vietnam","Yemen","Yibuti","Zambia","Zimbabue");
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');

        // Configurar los parámetros de la solicitud
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $data = [
            "messages" => [
                ["role" => "user", "content" => 'podrias decirme en una palabra el pais de este array '. json_encode($paises) .', de este codigo de pais, no me digas nada mas que el string donde coincida el codigo con el pais del array que te envie, no me pongas el resultado entre comillas ni nada solo el valor y no pongas punto final: ' . $codigo,]
            ],
            "model" => "gpt-4",
            "temperature" => 0,
            "max_tokens" => 200,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
            "stop" => ["_END"]
        ];

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("Error en la solicitud cURL: " . $error);
        }

        curl_close($curl);

        // Procesar la respuesta
        $response_data = json_decode($response, true);
        return $response_data['choices'][0]['message']['content'];
    }

    public function idiomaCodigo($idioma){

        // IDIOMAS:
        //         - es
        //         - en
        //         - de
        //         - fr
        //         - it
        //         - ar
        //         - pt_PT
        // Permite aceptar tanto el nombre del país como su abreviatura en mayúsculas
        $idioma = trim($idioma);
        switch (strtoupper($idioma)) {
            case 'ESPAÑA':
            case 'ES':
                return 'es';
                break;

            case 'FRANCIA':
            case 'FR':
                return 'fr';
                break;

            case 'MARRUECOS':
            case 'MA':
                return 'ar';
                break;

            case 'ALEMANIA':
            case 'DE':
                return 'de';
                break;

            case 'PORTUGAL':
            case 'PT':
            case 'PT_PT':
                return 'pt_PT';
                break;

            case 'ITALIA':
            case 'IT':
                return 'it';
                break;

            default:
                return 'en';
                break;
        }

        /* switch ($idioma) {
            case 'España':
                return 'es';
                break;

            case 'Francia':
                return 'fr';
                break;

            case 'Marruecos':
                return 'ar';
                break;

            case 'Alemania':
                return 'de';
                break;

            case 'Portugal':
                return 'pt_PT';
                break;

            case 'Italia':
                return 'it';
                break;

            default:
                return 'en';
                break;
        } */
    }
}
