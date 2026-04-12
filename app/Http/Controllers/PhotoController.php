<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApartamentoLimpiezaItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Checklist;
use App\Models\PhotoCategoria;
use App\Models\ApartamentoLimpieza;

class PhotoController extends Controller
{
    public function index($id, $cat) {
        $cat = (int) $cat;

        // Obtener la limpieza con sus relaciones
        $limpieza = ApartamentoLimpieza::with(['apartamento', 'tareaAsignada'])->findOrFail($id);

        $categorias = PhotoCategoria::whereJsonContains('id_cat', $cat)->get();

        $imagenes = ApartamentoLimpiezaItem::where('id_limpieza', $id)
            ->where('checklist_id', $cat)
            ->get()
            ->keyBy('item_id');

        return view('photos.index', [
            'limpieza' => $limpieza,
            'categorias' => $categorias,
            'imagenes' => $imagenes,
            'id' => $id,
            'cat' => $cat,
            'checklist' => Checklist::find($cat),
        ]);
    }


    // STORE: Subida de múltiples imágenes con sus categorías
    public function store(Request $request, $id, $cat)
    {
        try {
            $request->validate(['image' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:10240']);

            $limpieza = ApartamentoLimpieza::find($id);
            $idReserva = $limpieza->reserva_id;
            $file = $request->file('image');
            $fileName = Str::random(10) . '_' . time() . '.' . $file->extension();
            $file->move(public_path('images'), $fileName);

            $imageUrl = 'images/' . $fileName;

            // PRIMERO: Borrar fotos anteriores para este item específico
            $fotosAnteriores = ApartamentoLimpiezaItem::where([
                'id_limpieza' => $id,
                'checklist_id' => $request->checklist_id,
                'item_id' => $request->item_id
            ])->whereNotNull('photo_url')->get();

            foreach ($fotosAnteriores as $fotoAnterior) {
                // Borrar archivo físico si existe
                if ($fotoAnterior->photo_url && File::exists(public_path($fotoAnterior->photo_url))) {
                    File::delete(public_path($fotoAnterior->photo_url));
                }
                // Borrar registro de la base de datos
                $fotoAnterior->delete();
            }

            // AHORA: Crear el nuevo registro
            $registro = ApartamentoLimpiezaItem::create([
                'id_limpieza'  => $id,
                'checklist_id' => $request->checklist_id,
                'item_id'      => $request->item_id,
                'photo_url'    => $imageUrl,
                'photo_cat'    => 'image_' . $request->item_id,
                'id_reserva'   => $idReserva,
                'estado'       => 0 // Estado por defecto
            ]);

            // Si es una tarea del nuevo sistema, también guardar en tarea_checklist_completados
            if ($limpieza->tarea_asignada_id) {
                \App\Models\TareaChecklistCompletado::updateOrInsert(
                    [
                        'tarea_asignada_id' => $limpieza->tarea_asignada_id,
                        'item_checklist_id' => $request->item_id
                    ],
                    [
                        'completado_por' => auth()->id(),
                        'fecha_completado' => now(),
                        'estado' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'url' => asset($imageUrl),
                'message' => 'Imagen subida con éxito'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first()
            ], 422);
        }

    }


    // ACTUALIZAR: Reemplazar imagen ya existente
    public function actualizar(Request $request, $id)
    {
        $item = ApartamentoLimpiezaItem::findOrFail($id);

        if ($request->hasFile('imagen')) {
            // Borrar archivo anterior si existe
            if ($item->photo_url && File::exists(public_path($item->photo_url))) {
                File::delete(public_path($item->photo_url));
            }

            // Subir nueva imagen
            $randomPrefix = Str::random(10);
            $imageName = $randomPrefix . '_' . time() . '.' . $request->imagen->getClientOriginalExtension();
            $request->imagen->move(public_path('images'), $imageName);
            $item->photo_url = 'images/' . $imageName;
        }

        // Actualizar otros campos
        $item->photo_cat = $request->photo_cat ?? $item->photo_cat;
        $item->estado = $request->estado ?? $item->estado;
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagen actualizada correctamente',
        ]);
    }
}
