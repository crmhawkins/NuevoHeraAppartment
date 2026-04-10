@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-plus-circle text-primary me-2"></i>
            Crear Nuevo Cupón
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.cupones.index') }}">Cupones</a></li>
                <li class="breadcrumb-item active">Crear</li>
            </ol>
        </nav>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-circle me-2"></i>Errores de validación:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.cupones.store') }}" method="POST">
        @csrf
        @include('admin.cupones._form')
    </form>
</div>

@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function() {
    var errores = @json($errors->all());
    Swal.fire({
        icon: 'warning',
        title: 'Campos obligatorios',
        html: '<ul style="text-align:left;">' + errores.map(function(e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
        confirmButtonText: 'Entendido'
    });
});
</script>
@endif
@endsection
