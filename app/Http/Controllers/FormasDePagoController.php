<?php

namespace App\Http\Controllers;

use App\Models\FormasPago;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class FormasDePagoController extends Controller
{
    public function store(Request $request){
        $nombre = $request->nombre;
        $formaNueva = FormasPago::create([
            'nombre' => $nombre
        ]);

        Alert::toast('Creado', 'success');
        return redirect()->route('configuracion.index');

    }
    
    public function update($id, Request $request){
        $formaDePago = FormasPago::find($id);
        $nombre = $request->nombre;

        $formaDePago->nombre = $nombre;
        $formaDePago->save();
        return true;
    }

    public function delete($id){
        $formaDePago = FormasPago::find($id);

        if($formaDePago){
            $formaDePago->delete();
            return redirect(route('configuracion.index'));
        }

    }
}
