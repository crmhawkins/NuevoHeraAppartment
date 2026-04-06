<?php

namespace App\Http\Controllers;

use App\Models\Proveedores;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{
    public function index(){
        $proveedores = Proveedores::all();
        return view('admin.proveedores.index', compact('proveedores'));
    }

    public function create(){
        return view('admin.proveedores.create');
        
    }
    
    public function store(){
        
    }

    public function edit(){
        
    }

    public function update(){
        
    }

    public function destroy(){
        
    }
}
