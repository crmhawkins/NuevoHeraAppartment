@extends('layouts.appAdmin')

@section('title', 'Dashboard de Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Dashboard de Logs del Sistema
                    </h3>
                    <div class="apple-card-actions">
                        <a href="{{ route('admin.logs.files') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-alt me-1"></i>
                            Ver Archivos
                        </a>
                        <a href="{{ route('admin.logs.search') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-search me-1"></i>
                            Buscar Logs
                        </a>
                    </div>
                </div>
                <div class="apple-card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="d-flex gap-2">
                                <select name="days" class="form-select" style="width: auto;">
                                    <option value="1" {{ $days == 1 ? 'selected' : '' }}>Último día</option>
                                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Últimos 7 días</option>
                                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Últimos 30 días</option>
                                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Últimos 90 días</option>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>
                                    Filtrar
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Estadísticas principales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-primary">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Total de Peticiones</h6>
                                    <p class="apple-list-item-subtitle">{{ number_format($stats['total_requests']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Errores</h6>
                                    <p class="apple-list-item-subtitle">{{ number_format($stats['errors']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-warning">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Advertencias</h6>
                                    <p class="apple-list-item-subtitle">{{ number_format($stats['warnings']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-info">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Tasa de Error</h6>
                                    <p class="apple-list-item-subtitle">{{ $stats['error_rate'] }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="apple-card">
                                <div class="apple-card-header">
                                    <h5 class="apple-card-title">Información del Sistema</h5>
                                </div>
                                <div class="apple-card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <strong>Directorio de Logs:</strong>
                                            <code>{{ storage_path('logs') }}</code>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Última actualización:</strong>
                                            {{ now()->format('d/m/Y H:i:s') }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>Período analizado:</strong>
                                            Últimos {{ $days }} días
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="apple-card">
                                <div class="apple-card-header">
                                    <h5 class="apple-card-title">Acciones Rápidas</h5>
                                </div>
                                <div class="apple-card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.logs.files') }}" class="btn btn-outline-primary">
                                            <i class="fas fa-file-alt me-2"></i>
                                            Ver Archivos de Log
                                        </a>
                                        <a href="{{ route('admin.logs.search') }}" class="btn btn-outline-info">
                                            <i class="fas fa-search me-2"></i>
                                            Buscar en Logs
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" onclick="clearLogs()">
                                            <i class="fas fa-trash me-2"></i>
                                            Limpiar Logs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearLogs() {
    if (confirm('¿Estás seguro de que quieres eliminar todos los archivos de log? Esta acción no se puede deshacer.')) {
        fetch('{{ route("admin.logs.clear") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al limpiar los logs');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al limpiar los logs');
        });
    }
}
</script>
@endsection
