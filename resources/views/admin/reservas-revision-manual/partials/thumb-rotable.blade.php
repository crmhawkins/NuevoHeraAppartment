{{--
  [2026-04-22] Miniatura de foto DNI con botones de rotar (±90° y 180°).
  La auto-orient por EXIF+proporciones no siempre acierta (carnet ladeado
  dentro del frame, EXIF que miente) — estos botones dan control directo
  al admin.

  Variables requeridas:
    $foto     : App\Models\Photo
    $titulo   : texto para el modal y tooltip
    $alt      : alt de la miniatura
--}}
<div class="position-relative d-inline-block thumb-rotable" style="width:56px;height:56px;">
    {{-- Miniatura clicable (abre modal grande) --}}
    <a href="#" data-bs-toggle="modal" data-bs-target="#modalFoto"
       data-src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}?v={{ time() }}"
       data-titulo="{{ $titulo }}"
       title="{{ $titulo }} — click para ampliar">
        <img src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}?v={{ time() }}"
             alt="{{ $alt }}"
             style="width:56px;height:56px;object-fit:cover;border-radius:4px;border:1px solid #ccc;">
    </a>

    {{-- Botones de rotar: solo aparecen al hacer hover sobre la miniatura.
         Se envian como forms POST ocultos, recargan la pagina tras rotar. --}}
    <div class="thumb-rotar-controls">
        <form method="POST" action="{{ route('admin.reservas-revision-manual.rotar-foto', $foto->id) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="grados" value="-90">
            <button type="submit" class="btn btn-dark btn-sm p-0 thumb-rotar-btn"
                    title="Rotar 90° antihorario" onclick="return true;">
                <i class="fas fa-undo" style="font-size:9px;"></i>
            </button>
        </form>
        <form method="POST" action="{{ route('admin.reservas-revision-manual.rotar-foto', $foto->id) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="grados" value="180">
            <button type="submit" class="btn btn-dark btn-sm p-0 thumb-rotar-btn"
                    title="Rotar 180°">
                <i class="fas fa-sync" style="font-size:9px;"></i>
            </button>
        </form>
        <form method="POST" action="{{ route('admin.reservas-revision-manual.rotar-foto', $foto->id) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="grados" value="90">
            <button type="submit" class="btn btn-dark btn-sm p-0 thumb-rotar-btn"
                    title="Rotar 90° horario">
                <i class="fas fa-redo" style="font-size:9px;"></i>
            </button>
        </form>
    </div>
</div>
