<?php

namespace App\Http\Controllers;

use App\Models\CuentasContable;
use App\Models\GrupoContable;
use App\Models\SubGrupoContable;
use Illuminate\Http\Request;
use DataTables;
//use Yajra\DataTables\Facades\DataTables;
//use Yajra\DataTables\DataTables;

class CuentasContableController extends Controller
{
    /**
     * Mostrar la lista de contactos
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        $subGrupo = $request->get('subGrupo');
        $perPage = $request->get('perPage', 10);

        $query = CuentasContable::with('grupo');

        if ($search) {
            $query->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('numero', 'like', '%' . $search . '%')
                  ->orWhere('descripcion', 'like', '%' . $search . '%')
                  ->orWhereHas('grupo', function ($q) use ($search) {
                      $q->where('nombre', 'like', '%' . $search . '%');
                  });
        }

        if ($subGrupo) {
            $query->where('sub_grupo_id', $subGrupo);
        }

        $response = $query->orderBy($sort, $order)->paginate($perPage);

        $subgrupos = SubGrupoContable::all();
        return view('admin.contabilidad.cuentaContabilidad.index', compact('response', 'subgrupos'));
    }
    public function getCuentasByDataTables(){

        $CuentasContables = CuentasContable::select('sub_grupo_id', 'numero', 'nombre', 'descripcion','id');

        return Datatables::of($CuentasContables)
                ->addColumn('subGrupo', function ($CuentasContable) {
                    if($CuentasContable->sub_grupo_id){
                        $subgrupo = SubGrupoContable::where('id', $CuentasContable->sub_grupo_id )->first();
                        return strval($subgrupo->numero .' - '.$subgrupo->nombre);
                    }
                    else{
                        return "no ";
                    }
                })


               
                ->addColumn('action', function ($CuentasContable) {
                    return '<a href="/admin/cuentas-contables/'.$CuentasContable->id.'/edit" class="btn btn-xs btn-primary"><i class="fas fa-pencil-alt"></i> Editar</a>';
                }) 
                // ->addColumn('delete', function ($CuentasContable) {
                //     $url = route('admin.cuentasContables.destroy', [ 'id'=> $CuentasContable->id]);
                //     return '<form action="'.$url.'" method="POST" enctype="multipart/form-data" data-callback="formCallback">
                //         <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i></button>  
                //     </form>';
                // })

                ->addColumn('delete', function ($CuentasContable) {
                    return '<button type="button" class="btn btn-danger" onclick="deleteEntry('.$CuentasContable->id.')" ><i class="fas fa-times"></i></button>';
                })


                ->escapeColumns(null)   
                ->make();
    }
    /**
     *  Mostrar el formulario de creación
     *
     * @return \Illuminate\Http\Response
     */    
    public function create()
    {            
        
        $grupos = GrupoContable::all();
        $subgrupos = SubGrupoContable::all();

        return view('admin.contabilidad.cuentaContabilidad.create', compact('subgrupos', 'grupos'));
    }

    /**
     * Mostrar el formulario de edición
     *
     * @param  Contact  $contact
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cuenta = CuentasContable::find($id);

        $grupo = SubGrupoContable::all();

        return view('admin.contabilidad.cuentaContabilidad.edit', compact('cuenta', 'grupo'));

    }

    
     /**
     * Guardar un nuevo contacto
     *
    * @param  Request  $request
    *
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request)
    {   
         // Validamos los datos recibidos desde el formulario
         $validator = Validator::make($request->all(), [
            'sub_grupo_id' => 'required',
            'numero' => 'required|unique:sub_grupo_contable',
            'nombre' => 'required',
        ]);
        // $this->validate(request(), [
        //     'sub_grupo_id' => 'required',
        //     'numero' => 'required|unique:sub_grupo_contable',
        //     'nombre' => 'required',

        // ]);
        
        // Comprobamos que si hemos pasado la validacion en ese caso creamos el grupo y devolvemos alerta
        if ($validator->passes()) {
            CuentasContable::create($request->all());
            return AjaxForm::custom([
                'message' => 'Cuenta Creada.',
                'entryUrl' => route('admin.cuentasContables.index'),
             ])->jsonResponse();
        }
        // Si la validacion no a sido pasada se muestra esta alerta.

        return AjaxForm::custom([
            'message' => $validator->errors()->all(),
         ])->jsonResponse();

    }        


    /**
     * Actualizar contacto
     * 
     * @param  Request  $request
     * @param  Contact  $contact
     * 
     * @return \Illuminate\Http\Response
     */    
    public function updated(Request $request, Contact $contact)
    {    
        $validator = Validator::make($request->all(), [
            'sub_grupo_id' => 'required',
            'numero' => 'required',
            'nombre' => 'required',
        ]);
        // $this->validate(request(), [
        //     'numero' => 'required',
        //     'nombre' => 'required',

        // ]);

        if ($validator->passes()) {
            $grupo = CuentasContable::where('id', $request->id)->first();
            $grupo->sub_grupo_id = $request->grupo_id;
            $grupo->numero = $request->numero;
            $grupo->nombre = $request->nombre;
            $grupo->save();
         
            return AjaxForm::custom([
                'message' => 'Cuenta actualizada.',
                'entryUrl' => route('admin.cuentasContables.edit', $grupo->id),
             ])->jsonResponse();

        }
        return AjaxForm::custom([
            'message' => $validator->errors()->all(),
         ])->jsonResponse();

    }

    /**
     * Borrar contacto
     * 
     * @param  Contact  $contact
     * 
     * @return \Illuminate\Http\Response
     */    
    public function destroy($id)
    {   
        $grupo = CuentasContable::find($id);

        if ($grupo == null) {
            return AjaxForm::errorMessage('El id: '.$id.' no existe.')->jsonResponse();
        }
        $grupo->delete();

        return AjaxForm::custom([
            'message' => 'Cuenta Borrada.',
            'entryUrl' => route('admin.cuentasContables.index'),
         ])->jsonResponse();;
    }
}
