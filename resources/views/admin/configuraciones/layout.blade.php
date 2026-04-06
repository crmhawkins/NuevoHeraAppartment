@extends('layouts.appAdmin')

@section('title', 'Configuración')

@section('content')
<style>
    /* Estilos Apple para Configuración */
    .config-container {
        padding: 24px;
        background: #F2F2F7;
        min-height: calc(100vh - 80px);
    }

    .config-header {
        margin-bottom: 32px;
    }

    .config-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #1D1D1F;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .config-header p {
        font-size: 15px;
        color: #8E8E93;
        margin: 0;
    }

    .config-content-wrapper {
        margin-top: 0;
    }

    /* Cards */
    .config-card {
        background: #FFFFFF;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 24px;
    }

    .config-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #E5E5EA;
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-radius: 16px 16px 0 0;
    }

    .config-card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1D1D1F;
        margin: 0;
    }

    .config-card-body {
        padding: 24px;
    }

    .form-label {
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #E5E5EA;
        padding: 10px 12px;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    }

    /* Errores de validación */
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #DC3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }

    .form-control.is-invalid:focus,
    .form-select.is-invalid:focus {
        border-color: #DC3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #DC3545;
        font-weight: 500;
    }

    .invalid-feedback i {
        margin-right: 4px;
    }

    .alert-danger {
        background-color: #F8D7DA;
        border-color: #F5C6CB;
        color: #721C24;
        border-left: 4px solid #DC3545;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .alert-danger ul {
        margin-top: 8px;
        padding-left: 20px;
    }

    .alert-danger li {
        margin-bottom: 4px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
    }
</style>

<div class="config-container">
    <div class="config-header">
        <h1>@yield('config-title', 'Configuración')</h1>
        <p>@yield('config-subtitle', 'Gestiona la configuración del sistema y personaliza los ajustes según tus necesidades')</p>
    </div>

    <!-- Content -->
    <div class="config-content-wrapper">
        @yield('config-content')
    </div>
</div>

@endsection

