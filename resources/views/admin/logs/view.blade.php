@extends('layouts.appAdmin')

@section('title', 'Ver Log: ' . $filename)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-file-alt me-2"></i>
                        Contenido del Log: {{ $filename }}
                    </h3>
                    <div class="apple-card-actions">
                        <a href="{{ route('admin.logs.files') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver a Archivos
                        </a>
                        <a href="{{ route('admin.logs.download', $filename) }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-download me-1"></i>
                            Descargar
                        </a>
                    </div>
                </div>
                <div class="apple-card-body">
                    <!-- Controles de navegación -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <input type="hidden" name="lines" value="{{ $lines }}">
                                <label class="form-label me-2">Líneas por página:</label>
                                <select name="lines" class="form-select" style="width: auto;" onchange="this.form.submit()">
                                    <option value="50" {{ $lines == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $lines == 100 ? 'selected' : '' }}>100</option>
                                    <option value="200" {{ $lines == 200 ? 'selected' : '' }}>200</option>
                                    <option value="500" {{ $lines == 500 ? 'selected' : '' }}>500</option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Mostrando {{ count($content) }} de {{ $totalLines }} líneas
                            </small>
                        </div>
                    </div>

                    <!-- Navegación de páginas -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <nav aria-label="Navegación de log">
                                <ul class="pagination pagination-sm justify-content-center">
                                    @if($offset > 0)
                                        <li class="page-item">
                                            <a class="page-link" href="?lines={{ $lines }}&offset={{ max(0, $offset - $lines) }}">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                    @endif
                                    
                                    @if($offset + $lines < $totalLines)
                                        <li class="page-item">
                                            <a class="page-link" href="?lines={{ $lines }}&offset={{ $offset + $lines }}">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Contenido del log -->
                    <div class="log-content">
                        <pre class="log-pre"><code>@foreach($content as $line)
<span class="log-line" data-line="{{ $line['line_number'] }}">
<span class="log-line-number">{{ str_pad($line['line_number'], 6, '0', STR_PAD_LEFT) }}</span>
<span class="log-timestamp">{{ $line['timestamp'] ?? '' }}</span>
<span class="log-content-text">{{ $line['content'] }}</span>
</span>
@endforeach</code></pre>
                    </div>

                    <!-- Navegación inferior -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <nav aria-label="Navegación de log">
                                <ul class="pagination pagination-sm justify-content-center">
                                    @if($offset > 0)
                                        <li class="page-item">
                                            <a class="page-link" href="?lines={{ $lines }}&offset={{ max(0, $offset - $lines) }}">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                    @endif
                                    
                                    @if($offset + $lines < $totalLines)
                                        <li class="page-item">
                                            <a class="page-link" href="?lines={{ $lines }}&offset={{ $offset + $lines }}">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-content {
    background-color: #1e1e1e;
    border-radius: 8px;
    padding: 1rem;
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
}

.log-pre {
    margin: 0;
    color: #d4d4d4;
    font-size: 13px;
    line-height: 1.4;
}

.log-line {
    display: block;
    margin-bottom: 2px;
}

.log-line-number {
    color: #6a9955;
    margin-right: 10px;
    user-select: none;
}

.log-timestamp {
    color: #569cd6;
    margin-right: 10px;
}

.log-content-text {
    color: #d4d4d4;
}

.log-line:hover {
    background-color: #2d2d30;
    border-radius: 3px;
    padding: 2px 4px;
    margin: 0 -4px 2px -4px;
}

/* Colores para diferentes niveles de log */
.log-content-text:contains("ERROR") {
    color: #f44747;
}

.log-content-text:contains("WARNING") {
    color: #ffcc02;
}

.log-content-text:contains("INFO") {
    color: #4fc1ff;
}

.log-content-text:contains("DEBUG") {
    color: #6a9955;
}
</style>

<script>
// Auto-scroll to bottom on page load
document.addEventListener('DOMContentLoaded', function() {
    const logContent = document.querySelector('.log-content');
    if (logContent) {
        logContent.scrollTop = logContent.scrollHeight;
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        const currentOffset = {{ $offset }};
        const lines = {{ $lines }};
        
        if (e.key === 'ArrowUp' && currentOffset > 0) {
            window.location.href = `?lines=${lines}&offset=${Math.max(0, currentOffset - lines)}`;
        } else if (e.key === 'ArrowDown' && currentOffset + lines < {{ $totalLines }}) {
            window.location.href = `?lines=${lines}&offset=${currentOffset + lines}`;
        }
    }
});
</script>
@endsection
