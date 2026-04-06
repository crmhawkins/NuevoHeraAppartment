<?php

namespace App\Http\Controllers;

use App\Models\Anio;
use App\Models\Metalico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetalicoController extends Controller
{
    public function index(Request $request)
    {
        // Obtener saldo inicial
        $anio = Anio::first();
        $saldoInicial = $anio->saldo_inicial_metalico ?? 0;

        // Filtrar registros
        $query = Metalico::query();

        if ($request->filled('start_date')) {
            $query->where('fecha_ingreso', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('fecha_ingreso', '<=', $request->end_date);
        }

        if ($request->filled('reserva_id')) {
            $query->where('reserva_id', $request->reserva_id);
        }

        if ($request->filled('titulo')) {
            $query->where('titulo', 'like', '%' . $request->titulo . '%');
        }

        // Ordenar registros
        $entries = $query->orderBy('id', 'asc')->get();

        // Inicializar saldo acumulado
        $saldoAcumulado = $saldoInicial;

        // Recorrer registros y calcular saldo acumulado
        foreach ($entries as $linea) {
            $importe = abs($linea->importe);

            if ($linea->tipo === 'gasto') {
                $saldoAcumulado -= $importe;
            } else {
                $saldoAcumulado += $importe;
            }

            $linea->saldo = $saldoAcumulado;
        }

        // Ordenar en orden descendente para la vista
        $response = $entries->sortByDesc('id');

        return view('admin.metalicos.index', compact('response', 'saldoInicial'));
    }

    public function store(Request $request)
    {
        // Log de depuración
        \Log::info('MetalicoController::store - MÉTODO LLAMADO');
        \Log::info('MetalicoController::store - Datos recibidos:', $request->all());
        
        $request->validate([
            'titulo' => 'required|string|max:255',
            'importe' => 'required|numeric|min:0.01',
            'fecha_ingreso' => 'required|date',
            'tipo' => 'required|in:ingreso,gasto',
            'observaciones' => 'nullable|string|max:500',
            // PIN solo requerido para gastos; para ingresos permitimos null
            'pin' => 'nullable|required_if:tipo,gasto|string|size:4'
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'importe.required' => 'El importe es obligatorio.',
            'importe.numeric' => 'El importe debe ser un número válido.',
            'importe.min' => 'El importe debe ser mayor a 0.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe ser una fecha válida.',
            'tipo.required' => 'El tipo es obligatorio.',
            'tipo.in' => 'El tipo debe ser ingreso o gasto.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 500 caracteres.',
            'pin.required_if' => 'El PIN es obligatorio para crear gastos.',
            'pin.size' => 'El PIN debe tener exactamente 4 dígitos.'
        ]);

        // Validar PIN si es un gasto
        if ($request->tipo === 'gasto') {
            if ($request->pin !== '1970') {
                return redirect()->route('metalicos.create')
                    ->withInput()
                    ->with('error', 'PIN incorrecto. No se puede crear el gasto.');
            }
        }

        // Verificar duplicados (solo para gastos). Para ingresos permitimos repetición.
        if ($request->tipo === 'gasto') {
            $duplicado = Metalico::where('titulo', $request->titulo)
                ->where('importe', $request->importe)
                ->where('fecha_ingreso', $request->fecha_ingreso)
                ->where('tipo', 'gasto')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($duplicado) {
                return redirect()->route('metalicos.create')
                    ->withInput()
                    ->with('error', 'Ya existe un gasto idéntico creado recientemente. Por favor, verifica los datos.');
            }
        }

        // Crear el registro sin incluir el PIN en la base de datos
        $data = $request->except('pin');
        
        // Asegurar que reserva_id sea null si no se proporciona
        if (!isset($data['reserva_id']) || empty($data['reserva_id'])) {
            $data['reserva_id'] = null;
        }
        
        \Log::info('MetalicoController::store - Datos para crear:', $data);
        
        $metalico = Metalico::create($data);
        \Log::info('MetalicoController::store - Metálico creado con ID:', ['id' => $metalico->id]);

        $msg = $request->tipo === 'ingreso' ? 'Ingreso creado correctamente.' : 'Gasto creado correctamente.';
        return redirect()->route('metalicos.index')->with('success', $msg);
    }


    public function create()
    {
        return view('admin.metalicos.create');
    }

    public function store2(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'importe' => 'required|numeric',
            // 'reserva_id' => 'required|exists:reservas,id',
            'fecha_ingreso' => 'required|date',
        ]);
        $request['reserva_id'] = null;

        Metalico::create($request->all());

        return redirect()->route('metalicos.index')->with('success', 'Registro creado correctamente.');
    }

    public function show(Metalico $metalico)
    {
        return view('admin.metalicos.show', compact('metalico'));
    }

    public function edit(Metalico $metalico)
    {
        return view('admin.metalicos.edit', compact('metalico'));
    }

    public function update(Request $request, Metalico $metalico)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'importe' => 'required|numeric',
            'fecha_ingreso' => 'required|date',
            'tipo' => 'required|in:ingreso,gasto',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $metalico->update($request->all());

        return redirect()->route('metalicos.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Metalico $metalico)
    {
        try {
            $titulo = $metalico->titulo;
            $metalico->delete();

            Log::info("Movimiento metálico eliminado", [
                'id' => $metalico->id,
                'titulo' => $titulo,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('metalicos.index')->with('success', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al eliminar movimiento metálico", [
                'id' => $metalico->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->route('metalicos.index')->with('error', 'Error al eliminar el registro: ' . $e->getMessage());
        }
    }
}

