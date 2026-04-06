@extends('layouts.appAdmin')

@section('title', 'Informe AI - Análisis Financiero')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-robot text-primary me-2"></i>
                        Informe AI - Análisis Financiero
                    </h2>
                    <p class="text-muted mb-0">
                        Período: {{ \Carbon\Carbon::parse($informe->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($informe->fecha_fin)->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.diarioCaja.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Diario
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Análisis Generado por IA
                    </h5>
                    <small class="opacity-75">
                        Generado el {{ $informe->created_at->format('d/m/Y H:i') }}
                    </small>
                </div>
                <div class="card-body">
                    <div class="informe-content">
                        {!! $contenidoHtml !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.informe-content {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
}

.informe-content h1,
.informe-content h2,
.informe-content h3,
.informe-content h4,
.informe-content h5,
.informe-content h6 {
    color: #2c3e50;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.informe-content h1 {
    font-size: 2.5rem;
    border-bottom: 3px solid #3498db;
    padding-bottom: 0.5rem;
}

.informe-content h2 {
    font-size: 2rem;
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
    padding-left: 1rem;
}

.informe-content h3 {
    font-size: 1.5rem;
    color: #27ae60;
}

.informe-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.informe-content ul,
.informe-content ol {
    margin-bottom: 1rem;
    padding-left: 2rem;
}

.informe-content li {
    margin-bottom: 0.5rem;
}

.informe-content strong {
    color: #2c3e50;
    font-weight: 600;
}

.informe-content em {
    color: #7f8c8d;
    font-style: italic;
}

.informe-content blockquote {
    border-left: 4px solid #3498db;
    background-color: #f8f9fa;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 0 0.5rem 0.5rem 0;
}

.informe-content code {
    background-color: #f1f2f6;
    padding: 0.2rem 0.4rem;
    border-radius: 0.3rem;
    font-family: 'Courier New', monospace;
    color: #e74c3c;
}

.informe-content pre {
    background-color: #2c3e50;
    color: #ecf0f1;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
}

.informe-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.informe-content th,
.informe-content td {
    border: 1px solid #ddd;
    padding: 0.75rem;
    text-align: left;
}

.informe-content th {
    background-color: #3498db;
    color: white;
    font-weight: 600;
}

.informe-content tr:nth-child(even) {
    background-color: #f8f9fa;
}

.informe-content tr:hover {
    background-color: #e8f4f8;
}

/* Iconos para elementos específicos */
.informe-content h2::before {
    content: "📊 ";
    margin-right: 0.5rem;
}

.informe-content h3::before {
    content: "💡 ";
    margin-right: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .informe-content {
        font-size: 0.9rem;
    }
    
    .informe-content h1 {
        font-size: 2rem;
    }
    
    .informe-content h2 {
        font-size: 1.5rem;
    }
    
    .informe-content h3 {
        font-size: 1.2rem;
    }
}

/* Print styles */
@media print {
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .btn {
        display: none !important;
    }
    
    .informe-content {
        font-size: 12pt;
        line-height: 1.4;
    }
}
</style>
@endsection

