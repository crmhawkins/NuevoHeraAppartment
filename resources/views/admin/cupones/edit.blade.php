@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-warning me-2"></i>
            Editar Cupón: <span class="text-primary">{{ $cupon->codigo }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.cupones.index') }}">Cupones</a></li>
                <li class="breadcrumb-item active">Editar</li>
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

    <form action="{{ route('admin.cupones.update', $cupon) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.cupones._form')
    </form>
</div>
@endsection
