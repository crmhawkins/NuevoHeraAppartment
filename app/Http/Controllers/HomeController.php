<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\Cliente;
use App\Models\MensajeAuto;
use App\Models\Reserva;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;
// use leifermendez\police\PoliceHotelFacade;
// use Goutte\Client;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use OakLabs\Psd2\Authorization\Authorization;
use GuzzleHttp\Client;

class HomeController extends Controller
{
    private $endpoint, $cookie, $user, $pass, $_csrf, $headers, $fpdi;

    protected $pkgoptions = array(
        'countries' => array(),
        'user' => array(),
        'pdf' => array(),
    );


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->headers = []; // Initialize as an empty array
        // try {

        //     $this->user = 'H11070GEV04';
        //     $this->pass =  'H4Kins4p4rtamento2024';
        //     $this->endpoint = 'https://webpol.policia.es/e-hotel';
        //     $this->headers = [
        //         'User-Agent: PostmanRuntime/7.16.3',
        //     ];

        //     // if (!$user or !$pass) {
        //     //     throw new \Exception('error.login.users');
        //     // }

        //     return $this;

        // } catch (\Exception $e) {
        //     return $e->getMessage();
        // }
    }
    public function mostrarFormulario()
    {
        return view('importar_reservas'); // Vista con el formulario de carga
    }


    public function importarReservasDesdeCsv(Request $request)
    {
        // Validar que el archivo sea CSV
        $request->validate([
            'archivo' => 'required|mimes:csv,txt',
        ]);

        // Procesar el archivo CSV usando el separador correcto
        $csv = array_map(function ($row) {
            // Eliminar caracteres no deseados (saltos de línea, comillas, etc.)
            return str_getcsv(trim($row), ';'); // Usar ';' como separador de columnas
        }, file($request->file('archivo')->getRealPath()));

        $headers = array_map('trim', $csv[0]);
        unset($csv[0]); // Eliminar la cabecera

        // Depurar el contenido del archivo CSV
        \Log::info('Contenido CSV procesado:', ['data' => $csv]);

        foreach ($csv as $row) {
            // Verificar que el número de columnas en la fila coincida con los encabezados
            if (count($row) !== count($headers)) {
                // Si no coinciden, puede ser una fila mal formada, la ignoramos
                continue;
            }

            $fila = array_combine($headers, $row);

            // Depuración para ver qué datos estamos recibiendo
            \Log::info('Procesando reserva:', $fila);

            // Solo procesar las reservas con estado "ok"
            if (strtolower(trim($fila['Status'])) !== 'ok') {
                continue;
            }

            // Verificar si las claves existen antes de usarlas
            if (!isset($fila['Price']) || !isset($fila['Booker country'])) {
                \Log::error('Falta alguna clave en la fila', ['fila' => $fila]);
                continue; // Si falta alguna clave, continuar con la siguiente fila
            }

            // Eliminar " EUR" del precio y convertir a número
            $precio = floatval(str_replace(' EUR', '', $fila['Price']));

            // Buscar o crear el cliente
            $cliente = Cliente::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '', $fila['Guest name(s)'])) . 'guest.booking.com'], // email ficticio
                [
                    'alias' => $fila['Guest name(s)'],
                    'nombre' => explode(' ', $fila['Guest name(s)'])[0],
                    'apellido1' => explode(' ', $fila['Guest name(s)'])[1] ?? '',
                    'telefono' => $fila['Phone number'],
                    'direccion' => $fila['Address'],
                    'nacionalidad' => trim($fila['Booker country']) ?? 'ES', // Limpiar posibles espacios
                ]
            );

            // Relacionar con apartamento
            $apartamento = \App\Models\Apartamento::first(); // Ajusta esta lógica según tus necesidades

            if (!$apartamento) {
                continue; // Si no se encuentra apartamento, continuar
            }

            // Log overlap warning for CSV imports
            \App\Services\ReservationValidationService::hasOverlap(
                $apartamento->id,
                $fila['Check-in'],
                $fila['Check-out'],
                null,
                'CSV import'
            );

            // Crear la reserva
            Reserva::create([
                'cliente_id' => $cliente->id,
                'apartamento_id' => $apartamento->id,
                'room_type_id' => 1, // Ajusta según la lógica de tipo habitación
                'origen' => 'Importación CSV',
                'fecha_entrada' => $fila['Check-in'],
                'fecha_salida' => $fila['Check-out'],
                'codigo_reserva' => $fila['Book number'],
                'precio' => $precio, // Usar el precio limpio
                'numero_personas' => $fila['Persons'],
                'neto' => $precio, // Usar el precio limpio
                'comision' => floatval(str_replace(' EUR', '', $fila['Commission amount'])),
                'estado_id' => 1,
            ]);
        }

        return redirect()->route('mostrarFormulario')->with('success', 'Reservas importadas correctamente.');
    }




    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function bancos()
    {
        $data = [
            'code' => '1234567890',
            'state' => '4587-8548',
            'redirect_uri' => 'https://crm.apartamentosalgeciras.com/request-data',
            'client_id' => '3407106a-9433-44d5-a478-70facb316203',
            'client_secret' => 'T1fP5fN6uY4yA6qK3sW6nJ0yA6rD4uO2yJ5xA1uR4aU6eE1uH0',
        ];

        $authorization = new Authorization($data);

        // $this->assertEquals($data['code'], $authorization->getCode());
        // $this->assertEquals($data['state'], $authorization->getState());
        // $this->assertEquals($data['redirect_uri'], $authorization->getRedirectUri());
        // $this->assertEquals($data['client_id'], $authorization->getClientId());
        // $this->assertEquals($data['client_secret'], $authorization->getClientSecret());
        return response()->json($authorization);
    }
    // Función para extraer los datos específicos del DNI de la respuesta de Azure
    private function parseDniData($data) {
        $dniData = [];

        // Extrae campos específicos de la respuesta JSON
        if (isset($data['analyzeResult']['documentResults'][0]['fields'])) {
            $fields = $data['analyzeResult']['documentResults'][0]['fields'];
            $dniData['document_number'] = $fields['DocumentNumber']['valueString'] ?? null;
            $dniData['first_name'] = $fields['FirstName']['valueString'] ?? null;
            $dniData['last_name'] = $fields['LastName']['valueString'] ?? null;
            $dniData['date_of_birth'] = $fields['DateOfBirth']['valueDate'] ?? null;
        }

        return $dniData;
    }


