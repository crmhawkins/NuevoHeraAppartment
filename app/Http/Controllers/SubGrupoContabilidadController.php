<?php

namespace App\Http\Controllers;

use App\Models\GrupoContable;
use App\Models\SubGrupoContable;
use Illuminate\Http\Request;

class SubGrupoContabilidadController extends Controller
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

        $query = SubGrupoContable::with('grupo');

        if ($search) {
            $query->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('numero', 'like', '%' . $search . '%')
                  ->orWhere('descripcion', 'like', '%' . $search . '%')
                  ->orWhereHas('grupo', function ($q) use ($search) {
                      $q->where('nombre', 'like', '%' . $search . '%');
                  });
        }

        if ($subGrupo) {
            $query->where('grupo_id', $subGrupo);
        }

        $response = $query->orderBy($sort, $order)->paginate($perPage);
        $subgrupos = GrupoContable::all();

        return view('admin.contabilidad.subGrupoContabilidad.index', compact('response','subgrupos'));
    }


    /**
     *  Mostrar el formulario de creación
     *
     * @return \Illuminate\Http\Response
     */    
    public function create()
    {            
        $response = 'Hola mundo';

        return view('admin.contabilidad.grupoContabilidad.create', compact('response'));
    }

    /**
     * Mostrar el formulario de edición
     *
     * @param  GrupoContable  $contact
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(GrupoContable $grupoContable, Request $request)
    {
        $response = GrupoContable::find($request->id);

        return view('admin.contabilidad.grupoContabilidad.edit', compact('response'));

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
        $rules = [
            'numero' => 'required',
            'nombre' => 'required',
            'descripcion' => 'required'

        ];

        $validatedData = $request->validate($rules);
        GrupoContable::create($request->all());

        return redirect()->route('admin.ingresos.index')->with('status', 'El Grupo fue creado con éxito!');

    }        


    /**
     * Actualizar contacto
     * 
     * @param  Request  $request
     * @param  GrupoContable  $contact
     * 
     * @return \Illuminate\Http\Response
     */    
    public function updated(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'numero' => 'required',
            'nombre' => 'required',
            'descripcion' => 'required'

        ]);
        // $this->validate(request(), [
        //     'numero' => 'required',
        //     'nombre' => 'required',

        // ]);

        if ($validator->passes()) {
            $grupo = GrupoContable::where('id', $request->id)->first();
            
            $grupo->numero = $request->numero;
            $grupo->nombre = $request->nombre;
            $grupo->descripcion = $request->descripcion;

            $grupo->save();
         
            return AjaxForm::custom([
                'message' => 'Peticion guardada.',
                'entryUrl' => route('admin.grupoContabilidad.index'),
             ])->jsonResponse();

        }
        return AjaxForm::custom([
            'message' => $validator->errors()->all(),
         ])->jsonResponse();
        
    }

    /**
     * Borrar contacto
     * 
     * @param  GrupoContable  $contact
     * @param  Request  $request
     * 
     * @return \Illuminate\Http\Response
     */    
    public function destroy($id)
    {   
        $grupo = GrupoContable::find($id);

        if ($grupo == null) {
            return AjaxForm::errorMessage('El id: '.$id.' no existe.')->jsonResponse();
        }
        $grupo->delete();

        return AjaxForm::custom([
            'message' => 'Grupo Borrado.',
            'entryUrl' => route('admin.grupoContabilidad.index'),
         ])->jsonResponse();;
    }
}
