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

        $categorias = PhotoCategoria::whereJsonContains('id_cat', $cat)->get();

        $imagenes = ApartamentoLimpiezaItem::where('id_limpieza', $id)
            ->where('checklist_id', $cat)
            ->get()
            ->keyBy('item_id');

        return view('photos.index', [
            'categorias' => $categorias,
            'imagenes' => $imagenes,
            'id' => $id,
            'cat' => $cat,
            'checklist' => Checklist::find($cat)
        ]);
    }


    // STORE: Subida de múltiples imágenes con sus categorías
    public function store(Request $request, $id, $cat)
    {
        try {
            $limpieza = ApartamentoLimpieza::find($id);
            $idReserva = $limpieza->reserva_id;
            $file = $request['image'];
            $fileName = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $fileName);

            $imageUrl = 'images/' . $fileName;

            // Guardar o actualizar la imagen en apartamento_limpieza_items
            $registro = ApartamentoLimpiezaItem::updateOrCreate(
                [
                    'id_limpieza'  => $id,
                    'checklist_id' => $request->checklist_id,
                    'item_id'      => $request->item_id,
                    'photo_url'    => $imageUrl,
                    'photo_cat'    => 'image_' . $request->item_id, // Puedes adaptar esto si usas otro nombre
                    'id_reserva' => $idReserva,
                ]
            );

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
            if ($item->photo_url && File::exists(public_path($item->photo_url))) {
                File::delete(public_path($item->photo_url));
            }

            $randomPrefix = Str::random(10);
            $imageName = $randomPrefix . '_' . time() . '.' . $request->imagen->getClientOriginalExtension();
            $request->imagen->move(public_path('images'), $imageName);
            $item->photo_url = 'images/' . $imageName;
        }

        $item->photo_cat = $request->photo_cat ?? $item->photo_cat;
        $item->estado = $request->estado ?? $item->estado;
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagen actualizada correctamente',
        ]);
    }
}
