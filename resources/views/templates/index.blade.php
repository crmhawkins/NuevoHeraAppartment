@extends('layouts.appAdmin')

@section('content')
<style>
    .flag-icon {
        width: 24px;
        height: 18px;
        object-fit: cover;
        margin-right: 6px;
    }
</style>

<!-- Flag Icons CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css" />

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row mb-3 align-items-start align-items-md-center">
        <h2 class="mb-2 mb-md-0 me-md-3 encabezado_top">{{ __('Plantillas de WhatsApp') }}</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('templates.create') }}" class="btn bg-color-sexto text-uppercase">
                <i class="fa-solid fa-plus me-2"></i>
                Crear plantilla
            </a>
            <a href="{{ route('templates.sync') }}" class="btn bg-color-tercero text-uppercase">
                <i class="fa-solid fa-arrows-rotate me-2"></i>
                Sincronizar plantillas
            </a>
        </div>
    </div>

    <hr class="mb-3">

    <div class="row mb-3">
        <div class="col-md-3">
            <select id="filter-language" class="form-select">
                <option value="">Filtrar por idioma</option>
                @foreach($templates->pluck('language')->unique() as $lang)
                    <option value="{{ $lang }}">{{ $lang }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select id="filter-category" class="form-select">
                <option value="">Filtrar por categoría</option>
                @foreach($templates->pluck('category')->unique() as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select id="filter-status" class="form-select">
                <option value="">Filtrar por estado</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Pending">Pending</option>
            </select>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @elseif ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <table id="templatesTable" class="table table-striped table-hover align-middle">
                <thead>
                    <tr class="bg-color-primero-table">
                        <th>Nombre</th>
                        <th>Idioma</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th style="width: 280px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>
                                @php
                                    $lang = strtolower($template->language);
                                    $baseLang = explode('_', $lang)[0];
                                    $map = [
                                        'en' => 'gb', 'es' => 'es', 'fr' => 'fr', 'it' => 'it',
                                        'de' => 'de', 'ar' => 'sa', 'pt' => 'pt', 'nl' => 'nl', 'ru' => 'ru'
                                    ];
                                    $flagCode = $map[$baseLang] ?? 'un';
                                @endphp
                                <span class="fi fi-{{ $flagCode }} me-1"></span>{{ $template->language }}
                            </td>
                            <td>{{ $template->category }}</td>
                            <td>
                                <span class="badge
                                    {{ $template->status === 'APPROVED' ? 'bg-success' :
                                       ($template->status === 'REJECTED' ? 'bg-danger' :
                                       'bg-warning text-dark') }}">
                                    {{ ucfirst(strtolower($template->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('templates.show', $template) }}" class="btn bg-color-cuarto text-black btn-sm">Ver</a>
                                    <a href="{{ route('templates.edit', $template) }}" class="btn bg-color-quinto btn-sm">Editar</a>
                                    <a href="{{ route('templates.checkStatus', $template) }}"
                                       class="btn btn-secondary btn-sm"
                                       onclick="return confirm('¿Seguro que deseas actualizar el estado de esta plantilla?')">
                                        Actualizar estado
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        const table = $('#templatesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 20
        });

        $('#filter-language').on('change', function () {
            table.column(1).search(this.value).draw();
        });

        $('#filter-category').on('change', function () {
            table.column(2).search(this.value).draw();
        });

        $('#filter-status').on('change', function () {
            table.column(3).search(this.value).draw();
        });
    });
</script>
@endsection
