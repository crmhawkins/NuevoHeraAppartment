<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteVetado;
use App\Services\ClienteVetadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * [2026-04-19] Panel admin para el sistema de vetos.
 *
 * Rutas:
 *  GET  /admin/clientes-vetados           listado (activos + historico)
 *  POST /admin/clientes-vetados           crear veto (desde ficha cliente)
 *  POST /admin/clientes-vetados/{id}/levantar  levantar veto
 */
class ClienteVetoController extends Controller
{
    public function __construct(private ClienteVetadoService $svc) {}

    public function index(Request $request)
    {
        $activos = $this->svc->getVetosActivos();
        $levantados = ClienteVetado::whereNotNull('levantado_at')
            ->with(['clienteOriginal', 'vetadoPor', 'levantadoPor'])
            ->orderByDesc('levantado_at')
            ->limit(100)
            ->get();

        return view('admin.clientes-vetados.index', compact('activos', 'levantados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'motivo' => 'required|string|min:3|max:2000',
            'notas_internas' => 'nullable|string|max:2000',
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);
        $veto = $this->svc->vetar(
            $cliente,
            $data['motivo'],
            Auth::user(),
            $data['notas_internas'] ?? null
        );

        return redirect()
            ->route('admin.clientes-vetados.index')
            ->with('success', "Cliente vetado correctamente (veto #{$veto->id}).");
    }

    public function levantar(Request $request, int $id)
    {
        $data = $request->validate([
            'nota' => 'nullable|string|max:1000',
        ]);

        $this->svc->levantar($id, Auth::user(), $data['nota'] ?? null);

        return redirect()
            ->route('admin.clientes-vetados.index')
            ->with('success', "Veto #{$id} levantado.");
    }
}
