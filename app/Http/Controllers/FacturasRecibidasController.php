<?php

namespace App\Http\Controllers;

use App\Models\Gastos;
use App\Models\CategoriaGastos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacturasRecibidasController extends Controller
{
    // Categorias que son gastos corrientes (sin factura de proveedor)
    private const CATEGORIAS_EXCLUIDAS = [
        1,  // NOMINA
        6,  // SEGUROS SOCIALES
        30, // COMISION BANCARIA
        36, // DEVOLUCION CLIENTE
        45, // DEVOLUCION SOCIO
        57, // NOMINAS OBRA
        60, // SEGUROS SOCIALES OBRA
    ];

    public function index(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde', now()->startOfYear()->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', now()->format('Y-m-d'));

        $query = Gastos::with(['categoria', 'estado'])
            ->whereNotIn('categoria_id', self::CATEGORIAS_EXCLUIDAS)
            ->where('date', '>=', $fechaDesde)
            ->where('date', '<=', $fechaHasta);

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('tiene_factura')) {
            if ($request->tiene_factura === 'si') {
                $query->whereNotNull('factura_foto')->where('factura_foto', '!=', '');
            } elseif ($request->tiene_factura === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('factura_foto')->orWhere('factura_foto', '');
                });
            }
        }

        $gastos = $query->orderBy('date', 'desc')->paginate(25)->withQueryString();
        $categorias = CategoriaGastos::whereNotIn('id', self::CATEGORIAS_EXCLUIDAS)->orderBy('nombre')->get();

        // Totales
        $totalGastos = (clone $query)->sum(\DB::raw('ABS(quantity)'));
        $numGastos = (clone $query)->count();
        $conFactura = (clone $query)->whereNotNull('factura_foto')->where('factura_foto', '!=', '')->count();
        $sinFactura = $numGastos - $conFactura;

        return view('admin.tesoreria.facturas-recibidas', compact(
            'gastos', 'categorias', 'fechaDesde', 'fechaHasta',
            'totalGastos', 'numGastos', 'conFactura', 'sinFactura'
        ));
    }

    public function subirFactura(Request $request, $id)
    {
        $request->validate([
            'factura' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:5120',
        ]);

        $gasto = Gastos::findOrFail($id);

        $archivo = $request->file('factura');
        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = time() . "_{$id}.{$extension}";
        $ruta = $archivo->storeAs('facturas_privadas', $nombreArchivo);

        $gasto->update([
            'factura_foto' => $ruta,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Factura subida correctamente.',
            'ruta' => $gasto->factura_foto,
        ]);
    }

    public function descargarFactura($id)
    {
        $gasto = Gastos::findOrFail($id);

        if (empty($gasto->factura_foto)) {
            return redirect()->back()->with('error', 'Este gasto no tiene factura adjunta.');
        }

        if (!Storage::exists($gasto->factura_foto)) {
            return redirect()->back()->with('error', 'El archivo se ha perdido del servidor. Vuelva a subirlo.');
        }

        return Storage::download($gasto->factura_foto, "factura_gasto_{$id}." . pathinfo($gasto->factura_foto, PATHINFO_EXTENSION));
    }
}
