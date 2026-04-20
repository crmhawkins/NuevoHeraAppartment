<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MensajeAuto;
use App\Models\Photo;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id'); // Default sort column
        $order = $request->get('order', 'asc'); // Default sort order

        $clientes = Cliente::where(function($query) {
            $query->where('inactivo', '!=', 1)
                  ->orWhereNull('inactivo');
        })
        ->where(function ($query) use ($search) {
            if ($search) {
                $query->where('alias', 'like', '%'.$search.'%')
                      ->orWhere('apellido1', 'like', '%'.$search.'%')
                      ->orWhere('apellido2', 'like', '%'.$search.'%')
                      ->orWhere('nombre', 'like', '%'.$search.'%')
                      ->orWhere('idioma', 'like', '%'.$search.'%')
                      ->orWhere('email', 'like', '%'.$search.'%');
            }
        })
        ->orderBy($sort, $order)
        ->paginate(15);

        return view('Clientes.index', compact('clientes', 'search', 'sort', 'order'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $paises = array("Afganistán","Albania","Alemania","Andorra","Angola","Antigua y Barbuda","Arabia Saudita","Argelia","Argentina","Armenia","Australia","Austria","Azerbaiyán","Bahamas","Bangladés","Barbados","Baréin","Bélgica","Belice","Benín","Bielorrusia","Birmania","Bolivia","Bosnia y Herzegovina","Botsuana","Brasil","Brunéi","Bulgaria","Burkina Faso","Burundi","Bután","Cabo Verde","Camboya","Camerún","Canadá","Catar","Chad","Chile","China","Chipre","Ciudad del Vaticano","Colombia","Comoras","Corea del Norte","Corea del Sur","Costa de Marfil","Costa Rica","Croacia","Cuba","Dinamarca","Dominica","Ecuador","Egipto","El Salvador","Emiratos Árabes Unidos","Eritrea","Eslovaquia","Eslovenia","España","Estados Unidos","Estonia","Etiopía","Filipinas","Finlandia","Fiyi","Francia","Gabón","Gambia","Georgia","Ghana","Granada","Grecia","Guatemala","Guyana","Guinea","Guinea ecuatorial","Guinea-Bisáu","Haití","Honduras","Hungría","India","Indonesia","Irak","Irán","Irlanda","Islandia","Islas Marshall","Islas Salomón","Israel","Italia","Jamaica","Japón","Jordania","Kazajistán","Kenia","Kirguistán","Kiribati","Kuwait","Laos","Lesoto","Letonia","Líbano","Liberia","Libia","Liechtenstein","Lituania","Luxemburgo","Madagascar","Malasia","Malaui","Maldivas","Malí","Malta","Marruecos","Mauricio","Mauritania","México","Micronesia","Moldavia","Mónaco","Mongolia","Montenegro","Mozambique","Namibia","Nauru","Nepal","Nicaragua","Níger","Nigeria","Noruega","Nueva Zelanda","Omán","Países Bajos","Pakistán","Palaos","Palestina","Panamá","Papúa Nueva Guinea","Paraguay","Perú","Polonia","Portugal","Reino Unido","República Centroafricana","República Checa","República de Macedonia","República del Congo","República Democrática del Congo","República Dominicana","República Sudafricana","Ruanda","Rumanía","Rusia","Samoa","San Cristóbal y Nieves","San Marino","San Vicente y las Granadinas","Santa Lucía","Santo Tomé y Príncipe","Senegal","Serbia","Seychelles","Sierra Leona","Singapur","Siria","Somalia","Sri Lanka","Suazilandia","Sudán","Sudán del Sur","Suecia","Suiza","Surinam","Tailandia","Tanzania","Tayikistán","Timor Oriental","Togo","Tonga","Trinidad y Tobago","Túnez","Turkmenistán","Turquía","Tuvalu","Ucrania","Uganda","Uruguay","Uzbekistán","Vanuatu","Venezuela","Vietnam","Yemen","Yibuti","Zambia","Zimbabue");
        $idiomaAPais = [
            "Afganistán" => "Pastún",
            "Albania" => "Albanés",
            "Alemania" => "Alemán",
            "Andorra" => "Catalán",
            "Angola" => "Portugués",
            "Antigua y Barbuda" => "Inglés",
            "Arabia Saudita" => "Árabe",
            "Argelia" => "Árabe",
            "Argentina" => "Español",
            "Armenia" => "Armenio",
            "Australia" => "Inglés",
            "Austria" => "Alemán",
            "Azerbaiyán" => "Azerbaiyano",
            "Bahamas" => "Inglés",
            "Bangladés" => "Bengalí",
            "Barbados" => "Inglés",
            "Baréin" => "Árabe",
            "Bélgica" => "Neerlandés",
            "Belice" => "Inglés",
            "Benín" => "Francés",
            "Bielorrusia" => "Bielorruso",
            "Birmania" => "Birmano",
            "Bolivia" => "Español",
            "Bosnia y Herzegovina" => "Bosnio",
            "Botsuana" => "Setsuana",
            "Brasil" => "Portugués",
            "Brunéi" => "Malayo",
            "Bulgaria" => "Búlgaro",
            "Burkina Faso" => "Francés",
            "Burundi" => "Kirundi",
            "Bután" => "Dzongkha",
            "Cabo Verde" => "Portugués",
            "Camboya" => "Jemer",
            "Camerún" => "Francés",
            "Canadá" => "Inglés",
            "Catar" => "Árabe",
            "Chad" => "Árabe",
            "Chile" => "Español",
            "China" => "Chino",
            "Chipre" => "Griego",
            "Ciudad del Vaticano" => "Italiano",
            "Colombia" => "Español",
            "Comoras" => "Árabe",
            "Corea del Norte" => "Coreano",
            "Corea del Sur" => "Coreano",
            "Costa de Marfil" => "Francés",
            "Costa Rica" => "Español",
            "Croacia" => "Croata",
            "Cuba" => "Español",
            "Dinamarca" => "Danés",
            "Dominica" => "Inglés",
            "Ecuador" => "Español",
            "Egipto" => "Árabe",
            "El Salvador" => "Español",
            "Emiratos Árabes Unidos" => "Árabe",
            "Eritrea" => "Tigriña",
            "Eslovaquia" => "Eslovaco",
            "Eslovenia" => "Esloveno",
            "España" => "Español",
            "Estados Unidos" => "Inglés",
            "Estonia" => "Estonio",
            "Etiopía" => "Amárico",
            "Filipinas" => "Filipino",
            "Finlandia" => "Finés",
            "Fiyi" => "Inglés",
            "Francia" => "Francés",
            "Gabón" => "Francés",
            "Gambia" => "Inglés",
            "Georgia" => "Georgiano",
            "Ghana" => "Inglés",
            "Granada" => "Inglés",
            "Grecia" => "Griego",
            "Guatemala" => "Español",
            "Guyana" => "Inglés",
            "Guinea" => "Francés",
            "Guinea ecuatorial" => "Español",
            "Guinea-Bisáu" => "Portugués",
            "Haití" => "Francés",
            "Honduras" => "Español",
            "Hungría" => "Húngaro",
            "India" => "Hindi",
            "Indonesia" => "Indonesio",
            "Irak" => "Árabe",
            "Irán" => "Persa",
            "Irlanda" => "Inglés",
            "Islandia" => "Islandés",
            "Islas Marshall" => "Marshalés",
            "Islas Salomón" => "Inglés",
            "Israel" => "Hebreo",
            "Italia" => "Italiano",
            "Jamaica" => "Inglés",
            "Japón" => "Japonés",
            "Jordania" => "Árabe",
            "Kazajistán" => "Kazajo",
            "Kenia" => "Suajili",
            "Kirguistán" => "Kirguís",
            "Kiribati" => "Gilbertés",
            "Kuwait" => "Árabe",
            "Laos" => "Lao",
            "Lesoto" => "Sesoto",
            "Letonia" => "Letón",
            "Líbano" => "Árabe",
            "Liberia" => "Inglés",
            "Libia" => "Árabe",
            "Liechtenstein" => "Alemán",
            "Lituania" => "Lituano",
            "Luxemburgo" => "Luxemburgués",
            "Madagascar" => "Malgache",
            "Malasia" => "Malayo",
            "Malaui" => "Chichewa",
            "Maldivas" => "Divehi",
            "Malí" => "Francés",
            "Malta" => "Maltés",
            "Marruecos" => "Árabe",
            "Mauricio" => "Inglés",
            "Mauritania" => "Árabe",
            "México" => "Español",
            "Micronesia" => "Inglés",
            "Moldavia" => "Rumano",
            "Mónaco" => "Francés",
            "Mongolia" => "Mongol",
            "Montenegro" => "Montenegrino",
            "Mozambique" => "Portugués",
            "Namibia" => "Inglés",
            "Nauru" => "Nauruano",
            "Nepal" => "Nepalí",
            "Nicaragua" => "Español",
            "Níger" => "Francés",
            "Nigeria" => "Inglés",
            "Noruega" => "Noruego",
            "Nueva Zelanda" => "Inglés",
            "Omán" => "Árabe",
            "Países Bajos" => "Neerlandés",
            "Pakistán" => "Urdu",
            "Palaos" => "Paluano",
            "Palestina" => "Árabe",
            "Panamá" => "Español",
            "Papúa Nueva Guinea" => "Inglés",
            "Paraguay" => "Español",
            "Perú" => "Español",
            "Polonia" => "Polaco",
            "Portugal" => "Portugués",
            "Reino Unido" => "Inglés",
            "República Centroafricana" => "Francés",
            "República Checa" => "Checo",
            "República de Macedonia" => "Macedonio",
            "República del Congo" => "Francés",
            "República Democrática del Congo" => "Francés",
            "República Dominicana" => "Español",
            "República Sudafricana" => "Afrikáans",
            "Ruanda" => "Kinyarwanda",
            "Rumanía" => "Rumano",
            "Rusia" => "Ruso",
            "Samoa" => "Samoano",
            "San Cristóbal y Nieves" => "Inglés",
            "San Marino" => "Italiano",
            "San Vicente y las Granadinas" => "Inglés",
            "Santa Lucía" => "Inglés",
            "Santo Tomé y Príncipe" => "Portugués",
            "Senegal" => "Francés",
            "Serbia" => "Serbio",
            "Seychelles" => "Seychellense",
            "Sierra Leona" => "Inglés",
            "Singapur" => "Inglés",
            "Siria" => "Árabe",
            "Somalia" => "Somalí",
            "Sri Lanka" => "Cingalés",
            "Suazilandia" => "Swazi",
            "Sudán" => "Árabe",
            "Sudán del Sur" => "Inglés",
            "Suecia" => "Sueco",
            "Suiza" => "Alemán",
            "Surinam" => "Neerlandés",
            "Tailandia" => "Tailandés",
            "Tanzania" => "Suajili",
            "Tayikistán" => "Tayiko",
            "Timor Oriental" => "Tetún",
            "Togo" => "Francés",
            "Tonga" => "Tongano",
            "Trinidad y Tobago" => "Inglés",
            "Túnez" => "Árabe",
            "Turkmenistán" => "Turcomano",
            "Turquía" => "Turco",
            "Tuvalu" => "Tuvaluano",
            "Ucrania" => "Ucraniano",
            "Uganda" => "Inglés",
            "Uruguay" => "Español",
            "Uzbekistán" => "Uzbeko",
            "Vanuatu" => "Bislama",
            "Venezuela" => "Español",
            "Vietnam" => "Vietnamita",
            "Yemen" => "Árabe",
            "Yibuti" => "Árabe",
            "Zambia" => "Inglés",
            "Zimbabue" => "Inglés"
        ];
        return view('Clientes.create', compact('paises','idiomaAPais'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Definir las reglas de validación
        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'nullable|string|max:255',
            'sexo' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            // [FIX 2026-04-17] email ya no es unique: permite huespedes recurrentes
            // con varios registros (distintas reservas, misma persona). Si quieres
            // deduplicar, hazlo por DNI/num_identificacion, no por email.
            'email' => 'required|email|max:255',
            'idiomas' => 'nullable|string|max:255',
            'nacionalidad' => 'required|string|max:255',
            'tipo_documento' => 'nullable',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            // Campos de facturación
            'tipo_cliente' => 'required|string|in:particular,empresa,autonomo',
            'es_empresa' => 'boolean',
            'requiere_factura' => 'boolean',
            'condiciones_pago' => 'nullable|string|in:contado,30_dias,60_dias,90_dias',
            'facturacion_nombre_razon_social' => 'nullable|string|max:255',
            'facturacion_nif_cif' => 'nullable|string|max:20',
            'facturacion_direccion' => 'nullable|string|max:255',
            'facturacion_localidad' => 'nullable|string|max:255',
            'facturacion_codigo_postal' => 'nullable|string|max:10',
            'facturacion_provincia' => 'nullable|string|max:255',
            'facturacion_pais' => 'nullable|string|max:255',
            'facturacion_email' => 'nullable|email|max:255',
            'facturacion_telefono' => 'nullable|string|max:20',
            'observaciones_facturacion' => 'nullable|string|max:1000',
        ];

        // Mensajes de validación personalizados
        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'apellido1.required' => 'El primer apellido es obligatorio.',
            'apellido1.max' => 'El primer apellido no puede tener más de 255 caracteres.',
            'sexo.required' => 'El sexo es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.unique' => 'Este email ya está registrado.',
            'nacionalidad.required' => 'La nacionalidad es obligatoria.',
            // Mensajes para campos de facturación
            'tipo_cliente.required' => 'El tipo de cliente es obligatorio.',
            'tipo_cliente.in' => 'El tipo de cliente debe ser: particular, empresa o autónomo.',
            'facturacion_email.email' => 'El formato del email de facturación no es válido.',
            'facturacion_nif_cif.max' => 'El NIF/CIF no puede tener más de 20 caracteres.',
            'facturacion_codigo_postal.max' => 'El código postal no puede tener más de 10 caracteres.',
            'observaciones_facturacion.max' => 'Las observaciones no pueden tener más de 1000 caracteres.',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules, $messages);

        try {
            // Procesar los datos validados
            $cliente = new Cliente($validatedData);
            $cliente->save();

            return redirect()->route('clientes.index')
                ->with('swal_success', '¡Cliente creado exitosamente!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        $reservas = Reserva::where('cliente_id', $cliente->id)->get();
        $mensajes = MensajeAuto::where('cliente_id', $cliente->id)->get();
        $photos = Photo::where('cliente_id', $cliente->id)->get();
        
        // Estadísticas económicas
        $estadisticasEconomicas = [
            'total_pagado' => $reservas->sum('precio'),
            'total_neto' => $reservas->sum('neto'),
            'total_comisiones' => $reservas->sum('comision'),
            'total_cargos_pago' => $reservas->sum('cargo_por_pago'),
            'total_iva' => $reservas->sum('iva'),
            'reservas_activas' => $reservas->where('estado_id', '!=', 4)->count(), // Excluir canceladas
            'reservas_completadas' => $reservas->where('estado_id', 4)->count(),
            'valor_promedio_reserva' => $reservas->count() > 0 ? round($reservas->avg('precio'), 2) : 0,
            'reservas_pendientes_pago' => $reservas->where('estado_id', 1)->count(), // Asumiendo que estado_id 1 es pendiente
        ];
        
        // [2026-04-19] Info de veto para la vista: null si no esta vetado,
        // un ClienteVetado si tiene un veto activo.
        $vetoActivo = app(\App\Services\ClienteVetadoService::class)->isVetado($cliente);

        return view('Clientes.show', compact('cliente', 'mensajes', 'photos', 'reservas', 'estadisticasEconomicas', 'vetoActivo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        // Obtener datos relacionados para las estadísticas
        $reservas = \App\Models\Reserva::where('cliente_id', $cliente->id)->get();
        $mensajes = \App\Models\MensajeAuto::where('cliente_id', $cliente->id)->get();
        $photos = \App\Models\Photo::where('cliente_id', $cliente->id)->get();
        
        $paises = array("Afganistán","Albania","Alemania","Andorra","Angola","Antigua y Barbuda","Arabia Saudita","Argelia","Argentina","Armenia","Australia","Austria","Azerbaiyán","Bahamas","Bangladés","Barbados","Baréin","Bélgica","Belice","Benín","Bielorrusia","Birmania","Bolivia","Bosnia y Herzegovina","Botsuana","Brasil","Brunéi","Bulgaria","Burkina Faso","Burundi","Bután","Cabo Verde","Camboya","Camerún","Canadá","Catar","Chad","Chile","China","Chipre","Ciudad del Vaticano","Colombia","Comoras","Corea del Norte","Corea del Sur","Costa de Marfil","Costa Rica","Croacia","Cuba","Dinamarca","Dominica","Ecuador","Egipto","El Salvador","Emiratos Árabes Unidos","Eritrea","Eslovaquia","Eslovenia","España","Estados Unidos","Estonia","Etiopía","Filipinas","Finlandia","Fiyi","Francia","Gabón","Gambia","Georgia","Ghana","Granada","Grecia","Guatemala","Guyana","Guinea","Guinea ecuatorial","Guinea-Bisáu","Haití","Honduras","Hungría","India","Indonesia","Irak","Irán","Irlanda","Islandia","Islas Marshall","Islas Salomón","Israel","Italia","Jamaica","Japón","Jordania","Kazajistán","Kenia","Kirguistán","Kiribati","Kuwait","Laos","Lesoto","Letonia","Líbano","Liberia","Libia","Liechtenstein","Lituania","Luxemburgo","Madagascar","Malasia","Malaui","Maldivas","Malí","Malta","Marruecos","Mauricio","Mauritania","México","Micronesia","Moldavia","Mónaco","Mongolia","Montenegro","Mozambique","Namibia","Nauru","Nepal","Nicaragua","Níger","Nigeria","Noruega","Nueva Zelanda","Omán","Países Bajos","Pakistán","Palaos","Palestina","Panamá","Papúa Nueva Guinea","Paraguay","Perú","Polonia","Portugal","Reino Unido","República Centroafricana","República Checa","República de Macedonia","República del Congo","República Democrática del Congo","República Dominicana","República Sudafricana","Ruanda","Rumanía","Rusia","Samoa","San Cristóbal y Nieves","San Marino","San Vicente y las Granadinas","Santa Lucía","Santo Tomé y Príncipe","Senegal","Serbia","Seychelles","Sierra Leona","Singapur","Siria","Somalia","Sri Lanka","Suazilandia","Sudán","Sudán del Sur","Suecia","Suiza","Surinam","Tailandia","Tanzania","Tayikistán","Timor Oriental","Togo","Tonga","Trinidad y Tobago","Túnez","Turkmenistán","Turquía","Tuvalu","Ucrania","Uganda","Uruguay","Uzbekistán","Vanuatu","Venezuela","Vietnam","Yemen","Yibuti","Zambia","Zimbabue");
        $idiomaAPais = [
            "Afganistán" => "Pastún",
            "Albania" => "Albanés",
            "Alemania" => "Alemán",
            "Andorra" => "Catalán",
            "Angola" => "Portugués",
            "Antigua y Barbuda" => "Inglés",
            "Arabia Saudita" => "Árabe",
            "Argelia" => "Árabe",
            "Argentina" => "Español",
            "Armenia" => "Armenio",
            "Australia" => "Inglés",
            "Austria" => "Alemán",
            "Azerbaiyán" => "Azerbaiyano",
            "Bahamas" => "Inglés",
            "Bangladés" => "Bengalí",
            "Barbados" => "Inglés",
            "Baréin" => "Árabe",
            "Bélgica" => "Neerlandés",
            "Belice" => "Inglés",
            "Benín" => "Francés",
            "Bielorrusia" => "Bielorruso",
            "Birmania" => "Birmano",
            "Bolivia" => "Español",
            "Bosnia y Herzegovina" => "Bosnio",
            "Botsuana" => "Setsuana",
            "Brasil" => "Portugués",
            "Brunéi" => "Malayo",
            "Bulgaria" => "Búlgaro",
            "Burkina Faso" => "Francés",
            "Burundi" => "Kirundi",
            "Bután" => "Dzongkha",
            "Cabo Verde" => "Portugués",
            "Camboya" => "Jemer",
            "Camerún" => "Francés",
            "Canadá" => "Inglés",
            "Catar" => "Árabe",
            "Chad" => "Árabe",
            "Chile" => "Español",
            "China" => "Chino",
            "Chipre" => "Griego",
            "Ciudad del Vaticano" => "Italiano",
            "Colombia" => "Español",
            "Comoras" => "Árabe",
            "Corea del Norte" => "Coreano",
            "Corea del Sur" => "Coreano",
            "Costa de Marfil" => "Francés",
            "Costa Rica" => "Español",
            "Croacia" => "Croata",
            "Cuba" => "Español",
            "Dinamarca" => "Danés",
            "Dominica" => "Inglés",
            "Ecuador" => "Español",
            "Egipto" => "Árabe",
            "El Salvador" => "Español",
            "Emiratos Árabes Unidos" => "Árabe",
            "Eritrea" => "Tigriña",
            "Eslovaquia" => "Eslovaco",
            "Eslovenia" => "Esloveno",
            "España" => "Español",
            "Estados Unidos" => "Inglés",
            "Estonia" => "Estonio",
            "Etiopía" => "Amárico",
            "Filipinas" => "Filipino",
            "Finlandia" => "Finés",
            "Fiyi" => "Inglés",
            "Francia" => "Francés",
            "Gabón" => "Francés",
            "Gambia" => "Inglés",
            "Georgia" => "Georgiano",
            "Ghana" => "Inglés",
            "Granada" => "Inglés",
            "Grecia" => "Griego",
            "Guatemala" => "Español",
            "Guyana" => "Inglés",
            "Guinea" => "Francés",
            "Guinea ecuatorial" => "Español",
            "Guinea-Bisáu" => "Portugués",
            "Haití" => "Francés",
            "Honduras" => "Español",
            "Hungría" => "Húngaro",
            "India" => "Hindi",
            "Indonesia" => "Indonesio",
            "Irak" => "Árabe",
            "Irán" => "Persa",
            "Irlanda" => "Inglés",
            "Islandia" => "Islandés",
            "Islas Marshall" => "Marshalés",
            "Islas Salomón" => "Inglés",
            "Israel" => "Hebreo",
            "Italia" => "Italiano",
            "Jamaica" => "Inglés",
            "Japón" => "Japonés",
            "Jordania" => "Árabe",
            "Kazajistán" => "Kazajo",
            "Kenia" => "Suajili",
            "Kirguistán" => "Kirguís",
            "Kiribati" => "Gilbertés",
            "Kuwait" => "Árabe",
            "Laos" => "Lao",
            "Lesoto" => "Sesoto",
            "Letonia" => "Letón",
            "Líbano" => "Árabe",
            "Liberia" => "Inglés",
            "Libia" => "Árabe",
            "Liechtenstein" => "Alemán",
            "Lituania" => "Lituano",
            "Luxemburgo" => "Luxemburgués",
            "Madagascar" => "Malgache",
            "Malasia" => "Malayo",
            "Malaui" => "Chichewa",
            "Maldivas" => "Divehi",
            "Malí" => "Francés",
            "Malta" => "Maltés",
            "Marruecos" => "Árabe",
            "Mauricio" => "Inglés",
            "Mauritania" => "Árabe",
            "México" => "Español",
            "Micronesia" => "Inglés",
            "Moldavia" => "Rumano",
            "Mónaco" => "Francés",
            "Mongolia" => "Mongol",
            "Montenegro" => "Montenegrino",
            "Mozambique" => "Portugués",
            "Namibia" => "Inglés",
            "Nauru" => "Nauruano",
            "Nepal" => "Nepalí",
            "Nicaragua" => "Español",
            "Níger" => "Francés",
            "Nigeria" => "Inglés",
            "Noruega" => "Noruego",
            "Nueva Zelanda" => "Inglés",
            "Omán" => "Árabe",
            "Países Bajos" => "Neerlandés",
            "Pakistán" => "Urdu",
            "Palaos" => "Paluano",
            "Palestina" => "Árabe",
            "Panamá" => "Español",
            "Papúa Nueva Guinea" => "Inglés",
            "Paraguay" => "Español",
            "Perú" => "Español",
            "Polonia" => "Polaco",
            "Portugal" => "Portugués",
            "Reino Unido" => "Inglés",
            "República Centroafricana" => "Francés",
            "República Checa" => "Checo",
            "República de Macedonia" => "Macedonio",
            "República del Congo" => "Francés",
            "República Democrática del Congo" => "Francés",
            "República Dominicana" => "Español",
            "República Sudafricana" => "Afrikáans",
            "Ruanda" => "Kinyarwanda",
            "Rumanía" => "Rumano",
            "Rusia" => "Ruso",
            "Samoa" => "Samoano",
            "San Cristóbal y Nieves" => "Inglés",
            "San Marino" => "Italiano",
            "San Vicente y las Granadinas" => "Inglés",
            "Santa Lucía" => "Inglés",
            "Santo Tomé y Príncipe" => "Portugués",
            "Senegal" => "Francés",
            "Serbia" => "Serbio",
            "Seychelles" => "Seychellense",
            "Sierra Leona" => "Inglés",
            "Singapur" => "Inglés",
            "Siria" => "Árabe",
            "Somalia" => "Somalí",
            "Sri Lanka" => "Cingalés",
            "Suazilandia" => "Swazi",
            "Sudán" => "Árabe",
            "Sudán del Sur" => "Inglés",
            "Suecia" => "Sueco",
            "Suiza" => "Alemán",
            "Surinam" => "Neerlandés",
            "Tailandia" => "Tailandés",
            "Tanzania" => "Suajili",
            "Tayikistán" => "Tayiko",
            "Timor Oriental" => "Tetún",
            "Togo" => "Francés",
            "Tonga" => "Tongano",
            "Trinidad y Tobago" => "Inglés",
            "Túnez" => "Árabe",
            "Turkmenistán" => "Turcomano",
            "Turquía" => "Turco",
            "Tuvalu" => "Tuvaluano",
            "Ucrania" => "Ucraniano",
            "Uganda" => "Inglés",
            "Uruguay" => "Español",
            "Uzbekistán" => "Uzbeko",
            "Vanuatu" => "Bislama",
            "Venezuela" => "Español",
            "Vietnam" => "Vietnamita",
            "Yemen" => "Árabe",
            "Yibuti" => "Árabe",
            "Zambia" => "Inglés",
            "Zimbabue" => "Inglés"
        ];
        return view('Clientes.edit', compact('cliente', 'reservas', 'mensajes', 'photos', 'idiomaAPais', 'paises'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Encuentra el cliente por ID
        $cliente = Cliente::findOrFail($id);

        // Definir las reglas de validación
        // [FIX 2026-04-17] Varios campos pasaron de 'required' a 'nullable' en el
        // update. El motivo es que el formulario de edicion se usa tambien para
        // ediciones parciales (modales, tabs sueltas) y los campos que no se
        // envien no deben invalidar la peticion. Mas abajo hacemos que tampoco
        // pisen los valores existentes si llegan vacios.
        $rules = [
            'alias' => 'nullable|string|max:255',
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            // [FIX 2026-04-17] email permite duplicados para soportar huespedes
            // recurrentes. La dedup por persona real debe hacerse por documento
            // (num_identificacion), no por email.
            'email' => 'required|email|max:255',
            'nacionalidad' => 'nullable|string|max:255',
            'tipo_documento' => 'nullable|string|max:255|in:DNI,Pasaporte',
            'num_identificacion' => 'nullable|string|max:255',
            'fecha_expedicion_doc' => 'nullable|date',
            'idiomas' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            // Campos de facturación
            'tipo_cliente' => 'required|string|in:particular,empresa,autonomo',
            'es_empresa' => 'boolean',
            'requiere_factura' => 'boolean',
            'condiciones_pago' => 'nullable|string|in:contado,30_dias,60_dias,90_dias',
            'facturacion_nombre_razon_social' => 'nullable|string|max:255',
            'facturacion_nif_cif' => 'nullable|string|max:20',
            'facturacion_direccion' => 'nullable|string|max:255',
            'facturacion_localidad' => 'nullable|string|max:255',
            'facturacion_codigo_postal' => 'nullable|string|max:10',
            'facturacion_provincia' => 'nullable|string|max:255',
            'facturacion_pais' => 'nullable|string|max:255',
            'facturacion_email' => 'nullable|email|max:255',
            'facturacion_telefono' => 'nullable|string|max:20',
            'observaciones_facturacion' => 'nullable|string|max:1000',
        ];

        // Mensajes de validación personalizados
        $messages = [
            'alias.required' => 'El alias es obligatorio.',
            'alias.max' => 'El alias no puede tener más de 255 caracteres.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'apellido1.required' => 'El primer apellido es obligatorio.',
            'apellido1.max' => 'El primer apellido no puede tener más de 255 caracteres.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'sexo.required' => 'El sexo es obligatorio.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.unique' => 'Este email ya está registrado por otro cliente.',
            'nacionalidad.required' => 'La nacionalidad es obligatoria.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento debe ser DNI o Pasaporte.',
            'num_identificacion.required' => 'El número de identificación es obligatorio.',
            'fecha_expedicion_doc.required' => 'La fecha de expedición del documento es obligatoria.',
            'fecha_expedicion_doc.date' => 'La fecha de expedición debe ser una fecha válida.',
            'idiomas.required' => 'Los idiomas son obligatorios.',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules, $messages);

        try {
            // [FIX 2026-04-17] Actualizamos solo los campos que vienen con valor
            // en la peticion. Asi los formularios parciales (modales, tabs) no
            // blanquean campos ya rellenados como fecha_nacimiento, idiomas o
            // nacionalidad. Los booleanos se tratan aparte porque false es un
            // valor legitimo que queremos guardar.
            $dataToUpdate = [];
            $boolFields = ['es_empresa', 'requiere_factura'];
            foreach ($validatedData as $key => $value) {
                if (in_array($key, $boolFields, true)) {
                    // Booleanos: persistir siempre
                    $dataToUpdate[$key] = (bool) $value;
                } elseif ($value !== null && $value !== '') {
                    $dataToUpdate[$key] = $value;
                }
                // Si value es null o '', NO lo incluimos para no pisar el valor
                // existente en DB. Si realmente hay que vaciar un campo, usa el
                // endpoint especifico correspondiente.
            }

            $cliente->update($dataToUpdate);

            return redirect()->route('clientes.index')
                ->with('swal_success', '¡Cliente actualizado exitosamente!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->inactivo = 1;
            $cliente->save();

            return redirect()->route('clientes.index')
                ->with('swal_success', '¡Cliente inactivado exitosamente!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al inactivar el cliente: ' . $e->getMessage());
        }
    }
}
