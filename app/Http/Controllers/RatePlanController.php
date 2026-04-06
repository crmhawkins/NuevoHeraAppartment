<?php

namespace App\Http\Controllers;

use App\Models\RatePlan;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RatePlanController extends Controller
{
    private $apiUrl;
    private $apiToken;
    public function __construct()
    {
        $this->apiUrl = env('CHANNEX_URL');
        $this->apiToken = env('CHANNEX_TOKEN');
    }

    public function index()
    {
        $ratePlans = RatePlan::with('property')->get();
        return view('admin.rate-plans.index', compact('ratePlans'));
    }

    public function create()
    {
        $properties = Apartamento::all();
        return view('admin.rate-plans.create', compact('properties'));
    }

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'title' => 'required|string|max:255',
        'property_id' => 'required|exists:apartamentos,id',
        'room_type_id' => 'nullable|string',
        'tax_set_id' => 'nullable|string',
        'parent_rate_plan_id' => 'nullable|string',
        'children_fee' => 'nullable|numeric',
        'infant_fee' => 'nullable|numeric',
        'max_stay' => 'nullable|string', // Validar como string inicial
        'min_stay_arrival' => 'nullable|string', // Validar como string inicial
        'min_stay_through' => 'nullable|string', // Validar como string inicial
        'closed_to_arrival' => 'nullable|array',
        'closed_to_departure' => 'nullable|array',
        'stop_sell' => 'nullable|array',
        'options' => 'nullable',
        'currency' => 'required|string|max:3',
        'sell_mode' => 'required|string',
        'rate_mode' => 'required|string',
    ]);

    // Decodificar las cadenas JSON a arrays
    $validatedData['max_stay'] = json_decode($validatedData['max_stay'] ?? '[]', true);
    $validatedData['min_stay_arrival'] = json_decode($validatedData['min_stay_arrival'] ?? '[]', true);
    $validatedData['min_stay_through'] = json_decode($validatedData['min_stay_through'] ?? '[]', true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($validatedData['max_stay']) ||
        !is_array($validatedData['min_stay_arrival']) ||
        !is_array($validatedData['min_stay_through'])
    ) {
        return redirect()->back()->withErrors(['error' => 'Uno o más campos no tienen un formato JSON válido.'])->withInput();
    }

    // Obtener IDs de Channex
    $property = Apartamento::findOrFail($validatedData['property_id']);
    $channexPropertyId = $property->id_channex;

    $channexRoomTypeId = null;
    if (!empty($validatedData['room_type_id'])) {
        $roomType = \App\Models\RoomType::findOrFail($validatedData['room_type_id']);
        $channexRoomTypeId = $roomType->id_channex;
    }

    // Preparar datos para Channex
    $ratePlanData = [
        'rate_plan' => [
            'title' => $validatedData['title'],
            'property_id' => $channexPropertyId,
            'room_type_id' => $channexRoomTypeId,
            'tax_set_id' => $validatedData['tax_set_id'] ?? null,
            'parent_rate_plan_id' => $validatedData['parent_rate_plan_id'] ?? null,
            'children_fee' => $validatedData['children_fee'] ?? '0.00',
            'infant_fee' => $validatedData['infant_fee'] ?? '0.00',
            'max_stay' => $validatedData['max_stay'],
            'min_stay_arrival' => $validatedData['min_stay_arrival'],
            'min_stay_through' => $validatedData['min_stay_through'],
            'closed_to_arrival' => $validatedData['closed_to_arrival'] ?? [false, false, false, false, false, false, false],
            'closed_to_departure' => $validatedData['closed_to_departure'] ?? [false, false, false, false, false, false, false],
            'stop_sell' => $validatedData['stop_sell'] ?? [false, false, false, false, false, false, false],
            'options' => $validatedData['options'] ?? [['occupancy' => 3, 'is_primary' => true, 'rate' => 15000]],
            'currency' => $validatedData['currency'],
            'sell_mode' => $validatedData['sell_mode'],
            'rate_mode' => $validatedData['rate_mode'],
        ],
    ];

    // Petición a Channex
    $response = Http::withHeaders([
        'user-api-key' => $this->apiToken,
    ])->post("{$this->apiUrl}/rate_plans", $ratePlanData);

    if ($response->successful()) {
        $channexId = $response->json('data.attributes.id');

        // Guardar en la base de datos
        RatePlan::create([
            'title' => $validatedData['title'],
            'property_id' => $validatedData['property_id'],
            'room_type_id' => $validatedData['room_type_id'] ?? null,
            'tax_set_id' => $validatedData['tax_set_id'] ?? null,
            'parent_rate_plan_id' => $validatedData['parent_rate_plan_id'] ?? null,
            'children_fee' => $validatedData['children_fee'] ?? 0.00,
            'infant_fee' => $validatedData['infant_fee'] ?? 0.00,
            'max_stay' => json_encode($validatedData['max_stay']),
            'min_stay_arrival' => json_encode($validatedData['min_stay_arrival']),
            'min_stay_through' => json_encode($validatedData['min_stay_through']),
            'closed_to_arrival' => json_encode($validatedData['closed_to_arrival'] ?? []),
            'closed_to_departure' => json_encode($validatedData['closed_to_departure'] ?? []),
            'stop_sell' => json_encode($validatedData['stop_sell'] ?? []),
            'options' => json_encode($validatedData['options'] ?? []),
            'currency' => $validatedData['currency'],
            'sell_mode' => $validatedData['sell_mode'],
            'rate_mode' => $validatedData['rate_mode'],
            'id_channex' => $channexId,
        ]);

        return redirect()->route('admin.rate-plans.index')->with('success', 'Rate Plan creado y guardado con éxito.');
    }

    return redirect()->back()->withErrors(['error' => 'Error al crear el Rate Plan: ' . $response->body()])->withInput();
}




public function getRoomTypes($propertyId)
{
    $property = Apartamento::findOrFail($propertyId);
    $roomTypes = $property->roomTypes; // Relación entre Apartamento y RoomType (debes definirla)

    return response()->json($roomTypes);
}

    // Similar lógica para update y destroy
}
