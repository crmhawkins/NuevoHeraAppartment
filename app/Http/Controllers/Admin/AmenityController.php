<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AmenityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Amenity::with(['consumos', 'reposiciones']);

        // Filtros de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('categoria', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('proveedor', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $query->where('activo', true);
            } elseif ($request->estado === 'inactivo') {
                $query->where('activo', false);
            }
        }

        if ($request->filled('stock')) {
            switch ($request->stock) {
                case 'bajo':
                    $query->whereRaw('stock_actual <= stock_minimo');
                    break;
                case 'normal':
                    $query->whereRaw('stock_actual > stock_minimo');
                    if ($request->filled('stock_maximo')) {
                        $query->whereRaw('stock_actual < stock_maximo');
                    }
                    break;
                case 'alto':
                    $query->whereRaw('stock_actual >= stock_maximo');
                    break;
            }
        }

        // Ordenamiento
        $sort = $request->get('sort', 'nombre');
        $order = $request->get('order', 'asc');
        
        if (in_array($sort, ['nombre', 'categoria', 'precio_compra', 'stock_actual', 'created_at'])) {
            $query->orderBy($sort, $order);
        }

        $amenities = $query->paginate(20);
        $categorias = Amenity::distinct()->pluck('categoria')->sort();
        $stockBajo = Amenity::stockBajo()->get();

        // Estadísticas del dashboard
        $totalAmenities = Amenity::count();
        $amenitiesActivos = Amenity::where('activo', true)->count();
        $stockBajoCount = $stockBajo->count();
        $stockTotal = Amenity::sum('stock_actual');
        $valorTotal = Amenity::sum(DB::raw('stock_actual * precio_compra'));

        return view('admin.amenities.index', compact(
            'amenities', 
            'categorias', 
            'stockBajo',
            'totalAmenities',
            'amenitiesActivos',
            'stockBajoCount',
            'stockTotal',
            'valorTotal'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = [
            'Limpieza' => 'Limpieza',
            'Higiene' => 'Higiene',
            'Cocina' => 'Cocina',
            'Baño' => 'Baño',
            'Dormitorio' => 'Dormitorio',
            'Mantenimiento' => 'Mantenimiento',
            'Otros' => 'Otros'
        ];

        $unidadesMedida = [
            'unidades' => 'Unidades',
            'litros' => 'Litros',
            'gramos' => 'Gramos',
            'metros' => 'Metros',
            'rollos' => 'Rollos',
            'paquetes' => 'Paquetes'
        ];

        $tiposConsumo = [
            'por_reserva' => 'Por Reserva',
            'por_tiempo' => 'Por Tiempo',
            'por_persona' => 'Por Persona'
        ];

        return view('admin.amenities.create', compact('categorias', 'unidadesMedida', 'tiposConsumo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|string|max:255',
            'es_para_ninos' => 'boolean',
            'edad_minima' => 'nullable|integer|min:0|max:17',
            'edad_maxima' => 'nullable|integer|min:0|max:17',
            'tipo_nino' => 'nullable|in:bebe,nino_pequeno,nino_grande,adolescente',
            'cantidad_por_nino' => 'nullable|integer|min:1|max:100',
            'notas_ninos' => 'nullable|string|max:1000',
            'precio_compra' => 'required|numeric|min:0|max:999999.99',
            'unidad_medida' => 'required|string|max:255',
            'stock_actual' => 'required|numeric|min:0|max:999999.99',
            'stock_minimo' => 'required|numeric|min:0|max:999999.99',
            'stock_maximo' => 'nullable|numeric|min:0|max:999999.99',
            'tipo_consumo' => 'required|in:por_reserva,por_tiempo,por_persona',
            'consumo_por_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'consumo_minimo_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'consumo_maximo_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'duracion_dias' => 'nullable|integer|min:1|max:365',
            'consumo_por_persona' => 'nullable|numeric|min:0|max:999999.99',
            'unidad_consumo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'proveedor' => 'nullable|string|max:255',
            'codigo_producto' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El nombre del amenity es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'categoria.required' => 'Debes seleccionar una categoría.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio debe ser un número.',
            'precio_compra.min' => 'El precio no puede ser negativo.',
            'precio_compra.max' => 'El precio no puede ser mayor a 999,999.99.',
            'unidad_medida.required' => 'Debes seleccionar una unidad de medida.',
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.numeric' => 'El stock debe ser un número válido.',
            'stock_actual.min' => 'El stock no puede ser negativo.',
            'stock_actual.max' => 'El stock no puede ser mayor a 999,999.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número válido.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_minimo.max' => 'El stock mínimo no puede ser mayor a 999,999.',
            'stock_maximo.numeric' => 'El stock máximo debe ser un número válido.',
            'stock_maximo.min' => 'El stock máximo no puede ser negativo.',
            'stock_maximo.max' => 'El stock máximo no puede ser mayor a 999,999.',
            'tipo_consumo.required' => 'Debes seleccionar un tipo de consumo.',
            'tipo_consumo.in' => 'El tipo de consumo seleccionado no es válido.',
            'consumo_por_reserva.numeric' => 'El consumo por reserva debe ser un número.',
            'consumo_por_reserva.min' => 'El consumo por reserva no puede ser negativo.',
            'consumo_por_reserva.max' => 'El consumo por reserva no puede ser mayor a 999,999.99.',
            'consumo_minimo_reserva.numeric' => 'El consumo mínimo por reserva debe ser un número.',
            'consumo_minimo_reserva.min' => 'El consumo mínimo por reserva no puede ser negativo.',
            'consumo_minimo_reserva.max' => 'El consumo mínimo por reserva no puede ser mayor a 999,999.99.',
            'consumo_maximo_reserva.numeric' => 'El consumo máximo por reserva debe ser un número.',
            'consumo_maximo_reserva.min' => 'El consumo máximo por reserva no puede ser negativo.',
            'consumo_maximo_reserva.max' => 'El consumo máximo por reserva no puede ser mayor a 999,999.99.',
            'duracion_dias.integer' => 'La duración en días debe ser un número entero.',
            'duracion_dias.min' => 'La duración en días debe ser al menos 1.',
            'duracion_dias.max' => 'La duración en días no puede ser mayor a 365.',
            'consumo_por_persona.numeric' => 'El consumo por persona debe ser un número.',
            'consumo_por_persona.min' => 'El consumo por persona no puede ser negativo.',
            'consumo_por_persona.max' => 'El consumo por persona no puede ser mayor a 999,999.99.',
            'proveedor.max' => 'El proveedor no puede tener más de 255 caracteres.',
            'codigo_producto.max' => 'El código del producto no puede tener más de 255 caracteres.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['activo'] = $request->has('activo');
            $validatedData['es_para_ninos'] = $request->has('es_para_ninos');
            
            // Si no es para niños, limpiar campos relacionados
            if (!$validatedData['es_para_ninos']) {
                $validatedData['edad_minima'] = null;
                $validatedData['edad_maxima'] = null;
                $validatedData['tipo_nino'] = null;
                $validatedData['cantidad_por_nino'] = null;
                $validatedData['notas_ninos'] = null;
            }
            
            DB::beginTransaction();

            $amenity = Amenity::create($validatedData);

            // Si hay stock inicial, registrar como reposición
            if ($request->stock_actual > 0) {
                AmenityReposicion::create([
                    'amenity_id' => $amenity->id,
                    'user_id' => auth()->id(),
                    'cantidad_reponida' => $request->stock_actual,
                    'stock_anterior' => 0,
                    'stock_nuevo' => $request->stock_actual,
                    'precio_unitario' => $request->precio_compra,
                    'precio_total' => $request->precio_compra * $request->stock_actual,
                    'fecha_reposicion' => now()
                ]);
            }

            DB::commit();

            return redirect()->route('admin.amenities.index')
                ->with('swal_success', '¡Amenity creado con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el amenity: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $amenity = Amenity::with(['consumos.user', 'consumos.reserva', 'consumos.apartamento', 'reposiciones.user'])
            ->findOrFail($id);

        $consumosRecientes = $amenity->consumos()
            ->with(['limpieza.apartamento', 'limpieza.tareaAsignada'])
            ->orderBy('fecha_consumo', 'desc')
            ->limit(10)
            ->get();

        $reposicionesRecientes = $amenity->reposiciones()
            ->orderBy('fecha_reposicion', 'desc')
            ->limit(10)
            ->get();

        // Estadísticas del amenity
        $totalConsumos = $amenity->consumos()->count();
        $totalReposiciones = $amenity->reposiciones()->count();
        $consumoTotal = $amenity->consumos()->sum('cantidad_consumida');
        $reposicionTotal = $amenity->reposiciones()->sum('cantidad_reponida');
        $valorStockActual = $amenity->stock_actual * $amenity->precio_compra;
        $valorConsumido = $consumoTotal * $amenity->precio_compra;

        return view('admin.amenities.show', compact(
            'amenity', 
            'consumosRecientes', 
            'reposicionesRecientes',
            'totalConsumos',
            'totalReposiciones',
            'consumoTotal',
            'reposicionTotal',
            'valorStockActual',
            'valorConsumido'
        ));
    }

    /**
     * Mostrar todos los consumos de un amenity
     */
    public function consumos(string $id)
    {
        $amenity = Amenity::findOrFail($id);
        
        $consumos = $amenity->consumos()
            ->with(['user', 'reserva', 'apartamento', 'limpieza.apartamento', 'limpieza.tareaAsignada'])
            ->orderBy('fecha_consumo', 'desc')
            ->paginate(20);

        return view('admin.amenities.consumos', compact('amenity', 'consumos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $amenity = Amenity::findOrFail($id);
        
        $categorias = [
            'Limpieza' => 'Limpieza',
            'Higiene' => 'Higiene',
            'Cocina' => 'Cocina',
            'Baño' => 'Baño',
            'Dormitorio' => 'Dormitorio',
            'Mantenimiento' => 'Mantenimiento',
            'Otros' => 'Otros'
        ];

        $unidadesMedida = [
            'unidades' => 'Unidades',
            'litros' => 'Litros',
            'gramos' => 'Gramos',
            'metros' => 'Metros',
            'rollos' => 'Rollos',
            'paquetes' => 'Paquetes'
        ];

        $tiposConsumo = [
            'por_reserva' => 'Por Reserva',
            'por_tiempo' => 'Por Tiempo',
            'por_persona' => 'Por Persona'
        ];

        return view('admin.amenities.edit', compact('amenity', 'categorias', 'unidadesMedida', 'tiposConsumo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);

        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|string|max:255',
            'precio_compra' => 'required|numeric|min:0|max:999999.99',
            'unidad_medida' => 'required|string|max:255',
            'stock_minimo' => 'required|numeric|min:0|max:999999.99',
            'stock_maximo' => 'nullable|numeric|min:0|max:999999.99',
            'tipo_consumo' => 'required|in:por_reserva,por_tiempo,por_persona',
            'consumo_por_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'consumo_minimo_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'consumo_maximo_reserva' => 'nullable|numeric|min:0|max:999999.99',
            'duracion_dias' => 'nullable|integer|min:1|max:365',
            'consumo_por_persona' => 'nullable|numeric|min:0|max:999999.99',
            'unidad_consumo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'proveedor' => 'nullable|string|max:255',
            'codigo_producto' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El nombre del amenity es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'categoria.required' => 'Debes seleccionar una categoría.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio debe ser un número.',
            'precio_compra.min' => 'El precio no puede ser negativo.',
            'precio_compra.max' => 'El precio no puede ser mayor a 999,999.99.',
            'unidad_medida.required' => 'Debes seleccionar una unidad de medida.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número válido.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_minimo.max' => 'El stock mínimo no puede ser mayor a 999,999.',
            'stock_maximo.numeric' => 'El stock máximo debe ser un número válido.',
            'stock_maximo.min' => 'El stock máximo no puede ser negativo.',
            'stock_maximo.max' => 'El stock máximo no puede ser mayor a 999,999.',
            'tipo_consumo.required' => 'Debes seleccionar un tipo de consumo.',
            'tipo_consumo.in' => 'El tipo de consumo seleccionado no es válido.',
            'consumo_por_reserva.numeric' => 'El consumo por reserva debe ser un número.',
            'consumo_por_reserva.min' => 'El consumo por reserva no puede ser negativo.',
            'consumo_por_reserva.max' => 'El consumo por reserva no puede ser mayor a 999,999.99.',
            'consumo_minimo_reserva.numeric' => 'El consumo mínimo por reserva debe ser un número.',
            'consumo_minimo_reserva.min' => 'El consumo mínimo por reserva no puede ser negativo.',
            'consumo_minimo_reserva.max' => 'El consumo mínimo por reserva no puede ser mayor a 999,999.99.',
            'consumo_maximo_reserva.numeric' => 'El consumo máximo por reserva debe ser un número.',
            'consumo_maximo_reserva.min' => 'El consumo máximo por reserva no puede ser negativo.',
            'consumo_maximo_reserva.max' => 'El consumo máximo por reserva no puede ser mayor a 999,999.99.',
            'duracion_dias.integer' => 'La duración en días debe ser un número entero.',
            'duracion_dias.min' => 'La duración en días debe ser al menos 1.',
            'duracion_dias.max' => 'La duración en días no puede ser mayor a 365.',
            'consumo_por_persona.numeric' => 'El consumo por persona debe ser un número.',
            'consumo_por_persona.min' => 'El consumo por persona no puede ser negativo.',
            'consumo_por_persona.max' => 'El consumo por persona no puede ser mayor a 999,999.99.',
            'proveedor.max' => 'El proveedor no puede tener más de 255 caracteres.',
            'codigo_producto.max' => 'El código del producto no puede tener más de 255 caracteres.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['activo'] = $request->has('activo');

            $amenity->update($validatedData);

            return redirect()->route('admin.amenities.index')
                ->with('swal_success', '¡Amenity actualizado con éxito!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el amenity: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $amenity = Amenity::findOrFail($id);
            
            // Verificar si tiene consumos o reposiciones asociadas
            if ($amenity->consumos()->count() > 0 || $amenity->reposiciones()->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el amenity porque tiene consumos o reposiciones asociadas.');
            }

            $amenity->delete();

            return redirect()->route('admin.amenities.index')
                ->with('swal_success', '¡Amenity eliminado con éxito!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el amenity: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(string $id)
    {
        try {
            $amenity = Amenity::findOrFail($id);
            $amenity->update(['activo' => !$amenity->activo]);
            
            $status = $amenity->activo ? 'activado' : 'desactivado';
            
            return redirect()->back()
                ->with('swal_success', "Amenity {$status} correctamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al cambiar el estado del amenity: ' . $e->getMessage());
        }
    }

    /**
     * Registrar consumo manual
     */
    public function registrarConsumo(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);

        $rules = [
            'cantidad_consumida' => 'required|integer|min:1|max:' . $amenity->stock_actual,
            'tipo_consumo' => 'required|in:reserva,limpieza,ajuste',
            'reserva_id' => 'nullable|exists:reservas,id',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'observaciones' => 'nullable|string|max:500'
        ];

        $messages = [
            'cantidad_consumida.required' => 'La cantidad consumida es obligatoria.',
            'cantidad_consumida.integer' => 'La cantidad debe ser un número entero.',
            'cantidad_consumida.min' => 'La cantidad debe ser al menos 1.',
            'cantidad_consumida.max' => 'La cantidad no puede ser mayor al stock disponible.',
            'tipo_consumo.required' => 'Debes seleccionar un tipo de consumo.',
            'tipo_consumo.in' => 'El tipo de consumo seleccionado no es válido.',
            'reserva_id.exists' => 'La reserva seleccionada no existe.',
            'apartamento_id.exists' => 'El apartamento seleccionado no existe.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 500 caracteres.'
        ];

        $validatedData = $request->validate($rules, $messages);
        
        try {
            DB::beginTransaction();

            // Usar el método estándar para descontar stock
            $resultadoDescuento = $amenity->descontarStock($request->cantidad_consumida);

            // Registrar consumo con datos reales
            AmenityConsumo::create([
                'amenity_id' => $amenity->id,
                'reserva_id' => $request->reserva_id,
                'apartamento_id' => $request->apartamento_id,
                'user_id' => auth()->id(),
                'tipo_consumo' => $request->tipo_consumo,
                'cantidad_consumida' => $request->cantidad_consumida,
                'cantidad_anterior' => $resultadoDescuento['stock_anterior'],
                'cantidad_actual' => $resultadoDescuento['stock_actual'],
                'costo_unitario' => $amenity->precio_compra,
                'costo_total' => $amenity->precio_compra * $request->cantidad_consumida,
                'observaciones' => $request->observaciones,
                'fecha_consumo' => now()
            ]);

            DB::commit();

            return redirect()->route('admin.amenities.show', $id)
                ->with('swal_success', 'Consumo registrado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al registrar el consumo: ' . $e->getMessage());
        }
    }

    /**
     * Registrar reposición
     */
    public function registrarReposicion(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);

        $rules = [
            'cantidad_reponida' => 'required|numeric|min:0.01|max:999999.99',
            'precio_unitario' => 'required|numeric|min:0|max:999999.99',
            'proveedor' => 'nullable|string|max:255',
            'numero_factura' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:500'
        ];

        $messages = [
            'cantidad_reponida.required' => 'La cantidad reponida es obligatoria.',
            'cantidad_reponida.numeric' => 'La cantidad debe ser un número válido.',
            'cantidad_reponida.min' => 'La cantidad debe ser mayor a 0.',
            'cantidad_reponida.max' => 'La cantidad no puede ser mayor a 999,999.99.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.numeric' => 'El precio debe ser un número.',
            'precio_unitario.min' => 'El precio no puede ser negativo.',
            'precio_unitario.max' => 'El precio no puede ser mayor a 999,999.99.',
            'proveedor.max' => 'El proveedor no puede tener más de 255 caracteres.',
            'numero_factura.max' => 'El número de factura no puede tener más de 255 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 500 caracteres.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            DB::beginTransaction();

            $stockAnterior = $amenity->stock_actual;
            $stockNuevo = $stockAnterior + $request->cantidad_reponida;

            // Actualizar stock
            $amenity->update(['stock_actual' => $stockNuevo]);

            // Registrar reposición
            AmenityReposicion::create([
                'amenity_id' => $amenity->id,
                'user_id' => auth()->id(),
                'cantidad_reponida' => $request->cantidad_reponida,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'precio_unitario' => $request->precio_unitario,
                'precio_total' => $request->precio_unitario * $request->cantidad_reponida,
                'proveedor' => $request->proveedor,
                'numero_factura' => $request->numero_factura,
                'observaciones' => $request->observaciones,
                'fecha_reposicion' => now()
            ]);

            DB::commit();

            return redirect()->route('admin.amenities.show', $id)
                ->with('swal_success', 'Reposición registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al registrar la reposición: ' . $e->getMessage());
        }
    }

    /**
     * Calcular consumo para una reserva
     */
    public function calcularConsumoReserva(Request $request)
    {
        $rules = [
            'amenity_id' => 'required|exists:amenities,id',
            'numero_personas' => 'required|integer|min:1|max:100',
            'dias' => 'required|integer|min:1|max:365'
        ];

        $messages = [
            'amenity_id.required' => 'Debes seleccionar un amenity.',
            'amenity_id.exists' => 'El amenity seleccionado no existe.',
            'numero_personas.required' => 'El número de personas es obligatorio.',
            'numero_personas.integer' => 'El número de personas debe ser un número entero.',
            'numero_personas.min' => 'El número de personas debe ser al menos 1.',
            'numero_personas.max' => 'El número de personas no puede ser mayor a 100.',
            'dias.required' => 'El número de días es obligatorio.',
            'dias.integer' => 'El número de días debe ser un número entero.',
            'dias.min' => 'El número de días debe ser al menos 1.',
            'dias.max' => 'El número de días no puede ser mayor a 365.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            $amenity = Amenity::findOrFail($request->amenity_id);
            $consumoCalculado = $amenity->calcularConsumoReserva($request->numero_personas, $request->dias);

            return response()->json([
                'amenity' => $amenity->nombre,
                'consumo_calculado' => $consumoCalculado,
                'unidad_medida' => $amenity->unidad_medida,
                'stock_disponible' => $amenity->stock_actual,
                'suficiente_stock' => $amenity->stock_actual >= $consumoCalculado
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al calcular el consumo: ' . $e->getMessage()], 400);
        }
    }
}