public function pruebas()
{
    // Carga las variables de entorno para Custom Vision
    $endpoint = env('AZURE_CUSTOM_VISION_ENDPOINT');
    $apiKey = env('AZURE_CUSTOM_VISION_API_KEY');
    $projectId = env('AZURE_CUSTOM_VISION_PROJECT_ID');
    $publishedName = env('AZURE_CUSTOM_VISION_PUBLISHED_NAME');

    // Construye la ruta de la imagen en la carpeta public
    $filePath = public_path('dni.jpg');

    // Configura el cliente HTTP
    $client = new Client();

    // Lee la imagen
    $imageData = file_get_contents($filePath);

    try {
        // Envía la solicitud a Azure Custom Vision Prediction API
        $response = $client->post("{$endpoint}/customvision/v3.0/Prediction/{$projectId}/classify/iterations/{$publishedName}/image", [
            'headers' => [
                'Prediction-Key' => $apiKey,
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $imageData,
        ]);

        // Procesa la respuesta
        $resultData = json_decode($response->getBody(), true);

        // Aquí podrías extraer las predicciones específicas de los datos de DNI
        // Dependiendo de cómo hayas entrenado el modelo, podrías extraer campos específicos
        // dd($resultData); // Removed: dd() must never run in production

        return $this->parseDniData($resultData);

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

public function getReservas()
{
    $reservas = Reserva::all();
    foreach($reservas as $reserva){
        $cliente = Cliente::find($reserva->cliente_id);
        $reserva['cliente'] = $cliente;
        $apartamento = Apartamento::find($reserva->apartamento_id);
        $reserva['apartamento'] = $apartamento;

    }
    return response()->json($reservas);
}
    public function index()
    {
        return view('home');
    }

    public function test(ClienteService $clienteService){
        // $credentials = array(
        //     'user' => 'H11070GEV04',
        //     'pass' => 'H4Kins4p4rtamento2023'
        // );
        // $data = [
        //     'username' => 'H11070GEV04',
        //     'password' => 'H4Kins4p4rtamento2023',
        //     '_csrf' => '49614a9a-efc7-4c36-9063-b1cd6824aa9a'
        // ];
        //https://webpol.policia.es/e-hotel/execute_login
        //https://webpol.policia.es/e-hotel/login
        //https://webpol.policia.es/hospederia/manual/vista/grabadorManual
        //https://webpol.policia.es/hospederia/manual/insertar/huesped

        $browser = new HttpBrowser(HttpClient::create());
        $crawler = $browser->request('GET', 'https://webpol.policia.es/e-hotel/login');
        $csrfToken = $crawler->filter('meta[name="_csrf"]')->attr('content');

        $response1 = $browser->getResponse();
        $statusCode1 = $response1->getStatusCode();
        if ($statusCode1 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '1 - Código de estado HTTP: ' . $statusCode1;
            return;
        }

        $cookiesArray = [];
        foreach ($browser->getCookieJar()->all() as $cookie) {
            $cookiesArray[$cookie->getName()] = $cookie->getValue();
        }

        $postData = [
            'username' => env('MIR_USERNAME'),
            'password' => env('MIR_PASSWORD'),
            '_csrf'    => $csrfToken
        ];

        $headers = [
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_COOKIE' => 'FRONTAL_JSESSIONID: ' . $cookiesArray['FRONTAL_JSESSIONID'] . ' UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_: ' . $cookiesArray['UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_'] . ' cookiesession1: ' . $cookiesArray['cookiesession1']
        ];

        $browser->setServerParameters($headers);
        $crawler = $browser->request(
            'POST',
            'https://webpol.policia.es/e-hotel/execute_login',
            $postData
        );

        $response2 = $browser->getResponse();
        $statusCode2 = $response2->getStatusCode();
        if ($statusCode2 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '2 - Código de estado HTTP: ' . $statusCode2;
            return;
        }

        $crawler = $browser->request('GET', 'https://webpol.policia.es/e-hotel/hospederia/manual/vista/grabadorManual');
        $idHospederia = $crawler->filter('#idHospederia')->attr('value');

        $response3 = $browser->getResponse();
        $statusCode3 = $response3->getStatusCode();
        if ($statusCode3 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '3 - Código de estado HTTP: ' . $statusCode3;
            return;
        }
        mb_internal_encoding("UTF-8");

        $apellido = mb_convert_encoding('CASTAÑOS', 'UTF-8');

        $data = [
            'jsonHiddenComunes'=> null,
            'idHospederia' => $idHospederia,
            'nombre' => 'DANI',
            'apellido1' => $apellido,
            'apellido2' => 'MEFLE',
            'nacionalidad' => 'A9109AAAAA',
            'nacionalidadStr' => 'ESPAÑA',
            'tipoDocumento' => 'D',
            'tipoDocumentoStr' => 'DNI',
            'numIdentificacion' => '76586766D',
            'fechaExpedicionDoc' => '05/01/2022',
            'dia' => '23',
            'mes' => '11',
            'ano' => '2000',
            'fechaNacimiento' => '23/11/2000',
            'sexo' => 'M',
            'sexoStr' => 'MASCULINO',
            'fechaEntrada' => '21/12/2023',
            '_csrf' => $csrfToken
        ];

        $headers = [
            'Cookie' => 'FRONTAL_JSESSIONID: ' . $cookiesArray['FRONTAL_JSESSIONID'] . ' UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_: ' . $cookiesArray['UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_'] . ' cookiesession1: ' . $cookiesArray['cookiesession1'],
            'Accept' => 'text/html, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Referer' => 'https://webpol.policia.es/e-hotel/inicio',
            'X-Csrf-Token' => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
            // Otros encabezados según sea necesario
        ];
        // $data['apellido1'] = mb_convert_encoding('CASTAÑOS', 'UTF-8');

        $browser->setServerParameters($headers);

        $crawler = $browser->request(
            'POST',
            'https://webpol.policia.es/e-hotel/hospederia/manual/insertar/huesped',
            $data
        );
        // Diagnóstico: Ver contenido de la respuesta
        $responseContent = $browser->getResponse()->getContent();
        echo $responseContent;

        $response4 = $browser->getResponse();
        $statusCode4 = $response4->getStatusCode();

        if ($browser->getResponse()->getStatusCode() == 302) {
            $crawler = $browser->followRedirect();
            // Sigue la redirección
        }

        if ($statusCode4 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            // echo '4 - Código de estado HTTP: ' . $statusCode4 . $csrfToken . ' id: '. $idHospederia;
            return;
        }
        return [
            $csrfToken,
            $cookiesArray,
            $responseContent
        ];
    }

    public function paises(){
        $paisesEuropeos = [
            "ALBANIA", "ALEMANIA", "AUSTRIA", "BELGICA", "BULGARIA",
            "CHIPRE", "CROACIA", "DINAMARCA", "ESLOVAQUIA", "ESLOVENIA", "ESPAÑA",
            "ESTONIA", "FINLANDIA", "FRANCIA", "GRECIA", "HUNGRIA", "IRLANDA",
            "ISLANDIA", "ITALIA", "LETONIA", "LITUANIA", "LUXEMBURGO",
            "MALTA", "NORUEGA", "PAISES BAJOS", "POLONIA",
            "PORTUGAL", "REINO UNIDO", "REPUBLICA CHECA", "RUMANIA",
            "SUECIA"
        ];
        $paisesDni = [
                    "AFGANISTAN" => ["value" => "A9401AAAAA", "isEuropean" => in_array("AFGANISTAN", $paisesEuropeos)],
                    "AFRICA" => ["value" => "A9399AAAAA", "isEuropean" => in_array("AFRICA", $paisesEuropeos)],
                    "ALBANIA" => ["value" => "A9102AAAAA", "isEuropean" => in_array("ALBANIA", $paisesEuropeos)],
                    "ALEMANIA" => ["value" => "A9103AAAAA", "isEuropean" => in_array("ALEMANIA", $paisesEuropeos)],
                    "AMERICA" => ["value" => "A9299AAAAA", "isEuropean" => in_array("AMERICA", $paisesEuropeos)],
                    "ANDORRA" => ["value" => "A9133AAAAA", "isEuropean" => in_array("ANDORRA", $paisesEuropeos)],
                    "ANGOLA" => ["value" => "A9301AAAAA", "isEuropean" => in_array("ANGOLA", $paisesEuropeos)],
                    "ANTIGUA BARBUDA" => ["value" => "A9255AAAAA", "isEuropean" => in_array("ANTIGUA BARBUDA", $paisesEuropeos)],
                    "ANTILLAS NEERLANDESAS" => ["value" => "A9200AAAAA", "isEuropean" => in_array("ANTILLAS NEERLANDESAS", $paisesEuropeos)],
                    "APATRIDA" => ["value" => "A9600AAAAA", "isEuropean" => in_array("APATRIDA", $paisesEuropeos)],
                    "ARABIA SAUDITA" => ["value" => "A9403AAA1A", "isEuropean" => in_array("ARABIA SAUDITA", $paisesEuropeos)],
                    "ARGELIA" => ["value" => "A9304AAAAA", "isEuropean" => in_array("ARGELIA", $paisesEuropeos)],
                    "ARGENTINA" => ["value" => "A9202AAAAA", "isEuropean" => in_array("ARGENTINA", $paisesEuropeos)],
                    "ARMENIA" => ["value" => "A9142AAAAA", "isEuropean" => in_array("ARMENIA", $paisesEuropeos)],
                    "ARUBA" => ["value" => "A9257AAAAA", "isEuropean" => in_array("ARUBA", $paisesEuropeos)],
                    "ASIA" => ["value" => "A9499AAAAA", "isEuropean" => in_array("ASIA", $paisesEuropeos)],
                    "AUSTRALIA" => ["value" => "A9500AAAAA", "isEuropean" => in_array("AUSTRALIA", $paisesEuropeos)],
                    "AUSTRIA" => ["value" => "A9104AAAAA", "isEuropean" => in_array("AUSTRIA", $paisesEuropeos)],
                    "AZERBAYAN" => ["value" => "A9143AAA2A", "isEuropean" => in_array("AZERBAYAN", $paisesEuropeos)],
                    "BAHAMAS" => ["value" => "A9203AAAAA", "isEuropean" => in_array("BAHAMAS", $paisesEuropeos)],
                    "BAHREIN" => ["value" => "A9405AAAAA", "isEuropean" => in_array("BAHREIN", $paisesEuropeos)],
                    "BANGLADESH" => ["value" => "A9432AAAAA", "isEuropean" => in_array("BANGLADESH", $paisesEuropeos)],
                    "BARBADOS" => ["value" => "A9205AAAAA", "isEuropean" => in_array("BARBADOS", $paisesEuropeos)],
                    "BELGICA" => ["value" => "A9105AAAAA", "isEuropean" => in_array("BELGICA", $paisesEuropeos)],
                    "BELICE" => ["value" => "A9207AAAAA", "isEuropean" => in_array("BELICE", $paisesEuropeos)],
                    "BHUTAN" => ["value" => "A9407AAAAA", "isEuropean" => in_array("BHUTAN", $paisesEuropeos)],
                    "BIELORRUSIA" => ["value" => "A9144AAAAA", "isEuropean" => in_array("BIELORRUSIA", $paisesEuropeos)],
                    "BOLIVIA" => ["value" => "A9204AAAAA", "isEuropean" => in_array("BOLIVIA", $paisesEuropeos)],
                    "BOSNIA HERZEGOVINA" => ["value" => "A9156AAAAA", "isEuropean" => in_array("BOSNIA HERZEGOVINA", $paisesEuropeos)],
                    "BOTSWANA" => ["value" => "A9305AAAAA", "isEuropean" => in_array("BOTSWANA", $paisesEuropeos)],
                    "BRASIL" => ["value" => "A9206AAAAA", "isEuropean" => in_array("BRASIL", $paisesEuropeos)],
                    "BRUNEI" => ["value" => "A9409AAAAA", "isEuropean" => in_array("BRUNEI", $paisesEuropeos)],
                    "BULGARIA" => ["value" => "A9134AAAAA", "isEuropean" => in_array("BULGARIA", $paisesEuropeos)],
                    "BURKINA FASO" => ["value" => "A9303AAAAA", "isEuropean" => in_array("BURKINA FASO", $paisesEuropeos)],
                    "BURUNDI" => ["value" => "A9302AAAAA", "isEuropean" => in_array("BURUNDI", $paisesEuropeos)],
                    "BUTAN" => ["value" => "A9442AAAAA", "isEuropean" => in_array("BUTAN", $paisesEuropeos)],
                    "CABO VERDE" => ["value" => "A9308AAAAA", "isEuropean" => in_array("CABO VERDE", $paisesEuropeos)],
                    "CAMERUN" => ["value" => "A9307AAAAA", "isEuropean" => in_array("CAMERUN", $paisesEuropeos)],
                    "CANADA" => ["value" => "A9259AAAAA", "isEuropean" => in_array("CANADA", $paisesEuropeos)],
                    "CATAR" => ["value" => "A9408AAAAA", "isEuropean" => in_array("CATAR", $paisesEuropeos)],
                    "CENTROAMERICA" => ["value" => "A9201AAAAA", "isEuropean" => in_array("CENTROAMERICA", $paisesEuropeos)],
                    "CHAD" => ["value" => "A9306AAAAA", "isEuropean" => in_array("CHAD", $paisesEuropeos)],
                    "CHECOSLOVAQUIA" => ["value" => "A9114AAAAA", "isEuropean" => in_array("CHECOSLOVAQUIA", $paisesEuropeos)],
                    "CHILE" => ["value" => "A9212AAAAA", "isEuropean" => in_array("CHILE", $paisesEuropeos)],
                    "CHINA" => ["value" => "A9433AAAAA", "isEuropean" => in_array("CHINA", $paisesEuropeos)],
                    "CHIPRE" => ["value" => "A9135AAAAA", "isEuropean" => in_array("CHIPRE", $paisesEuropeos)],
                    "COLOMBIA" => ["value" => "A9213AAAAA", "isEuropean" => in_array("COLOMBIA", $paisesEuropeos)],
                    "COMORAS" => ["value" => "A9309AAAAA", "isEuropean" => in_array("COMORAS", $paisesEuropeos)],
                    "CONGO" => ["value" => "A9311AAAAA", "isEuropean" => in_array("CONGO", $paisesEuropeos)],
                    "COREA" => ["value" => "A9450AAAAA", "isEuropean" => in_array("COREA", $paisesEuropeos)],
                    "COREA NORTE" => ["value" => "A9451AAAAA", "isEuropean" => in_array("COREA NORTE", $paisesEuropeos)],
                    "COREA SUR" => ["value" => "A9452AAAAA", "isEuropean" => in_array("COREA SUR", $paisesEuropeos)],
                    "COSTA DE MARFIL" => ["value" => "A9310AAAAA", "isEuropean" => in_array("COSTA DE MARFIL", $paisesEuropeos)],
                    "COSTA RICA" => ["value" => "A9214AAAAA", "isEuropean" => in_array("COSTA RICA", $paisesEuropeos)],
                    "CROACIA" => ["value" => "A9136AAAAA", "isEuropean" => in_array("CROACIA", $paisesEuropeos)],
                    "CUBA" => ["value" => "A9250AAAAA", "isEuropean" => in_array("CUBA", $paisesEuropeos)],
                    "DINAMARCA" => ["value" => "A9106AAAAA", "isEuropean" => in_array("DINAMARCA", $paisesEuropeos)],
                    "DJIBOUTI" => ["value" => "A9312AAAAA", "isEuropean" => in_array("DJIBOUTI", $paisesEuropeos)],
                    "DOMINICA" => ["value" => "A9260AAAAA", "isEuropean" => in_array("DOMINICA", $paisesEuropeos)],
                    "ECUADOR" => ["value" => "A9215AAAAA", "isEuropean" => in_array("ECUADOR", $paisesEuropeos)],
                    "EGIPTO" => ["value" => "A9313AAAAA", "isEuropean" => in_array("EGIPTO", $paisesEuropeos)],
                    "EL SALVADOR" => ["value" => "A9216AAAAA", "isEuropean" => in_array("EL SALVADOR", $paisesEuropeos)],
                    "EMIRATOS ARABES UNIDOS" => ["value" => "A9411AAAAA", "isEuropean" => in_array("EMIRATOS ARABES UNIDOS", $paisesEuropeos)],
                    "ERITREA" => ["value" => "A9314AAAAA", "isEuropean" => in_array("ERITREA", $paisesEuropeos)],
                    "ESLOVAQUIA" => ["value" => "A9137AAAAA", "isEuropean" => in_array("ESLOVAQUIA", $paisesEuropeos)],
                    "ESLOVENIA" => ["value" => "A9138AAAAA", "isEuropean" => in_array("ESLOVENIA", $paisesEuropeos)],
                    "ESPAÑA" => ["value" => "A9107AAAAA", "isEuropean" => in_array("ESPAÑA", $paisesEuropeos)],
                    "ESTADOS UNIDOS" => ["value" => "A9261AAAAA", "isEuropean" => in_array("ESTADOS UNIDOS", $paisesEuropeos)],
                    "ESTONIA" => ["value" => "A9139AAAAA", "isEuropean" => in_array("ESTONIA", $paisesEuropeos)],
                    "ETIOPIA" => ["value" => "A9315AAAAA", "isEuropean" => in_array("ETIOPIA", $paisesEuropeos)],
                    "EUROPA" => ["value" => "A9398AAAAA", "isEuropean" => in_array("EUROPA", $paisesEuropeos)],
                    "FIJI" => ["value" => "A9503AAAAA", "isEuropean" => in_array("FIJI", $paisesEuropeos)],
                    "FILIPINAS" => ["value" => "A9444AAAAA", "isEuropean" => in_array("FILIPINAS", $paisesEuropeos)],
                    "FINLANDIA" => ["value" => "A9108AAAAA", "isEuropean" => in_array("FINLANDIA", $paisesEuropeos)],
                    "FRANCIA" => ["value" => "A9109AAAAA", "isEuropean" => in_array("FRANCIA", $paisesEuropeos)],
                    "GABON" => ["value" => "A9316AAAAA", "isEuropean" => in_array("GABON", $paisesEuropeos)],
                    "GAMBIA" => ["value" => "A9323AAAAA", "isEuropean" => in_array("GAMBIA", $paisesEuropeos)],
                    "GEORGIA" => ["value" => "A9145AAAAA", "isEuropean" => in_array("GEORGIA", $paisesEuropeos)],
                    "GHANA" => ["value" => "A9322AAAAA", "isEuropean" => in_array("GHANA", $paisesEuropeos)],
                    "GRECIA" => ["value" => "A9113AAAAA", "isEuropean" => in_array("GRECIA", $paisesEuropeos)],
                    "GUATEMALA" => ["value" => "A9228AAAAA", "isEuropean" => in_array("GUATEMALA", $paisesEuropeos)],
                    "GUINEA" => ["value" => "A9325AAA3A", "isEuropean" => in_array("GUINEA", $paisesEuropeos)],
                    "GUINEA BISSAU" => ["value" => "A9328AAA1A", "isEuropean" => in_array("GUINEA BISSAU", $paisesEuropeos)],
                    "GUINEA ECUATORIAL" => ["value" => "A9324AAAAA", "isEuropean" => in_array("GUINEA ECUATORIAL", $paisesEuropeos)],
                    "GUYANA" => ["value" => "A9225AAAAA", "isEuropean" => in_array("GUYANA", $paisesEuropeos)],
                    "HAITI" => ["value" => "A9230AAAAA", "isEuropean" => in_array("HAITI", $paisesEuropeos)],
                    "HONDURAS" => ["value" => "A9232AAAAA", "isEuropean" => in_array("HONDURAS", $paisesEuropeos)],
                    "HONG KONG CHINO" => ["value" => "A9462AAAAA", "isEuropean" => in_array("HONG KONG CHINO", $paisesEuropeos)],
                    "HUNGRIA" => ["value" => "A9114AAAAA", "isEuropean" => in_array("HUNGRIA", $paisesEuropeos)],
                    "IFNI" => ["value" => "A9395AAAAA", "isEuropean" => in_array("IFNI", $paisesEuropeos)],
                    "INDIA" => ["value" => "A9412AAAAA", "isEuropean" => in_array("INDIA", $paisesEuropeos)],
                    "INDONESIA" => ["value" => "A9414AAAAA", "isEuropean" => in_array("INDONESIA", $paisesEuropeos)],
                    "IRAK" => ["value" => "A9413AAAAA", "isEuropean" => in_array("IRAK", $paisesEuropeos)],
                    "IRAN" => ["value" => "A9415AAAAA", "isEuropean" => in_array("IRAN", $paisesEuropeos)],
                    "IRLANDA" => ["value" => "A9115AAAAA", "isEuropean" => in_array("IRLANDA", $paisesEuropeos)],
                    "ISLANDIA" => ["value" => "A9116AAAAA", "isEuropean" => in_array("ISLANDIA", $paisesEuropeos)],
                    "ISLAS MARIANAS NORTE" => ["value" => "A9518AAAAA", "isEuropean" => in_array("ISLAS MARIANAS NORTE", $paisesEuropeos)],
                    "ISLAS MARSHALL" => ["value" => "A9520AAAAA", "isEuropean" => in_array("ISLAS MARSHALL", $paisesEuropeos)],
                    "ISLAS SALOMON" => ["value" => "A9551AAA1A", "isEuropean" => in_array("ISLAS SALOMON", $paisesEuropeos)],
                    "ISRAEL" => ["value" => "A9417AAAAA", "isEuropean" => in_array("ISRAEL", $paisesEuropeos)],
                    "ITALIA" => ["value" => "A9117AAAAA", "isEuropean" => in_array("ITALIA", $paisesEuropeos)],
                    "JAMAICA" => ["value" => "A9233AAAAA", "isEuropean" => in_array("JAMAICA", $paisesEuropeos)],
                    "JAPON" => ["value" => "A9416AAAAA", "isEuropean" => in_array("JAPON", $paisesEuropeos)],
                    "JORDANIA" => ["value" => "A9419AAAAA", "isEuropean" => in_array("JORDANIA", $paisesEuropeos)],
                    "KAZAJSTAN" => ["value" => "A9465AAAAA", "isEuropean" => in_array("KAZAJSTAN", $paisesEuropeos)],
                    "KENIA" => ["value" => "A9336AAAAA", "isEuropean" => in_array("KENIA", $paisesEuropeos)],
                    "KIRIBATI" => ["value" => "A9501AAAAA", "isEuropean" => in_array("KIRIBATI", $paisesEuropeos)],
                    "KUWAIT" => ["value" => "A9421AAAAA", "isEuropean" => in_array("KUWAIT", $paisesEuropeos)],
                    "LAOS" => ["value" => "A9418AAAAA", "isEuropean" => in_array("LAOS", $paisesEuropeos)],
                    "LESOTHO" => ["value" => "A9337AAAAA", "isEuropean" => in_array("LESOTHO", $paisesEuropeos)],
                    "LETONIA" => ["value" => "A9138AAAAA", "isEuropean" => in_array("LETONIA", $paisesEuropeos)],
                    "LIBANO" => ["value" => "A9423AAAAA", "isEuropean" => in_array("LIBANO", $paisesEuropeos)],
                    "LIBERIA" => ["value" => "A9342AAAAA", "isEuropean" => in_array("LIBERIA", $paisesEuropeos)],
                    "LIBIA" => ["value" => "A9344AAAAA", "isEuropean" => in_array("LIBIA", $paisesEuropeos)],
                    "LIECHTENSTEIN" => ["value" => "A9118AAAAA", "isEuropean" => in_array("LIECHTENSTEIN", $paisesEuropeos)],
                    "LITUANIA" => ["value" => "A9139AAAAA", "isEuropean" => in_array("LITUANIA", $paisesEuropeos)],
                    "LUXEMBURGO" => ["value" => "A9119AAAAA", "isEuropean" => in_array("LUXEMBURGO", $paisesEuropeos)],
                    "MACAO" => ["value" => "A9463AAAAA", "isEuropean" => in_array("MACAO", $paisesEuropeos)],
                    "MACEDONIA" => ["value" => "A9159AAAAA", "isEuropean" => in_array("MACEDONIA", $paisesEuropeos)],
                    "MADAGASCAR" => ["value" => "A9354AAAAA", "isEuropean" => in_array("MADAGASCAR", $paisesEuropeos)],
                    "MALASIA" => ["value" => "A9425AAAAA", "isEuropean" => in_array("MALASIA", $paisesEuropeos)],
                    "MALAWI" => ["value" => "A9346AAAAA", "isEuropean" => in_array("MALAWI", $paisesEuropeos)],
                    "MALDIVAS" => ["value" => "A9436AAAAA", "isEuropean" => in_array("MALDIVAS", $paisesEuropeos)],
                    "MALI" => ["value" => "A9347AAAAA", "isEuropean" => in_array("MALI", $paisesEuropeos)],
                    "MALTA" => ["value" => "A9120AAAAA", "isEuropean" => in_array("MALTA", $paisesEuropeos)],
                    "MARRUECOS" => ["value" => "A9348AAAAA", "isEuropean" => in_array("MARRUECOS", $paisesEuropeos)],
                    "MAURICIO" => ["value" => "A9349AAAAA", "isEuropean" => in_array("MAURICIO", $paisesEuropeos)],
                    "MAURITANIA" => ["value" => "A9350AAAAA", "isEuropean" => in_array("MAURITANIA", $paisesEuropeos)],
                    "MEXICO" => ["value" => "A9234AAA1A", "isEuropean" => in_array("MEXICO", $paisesEuropeos)],
                    "MOLDAVIA" => ["value" => "A9148AAAAA", "isEuropean" => in_array("MOLDAVIA", $paisesEuropeos)],
                    "MONACO" => ["value" => "A9121AAAAA", "isEuropean" => in_array("MONACO", $paisesEuropeos)],
                    "MONGOLIA" => ["value" => "A9427AAAAA", "isEuropean" => in_array("MONGOLIA", $paisesEuropeos)],
                    "MONTENEGRO" => ["value" => "A9160AAAAA", "isEuropean" => in_array("MONTENEGRO", $paisesEuropeos)],
                    "MOZAMBIQUE" => ["value" => "A9351AAAAA", "isEuropean" => in_array("MOZAMBIQUE", $paisesEuropeos)],
                    "MYANMAR" => ["value" => "A9400AAAAA", "isEuropean" => in_array("MYANMAR", $paisesEuropeos)],
                    "NAMIBIA" => ["value" => "A9353AAAAA", "isEuropean" => in_array("NAMIBIA", $paisesEuropeos)],
                    "NAURU" => ["value" => "A9541AAAAA", "isEuropean" => in_array("NAURU", $paisesEuropeos)],
                    "NEPAL" => ["value" => "A9541AAAAA", "isEuropean" => in_array("NEPAL", $paisesEuropeos)],
                    "NICARAGUA" => ["value" => "A9236AAAAA", "isEuropean" => in_array("NICARAGUA", $paisesEuropeos)],
                    "NIGER" => ["value" => "A9360AAAAA", "isEuropean" => in_array("NIGER", $paisesEuropeos)],
                    "NIGERIA" => ["value" => "A9352AAAAA", "isEuropean" => in_array("NIGERIA", $paisesEuropeos)],
                    "NORUEGA" => ["value" => "A9122AAAAA", "isEuropean" => in_array("NORUEGA", $paisesEuropeos)],
                    "NUEVA ZELANDA" => ["value" => "A9540AAAAA", "isEuropean" => in_array("NUEVA ZELANDA", $paisesEuropeos)],
                    "OCEANIA" => ["value" => "A9599AAAAA", "isEuropean" => in_array("OCEANIA", $paisesEuropeos)],
                    "OMAN" => ["value" => "A9444AAAAA", "isEuropean" => in_array("OMAN", $paisesEuropeos)],
                    "PAÍSES BAJOS" => ["value" => "A9123AAA1A", "isEuropean" => in_array("PAISES BAJOS", $paisesEuropeos)],
                    "PAKISTAN" => ["value" => "A9424AAA1A", "isEuropean" => in_array("PAKISTAN", $paisesEuropeos)],
                    "PALESTINA" => ["value" => "A9440AAAAA", "isEuropean" => in_array("PALESTINA", $paisesEuropeos)],
                    "PANAMA" => ["value" => "A9238AAAAA", "isEuropean" => in_array("PANAMA", $paisesEuropeos)],
                    "PAPUA NUEVA GUINEA" => ["value" => "A9542AAAAA", "isEuropean" => in_array("PAPUA NUEVA GUINEA", $paisesEuropeos)],
                    "PARAGUAY" => ["value" => "A9240AAAAA", "isEuropean" => in_array("PARAGUAY", $paisesEuropeos)],
                    "PERU" => ["value" => "A9242AAAAA", "isEuropean" => in_array("PERU", $paisesEuropeos)],
                    "POLONIA" => ["value" => "A9124AAAAA", "isEuropean" => in_array("POLONIA", $paisesEuropeos)],
                    "PORTUGAL" => ["value" => "A9125AAAAA", "isEuropean" => in_array("PORTUGAL", $paisesEuropeos)],
                    "PUERTO RICO" => ["value" => "A9244AAAAA", "isEuropean" => in_array("PUERTO RICO", $paisesEuropeos)],
                    "QATAR" => ["value" => "A9431AAAAA", "isEuropean" => in_array("QATAR", $paisesEuropeos)],
                    "REINO UNIDO" => ["value" => "A9112AAA1A", "isEuropean" => in_array("REINO UNIDO", $paisesEuropeos)],
                    "REPUBLICA BENIN" => ["value" => "A9302AAA1A", "isEuropean" => in_array("REPUBLICA BENIN", $paisesEuropeos)],
                    "REPUBLICA CENTROAFRICANA" => ["value" => "A9310AAA1A", "isEuropean" => in_array("REPUBLICA CENTROAFRICANA", $paisesEuropeos)],
                    "REPUBLICA CHECA" => ["value" => "A9157AAAAA", "isEuropean" => in_array("REPUBLICA CHECA", $paisesEuropeos)],
                    "REPUBLICA CONGO" => ["value" => "A9312AAA1A", "isEuropean" => in_array("REPUBLICA CONGO", $paisesEuropeos)],
                    "REPUBLICA DEMOCRATICA CONGO" => ["value" => "A9380AAAAA", "isEuropean" => in_array("REPUBLICA DEMOCRATICA CONGO", $paisesEuropeos)],
                    "REPUBLICA DOMINICANA" => ["value" => "A9218AAA1A", "isEuropean" => in_array("REPUBLICA DOMINICANA", $paisesEuropeos)],
                    "REPUBLICA GRANADA" => ["value" => "A9229AAAAA", "isEuropean" => in_array("REPUBLICA GRANADA", $paisesEuropeos)],
                    "REPUBLICA KIRGUISTAN" => ["value" => "A9466AAA1A", "isEuropean" => in_array("REPUBLICA KIRGUISTAN", $paisesEuropeos)],
                    "REPUBLICA SUDAN SUR" => ["value" => "A9369AAAAA", "isEuropean" => in_array("REPUBLICA SUDAN SUR", $paisesEuropeos)],
                    "RIO MUNI" => ["value" => "A9397AAAAA", "isEuropean" => in_array("RIO MUNI", $paisesEuropeos)],
                    "RUANDA" => ["value" => "A9306AAAAA", "isEuropean" => in_array("RUANDA", $paisesEuropeos)],
                    "RUMANIA" => ["value" => "A9127AAAAA", "isEuropean" => in_array("RUMANIA", $paisesEuropeos)],
                    "RUSIA" => ["value" => "A9149AAAAA", "isEuropean" => in_array("RUSIA", $paisesEuropeos)],
                    "SAHARA" => ["value" => "A9398AAAAA", "isEuropean" => in_array("SAHARA", $paisesEuropeos)],
                    "SAINT KITTS NEVIS" => ["value" => "A9256AAA1A", "isEuropean" => in_array("SAINT KITTS NEVIS", $paisesEuropeos)],
                    "SALVADOR" => ["value" => "A9220AAAAA", "isEuropean" => in_array("SALVADOR", $paisesEuropeos)],
                    "SAMOA OCCIDENTAL" => ["value" => "A9552AAAAA", "isEuropean" => in_array("SAMOA OCCIDENTAL", $paisesEuropeos)],
                    "SAN MARINO" => ["value" => "A9135AAAAA", "isEuropean" => in_array("SAN MARINO", $paisesEuropeos)],
                    "SAN MARTIN" => ["value" => "A9259AAAAA", "isEuropean" => in_array("SAN MARTIN", $paisesEuropeos)],
                    "SAN VICENTE GRANADINAS" => ["value" => "A9254AAA1A", "isEuropean" => in_array("SAN VICENTE GRANADINAS", $paisesEuropeos)],
                    "SANTA LUCIA" => ["value" => "A9253AAAAA", "isEuropean" => in_array("SANTA LUCIA", $paisesEuropeos)],
                    "SANTA SEDE" => ["value" => "A9136AAA2A", "isEuropean" => in_array("SANTA SEDE", $paisesEuropeos)],
                    "SANTO TOME PRINCIPE" => ["value" => "A9361AAAAA", "isEuropean" => in_array("SANTO TOME PRINCIPE", $paisesEuropeos)],
                    "SENEGAL" => ["value" => "A9362AAAAA", "isEuropean" => in_array("SENEGAL", $paisesEuropeos)],
                    "SERBIA" => ["value" => "A9155AAAAA", "isEuropean" => in_array("SERBIA", $paisesEuropeos)],
                    "SEYCHELLES" => ["value" => "A9363AAAAA", "isEuropean" => in_array("SEYCHELLES", $paisesEuropeos)],
                    "SIERRA LEONA" => ["value" => "A9364AAAAA", "isEuropean" => in_array("SIERRA LEONA", $paisesEuropeos)],
                    "SINGAPUR" => ["value" => "A9426AAAAA", "isEuropean" => in_array("SINGAPUR", $paisesEuropeos)],
                    "SIRIA" => ["value" => "A9433AAAAA", "isEuropean" => in_array("SIRIA", $paisesEuropeos)],
                    "SOMALIA" => ["value" => "A9365AAAAA", "isEuropean" => in_array("SOMALIA", $paisesEuropeos)],
                    "SRI LANKA" => ["value" => "A9404AAAAA", "isEuropean" => in_array("SRI LANKA", $paisesEuropeos)],
                    "SUDAFRICA" => ["value" => "A9367AAAAA", "isEuropean" => in_array("SUDAFRICA", $paisesEuropeos)],
                    "SUDAN" => ["value" => "A9368AAAAA", "isEuropean" => in_array("SUDAN", $paisesEuropeos)],
                    "SUDAN SUR" => ["value" => "A9369AAA1A", "isEuropean" => in_array("SUDAN SUR", $paisesEuropeos)],
                    "SUECIA" => ["value" => "A9128AAAAA", "isEuropean" => in_array("SUECIA", $paisesEuropeos)],
                    "SUIZA" => ["value" => "A9129AAAAA", "isEuropean" => in_array("SUIZA", $paisesEuropeos)],
                    "SURINAM" => ["value" => "A9250AAAAA", "isEuropean" => in_array("SURINAM", $paisesEuropeos)],
                    "SWAZILANDIA" => ["value" => "A9371AAAAA", "isEuropean" => in_array("SWAZILANDIA", $paisesEuropeos)],
                    "TADJIKISTAN" => ["value" => "A9469AAAAA", "isEuropean" => in_array("TADJIKISTAN", $paisesEuropeos)],
                    "TAILANDIA" => ["value" => "A9428AAA1A", "isEuropean" => in_array("TAILANDIA", $paisesEuropeos)],
                    "TAIWAN TAIPEI" => ["value" => "A9408AAA3A", "isEuropean" => in_array("TAIWAN TAIPEI", $paisesEuropeos)],
                    "TANZANIA" => ["value" => "A9370AAAAA", "isEuropean" => in_array("TANZANIA", $paisesEuropeos)],
                    "TIMOR ORIENTAL" => ["value" => "A9464AAAAA", "isEuropean" => in_array("TIMOR ORIENTAL", $paisesEuropeos)],
                    "TOGO" => ["value" => "A9374AAAAA", "isEuropean" => in_array("TOGO", $paisesEuropeos)],
                    "TONGA" => ["value" => "A9554AAAAA", "isEuropean" => in_array("TONGA", $paisesEuropeos)],
                    "TRINIDAD TOBAGO" => ["value" => "A9245AAAAA", "isEuropean" => in_array("TRINIDAD TOBAGO", $paisesEuropeos)],
                    "TUNEZ" => ["value" => "A9378AAAAA", "isEuropean" => in_array("TUNEZ", $paisesEuropeos)],
                    "TURKMENISTAN" => ["value" => "A9467AAA1A", "isEuropean" => in_array("TURKMENISTAN", $paisesEuropeos)],
                    "TURQUIA" => ["value" => "A9130AAAAA", "isEuropean" => in_array("TURQUIA", $paisesEuropeos)],
                    "TUVALU" => ["value" => "A9560AAAAA", "isEuropean" => in_array("TUVALU", $paisesEuropeos)],
                    "UCRANIA" => ["value" => "A9152AAAAA", "isEuropean" => in_array("UCRANIA", $paisesEuropeos)],
                    "UGANDA" => ["value" => "A9358AAAAA", "isEuropean" => in_array("UGANDA", $paisesEuropeos)],
                    "UNION EUROPEA" => ["value" => "A9190AAA1A", "isEuropean" => in_array("UNION EUROPEA", $paisesEuropeos)],
                    "URUGUAY" => ["value" => "A9246AAAAA", "isEuropean" => in_array("URUGUAY", $paisesEuropeos)],
                    "UZBEKISTAN" => ["value" => "A9468AAAAA", "isEuropean" => in_array("UZBEKISTAN", $paisesEuropeos)],
                    "VANUATU" => ["value" => "A9565AAAAA", "isEuropean" => in_array("VANUATU", $paisesEuropeos)],
                    "VENEZUELA" => ["value" => "A9248AAAAA", "isEuropean" => in_array("VENEZUELA", $paisesEuropeos)],
                    "VIETNAM" => ["value" => "A9430AAAAA", "isEuropean" => in_array("VIETNAM", $paisesEuropeos)],
                    "YEMEN" => ["value" => "A9434AAAAA", "isEuropean" => in_array("YEMEN", $paisesEuropeos)],
                    "ZAMBIA" => ["value" => "A9382AAAAA", "isEuropean" => in_array("ZAMBIA", $paisesEuropeos)],
                    "ZIMBABWE" => ["value" => "A9357AAAAA", "isEuropean" => in_array("ZIMBABWE", $paisesEuropeos)]
                ];

                $jsonPaisesDni = json_encode($paisesDni, JSON_PRETTY_PRINT);
                return response()->json($paisesDni);
    }

    public function tipos(){
        $optionesTipo = [
            [
                "codigo" => "P",
                "descripcion" => "PASAPORTE",
            ],
            [
                "codigo" => "I",
                "descripcion" => "CARTA DE IDENTIDAD EXTRANJERA",
            ],
            [
                "codigo" => "N",
                "descripcion" => "NIE O TARJETA ESPAÑOLA DE EXTRANJEROS",
            ],
            [
                "codigo" => "X",
                "descripcion" => "PERMISO DE RESIDENCIA DE ESTADO MIEMBRO DE LA UE",
            ],
            [
                "codigo" => "C",
                "descripcion" => "PERMISO CONDUCIR ESPAÑOL",
            ],
            [
                "codigo" => "D",
                "descripcion" => "DNI",
            ]
        ];
        return response()->json($optionesTipo);

    }

}
