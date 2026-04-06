<?php

namespace App\Http\Controllers;

use App\Models\Huesped;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HuespedesController extends Controller
{
    public function index(){
        $huespedes = Huesped::with('reserva')->paginate(15);
        return view('huespedes.index', compact('huespedes'));
    }

    public function show(string $id){
        $huesped = Huesped::findOrFail($id);
        $photos = Photo::with('categoria')->where('huespedes_id', $id)->get();
        return view('huespedes.show', compact('huesped','photos'));
    }

    public function create(){
        return view('huespedes.create');
    }

    public function store(Request $request){
        $request->validate([
            'reserva_id' => 'required|exists:reservas,id',
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today|after:1900-01-01',
            'sexo' => 'required|in:M,F',
            'nacionalidad' => 'required|string|max:255',
            'tipo_documento' => 'required|in:1,2',
            'numero_identificacion' => 'required|string|max:20|regex:/^[A-Za-z0-9\-]+$/',
            'numero_soporte_documento' => 'nullable|required_if:tipo_documento,1|string|max:20',
            'fecha_expedicion' => 'required|date|before_or_equal:today',
            'email' => 'required|email|max:255',
            'telefono_movil' => 'required|string|max:20',
            'direccion' => 'required|string|max:500',
            'codigo_postal' => 'required|string|max:10',
            'localidad' => 'nullable|string|max:255',
            'foto_dni_frente' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'foto_dni_reverso' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Verificar que no existe ya un huésped con el mismo documento en esta reserva
        $existente = Huesped::where('reserva_id', $request->reserva_id)
            ->where('numero_identificacion', $request->numero_identificacion)
            ->first();
        if ($existente) {
            return redirect()->back()
                ->withErrors(['numero_identificacion' => 'Ya existe un huésped con este documento en esta reserva.'])
                ->withInput();
        }

        // Sanitizar campos de texto: eliminar caracteres de control y espacios extra
        $data = $request->all();
        foreach (['nombre', 'primer_apellido', 'segundo_apellido', 'direccion', 'localidad'] as $campo) {
            if (isset($data[$campo])) {
                $data[$campo] = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $data[$campo]));
            }
        }
        // Normalizar número de documento a mayúsculas
        $data['numero_identificacion'] = strtoupper($data['numero_identificacion']);

        $huesped = Huesped::create($data);

        // Subir fotos
        if ($request->hasFile('foto_dni_frente')) {
            $fotoFrente = $request->file('foto_dni_frente');
            $fotoFrentePath = $fotoFrente->store('imagesCliente', 'public');

            // Limpiar metadatos EXIF de la imagen
            $fullPath = storage_path('app/public/' . $fotoFrentePath);
            $this->stripExifData($fullPath);

            // Determinar categoría según tipo de documento
            $categoriaId = $request->tipo_documento == 1 ? 13 : 15; // 13=DNI Frontal, 15=Pasaporte

            Photo::create([
                'huespedes_id' => $huesped->id,
                'reserva_id' => $huesped->reserva_id,
                'url' => $fotoFrentePath,
                'photo_categoria_id' => $categoriaId
            ]);
        }

        if ($request->hasFile('foto_dni_reverso') && $request->tipo_documento == 1) {
            $fotoReverso = $request->file('foto_dni_reverso');
            $fotoReversoPath = $fotoReverso->store('imagesCliente', 'public');

            // Limpiar metadatos EXIF de la imagen
            $fullPath = storage_path('app/public/' . $fotoReversoPath);
            $this->stripExifData($fullPath);

            Photo::create([
                'huespedes_id' => $huesped->id,
                'reserva_id' => $huesped->reserva_id,
                'url' => $fotoReversoPath,
                'photo_categoria_id' => 14 // 14=DNI Trasera
            ]);
        }

        // Auto-envío a MIR si la reserva tiene todos los datos completos
        try {
            $reserva = \App\Models\Reserva::find($huesped->reserva_id);
            if ($reserva) {
                $mirService = new \App\Services\MIRService();
                $mirService->enviarSiLista($reserva);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MIR: Error en auto-envío tras crear huésped: ' . $e->getMessage());
        }

        return redirect()->route('huespedes.show', $huesped->id)
            ->with('success', 'Huésped creado exitosamente.');
    }

    public function edit(string $id){
        $huesped = Huesped::findOrFail($id);
        $photos = Photo::with('categoria')->where('huespedes_id', $id)->get();
        return view('huespedes.edit', compact('huesped', 'photos'));
    }

    public function update(Request $request, string $id){
        $huesped = Huesped::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today|after:1900-01-01',
            'sexo' => 'required|in:M,F',
            'nacionalidad' => 'required|string|max:255',
            'tipo_documento' => 'required|in:1,2',
            'numero_identificacion' => 'required|string|max:20|regex:/^[A-Za-z0-9\-]+$/',
            'numero_soporte_documento' => 'nullable|required_if:tipo_documento,1|string|max:20',
            'fecha_expedicion' => 'required|date|before_or_equal:today',
            'email' => 'required|email|max:255',
            'telefono_movil' => 'required|string|max:20',
            'direccion' => 'required|string|max:500',
            'codigo_postal' => 'required|string|max:10',
            'localidad' => 'nullable|string|max:255',
            'foto_dni_frente' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'foto_dni_reverso' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Sanitizar campos de texto: eliminar caracteres de control y espacios extra
        $data = $request->all();
        foreach (['nombre', 'primer_apellido', 'segundo_apellido', 'direccion', 'localidad'] as $campo) {
            if (isset($data[$campo])) {
                $data[$campo] = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $data[$campo]));
            }
        }
        // Normalizar número de documento a mayúsculas
        $data['numero_identificacion'] = strtoupper($data['numero_identificacion']);

        $huesped->update($data);

        // Actualizar fotos si se suben nuevas
        if ($request->hasFile('foto_dni_frente')) {
            // Determinar categoría según tipo de documento
            $categoriaId = $request->tipo_documento == 1 ? 13 : 15; // 13=DNI Frontal, 15=Pasaporte

            // Eliminar foto anterior si existe
            $fotoAnterior = Photo::where('huespedes_id', $huesped->id)
                ->where('photo_categoria_id', $categoriaId)
                ->first();
            if ($fotoAnterior) {
                Storage::disk('public')->delete($fotoAnterior->url);
                $fotoAnterior->delete();
            }

            $fotoFrente = $request->file('foto_dni_frente');
            $fotoFrentePath = $fotoFrente->store('imagesCliente', 'public');

            // Limpiar metadatos EXIF de la imagen
            $fullPath = storage_path('app/public/' . $fotoFrentePath);
            $this->stripExifData($fullPath);

            Photo::create([
                'huespedes_id' => $huesped->id,
                'reserva_id' => $huesped->reserva_id,
                'url' => $fotoFrentePath,
                'photo_categoria_id' => $categoriaId
            ]);
        }

        if ($request->hasFile('foto_dni_reverso') && $request->tipo_documento == 1) {
            // Eliminar foto anterior si existe
            $fotoAnterior = Photo::where('huespedes_id', $huesped->id)
                ->where('photo_categoria_id', 14) // 14=DNI Trasera
                ->first();
            if ($fotoAnterior) {
                Storage::disk('public')->delete($fotoAnterior->url);
                $fotoAnterior->delete();
            }

            $fotoReverso = $request->file('foto_dni_reverso');
            $fotoReversoPath = $fotoReverso->store('imagesCliente', 'public');

            // Limpiar metadatos EXIF de la imagen
            $fullPath = storage_path('app/public/' . $fotoReversoPath);
            $this->stripExifData($fullPath);

            Photo::create([
                'huespedes_id' => $huesped->id,
                'reserva_id' => $huesped->reserva_id,
                'url' => $fotoReversoPath,
                'photo_categoria_id' => 14 // 14=DNI Trasera
            ]);
        }

        return redirect()->route('huespedes.show', $huesped->id)
            ->with('success', 'Huésped actualizado exitosamente.');
    }

    public function destroy(string $id){
        $huesped = Huesped::findOrFail($id);
        
        // Eliminar fotos asociadas
        $photos = Photo::where('huespedes_id', $id)->get();
        foreach ($photos as $photo) {
            Storage::disk('public')->delete($photo->url);
            $photo->delete();
        }
        
        $huesped->delete();
        
        return redirect()->route('huespedes.index')
            ->with('success', 'Huésped eliminado exitosamente.');
    }

    /**
     * Limpiar metadatos EXIF de una imagen re-guardándola sin metadatos.
     */
    private function stripExifData(string $filePath): void
    {
        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg'])) {
                $img = @imagecreatefromjpeg($filePath);
                if ($img) {
                    imagejpeg($img, $filePath, 90); // Re-guardar sin EXIF
                    imagedestroy($img);
                }
            } elseif ($extension === 'png') {
                $img = @imagecreatefrompng($filePath);
                if ($img) {
                    imagepng($img, $filePath, 6);
                    imagedestroy($img);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo limpiar EXIF de imagen', ['path' => $filePath, 'error' => $e->getMessage()]);
        }
    }
}
