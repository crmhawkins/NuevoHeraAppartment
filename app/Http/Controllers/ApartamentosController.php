<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\ApartamentoPhoto;
use App\Models\Edificio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;

class ApartamentosController extends Controller
{
    private $apiUrl;
    private $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.channex.api_url', 'https://staging.channex.io/api/v1');
        $this->apiToken = config('services.channex.api_token');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pisos = Apartamento::all();
        return view('apartamentos.index', compact('pisos'));
    }

    public function indexAdmin(Request $request)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        $edificioId = $request->get('edificio_id');
        $apartamentoId = $request->get('apartamento_id');
        
        // Log the search operation
        $this->logRead('APARTAMENTOS', null, [
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
            'edificio_id' => $edificioId,
            'apartamento_id' => $apartamentoId
        ]);
        
        $apartamentoslist = Apartamento::all();
        $apartamentos = Apartamento::when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', '%' . $search . '%')
                      ->orWhere('titulo', 'like', '%' . $search . '%')
                      ->orWhere('id', 'like', '%' . $search . '%');
                });
            })
            ->when($edificioId, function ($query, $edificioId) {
                $query->where('edificio_id', $edificioId);
            })
            ->when($apartamentoId, function ($query, $apartamentoId) {
                $query->where('id', $apartamentoId);
            })
            ->orderBy($sort, $order)
            ->paginate(20);

        $edificios = Edificio::all();

        // Calcular estadísticas para cada apartamento del año actual
        $añoActual = date('Y');
        $estadisticasApartamentos = [];
        
        foreach ($apartamentos as $apartamento) {
            $reservasAño = $apartamento->reservas()->whereYear('fecha_entrada', $añoActual)->get();
            
            $estadisticasApartamentos[$apartamento->id] = [
                'ingresos_año' => $reservasAño->sum('precio'),
                'ocupaciones_año' => $reservasAño->count(),
                'ingresos_netos' => $reservasAño->sum('neto')
            ];
        }

        return view('admin.apartamentos.index', compact('apartamentoslist', 'apartamentos', 'edificios', 'search', 'sort', 'order', 'estadisticasApartamentos', 'añoActual'));
    }

    public function createAdmin()
    {
        $edificios = Edificio::all();
        $servicios = \App\Models\Servicio::activos()->ordenados()->get();
        return view('admin.apartamentos.create', compact('edificios', 'servicios'));
    }

    public function editAdmin($id)
    {
        $apartamento = Apartamento::with('photos')->findOrFail($id);
        $edificios = Edificio::all();
        $servicios = \App\Models\Servicio::activos()->ordenados()->get();
        $serviciosSeleccionados = $apartamento->servicios->pluck('id')->toArray();
        $photos = $apartamento->photos()->ordenadas()->get();
        return view('admin.apartamentos.edit', compact('apartamento', 'edificios', 'servicios', 'serviciosSeleccionados', 'photos'));
    }

    public function updateAdmin(Request $request, $id)
    {
        $apartamento = Apartamento::findOrFail($id);
        
        // Log the update attempt
        $this->logUpdate('APARTAMENTO', $id, $apartamento->toArray(), $request->all());

        // Reglas de validación completas para Channex + Booking.com
        $rules = [
            'edificio_id' => 'required|exists:edificios,id',
            'title' => 'required|string|max:255',
            'claves' => 'required|string|max:255',
            'property_type' => 'required|string|in:apartment,hotel,hostel,villa,guest_house',
            'country' => 'nullable|string|size:2',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'zip_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'bedrooms' => 'nullable|integer|min:1',
            'bathrooms' => 'nullable|numeric|min:0.5',
            'max_guests' => 'nullable|integer|min:1',
            'size' => 'nullable|numeric|min:1',
            'id_booking' => 'nullable|string|max:100',
            'id_airbnb' => 'nullable|string|max:100',
            'id_web' => 'nullable|string|max:100',
            // Booking.com fields
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'check_in_instructions' => 'nullable|string',
            'check_out_instructions' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'cancellation_policy' => 'nullable|in:flexible,moderate,strict,super_strict',
            'cancellation_details' => 'nullable|string',
            'cancellation_deadline' => 'nullable|integer|min:0',
            'min_age_child' => 'nullable|integer|min:0',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'wifi_speed' => 'nullable|string|max:50',
            'wifi_coverage' => 'nullable|in:full,partial,none',
            'parking_spaces' => 'nullable|integer|min:0',
            'parking_price_per_day' => 'nullable|numeric|min:0',
            'extra_bed_price' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'security_deposit_type' => 'nullable|in:cash,credit_card,none',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'tourist_tax' => 'nullable|numeric|min:0',
            'city_tax' => 'nullable|numeric|min:0',
            'nearest_beach_distance' => 'nullable|numeric|min:0',
            'nearest_airport_distance' => 'nullable|numeric|min:0',
            'metro_station_distance' => 'nullable|numeric|min:0',
            'bus_stop_distance' => 'nullable|numeric|min:0',
            'floor_number' => 'nullable|integer',
            'building_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'last_renovation_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'balcony_size' => 'nullable|numeric|min:0',
            'terrace_size' => 'nullable|numeric|min:0',
            'rating_score' => 'nullable|numeric|between:0,10',
            'reviews_count' => 'nullable|integer|min:0',
            'cleanliness_rating' => 'nullable|numeric|between:0,10',
            'location_rating' => 'nullable|numeric|between:0,10',
            'value_rating' => 'nullable|numeric|between:0,10',
            'service_rating' => 'nullable|numeric|between:0,10',
            'payment_options' => 'nullable|array',
            'languages_spoken' => 'nullable|array',
            'bed_types' => 'nullable|array',
            'view_type' => 'nullable|string|max:50',
            'nearest_beach_name' => 'nullable|string|max:255',
            'nearest_airport_name' => 'nullable|string|max:255',
        ];

        // Mensajes de validación personalizados
        $messages = [
            'edificio_id.required' => 'El edificio es obligatorio.',
            'edificio_id.exists' => 'El edificio seleccionado no existe.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede tener más de 255 caracteres.',
            'claves.required' => 'Las claves de acceso son obligatorias.',
            'claves.max' => 'Las claves no pueden tener más de 255 caracteres.',
            'property_type.required' => 'El tipo de propiedad es obligatorio.',
            'property_type.in' => 'El tipo de propiedad debe ser válido.',
            'country.size' => 'El código de país debe tener exactamente 2 caracteres.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'timezone.required' => 'La zona horaria es obligatoria.',
            'description.max' => 'La descripción no puede tener más de 2000 caracteres.',
            'important_information.max' => 'La información importante no puede tener más de 2000 caracteres.',
            'email.email' => 'El formato del email no es válido.',
            'website.url' => 'El formato de la URL no es válido.',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules, $messages);

        try {
            // Mapear campos del formulario al modelo
            $dataToUpdate = [
                'titulo' => $validatedData['title'] ?? null,
                'claves' => $validatedData['claves'] ?? null,
                'property_type' => $validatedData['property_type'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'zip_code' => $validatedData['zip_code'] ?? null,
                'description' => $validatedData['description'] ?? null,
                'bedrooms' => $validatedData['bedrooms'] ?? null,
                'bathrooms' => $validatedData['bathrooms'] ?? null,
                'max_guests' => $validatedData['max_guests'] ?? null,
                'size' => $validatedData['size'] ?? null,
                'important_information' => $request->input('important_information') ?? null,
                'email' => $request->input('email') ?? null,
                'phone' => $request->input('phone') ?? null,
                'website' => $request->input('website') ?? null,
                'edificio_id' => $validatedData['edificio_id'],
                'tipo_cerradura' => $request->input('tipo_cerradura', 'manual'),
                'tuyalaravel_lock_id' => $request->input('tuyalaravel_lock_id') ?: null,
                'ttlock_lock_id' => $request->input('ttlock_lock_id') ?: null,
            ];

            // Campos Booking.com - Check-in/Check-out
            if ($request->has('check_in_time')) {
                $dataToUpdate['check_in_time'] = $validatedData['check_in_time'] ?? null;
            }
            if ($request->has('check_out_time')) {
                $dataToUpdate['check_out_time'] = $validatedData['check_out_time'] ?? null;
            }
            $dataToUpdate['check_in_instructions'] = $request->input('check_in_instructions');
            $dataToUpdate['check_out_instructions'] = $request->input('check_out_instructions');
            
            // Campos Booking.com - Amenities (booleanos)
            $amenityFields = [
                'wifi', 'wifi_free', 'parking', 'parking_free', 'air_conditioning', 'heating',
                'tv', 'cable_tv', 'kitchen', 'kitchen_fully_equipped', 'dishwasher', 'washing_machine',
                'dryer', 'microwave', 'refrigerator', 'oven', 'coffee_machine', 'balcony',
                'terrace', 'garden', 'swimming_pool', 'elevator', 'pets_allowed', 'smoking_allowed',
                'accessible', 'safe', 'hair_dryer', 'iron', 'linen', 'towels', 'workspace',
                'public_transport_nearby', 'sofa_bed', 'extra_bed_available', 'parking_reservation_required',
                'fire_extinguisher', 'smoke_detector', 'first_aid_kit', 'tourist_tax_included', 'city_tax_included'
            ];
            foreach ($amenityFields as $field) {
                $dataToUpdate[$field] = $request->has($field) ? true : false;
            }
            
            // Campos Booking.com - Numéricos y texto
            $numericFields = [
                'parking_spaces', 'parking_price_per_day', 'extra_bed_price', 'security_deposit',
                'cleaning_fee', 'tourist_tax', 'city_tax', 'nearest_beach_distance',
                'nearest_airport_distance', 'metro_station_distance', 'bus_stop_distance',
                'floor_number', 'building_year', 'last_renovation_year', 'balcony_size',
                'terrace_size', 'rating_score', 'reviews_count', 'cleanliness_rating',
                'location_rating', 'value_rating', 'service_rating', 'cancellation_deadline', 'min_age_child'
            ];
            foreach ($numericFields as $field) {
                if ($request->has($field)) {
                    $dataToUpdate[$field] = $validatedData[$field] ?? null;
                }
            }
            
            // Campos Booking.com - Texto
            $textFields = [
                'house_rules', 'cancellation_details', 'wifi_speed', 'nearest_beach_name',
                'nearest_airport_name', 'view_type'
            ];
            foreach ($textFields as $field) {
                if ($request->has($field)) {
                    $dataToUpdate[$field] = $validatedData[$field] ?? null;
                }
            }
            
            // Campos Booking.com - Enums y arrays
            if ($request->has('cancellation_policy')) {
                $dataToUpdate['cancellation_policy'] = $validatedData['cancellation_policy'] ?? null;
            }
            if ($request->has('security_deposit_type')) {
                $dataToUpdate['security_deposit_type'] = $validatedData['security_deposit_type'] ?? null;
            }
            if ($request->has('wifi_coverage')) {
                $dataToUpdate['wifi_coverage'] = $validatedData['wifi_coverage'] ?? null;
            }
            if ($request->has('payment_options')) {
                $dataToUpdate['payment_options'] = $validatedData['payment_options'] ?? null;
            }
            if ($request->has('languages_spoken')) {
                $dataToUpdate['languages_spoken'] = $validatedData['languages_spoken'] ?? null;
            }
            if ($request->has('bed_types')) {
                $dataToUpdate['bed_types'] = $validatedData['bed_types'] ?? null;
            }
            if ($request->has('quiet_hours_start')) {
                $dataToUpdate['quiet_hours_start'] = $validatedData['quiet_hours_start'] ?? null;
            }
            if ($request->has('quiet_hours_end')) {
                $dataToUpdate['quiet_hours_end'] = $validatedData['quiet_hours_end'] ?? null;
            }

            // Actualizar el modelo
            $apartamento->fill($dataToUpdate);
            $apartamento->save();

            // Sincronizar servicios (many-to-many)
            if ($request->has('servicios')) {
                $apartamento->servicios()->sync($request->input('servicios', []));
            } else {
                $apartamento->servicios()->detach();
            }

            return redirect()->route('apartamentos.admin.index')
                ->with('swal_success', '¡Apartamento actualizado exitosamente!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el apartamento: ' . $e->getMessage());
        }
    }

    public function storeAdmin(Request $request)
    {
        // Reglas de validación completas para Channex
        $rules = [
            'claves' => 'required|string|max:255',
            'edificio_id' => 'required|exists:edificios,id',
            'titulo' => 'required|string|max:255',
            'property_type' => 'required|string|in:apartment,hotel,hostel,villa,guest_house',
            'currency' => 'required|string|size:3',
            'country' => 'required|string|size:2',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'zip_code' => 'required|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timezone' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'important_information' => 'nullable|string|max:2000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:500',
        ];

        // Mensajes de validación personalizados
        $messages = [
            'claves.required' => 'Las claves de acceso son obligatorias.',
            'claves.max' => 'Las claves no pueden tener más de 255 caracteres.',
            'edificio_id.required' => 'El edificio es obligatorio.',
            'edificio_id.exists' => 'El edificio seleccionado no existe.',
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'property_type.required' => 'El tipo de propiedad es obligatorio.',
            'property_type.in' => 'El tipo de propiedad debe ser válido.',
            'currency.required' => 'La moneda es obligatoria.',
            'currency.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'country.required' => 'El país es obligatorio.',
            'country.size' => 'El código de país debe tener exactamente 2 caracteres.',
            'state.required' => 'El estado/provincia es obligatorio.',
            'city.required' => 'La ciudad es obligatoria.',
            'address.required' => 'La dirección es obligatoria.',
            'zip_code.required' => 'El código postal es obligatorio.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'timezone.required' => 'La zona horaria es obligatoria.',
            'description.max' => 'La descripción no puede tener más de 2000 caracteres.',
            'important_information.max' => 'La información importante no puede tener más de 2000 caracteres.',
            'email.email' => 'El formato del email no es válido.',
            'website.url' => 'El formato de la URL no es válido.',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules, $messages);

        try {
            // Crear el apartamento con los datos validados
            $apartamento = Apartamento::create($validatedData);

            // Sincronizar servicios (many-to-many)
            if ($request->has('servicios')) {
                $apartamento->servicios()->sync($request->input('servicios', []));
            }

            // Procesar fotos si se enviaron
            $fotosSubidas = 0;
            if ($request->hasFile('photos')) {
                try {
                    $files = $request->file('photos');
                    
                    foreach ($files as $index => $photo) {
                        if ($photo && $photo->isValid()) {
                            $path = $photo->store("apartamentos/{$apartamento->id}", 'public');
                            
                            // Primera foto es principal
                            $isPrimary = ($fotosSubidas === 0);
                            
                            // Si esta será la primera foto, quitar principal de todas las existentes
                            if ($isPrimary) {
                                $apartamento->photos()->update(['is_primary' => false]);
                            }
                            
                            ApartamentoPhoto::create([
                                'apartamento_id' => $apartamento->id,
                                'path' => $path,
                                'url' => Storage::url($path),
                                'position' => $fotosSubidas + 1,
                                'is_primary' => $isPrimary,
                            ]);
                            
                            $fotosSubidas++;
                        }
                    }
                    
                    if ($fotosSubidas > 0) {
                        Alert::success('Éxito', "¡Apartamento creado con {$fotosSubidas} foto(s) exitosamente!");
                    } else {
                        Alert::success('Éxito', '¡Apartamento creado exitosamente!');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error al subir fotos en creación de apartamento', [
                        'apartamento_id' => $apartamento->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // No fallar el proceso si hay error con las fotos
                    Alert::warning('Advertencia', 'El apartamento se creó correctamente, pero hubo un problema al subir algunas fotos.');
                }
            } else {
                Alert::success('Éxito', '¡Apartamento creado exitosamente!');
            }

            // Redirigir a edición para poder añadir más fotos o configurar
            return redirect()->route('apartamentos.admin.edit', $apartamento->id)
                ->with('swal_success', '¡Apartamento creado exitosamente! Puedes añadir más fotos aquí.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el apartamento: ' . $e->getMessage());
        }
    }

    public function registrarWebhooks($id){
        try {
            $apartamento = Apartamento::findOrFail($id);

            $url = 'https://staging.channex.io/api/v1/webhooks';

            $masks = [
                [
                    'nombre' => 'ari',
                    'url' => 'ari-changes'
                ],
                [
                    'nombre' => 'booking',
                    'url' => 'booking-any'
                ],
                [
                    'nombre' => 'booking_unmapped_room',
                    'url' => 'booking-unmapped-room'
                ],
                [
                    'nombre' => 'booking_unmapped_rate',
                    'url' => 'booking-unmapped-rate'
                ],
                [
                    'nombre' => 'message',
                    'url' => 'message'
                ],
                [
                    'nombre' => 'sync_error',
                    'url' => 'sync-error'
                ],
                [
                    'nombre' => 'reservation_request',
                    'url' => 'reservation-request'
                ],
                [
                    'nombre' => 'alteration_request',
                    'url' => 'alteration_request'
                ],
                [
                    'nombre' => 'review',
                    'url' => 'review'
                ],
            ];
            $responses = [];

            foreach ($masks as $mask) {
                $data = [
                    "property_id" => $apartamento->id_channex,
                    "callback_url" => "https://crm.apartamentosalgeciras.com/api/webhooks/". $apartamento->id ."/" . $mask['url'],
                    "event_mask" => $mask['nombre'],
                    "request_params" => new \stdClass(),
                    "headers" => new \stdClass(),
                    "is_active" => true,
                    "send_data" => true,
                ];

                // Petición a la API
                $response = Http::withHeaders([
                    'user-api-key' => $this->apiToken,
                ])->post($url, ['webhook' => $data]);

                // Manejo de respuesta
                if ($response->successful()) {
                    $responses[] = [
                        'status' => 'success',
                        'message' => 'Webhook registrado con éxito',
                        'data' => $response->json(),
                    ];
                } else {
                    $responses[] = [
                        'status' => 'error',
                        'message' => 'Error al registrar webhook',
                        'data' => $response->json(),
                    ];
                }
            }
            
            return response()->json($responses);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Display the specified apartment in admin panel.
     */
    public function showAdmin(string $id, Request $request)
    {
        try {
            $apartamento = Apartamento::with(['photos', 'tarifas', 'edificioRel', 'reservas.cliente', 'reservas.estado'])->findOrFail($id);
            $edificios = Edificio::all();
            
            // Obtener filtros de fecha
            $año = $request->get('año', date('Y'));
            $mes = $request->get('mes');
            
            // Calcular estadísticas
            $estadisticas = $this->calcularEstadisticasApartamento($apartamento, $año, $mes);
            
            return view('admin.apartamentos.show', compact('apartamento', 'edificios', 'estadisticas', 'año', 'mes'));
        } catch (\Exception $e) {
            return redirect()->route('apartamentos.admin.index')
                ->with('swal_error', 'Apartamento no encontrado: ' . $e->getMessage());
        }
    }

    /**
     * Calcular estadísticas de un apartamento
     */
    private function calcularEstadisticasApartamento($apartamento, $año, $mes = null)
    {
        $query = $apartamento->reservas()->whereYear('fecha_entrada', $año);
        
        if ($mes) {
            $query->whereMonth('fecha_entrada', $mes);
        }
        
        $reservas = $query->get();
        
        // Calcular estadísticas de número de personas
        $estadisticasPersonas = $this->calcularEstadisticasPersonas($reservas);
        
        return [
            'total_reservas' => $reservas->count(),
            'total_ingresos' => $reservas->sum('precio'),
            'ingresos_netos' => $reservas->sum('neto'),
            'ocupacion_dias' => $reservas->sum(function($reserva) {
                return \Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays($reserva->fecha_salida);
            }),
            'reservas_activas' => $reservas->where('estado_id', 3)->count(),
            'reservas_completadas' => $reservas->where('estado_id', 4)->count(),
            'reservas_canceladas' => $reservas->where('estado_id', 5)->count(),
            'promedio_por_reserva' => $reservas->count() > 0 ? $reservas->avg('precio') : 0,
            'mes_mas_ocupado' => $this->obtenerMesMasOcupado($apartamento, $año),
            'reservas_por_mes' => $this->obtenerReservasPorMes($apartamento, $año),
            // Estadísticas de personas
            'estadisticas_personas' => $estadisticasPersonas
        ];
    }

    /**
     * Calcular estadísticas de número de personas por reserva
     */
    private function calcularEstadisticasPersonas($reservas)
    {
        $totalReservas = $reservas->count();
        
        if ($totalReservas === 0) {
            return [
                'media_adultos' => 0,
                'porcentajes' => [
                    '1_persona' => 0,
                    '2_personas' => 0,
                    '3_personas' => 0,
                    '4_personas' => 0
                ],
                'conteos' => [
                    '1_persona' => 0,
                    '2_personas' => 0,
                    '3_personas' => 0,
                    '4_personas' => 0
                ],
                'total_con_datos' => 0
            ];
        }
        
        // Contar directamente desde la colección de reservas (convertir strings a enteros)
        $conteos = [
            '1_persona' => $reservas->where('numero_personas', '1')->count(),
            '2_personas' => $reservas->where('numero_personas', '2')->count(),
            '3_personas' => $reservas->where('numero_personas', '3')->count(),
            '4_personas' => $reservas->where('numero_personas', '4')->count()
        ];
        
        // Calcular total de reservas con datos de personas
        $totalConDatos = $reservas->filter(function($reserva) {
            return !empty($reserva->numero_personas) && $reserva->numero_personas !== '0';
        })->count();
        
        // Calcular media de adultos
        $mediaAdultos = 0;
        if ($totalConDatos > 0) {
            $sumaPersonas = $reservas->filter(function($reserva) {
                return !empty($reserva->numero_personas) && $reserva->numero_personas !== '0';
            })->sum(function($reserva) {
                return (int)$reserva->numero_personas;
            });
            $mediaAdultos = $sumaPersonas / $totalConDatos;
        }
        
        // Calcular porcentajes
        $porcentajes = [
            '1_persona' => $totalReservas > 0 ? round(($conteos['1_persona'] / $totalReservas) * 100, 1) : 0,
            '2_personas' => $totalReservas > 0 ? round(($conteos['2_personas'] / $totalReservas) * 100, 1) : 0,
            '3_personas' => $totalReservas > 0 ? round(($conteos['3_personas'] / $totalReservas) * 100, 1) : 0,
            '4_personas' => $totalReservas > 0 ? round(($conteos['4_personas'] / $totalReservas) * 100, 1) : 0
        ];
        
        return [
            'media_adultos' => round($mediaAdultos, 1),
            'porcentajes' => $porcentajes,
            'conteos' => $conteos,
            'total_con_datos' => $totalConDatos
        ];
    }

    /**
     * Obtener el mes más ocupado del año
     */
    private function obtenerMesMasOcupado($apartamento, $año)
    {
        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $count = $apartamento->reservas()
                ->whereYear('fecha_entrada', $año)
                ->whereMonth('fecha_entrada', $i)
                ->count();
            $meses[$i] = $count;
        }
        
        $mesMasOcupado = array_keys($meses, max($meses))[0];
        return [
            'mes' => $mesMasOcupado,
            'nombre' => \Carbon\Carbon::create($año, $mesMasOcupado, 1)->format('F'),
            'reservas' => $meses[$mesMasOcupado]
        ];
    }

    /**
     * Obtener reservas por mes para el gráfico
     */
    private function obtenerReservasPorMes($apartamento, $año)
    {
        $datos = [];
        for ($i = 1; $i <= 12; $i++) {
            $reservas = $apartamento->reservas()
                ->whereYear('fecha_entrada', $año)
                ->whereMonth('fecha_entrada', $i)
                ->get();
            
            $datos[] = [
                'mes' => \Carbon\Carbon::create($año, $i, 1)->format('M'),
                'reservas' => $reservas->count(),
                'ingresos' => $reservas->sum('precio')
            ];
        }
        return $datos;
    }

    /**
     * Display apartment statistics in admin panel.
     */
    public function estadisticasAdmin(string $id)
    {
        try {
            $apartamento = Apartamento::with([
                'photos', 
                'tarifas', 
                'edificioRel', 
                'reservas.cliente', 
                'reservas.estado'
            ])->findOrFail($id);

            // Estadísticas generales
            $totalReservas = $apartamento->reservas->count();
            $precioPromedio = $apartamento->reservas->avg('precio') ?? 0;
            $totalIngresos = $apartamento->reservas->sum('precio') ?? 0;
            $totalFotos = $apartamento->photos->count();

            // Estadísticas por mes (últimos 12 meses)
            $estadisticasMensuales = [];
            for ($i = 11; $i >= 0; $i--) {
                $fecha = now()->subMonths($i);
                $mes = $fecha->format('M Y');
                
                $reservasMes = $apartamento->reservas()
                    ->whereYear('fecha_entrada', $fecha->year)
                    ->whereMonth('fecha_entrada', $fecha->month)
                    ->get();
                
                $estadisticasMensuales[$mes] = [
                    'reservas' => $reservasMes->count(),
                    'ingresos' => $reservasMes->sum('precio'),
                    'promedio' => $reservasMes->avg('precio') ?? 0
                ];
            }

            // Estadísticas por estado de reserva
            $estadosReservas = $apartamento->reservas
                ->groupBy('estado.nombre')
                ->map(function ($reservas) {
                    return $reservas->count();
                })
                ->toArray();

            // Top clientes
            $topClientes = $apartamento->reservas
                ->groupBy('cliente.nombre')
                ->map(function ($reservas) {
                    return [
                        'nombre' => $reservas->first()->cliente->nombre ?? 'Sin nombre',
                        'total_reservas' => $reservas->count(),
                        'total_gastado' => $reservas->sum('precio'),
                        'ultima_reserva' => $reservas->max('fecha_entrada')
                    ];
                })
                ->sortByDesc('total_gastado')
                ->take(10)
                ->values()
                ->toArray();

            // Estadísticas de ocupación por día de la semana
            $ocupacionSemanal = [];
            $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            
            foreach ($diasSemana as $index => $dia) {
                $reservasDia = $apartamento->reservas()
                    ->whereRaw('WEEKDAY(fecha_entrada) = ?', [$index])
                    ->get();
                
                $ocupacionSemanal[$dia] = [
                    'reservas' => $reservasDia->count(),
                    'porcentaje' => $totalReservas > 0 ? round(($reservasDia->count() / $totalReservas) * 100, 1) : 0
                ];
            }
            
            // Convertir a array para Chart.js
            $ocupacionSemanal = array_map(function($item) {
                return [
                    'reservas' => $item['reservas'],
                    'porcentaje' => $item['porcentaje']
                ];
            }, $ocupacionSemanal);

            // Estadísticas de temporada
            $reservasTemporadaAlta = $apartamento->reservas()
                ->whereMonth('fecha_entrada', '>=', 6)
                ->whereMonth('fecha_entrada', '<=', 9)
                ->get();
            
            $reservasTemporadaBaja = $apartamento->reservas()
                ->where(function($query) {
                    $query->whereMonth('fecha_entrada', '<=', 3)
                          ->orWhereMonth('fecha_entrada', '>=', 10);
                })
                ->get();

            $estadisticasTemporada = [
                'alta' => [
                    'reservas' => $reservasTemporadaAlta->count(),
                    'ingresos' => $reservasTemporadaAlta->sum('precio'),
                    'promedio' => $reservasTemporadaAlta->avg('precio') ?? 0
                ],
                'baja' => [
                    'reservas' => $reservasTemporadaBaja->count(),
                    'ingresos' => $reservasTemporadaBaja->sum('precio'),
                    'promedio' => $reservasTemporadaBaja->avg('precio') ?? 0
                ]
            ];

            return view('admin.apartamentos.estadisticas', compact(
                'apartamento',
                'totalReservas',
                'precioPromedio',
                'totalIngresos',
                'totalFotos',
                'estadisticasMensuales',
                'estadosReservas',
                'topClientes',
                'ocupacionSemanal',
                'estadisticasTemporada'
            ));

        } catch (\Exception $e) {
            return redirect()->route('apartamentos.admin.index')
                ->with('swal_error', 'Error al cargar estadísticas: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $apartamento = Apartamento::findOrFail($id);
            
            // Log the deletion
            $this->logDelete('APARTAMENTO', $id, $apartamento->toArray());
            
            $apartamento->delete();

            return redirect()->route('apartamentos.admin.index')
                ->with('swal_success', '¡Apartamento eliminado exitosamente!');
        } catch (\Exception $e) {
            // Log the error
            $this->logError('Error al eliminar apartamento', [
                'apartamento_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el apartamento: ' . $e->getMessage());
        }
    }

    /**
     * Subir fotos del apartamento
     */
    public function uploadPhotos(Request $request, $id)
    {
        try {
            $apartamento = Apartamento::findOrFail($id);

            // Validar y capturar errores de validación para devolver JSON
            try {
                $request->validate([
                    'photos' => 'required|array|min:1',
                    'photos.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
                ], [
                    'photos.required' => 'Debes seleccionar al menos una foto.',
                    'photos.array' => 'Las fotos deben ser un array.',
                    'photos.min' => 'Debes seleccionar al menos una foto.',
                    'photos.*.required' => 'Una o más fotos no son válidas.',
                    'photos.*.image' => 'Todos los archivos deben ser imágenes.',
                    'photos.*.mimes' => 'Las imágenes deben ser JPG, PNG o WEBP.',
                    'photos.*.max' => 'Cada imagen no puede exceder 5MB.',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            $uploadedPhotos = [];
            
            if (!$request->hasFile('photos')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se seleccionaron fotos para subir'
                ], 400);
            }
            
            $files = $request->file('photos');
            if (empty($files) || (is_array($files) && count(array_filter($files)) === 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron archivos válidos'
                ], 400);
            }
            
            // Contar fotos existentes ANTES de crear nuevas (para is_primary)
            $photosCountAntes = $apartamento->photos()->count();
            $lastPosition = $apartamento->photos()->max('position') ?? 0;
            
            foreach ($files as $index => $photo) {
                if ($photo && $photo->isValid()) {
                    try {
                        // Guardar en storage/app/public/apartamentos/{id}/
                        $path = $photo->store("apartamentos/{$id}", 'public');
                        
                        // Determinar si es principal (primera foto del lote si no hay fotos previas)
                        $isPrimary = ($photosCountAntes === 0 && $index === 0);
                        
                        // Si esta será la primera foto, quitar principal de todas las existentes
                        if ($isPrimary) {
                            $apartamento->photos()->update(['is_primary' => false]);
                        }
                        
                        // Crear registro de foto
                        $apartamentoPhoto = ApartamentoPhoto::create([
                            'apartamento_id' => $apartamento->id,
                            'path' => $path,
                            'url' => Storage::url($path),
                            'position' => $lastPosition + $index + 1,
                            'is_primary' => $isPrimary,
                        ]);

                        $uploadedPhotos[] = $apartamentoPhoto;
                    } catch (\Exception $e) {
                        \Log::error('Error al procesar foto individual', [
                            'apartamento_id' => $id,
                            'file_name' => $photo->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        // Continuar con las siguientes fotos
                    }
                }
            }

            if (count($uploadedPhotos) > 0) {
                Alert::success('Éxito', count($uploadedPhotos) . ' foto(s) subida(s) correctamente.');
                return response()->json([
                    'success' => true,
                    'message' => count($uploadedPhotos) . ' foto(s) subida(s) correctamente',
                    'photos' => $uploadedPhotos
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron procesar las fotos. Verifica que sean archivos de imagen válidos.'
                ], 400);
            }

        } catch (\Exception $e) {
            Alert::error('Error', 'Error al subir las fotos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir las fotos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar foto del apartamento
     */
    public function deletePhoto($id, $photoId)
    {
        try {
            $apartamento = Apartamento::findOrFail($id);
            $photo = ApartamentoPhoto::where('apartamento_id', $id)->findOrFail($photoId);

            // Eliminar archivo físico
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }

            // Eliminar registro
            $photo->delete();

            Alert::success('Éxito', 'Foto eliminada correctamente.');
            return response()->json([
                'success' => true,
                'message' => 'Foto eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            Alert::error('Error', 'Error al eliminar la foto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Establecer foto principal
     */
    public function setPrimaryPhoto($id, $photoId)
    {
        try {
            $apartamento = Apartamento::findOrFail($id);
            $photo = ApartamentoPhoto::where('apartamento_id', $id)->findOrFail($photoId);

            // Quitar principal de todas las fotos
            $apartamento->photos()->update(['is_primary' => false]);

            // Establecer esta como principal
            $photo->update(['is_primary' => true]);

            Alert::success('Éxito', 'Foto principal actualizada.');
            return response()->json([
                'success' => true,
                'message' => 'Foto principal actualizada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar orden de fotos
     */
    public function updatePhotoOrder(Request $request, $id)
    {
        try {
            $apartamento = Apartamento::findOrFail($id);
            $order = $request->input('order', []); // Array de IDs en orden

            foreach ($order as $position => $photoId) {
                ApartamentoPhoto::where('apartamento_id', $id)
                    ->where('id', $photoId)
                    ->update(['position' => $position + 1]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
