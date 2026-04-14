@extends('layouts.appAdmin')

@section('title', 'Facturas pendientes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice me-2 text-primary"></i>
                Facturas pendientes
            </h1>
            <p class="text-muted mb-0">Facturas subidas desde el movil, procesadas por IA y emparejadas con gastos del banco.</p>
        </div>
        <div>
            @php
                $token = config('services.facturas.upload_token');
            @endphp
            @if($token)
                <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('{{ url('/facturas/subir/' . $token) }}'); this.textContent='Copiado!'">
                    <i class="fas fa-link"></i> Copiar enlace movil
                </button>
            @else
                <span class="badge bg-warning">Configura FACTURAS_UPLOAD_TOKEN en .env</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">En cola</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $enCola->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En espera (sin match)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $enEspera->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Con error</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $conError->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Asociadas recientes</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $asociadasRecientes->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs" id="facturasTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-espera">
                En espera <span class="badge bg-warning">{{ $enEspera->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-error">
                Con error <span class="badge bg-danger">{{ $conError->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-cola">
                En cola <span class="badge bg-secondary">{{ $enCola->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-asociadas">
                Asociadas recientes
            </a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white rounded-bottom">
        @include('admin.facturas_pendientes._tabla', ['id' => 'tab-espera', 'active' => true,  'facturas' => $enEspera, 'mostrarCandidatos' => true])
        @include('admin.facturas_pendientes._tabla', ['id' => 'tab-error', 'active' => false, 'facturas' => $conError, 'mostrarCandidatos' => true])
        @include('admin.facturas_pendientes._tabla', ['id' => 'tab-cola',  'active' => false, 'facturas' => $enCola,   'mostrarCandidatos' => false])
        @include('admin.facturas_pendientes._tabla', ['id' => 'tab-asociadas', 'active' => false, 'facturas' => $asociadasRecientes, 'mostrarCandidatos' => false])
    </div>
</div>

{{-- Modal asociar manual --}}
<div class="modal fade" id="modalAsociar" tabindex="-1">
    <div class="modal-dialog">
        <form id="formAsociar" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asociar factura a gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">ID del gasto:</label>
                    <input type="number" name="gasto_id" class="form-control" required>
                    <small class="text-muted">Busca el id en el Diario de caja. La imagen se movera a procesadas y se adjuntara al gasto.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asociar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('[data-asociar-url]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('formAsociar').action = btn.dataset.asociarUrl;
        new bootstrap.Modal(document.getElementById('modalAsociar')).show();
    });
});
</script>
@endsection
