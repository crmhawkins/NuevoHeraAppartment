@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0 encabezado_top">
            <i class="fa-solid fa-plus me-2"></i>
            Crear nueva plantilla
        </h2>
        <a href="{{ route('templates.index') }}" class="btn bg-color-segundo">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>

    <hr>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('templates.store') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input type="text" class="form-control" name="name" id="name" required>
        </div>

        <div class="mb-3">
            <label for="language" class="form-label">Idioma</label>
            <input type="text" class="form-control" name="language" id="language" placeholder="Ej: es, en, fr" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Categoría</label>
            <select name="category" class="form-select" id="category" required>
                <option value="MARKETING">Marketing</option>
                <option value="UTILITY">Utility</option>
                <option value="AUTHENTICATION">Authentication</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="components" class="form-label">Componentes (JSON)</label>
            <textarea name="components" id="components" rows="10" class="form-control" placeholder='[{ "type": "BODY", "text": "..." }]' required></textarea>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn bg-color-primero">
                <i class="fa-solid fa-paper-plane me-2"></i>
                Enviar para aprobación
            </button>
        </div>
    </form>
</div>
@endsection
