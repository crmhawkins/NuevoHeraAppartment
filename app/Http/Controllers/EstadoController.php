<?php

namespace App\Http\Controllers;

use App\Mail\EnvioClavesEmail;
use App\Models\Comprobacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EstadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emailAddress = 'p.ragel@hawkins.es'; // Dirección del destinatario

        Mail::to($emailAddress)->send(new EnvioClavesEmail(
            'emails.envioClavesEmail',
            'data',
            'Prueba',
            'token',
            'titulo'
        ));

        return view('welcome');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function comprobacionServer()
    {
        Comprobacion::create([
            'fecha' => Carbon::now()
        ]);
        return response()->json([
            'estatus' => true
        ]);
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
        //
    }
}
