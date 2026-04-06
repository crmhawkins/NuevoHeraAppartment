@extends('layouts.appAdmin')

@section('title', 'Archivos de Log')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-file-alt me-2"></i>
                        Archivos de Log del Sistema
                    </h3>
                    <div class="apple-card-actions">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver al Dashboard
                        </a>
                    </div>
                </div>
                <div class="apple-card-body">
                    @if(count($files) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Última Modificación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($files as $file)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-alt text-primary me-2"></i>
                                                <strong>{{ $file['name'] }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ formatBytes($file['size']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $file['modified']->format('d/m/Y H:i:s') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('admin.logs.view', $file['name']) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="Ver contenido">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.logs.download', $file['name']) }}" 
                                                       class="btn btn-outline-success" 
                                                       title="Descargar">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron archivos de log</h5>
                            <p class="text-muted">Los archivos de log aparecerán aquí una vez que se generen.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
@endphp
