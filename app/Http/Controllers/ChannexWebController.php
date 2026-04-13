<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\RatePlan;
use App\Models\RatePlanOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChannexWebController extends Controller
{
    private $apiUrl;
    private $apiToken;
    public function __construct()
    {
        $this->apiUrl = env('CHANNEX_URL');
        $this->apiToken = env('CHANNEX_TOKEN');
    }
    // private $apiUrl = 'https://staging.channex.io/api/v1';
    // private $apiToken = 'uMxPHon+J28pd17nie3qeU+kF7gUulWjb2UF5SRFr4rSIhmLHLwuL6TjY92JGxsx';

    // Crear Propiedad
    public function index()
    {
        $properties = Apartamento::all(); // Obtiene todas las propiedades
    return view('admin.channex.index', compact('properties'));
    }
    public function createProperty()
    {
        return view('admin.channex.createPropiedad');
    }
    public function createTestProperty()
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("{$this->apiUrl}/properties", [
            'property' => [
                'title' => 'Demo Hotel PMS',
                'currency' => 'GBP',
                'email' => 'hotel@channex.io',
                'phone' => '01267237037',
                'zip_code' => 'SA23 2JH',
                'country' => 'GB',
                'state' => 'Demo State',
                'city' => 'Demo Town',
                'address' => 'Demo Street',
                'longitude' => '-0.2416781',
                'latitude' => '51.5285582',
                'timezone' => 'Europe/London',
                'facilities' => [],
                'property_type' => 'hotel',
                'settings' => [
                    'allow_availability_autoupdate_on_confirmation' => true,
                    'allow_availability_autoupdate_on_modification' => true,
                    'allow_availability_autoupdate_on_cancellation' => true,
                    'min_stay_type' => 'both',
                    'min_price' => null,
                    'max_price' => null,
                    'state_length' => 500,
                    'cut_off_time' => '00:00:00',
                    'cut_off_days' => 0,
                ],
                'content' => [
                    'description' => 'Some Property Description Text',
                    'important_information' => 'Some important notes about property',
                ],
            ],
        ]);

        if ($response->successful()) {
            return response()->json([
                'message' => 'Propiedad creada con éxito',
                'property' => $response->json(),
            ]);
        }

        return response()->json([
            'message' => 'Error al crear la propiedad',
            'error' => $response->json(),
        ], $response->status());
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'country' => 'required|string|max:50',
            'state' => 'required|string|max:100',
            'timezone' => 'required|string|max:50',
            'property_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'important_information' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'zip_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'website' => 'nullable|url',
            'photos' => 'nullable|array',
            'photos.*.file' => 'nullable|file|image|max:2048',
            'photos.*.position' => 'nullable|integer',
            'photos.*.author' => 'nullable|string',
            'photos.*.kind' => 'nullable|string',
            'photos.*.description' => 'nullable|string',
        ]);

        $photos = [];
        if ($request->has('photos') && isset($request->photos[0]->file)) {
            foreach ($request->file('photos') as $index => $photo) {
                // Subir el archivo al almacenamiento
                $path = $photo['file']->store('photos', 'public');

                // Crear la entrada para las fotos
                $photos[] = [
                    //'url' => url(Storage::url($path)), // Asegura el esquema completo
                    'url' => 'https://apartamentosalgeciras.com/wp-content/uploads/2022/10/HAWKINS-SUITES-26-1.jpeg', // Asegura el esquema completo
                    'position' => $request->input("photos.{$index}.position", $index),
                    'author' => $request->input("photos.{$index}.author"),
                    'description' => $request->input("photos.{$index}.description"),
                ];
            }
        }
        //dd($photos);
        $response = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("{$this->apiUrl}/properties", [
            'property' => [
                'title' => $validatedData['title'],
                'currency' => $validatedData['currency'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'zip_code' => $validatedData['zip_code'],
                'country' => $validatedData['country'],
                'state' => $validatedData['state'],
                'city' => $validatedData['city'],
                'address' => $validatedData['address'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'timezone' => $validatedData['timezone'],
                'website' => $validatedData['website'],
                'property_type' => $validatedData['property_type'],
                'content' => [
                    'description' => $validatedData['description'],
                    'important_information' => $validatedData['important_information'],
                    'photos' => $photos,
                ],
            ],
        ]);
        //dd($response->json());

        if ($response->successful()) {
            $propertyId = $response->json('data.attributes.id');

            // Guardar apartamento
            $apartamento = Apartamento::create([
                'nombre' => $validatedData['title'],
                'titulo' => $validatedData['title'],
                'id_channex' => $propertyId,
                'currency' => $validatedData['currency'],
                'country' => $validatedData['country'],
                'state' => $validatedData['state'],
                'city' => $validatedData['city'],
                'address' => $validatedData['address'],
                'zip_code' => $validatedData['zip_code'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'timezone' => $validatedData['timezone'],
                'property_type' => $validatedData['property_type'],
                'description' => $validatedData['description'],
                'important_information' => $validatedData['important_information'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'website' => $validatedData['website'],
                'edificio_id' => $validatedData['edificio_id'],
            ]);

            // Guardar fotos relacionadas
            foreach ($photos as $photo) {
                $apartamento->photos()->create($photo);
            }

            // Registrar webhooks
            $this->registerWebhooks($propertyId);

            return redirect()->route('channex.createProperty')->with('success', 'Propiedad creada con éxito');
        }

        return redirect()->back()->withErrors(['error' => 'Error al crear la propiedad'])->withInput();
    }

    private function registerWebhooks($propertyId)
    {
        $webhookEvents = [
            'ari',
            'booking',
            'booking_unmapped_room',
            'booking_unmapped_rate',
            'message',
            'sync_error',
            'reservation_request',
            'alteration_request',
            'review',
        ];

        $webhookUrl = config('services.channex.webhook_url'); // Define esta URL en el archivo de configuración
        $headers = [
            'user-api-key' => $this->apiToken,
        ];

        foreach ($webhookEvents as $event) {
            $response = Http::withHeaders($headers)->post("{$this->apiUrl}/webhooks", [
                'webhook' => [
                    'event' => $event,
                    'url' => $webhookUrl,
                    'property_id' => $propertyId,
                ],
            ]);

            if (!$response->successful()) {
                Log::error("Error creating webhook for event {$event}: " . $response->body());
            }
        }
    }



//return $response->json();

    public function createRoomTypes($propertyId)
    {
        $roomTypes = [
            ['name' => 'Twin Room', 'occupancy' => 2],
            ['name' => 'Double Room', 'occupancy' => 2],
        ];

        $results = [];
        foreach ($roomTypes as $roomType) {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/room-types", array_merge($roomType, ['property_id' => $propertyId]));

            if ($response->successful()) {
                $results[] = $response->json();
            } else {
                return response()->json([
                    'message' => 'Error al crear tipo de habitación',
                    'error' => $response->json(),
                ], $response->status());
            }
        }

        return response()->json([
            'message' => 'Tipos de habitación creados con éxito',
            'room_types' => $results,
        ]);
    }

    public function createRatePlans($roomTypeIds)
    {
        $ratePlans = [
            [
                'title' => 'Best Available Rate',
                'currency' => 'USD',
                'room_type_id' => $roomTypeIds['Twin Room'],
                'default_rate' => 100,
            ],
            [
                'title' => 'Bed & Breakfast',
                'currency' => 'USD',
                'room_type_id' => $roomTypeIds['Twin Room'],
                'default_rate' => 120,
            ],
            [
                'title' => 'Best Available Rate',
                'currency' => 'USD',
                'room_type_id' => $roomTypeIds['Double Room'],
                'default_rate' => 100,
            ],
            [
                'title' => 'Bed & Breakfast',
                'currency' => 'USD',
                'room_type_id' => $roomTypeIds['Double Room'],
                'default_rate' => 120,
            ],
        ];

        $results = [];
        foreach ($ratePlans as $ratePlan) {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/rate-plans", $ratePlan);

            if ($response->successful()) {
                $results[] = $response->json();
            } else {
                return response()->json([
                    'message' => 'Error al crear plan de tarifas',
                    'error' => $response->json(),
                ], $response->status());
            }
        }

        return response()->json([
            'message' => 'Planes de tarifas creados con éxito',
            'rate_plans' => $results,
        ]);
    }

    public function createDistributionChannels($propertyId)
    {
        $channels = [
            [
                'name' => 'Booking.com',
                'property_id' => $propertyId,
                'channel_code' => 'booking_com',
            ],
            [
                'name' => 'Airbnb',
                'property_id' => $propertyId,
                'channel_code' => 'airbnb',
            ],
            // Añadir más canales según sea necesario
        ];

        $results = [];
        foreach ($channels as $channel) {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/distribution-channels", $channel);

            if ($response->successful()) {
                $results[] = $response->json();
            } else {
                return response()->json([
                    'message' => 'Error al crear canal de distribución',
                    'error' => $response->json(),
                ], $response->status());
            }
        }

        return response()->json([
            'message' => 'Canales de distribución creados con éxito',
            'channels' => $results,
        ]);
    }


    public function createBooking($channelCode, $propertyId, $roomTypeId)
    {
        $bookingData = [
            'property_id' => $propertyId,
            'channel_code' => $channelCode, // Booking.com o Airbnb, por ejemplo
            'room_type_id' => $roomTypeId,
            'check_in' => '2024-12-10', // Fecha de entrada
            'check_out' => '2024-12-15', // Fecha de salida
            'guest_name' => 'John Doe', // Nombre del huésped
            'guest_email' => 'johndoe@example.com', // Email del huésped
            'guest_phone' => '1234567890', // Teléfono del huésped
        ];

        $response = Http::withToken($this->apiToken)
            ->post("{$this->apiUrl}/bookings", $bookingData);

        if ($response->successful()) {
            return response()->json([
                'message' => 'Reserva creada con éxito',
                'booking' => $response->json(),
            ]);
        }

        return response()->json([
            'message' => 'Error al crear reserva',
            'error' => $response->json(),
        ], $response->status());
    }

    public function confirmBooking($bookingId)
    {
        $response = Http::withToken($this->apiToken)
            ->post("{$this->apiUrl}/bookings/{$bookingId}/confirm");

        if ($response->successful()) {
            return response()->json([
                'message' => 'Reserva confirmada con éxito',
                'confirmation' => $response->json(),
            ]);
        }

        return response()->json([
            'message' => 'Error al confirmar reserva',
            'error' => $response->json(),
        ], $response->status());
    }


    public function fullSync(Request $request)
    {
        // Datos necesarios para la solicitud
        // $apiKey = 'uMxPHon+J28pd17nie3qeU+kF7gUulWjb2UF5SRFr4rSIhmLHLwuL6TjY92JGxsx'; // Cambia esto por tu API Key real
        $providerCode = 'OpenChannel'; // Cambia esto por tu código de proveedor real
        $hotelCode = '12152494'; // Cambia esto por tu código de hotel real

        // URL de la API
        $url = 'https://app.channex.io/api/v1/channel_webhooks/open_channel/request_full_sync';

        try {
            // Realizar la solicitud POST
            $response = Http::withHeaders([
                'user-api-key' => $this->apiToken,
            ])->post($url, [
                'provider_code' => $providerCode,
                'hotel_code' => $hotelCode,
            ]);

            // Verificar el estado de la respuesta
            if ($response->successful()) {
                return view('admin.channex.fullSync', compact(['message' => 'Sincronización iniciada con éxito.']));
            } else {
                return view('admin.channex.fullSync', [
                    'message' => 'Error al iniciar la sincronización.',
                    'error' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            // Manejar errores
            return view('admin.channex.fullSync', [
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    // RATE PLANS
    public function ratePlansList(Request $request)
    {
        // Datos necesarios para la solicitud
        // $apiKey = 'uMxPHon+J28pd17nie3qeU+kF7gUulWjb2UF5SRFr4rSIhmLHLwuL6TjY92JGxsx'; // Cambia esto por tu API Key real
        // $url = 'https://staging.channex.io/api/v1/rate_plans';

        try {
            // Realizar la solicitud GET
            $response = Http::withHeaders([
                'user-api-key' => $this->apiToken,
            ])->get("{$this->apiUrl}/rate_plans");

            // Verificar el estado de la respuesta y retornar directamente
            if ($response->successful()) {
                //dd($response->json());
                $ratePlans = $response->json('data'); // Obtén los datos relevantes

                foreach ($ratePlans as $plan) {
                    $attributes = $plan['attributes'];
                    $relationships = $plan['relationships'];

                    // Crear o actualizar el RatePlan
                    $ratePlan = RatePlan::updateOrCreate(
                        ['id_rate_plans' => $attributes['id']],
                        [
                            'title' => $attributes['title'],
                            'currency' => $attributes['currency'],
                            'meal_type' => $attributes['meal_type'],
                            'rate_mode' => $attributes['rate_mode'],
                            'sell_mode' => $attributes['sell_mode'],
                            'property_id' => $relationships['property']['data']['id'] ?? null,
                            'room_type_id' => $relationships['room_type']['data']['id'] ?? null,
                        ]
                    );

                    // Eliminar opciones antiguas
                    //$ratePlan->options()->delete();

                    // Guardar las opciones
                    foreach ($attributes['options'] as $option) {
                        RatePlanOption::create([
                            'rate_plan_id' => $ratePlan->id,
                            'rate' => $option['rate'],
                            'occupancy' => $option['occupancy'],
                            'is_primary' => $option['is_primary'] ?? false,
                            'inherit_rate' => $option['inherit_rate'] ?? false,
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al obtener los planes de tarifas.',
                    'details' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar errores y retornar directamente
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function ratePlansUpdate(Request $request)
    {
        // Datos necesarios para la solicitud
        // $apiKey = 'uMxPHon+J28pd17nie3qeU+kF7gUulWjb2UF5SRFr4rSIhmLHLwuL6TjY92JGxsx'; // Cambia esto por tu API Key real
        // $url = 'https://staging.channex.io/api/v1/rate_plans';

        try {
            // Realizar la solicitud GET
            $response = Http::withHeaders([
                'user-api-key' => $this->apiToken,
            ])->get("{$this->apiUrl}/rate_plans");

            // Verificar el estado de la respuesta y retornar directamente
            if ($response->successful()) {
                //dd($response->json());
                $ratePlans = $response->json('data'); // Obtén los datos relevantes

                foreach ($ratePlans as $plan) {
                    $attributes = $plan['attributes'];
                    $relationships = $plan['relationships'];

                    // Crear o actualizar el RatePlan
                    $ratePlan = RatePlan::updateOrCreate(
                        ['id_rate_plans' => $attributes['id']],
                        [
                            'title' => $attributes['title'],
                            'currency' => $attributes['currency'],
                            'meal_type' => $attributes['meal_type'],
                            'rate_mode' => $attributes['rate_mode'],
                            'sell_mode' => $attributes['sell_mode'],
                            'property_id' => $relationships['property']['data']['id'] ?? null,
                            'room_type_id' => $relationships['room_type']['data']['id'] ?? null,
                        ]
                    );

                    // Eliminar opciones antiguas
                    //$ratePlan->options()->delete();

                    // Guardar las opciones
                    foreach ($attributes['options'] as $option) {
                        RatePlanOption::create([
                            'rate_plan_id' => $ratePlan->id,
                            'rate' => $option['rate'],
                            'occupancy' => $option['occupancy'],
                            'is_primary' => $option['is_primary'] ?? false,
                            'inherit_rate' => $option['inherit_rate'] ?? false,
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al obtener los planes de tarifas.',
                    'details' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar errores y retornar directamente
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
