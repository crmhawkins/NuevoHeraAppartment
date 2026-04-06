<?php

namespace App\Http\Controllers;

use App\Models\RatePlan;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RateUpdateController extends Controller
{
    private $apiUrl;
    private $apiToken;
    public function __construct()
    {
        $this->apiUrl = env('CHANNEX_URL');
        $this->apiToken = env('CHANNEX_TOKEN');
    }

    public function create()
    {
        $properties = Apartamento::all(); // Carga los apartamentos
        $ratePlans = RatePlan::all(); // Carga los Rate Plans

        return view('admin.rate-updates.create', compact('properties', 'ratePlans'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'property_id' => 'required|exists:apartamentos,id',
            'rate_plan_id' => 'required|exists:rate_plans,id',
            'date' => 'required|date',
            'rate' => 'required|numeric|min:0',
        ]);

        $property = Apartamento::findOrFail($validatedData['property_id']);
        $ratePlan = RatePlan::findOrFail($validatedData['rate_plan_id']);

        // Realizar la solicitud a Channex
        $response = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("{$this->apiUrl}/ari", [
            'values' => [
                [
                    'property_id' => $property->id_channex, // ID de Channex de la propiedad
                    'rate_plan_id' => $ratePlan->id_channex, // ID de Channex del Rate Plan
                    'date' => $validatedData['date'],
                    'rate' => $validatedData['rate'],
                ],
            ],
        ]);

        if ($response->successful()) {
            return redirect()->route('rate-updates.create')->with('success', 'Tarifa actualizada con éxito');
        }

        return redirect()->back()->withErrors(['error' => 'Error al actualizar la tarifa: ' . $response->body()])->withInput();
    }
}
