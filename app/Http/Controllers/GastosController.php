<?php

namespace App\Http\Controllers;

use App\Models\Bancos;
use App\Models\CategoriaGastos;
use App\Models\DiarioCaja;
use App\Models\EstadosGastos;
use App\Models\Gastos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GastosController extends Controller
{
    public function index(Request $request) {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        $month = $request->get('month');
        $category = $request->get('category');
        $perPage = $request->get('perPage', 10); // Predeterminado a 10
        $estado_id = $request->get('estado_id');

        // Obtener categorías que se contabilizan por separado
        $categoriasGastosSeparadas = CategoriaGastos::where('contabilizar_misma_empresa', true)->pluck('id')->toArray();
        
        // Construcción de la consulta con filtros
        $query = Gastos::where(function ($query) use ($search, $month, $category, $estado_id, $categoriasGastosSeparadas) {
            if ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            }
            if ($month) {
                $query->whereMonth('date', $month);
            }
            if ($category) {
                $query->where('categoria_id', $category);
            }
            if ($estado_id) {
                $query->where('estado_id', $estado_id);
            }
            
            // Excluir categorías que se contabilizan por separado
            if (!empty($categoriasGastosSeparadas)) {
                $query->whereNotIn('categoria_id', $categoriasGastosSeparadas);
            }
        });

        $totalQuantity = $query->sum('quantity');

        // Manejar la opción "Todo" (perPage = -1)
        if ($perPage == -1) {
            $gastos = $query->orderBy($sort, $order)->get();
        } else {
            $gastos = $query->orderBy($sort, $order)->paginate($perPage)->appends($request->except('page'));
        }

        $categorias = CategoriaGastos::all();
        $estados = EstadosGastos::all();

        return view('admin.gastos.index', compact('gastos', 'totalQuantity', 'categorias', 'estados'));
    }






    public function create(){
        $categorias = CategoriaGastos::all();
        $bancos = Bancos::all();
        $estados = EstadosGastos::all();

        return view('admin.gastos.create', compact('categorias','bancos', 'estados'));
    }

    public function store(Request $request)
    {
        $rules = [
            'estado_id' => 'required|exists:estados_gastos,id',
            'categoria_id' => 'required|exists:categoria_gastos,id',
            'bank_id' => 'required|exists:bank_accounts,id',
            'title' => 'required|string|max:255',
            'date' => 'required',
            'quantity' => 'required|numeric',
            'factura_foto' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);

        // Crear el gasto en la base de datos sin la foto
        $gasto = Gastos::create(collect($validatedData)->except('factura_foto')->toArray());

        // Manejar la carga de la foto si existe
        if ($request->hasFile('factura_foto')) {
            if ($request->file('factura_foto')->isValid()) {
                $file = $request->file('factura_foto');
                $filename = time() . '_' . $file->getClientOriginalName(); // Crear un nombre de archivo único
                $path = $file->storeAs('public/facturas', $filename); // Guardar el archivo en el storage

                // Actualizar la instancia de gasto con la ruta de la foto
                $gasto->factura_foto = $path;
                $gasto->save(); // Guardar el path en la columna factura_foto
            }
        }

        // Registrar el gasto en el Diario de Caja
        DiarioCaja::create([
            'asiento_contable' => $this->generarAsientoContable(),
            'cuenta_id' => $validatedData['categoria_id'],
            'gasto_id' => $gasto->id,
            'date' => Carbon::parse($validatedData['date']),
            'concepto' => $validatedData['title'],
            'debe' => $validatedData['quantity'],
            'tipo' => 'gasto',
            'estado_id' => $validatedData['estado_id']
        ]);

        // Redireccionar al índice de gastos con un mensaje de éxito
        return redirect()->route('admin.gastos.index')->with('status', 'Gasto creado con éxito!');
    }


    private function generarAsientoContable()
    {
        // Generar un número de asiento contable único para cada registro
        $asiento = DiarioCaja::orderBy('id', 'desc')->first();
        $anio = Carbon::now()->format('Y');
        $numeroAsiento;

        if ($asiento != null) {
            $asientoTemporal = explode("/", $asiento->asiento_contable);
            $numeroAsientos = $asientoTemporal[0] + 1;
            $numeroConCeros = str_pad($numeroAsientos, 4, "0", STR_PAD_LEFT);
            $numeroAsiento =  $numeroConCeros. '/' . $anio;
        } else {
            $numeroAsiento = '0001' . '/' . $anio;
        }

        return $numeroAsiento;
    }
    public function edit($id)
    {
        $gasto = Gastos::findOrFail($id);  // Asegúrate de usar findOrFail para manejar errores si el ID no existe
        $categorias = CategoriaGastos::all();
        $bancos = Bancos::all();  // Asegúrate de tener el modelo y controlador para Bancos también
        $estados = EstadosGastos::all();
        return view('admin.gastos.edit', compact('gasto', 'categorias', 'bancos', 'estados'));
    }

    public function update(Request $request, $id)
    {
        $gasto = Gastos::findOrFail($id); // Obtener el gasto existente

        $rules = [
            'estado_id' => 'required|exists:estados_gastos,id',
            'categoria_id' => 'required|exists:categoria_gastos,id',
            'bank_id' => 'required|exists:bank_accounts,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'quantity' => 'required|numeric',
            'factura_foto' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);

        // Actualizar el gasto en la base de datos sin la foto
        $gasto->update(collect($validatedData)->except('factura_foto')->toArray());

        // Manejar la carga de la foto si existe
        if ($request->hasFile('factura_foto')) {
            if ($request->file('factura_foto')->isValid()) {
                // Eliminar el archivo antiguo si existe
                if ($gasto->factura_foto) {
                    Storage::delete($gasto->factura_foto);
                }

                $file = $request->file('factura_foto');
                $filename = time() . '_' . $file->getClientOriginalName(); // Crear un nombre de archivo único
                $path = $file->storeAs('public/facturas', $filename); // Guardar el archivo en el storage

                // Actualizar la instancia de gasto con la ruta de la foto
                $gasto->factura_foto = $path;
                $gasto->save(); // Guardar el path en la columna factura_foto
            }
        }

        // Actualizar también el Diario de Caja correspondiente
        $diarioCaja = DiarioCaja::where('gasto_id', $id)->first();
        if ($diarioCaja) {
            $diarioCaja->update([
                'date' => $validatedData['date'],
                'concepto' => $validatedData['title'],
                'debe' => $validatedData['quantity']
            ]);
        }

        // Redireccionar al índice de gastos con un mensaje de éxito
        return redirect()->route('admin.gastos.index')->with('status', 'Gasto actualizado con éxito!');
    }


    public function destroy($id){
        $gasto = Gastos::findOrFail($id);

        // Buscar y eliminar la referencia en el Diario de Caja
        $diarioCaja = DiarioCaja::where('gasto_id', $id)->first();
        if ($diarioCaja) {
            $diarioCaja->delete();
        }

        // Eliminar el gasto
        $gasto->delete();

        return redirect()->route('admin.gastos.index')->with('status', 'Gasto eliminado con éxito.');
    }
    public function clasificarGastos(Request $request){
        $origen = $request->Origen;
        $contenido  = $request->Contenido;
        // $tipo = $request->Tipo;
        $importe = $request->Importe;
        $fecha = $request->Fecha;
        $crearGasto = Gastos::create([
            'title' => $contenido,
            'quantity' => $importe,
            'date' => $fecha,
            'estado_id' => 1
        ]);

        return response()->json([
            'mensaje' => 'El gasto se añadio correctamente'
        ]);

        // if($tipo == 0){

        // }

    }

    public function download($id)
    {
        $gasto = Gastos::findOrFail($id);

        if (!$gasto->factura_foto) {
            return redirect()->back()->with('error', 'Este gasto no tiene archivo adjunto. Puede subirlo desde Facturas Recibidas.');
        }

        $pathToFile = storage_path('app/' . $gasto->factura_foto);
        if (!file_exists($pathToFile)) {
            return redirect()->back()->with('error', 'El archivo adjunto se ha perdido del servidor. Por favor, vuelva a subirlo desde Facturas Recibidas.');
        }

        return response()->download($pathToFile);
    }
}
