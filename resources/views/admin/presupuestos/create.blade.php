@extends('layouts.appAdmin')

@section('title', 'Crear Presupuesto')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-plus me-2 text-success"></i>
                Crear Presupuesto
            </h1>
            <p class="text-muted mb-0">Crea un nuevo presupuesto para un cliente</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('presupuestos.index') }}">Presupuestos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Crear Presupuesto</li>
            </ol>
        </nav>
    </div>

    <!-- Formulario de Presupuesto -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-edit me-2 text-primary"></i>
                Información del Presupuesto
            </h5>
        </div>
        <div class="card-body">
            @include('admin.presupuestos.form', [
                'action' => route('presupuestos.store'),
                'method' => null,
                'presupuesto' => null,
                'clientes' => $clientes
            ])
        </div>
    </div>
</div>
@endsection
