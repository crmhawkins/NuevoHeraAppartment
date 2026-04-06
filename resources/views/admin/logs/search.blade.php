@extends('layouts.appAdmin')

@section('title', 'Buscar en Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-search me-2"></i>
                        Buscar en Logs del Sistema
                    </h3>
                    <div class="apple-card-actions">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver al Dashboard
                        </a>
                    </div>
                </div>
                <div class="apple-card-body">
                    <!-- Formulario de búsqueda -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="q" class="form-label">Texto a buscar</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="q" 
                                       name="q" 
                                       value="{{ $query }}" 
                                       placeholder="Ingresa texto a buscar...">
                            </div>
                            <div class="col-md-2">
                                <label for="level" class="form-label">Nivel</label>
                                <select class="form-select" id="level" name="level">
                                    <option value="">Todos</option>
                                    <option value="ERROR" {{ $level == 'ERROR' ? 'selected' : '' }}>ERROR</option>
                                    <option value="WARNING" {{ $level == 'WARNING' ? 'selected' : '' }}>WARNING</option>
                                    <option value="INFO" {{ $level == 'INFO' ? 'selected' : '' }}>INFO</option>
                                    <option value="DEBUG" {{ $level == 'DEBUG' ? 'selected' : '' }}>DEBUG</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date" class="form-label">Fecha</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date" 
                                       name="date" 
                                       value="{{ $date }}">
                            </div>
                            <div class="col-md-2">
                                <label for="user" class="form-label">Usuario</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="user" 
                                       name="user" 
                                       value="{{ $user }}" 
                                       placeholder="Nombre de usuario...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Resultados -->
                    @if(request()->hasAny(['q', 'level', 'date', 'user']))
                        @if(count($results) > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Se encontraron {{ count($results) }} resultados
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Archivo</th>
                                            <th>Línea</th>
                                            <th>Timestamp</th>
                                            <th>Contenido</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($results as $result)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">{{ $result['file'] }}</span>
                                                </td>
                                                <td>
                                                    <code>{{ $result['line_number'] }}</code>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $result['timestamp'] ?? 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="log-search-result">
                                                        {!! highlightSearchTerm($result['content'], $query) !!}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No se encontraron resultados para la búsqueda realizada.
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Buscar en Logs</h5>
                            <p class="text-muted">Utiliza el formulario superior para buscar en los archivos de log del sistema.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-search-result {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    max-width: 500px;
    overflow-x: auto;
    white-space: pre-wrap;
}

.highlight {
    background-color: #ffeb3b;
    padding: 2px 4px;
    border-radius: 2px;
    font-weight: bold;
}
</style>
@endsection

@php
function highlightSearchTerm($content, $query) {
    if (empty($query)) {
        return htmlspecialchars($content);
    }
    
    $highlighted = preg_replace(
        '/(' . preg_quote($query, '/') . ')/i',
        '<span class="highlight">$1</span>',
        htmlspecialchars($content)
    );
    
    return $highlighted;
}
@endphp
