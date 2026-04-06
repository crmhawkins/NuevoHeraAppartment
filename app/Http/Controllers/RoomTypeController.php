<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RoomTypeController extends Controller
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
        $roomTypes = RoomType::with('property')->get();
        return view('admin.room-types.index', compact('roomTypes'));
    }

    public function create()
    {
        $properties = Apartamento::all();
        return view('admin.room-types.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'property_id' => 'required|exists:apartamentos,id',
            'count_of_rooms' => 'required|min:1',
            'occ_adults' => 'required|min:1',
            'occ_children' => 'nullable|min:0',
            'occ_infants' => 'nullable|min:0',
            'default_occupancy' => 'required|min:1',
            'facilities' => 'nullable|array',
            'room_kind' => 'nullable|string|max:50',
            'capacity' => 'nullable|min:1',
            'description' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*.url' => 'required_with:photos|string|url',
            'photos.*.author' => 'nullable|string',
            'photos.*.description' => 'nullable|string',
            'photos.*.kind' => 'nullable|string',
            'photos.*.position' => 'nullable',
        ]);

        $property = Apartamento::findOrFail($validatedData['property_id']);

        // Construir la solicitud a Channex
        $roomTypeData = [
            'room_type' => [
                'property_id' => $property->id_channex, // ID Channex de la propiedad
                'title' => $validatedData['title'],
                'count_of_rooms' => $validatedData['count_of_rooms'],
                'occ_adults' => $validatedData['occ_adults'],
                'occ_children' => $validatedData['occ_children'] ?? 0,
                'occ_infants' => $validatedData['occ_infants'] ?? 0,
                'default_occupancy' => $validatedData['default_occupancy'],
                'facilities' => $validatedData['facilities'] ?? [],
                'room_kind' => $validatedData['room_kind'] ?? 'room',
                'capacity' => $validatedData['capacity'],
                'content' => [
                    'description' => $validatedData['description'],
                    //'photos' => $validatedData['photos'] ?? [],
                ],
            ],
        ];
        //dd($roomTypeData);

        $response = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("{$this->apiUrl}/room_types", $roomTypeData);


        //dd($response->json());

        if ($response->successful()) {
            // Guardar RoomType en la base de datos
            $roomType = RoomType::create([
                'title' => $validatedData['title'],
                'property_id' => $validatedData['property_id'],
                'count_of_rooms' => $validatedData['count_of_rooms'],
                'occ_adults' => $validatedData['occ_adults'],
                'occ_children' => $validatedData['occ_children'] ?? 0,
                'occ_infants' => $validatedData['occ_infants'] ?? 0,
                'default_occupancy' => $validatedData['default_occupancy'],
                'facilities' => $validatedData['facilities'] ?? [],
                'room_kind' => $validatedData['room_kind'] ?? 'room',
                'capacity' => $validatedData['capacity'],
                'description' => $validatedData['description'],
                'photos' => $validatedData['photos'] ?? [],
                'id_channex' => $response->json('data.attributes.id'),
            ]);

            return redirect()->route('channex.roomTypes.index')->with('success', 'Room Type creado con éxito');
        }

        return redirect()->back()->withErrors(['error' => 'Error al crear el Room Type: ' . $response->body()])->withInput();
    }



}
