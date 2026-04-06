<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CierreApartamento;
use App\Models\Apartamento;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Reserva;

class CerrarApartamentoController extends Controller
{
    /**
     * Mostrar la vista de cerrar apartamento
     */
    public function index()
    {
        // Obtener todos los cierres con sus relaciones
        $cierres = CierreApartamento::with(['apartamento', 'reserva'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcular estadísticas
        $cierresActivos = CierreApartamento::where('activo', true)->count();
        $cierresHoy = CierreApartamento::whereDate('created_at', today())->count();
        $apartamentosCerrados = CierreApartamento::where('activo', true)
            ->distinct('apartamento_id')
            ->count('apartamento_id');
        $totalCierres = CierreApartamento::count();

        return view('admin.cerrar-apartamento.index', compact(
            'cierres',
            'cierresActivos',
            'cierresHoy',
            'apartamentosCerrados',
            'totalCierres'
        ));
    }

    /**
     * Mostrar el formulario para crear un nuevo cierre de apartamento
     */
    public function create()
    {
        $apartamentos = Apartamento::whereNotNull('id_channex')->orderBy('nombre')->get();
        return view('admin.cerrar-apartamento.create', compact('apartamentos'));
    }

    /**
     * Guardar un nuevo cierre de apartamento
     */
    public function store(Request $request)
    {
        // Log de debug
        Log::info('Intentando crear cierre de apartamento', [
            'apartamento_id' => $request->apartamento_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin
        ]);

        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after:fecha_inicio'
        ]);

        Log::info('Validaciones pasaron correctamente');

        DB::beginTransaction();
        try {
            // 1. Obtener cliente "Apartamento Cerrado"
            $clienteCerrado = Cliente::where('is_null', true)->first();
            if (!$clienteCerrado) {
                throw new \Exception('No se encontró el cliente "Apartamento Cerrado"');
            }

            // 2. Obtener estado "Cerrado"
            $estadoCerrado = Estado::where('nombre', 'Cerrado')->first();
            if (!$estadoCerrado) {
                throw new \Exception('No se encontró el estado "Cerrado"');
            }

            // 3. Obtener apartamento
            $apartamento = Apartamento::find($request->apartamento_id);
            if (!$apartamento) {
                throw new \Exception('Apartamento no encontrado');
            }

            // 4. Verificar que no haya conflictos con reservas existentes
            // Solo detectamos reservas que realmente se solapan con el período de cierre
            // Una reserva se solapa si:
            // - Comienza ANTES de que termine el cierre Y termina DESPUÉS de que empiece el cierre
            // - O contiene todo el período de cierre
            // Nota: Si el cierre termina el día X y una reserva empieza el día X, NO hay conflicto
            $conflictoReserva = Reserva::where('apartamento_id', $apartamento->id)
                ->where('estado_id', '!=', 4) // Excluir canceladas
                ->where(function($query) use ($request) {
                    $query->where(function($q) use ($request) {
                        // Reservas que comienzan ANTES de que termine el cierre Y terminan DESPUÉS de que empiece el cierre
                        // Usamos < en lugar de <= para fecha_entrada para permitir que una reserva empiece el mismo día que termina el cierre
                        $q->where('fecha_entrada', '<', $request->fecha_fin)
                          ->where('fecha_salida', '>', $request->fecha_inicio);
                    })->orWhere(function($q) use ($request) {
                        // Reservas que contienen todo el período de cierre
                        $q->where('fecha_entrada', '<=', $request->fecha_inicio)
                          ->where('fecha_salida', '>=', $request->fecha_fin);
                    });
                })
                ->first();

            if ($conflictoReserva) {
                throw new \Exception('Ya existe una reserva activa en el apartamento para las fechas seleccionadas');
            }

            // 5. Crear reserva
            $reserva = Reserva::create([
                'apartamento_id' => $apartamento->id,
                'cliente_id' => $clienteCerrado->id,
                'estado_id' => $estadoCerrado->id,
                'fecha_entrada' => $request->fecha_inicio,
                'fecha_salida' => $request->fecha_fin,
                'precio' => 0,
                'neto' => 0,
                'numero_personas' => 0,
                'numero_personas_plataforma' => 0,
                'origen' => 'Sistema - Cierre Apartamento',
                'observaciones' => 'Cierre automático de apartamento creado desde el sistema'
            ]);

            // 6. Crear registro de cierre
            $cierre = CierreApartamento::create([
                'apartamento_id' => $apartamento->id,
                'reserva_id' => $reserva->id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'observaciones' => $request->observaciones ?? null,
                'activo' => true
            ]);

            DB::commit();

            Log::info('Cierre de apartamento creado exitosamente', [
                'cierre_id' => $cierre->id,
                'reserva_id' => $reserva->id,
                'apartamento' => $apartamento->nombre
            ]);

            return redirect()->route('admin.cerrar-apartamento.index')
                ->with('swal_success', "Cierre de apartamento creado exitosamente. Apartamento: {$apartamento->nombre} del {$request->fecha_inicio} al {$request->fecha_fin}");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear cierre de apartamento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('swal_error', 'Error al crear el cierre: ' . $e->getMessage());
        }
    }
}
