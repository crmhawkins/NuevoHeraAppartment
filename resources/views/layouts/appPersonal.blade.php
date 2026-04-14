<!doctype html>
<html lang="{{ Auth::check() && Auth::user()->idioma_preferido ? Auth::user()->idioma_preferido : str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- CSS del Style Guide de Limpieza -->
    <link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
    
    <!-- CSS específico del Dashboard de Limpiadoras -->
    <link rel="stylesheet" href="{{ asset('css/limpiadora-dashboard.css') }}">
    
    @stack('styles')
    
    @yield('scriptHead')

    <!-- Scripts -->
    <script>
        // Tiempo de sesión en milisegundos
        var sessionLifetime = {{ config('session.lifetime') * 60000 }};
    </script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
<style>
    /* Version: 1.0.1 - Botones en fila fix */
    /* Apple Header Styles */
    .apple-header {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        height: 60px;
        border-radius: 0 0 20px 20px !important;
        box-shadow: 0 4px 20px rgba(0, 122, 255, 0.3);
        /* backdrop-filter removed for performance */
        position: relative;
        overflow: hidden;
    }

    .apple-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        pointer-events: none;
    }

    .apple-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }

    .apple-header-left {
        flex: 1;
        display: flex;
        align-items: center;
    }

    .apple-header-center {
        flex: 2;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .apple-header-right {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .apple-back-button {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        text-decoration: none;
        transition: all 0.3s ease;
        /* backdrop-filter removed for performance */
    }

    .apple-back-button:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
        color: #FFFFFF;
    }

    .apple-back-button i {
        font-size: 16px;
        font-weight: 600;
    }

    .apple-welcome {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .apple-welcome-text {
        font-size: 12px;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.8);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .apple-user-name {
        font-size: 16px;
        font-weight: 600;
        color: #FFFFFF;
        letter-spacing: -0.01em;
        text-transform: uppercase;
    }

    .apple-logout-button {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        text-decoration: none;
        transition: all 0.3s ease;
        /* backdrop-filter removed for performance */
    }

    .apple-logout-button:hover {
        background: rgba(255, 59, 48, 0.2);
        transform: scale(1.05);
        color: #FFFFFF;
    }

    .apple-logout-button i {
        font-size: 14px;
        font-weight: 600;
    }

    .apple-user-button {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        text-decoration: none;
        transition: all 0.3s ease;
        /* backdrop-filter removed for performance */
        margin-right: 12px;
    }

    .apple-user-button:hover {
        background: rgba(0, 122, 255, 0.2);
        transform: scale(1.05);
        color: #FFFFFF;
    }

    .apple-user-button i {
        font-size: 14px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .apple-header {
            height: 56px;
            border-radius: 0 0 25px 25px;
        }

        .apple-header-content {
            padding: 0 16px;
        }

        .apple-welcome-text {
            font-size: 11px;
        }

        .apple-user-name {
            font-size: 15px;
        }

        .apple-back-button,
        .apple-logout-button {
            width: 32px;
            height: 32px;
        }

        .apple-back-button i {
            font-size: 14px;
        }

        .apple-logout-button i {
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .apple-header {
            height: 52px;
            border-radius: 0 0 20px 20px;
        }

        .apple-header-content {
            padding: 0 12px;
        }

        .apple-welcome-text {
            font-size: 10px;
        }

        .apple-user-name {
            font-size: 14px;
        }
    }

    /* Apple Tab Bar Styles */
    .apple-tab-bar {
        background: rgba(255, 255, 255, 0.98);
        /* backdrop-filter removed for performance */
        border-top: 1px solid rgba(0, 0, 0, 0.15);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        height: 84px;
        padding-bottom: env(safe-area-inset-bottom);
    }

    .apple-tab-bar-content {
        display: flex;
        align-items: center;
        justify-content: space-around;
        height: 100%;
        padding: 8px 0;
    }

    .apple-tab-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #6C6C70;
        transition: all 0.3s ease;
        padding: 8px 12px;
        border-radius: 12px;
        min-width: 60px;
        position: relative;
        cursor: pointer;
    }

    .apple-tab-item:hover {
        color: #007AFF;
        transform: translateY(-2px);
    }

    .apple-tab-item:active {
        transform: translateY(0px) scale(0.95);
    }

    .apple-tab-item.active {
        color: #007AFF;
    }

    .apple-tab-item.active::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 4px;
        height: 4px;
        background: #007AFF;
        border-radius: 50%;
    }

    .apple-tab-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 4px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .apple-tab-item:hover .apple-tab-icon {
        background: rgba(0, 122, 255, 0.1);
    }

    .apple-tab-item.active .apple-tab-icon {
        background: rgba(0, 122, 255, 0.15);
    }

    .apple-tab-icon i {
        font-size: 20px;
        font-weight: 600;
        color: inherit;
    }

    .apple-tab-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2px;
        text-align: center;
        line-height: 1.2;
        color: inherit;
    }

    /* Ajuste para el contenido principal */
    .contendor {
        padding-bottom: 100px;
    }

    /* Responsive para tab bar */
    @media (max-width: 768px) {
        .apple-tab-bar {
            height: 80px;
        }

        .apple-tab-item {
            padding: 6px 10px;
            min-width: 56px;
        }

        .apple-tab-icon {
            width: 26px;
            height: 26px;
        }

        .apple-tab-icon i {
            font-size: 18px;
        }

        .apple-tab-label {
            font-size: 10px;
        }
    }

    @media (max-width: 480px) {
        .apple-tab-bar {
            height: 76px;
        }

        .apple-tab-item {
            padding: 4px 6px;
            min-width: 48px;
        }

        .apple-tab-icon {
            width: 24px;
            height: 24px;
        }

        .apple-tab-icon i {
            font-size: 16px;
        }

        .apple-tab-label {
            font-size: 9px;
        }
    }

    @media (max-width: 360px) {
        .apple-tab-bar {
            height: 72px;
        }

        .apple-tab-item {
            padding: 3px 4px;
            min-width: 44px;
        }

        .apple-tab-icon {
            width: 22px;
            height: 22px;
        }

        .apple-tab-icon i {
            font-size: 14px;
        }

        .apple-tab-label {
            font-size: 8px;
        }
    }

    /* Apple Gestion Styles */
    .apple-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .apple-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 500;
        /* backdrop-filter removed for performance */
    }

    .apple-alert-success {
        background: rgba(52, 199, 89, 0.1);
        color: #34C759;
        border: 1px solid rgba(52, 199, 89, 0.2);
    }

    .apple-alert i {
        font-size: 18px;
    }

    .apple-action-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 32px;
    }

    .apple-btn-row {
        display: flex !important;
        flex-direction: row !important;
        gap: 12px;
        width: 100%;
    }

    .apple-btn-form {
        flex: 1;
        width: 50%;
    }

    .apple-btn-full {
        width: 100%;
    }

    /* Forzar botones en fila en pantallas grandes */
    @media (min-width: 481px) {
        .apple-btn-row {
            display: flex !important;
            flex-direction: row !important;
        }
        
        .apple-btn-form {
            flex: 1;
            width: 50%;
        }
    }

    .apple-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 16px 24px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        /* backdrop-filter removed for performance */
    }

    .apple-btn-primary {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        color: #FFFFFF;
        box-shadow: 0 4px 20px rgba(0, 122, 255, 0.3);
    }

    .apple-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 122, 255, 0.4);
        color: #FFFFFF;
    }

    .apple-btn-danger {
        background: linear-gradient(135deg, #FF3B30 0%, #D70015 100%);
        color: #FFFFFF;
        box-shadow: 0 4px 20px rgba(255, 59, 48, 0.3);
    }

    .apple-btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 59, 48, 0.4);
        color: #FFFFFF;
    }

    .apple-btn-warning {
        background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%);
        color: #FFFFFF;
        box-shadow: 0 4px 20px rgba(255, 149, 0, 0.3);
    }

    .apple-btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 149, 0, 0.4);
        color: #FFFFFF;
    }

    .apple-btn-secondary {
        background: linear-gradient(135deg, #8E8E93 0%, #6C6C70 100%);
        color: #FFFFFF;
        box-shadow: 0 4px 20px rgba(142, 142, 147, 0.3);
    }

    .apple-btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(142, 142, 147, 0.4);
        color: #FFFFFF;
    }

    .apple-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .apple-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        /* backdrop-filter removed for performance */
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .apple-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .apple-card-header:hover {
        background: linear-gradient(135deg, #E5E5EA 0%, #D1D1D6 100%);
    }

    .apple-card-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 600;
        color: #1D1D1F;
    }

    .apple-card-title i {
        font-size: 18px;
        color: #007AFF;
    }

    .apple-card-counter {
        background: #007AFF;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 12px;
        min-width: 20px;
        text-align: center;
        line-height: 1;
    }

    .apple-card-toggle {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8E8E93;
        transition: all 0.3s ease;
    }

    .apple-card-toggle i {
        font-size: 14px;
        transition: transform 0.3s ease;
    }

    .apple-card-header[aria-expanded="true"] .apple-card-toggle i {
        transform: rotate(180deg);
    }

    .apple-card-body {
        padding: 0;
    }

    .apple-list {
        padding: 0;
    }

    .apple-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .apple-list-item:last-child {
        border-bottom: none;
    }

    .apple-list-item:hover {
        background: rgba(0, 122, 255, 0.05);
    }

    .apple-list-item-info {
        background: rgba(0, 122, 255, 0.1);
        border-left: 4px solid #007AFF;
    }

    .apple-list-item-warning {
        background: rgba(255, 149, 0, 0.1);
        border-left: 4px solid #FF9500;
    }

    .apple-list-item-success {
        background: rgba(52, 199, 89, 0.1);
        border-left: 4px solid #34C759;
    }

    .apple-list-content {
        flex: 1;
    }

    .apple-list-title {
        font-size: 16px;
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 4px;
    }

    .apple-list-subtitle {
        font-size: 14px;
        color: #8E8E93;
        line-height: 1.4;
    }

    .apple-list-arrow {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #C7C7CC;
        transition: all 0.3s ease;
    }

    .apple-list-item:hover .apple-list-arrow {
        color: #007AFF;
        transform: translateX(4px);
    }

    .apple-list-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(52, 199, 89, 0.1);
        border-radius: 8px;
        color: #34C759;
    }

    .apple-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
        text-align: center;
        color: #8E8E93;
    }

    .apple-empty-state i {
        font-size: 32px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .apple-empty-state span {
        font-size: 16px;
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .apple-container {
            padding: 16px;
        }

        .apple-btn-row {
            flex-direction: column;
            gap: 8px;
        }

        .apple-card-header {
            padding: 14px 16px;
        }

        .apple-card-title {
            font-size: 15px;
        }

        .apple-list-item {
            padding: 12px 16px;
        }

        .apple-list-title {
            font-size: 15px;
        }

        .apple-list-subtitle {
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .apple-container {
            padding: 12px;
        }

        .apple-card-header {
            padding: 12px 14px;
        }

        .apple-card-title {
            font-size: 14px;
        }

        .apple-card-counter {
            font-size: 11px;
            padding: 3px 6px;
        }

        .apple-list-item {
            padding: 10px 14px;
        }

        .apple-list-title {
            font-size: 14px;
        }

        .apple-list-subtitle {
            font-size: 12px;
        }
    }

    @media (max-width: 768px) {
        .apple-container {
            padding: 16px;
        }

        .apple-card-header {
            padding: 14px 16px;
        }

        .apple-card-title {
            font-size: 15px;
        }

        .apple-list-item {
            padding: 12px 16px;
        }

        .apple-list-title {
            font-size: 15px;
        }

        .apple-list-subtitle {
            font-size: 13px;
        }
    }

    /* Apple Photo Styles - Version 1.0.3 - Color Fix */
    .apple-photo-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        /* backdrop-filter removed for performance */
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .apple-photo-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .apple-photo-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 600;
        color: #1D1D1F;
    }

    .apple-photo-title span {
        color: #1D1D1F;
        font-weight: 600;
    }

    .apple-photo-title i {
        font-size: 18px;
        color: #007AFF;
    }

    .apple-photo-status {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .apple-photo-status i {
        font-size: 12px;
        color: #C7C7CC;
        transition: all 0.3s ease;
    }

    .apple-photo-status.completed i {
        color: #34C759;
    }

    .apple-photo-content {
        padding: 20px;
    }

    .apple-file-input {
        display: none;
    }

    .apple-camera-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px 24px;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(0, 122, 255, 0.3);
    }

    .apple-camera-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 122, 255, 0.4);
    }

    .apple-camera-btn i {
        font-size: 18px;
    }

    .apple-preview-container {
        margin-top: 16px;
        border-radius: 12px;
        overflow: hidden;
        background: #F2F2F7;
        display: none;
    }

    .apple-preview-container.has-image {
        display: block;
    }

    .apple-preview-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 12px;
    }

    .apple-continue-section {
        margin-top: 32px;
        padding: 20px 0;
    }

    .apple-continue-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 18px 24px;
        background: linear-gradient(135deg, #34C759 0%, #28A745 100%);
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(52, 199, 89, 0.3);
    }

    .apple-continue-btn:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(52, 199, 89, 0.4);
    }

    .apple-continue-btn:disabled {
        background: linear-gradient(135deg, #8E8E93 0%, #6C6C70 100%);
        cursor: not-allowed;
        box-shadow: 0 2px 10px rgba(142, 142, 147, 0.2);
    }

    .apple-continue-btn i {
        font-size: 16px;
    }

    /* Mensaje de Terminar para Fotos */
    .terminar-message {
        background: rgba(0, 122, 255, 0.1);
        border: 1px solid rgba(0, 122, 255, 0.2);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
        animation: fadeIn 0.3s ease;
    }

    .message-content {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .message-content i {
        color: var(--apple-blue);
        font-size: 16px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .message-content span {
        color: var(--apple-blue);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive para fotos */
    @media (max-width: 768px) {
        .apple-photo-header {
            padding: 14px 16px;
        }

        .apple-photo-title {
            font-size: 15px;
        }

        .apple-photo-content {
            padding: 16px;
        }

        .apple-camera-btn {
            padding: 14px 20px;
            font-size: 15px;
        }

        .apple-continue-btn {
            padding: 16px 20px;
            font-size: 16px;
        }
    }

    @media (max-width: 480px) {
        .apple-photo-header {
            padding: 12px 14px;
        }

        .apple-photo-title {
            font-size: 14px;
        }

        .apple-photo-content {
            padding: 14px;
        }

        .apple-camera-btn {
            padding: 12px 16px;
            font-size: 14px;
        }

        .apple-continue-btn {
            padding: 14px 16px;
            font-size: 15px;
        }

        .apple-preview-image {
            height: 150px;
        }
    }

    /* Overlay de Carga Global */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        /* backdrop-filter removed for performance */
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    .loading-content {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(0, 0, 0, 0.05);
        max-width: 400px;
        width: 90%;
    }

    .loading-spinner {
        margin-bottom: 24px;
    }

    .spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(0, 122, 255, 0.2);
        border-top: 4px solid var(--apple-blue);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-text h3 {
        color: #1D1D1F;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .loading-text p {
        color: #6C6C70;
        font-size: 14px;
        margin-bottom: 24px;
        line-height: 1.4;
    }

    .loading-progress {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: rgba(0, 122, 255, 0.1);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--apple-blue), #4DA3FF);
        border-radius: 4px;
        width: 0%;
        transition: width 0.3s ease;
    }

    .progress-text {
        color: var(--apple-blue);
        font-size: 14px;
        font-weight: 600;
    }

    /* Responsive para overlay */
    @media (max-width: 768px) {
        .loading-content {
            padding: 30px 20px;
            margin: 20px;
        }
        
        .loading-text h3 {
            font-size: 18px;
        }
        
        .loading-text p {
            font-size: 13px;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
        }
    }
</style>
    @yield('styles')
</head>
<body>
    <div id="app">
        <nav class="apple-header">
            <div class="apple-header-content">
                <div class="apple-header-left">
                    @if(!in_array(Route::currentRouteName(), ['dashboard.index', 'inicio', 'limpiadora.dashboard', 'mantenimiento.dashboard']))
                        <a href="{{ url()->previous() }}" class="apple-back-button">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    @endif
                </div>
                <div class="apple-header-center">
                    <div class="apple-welcome">
                        <span class="apple-welcome-text">{{ Auth::user()->idioma_preferido === 'ar' ? "\u0645\u0631\u062D\u0628\u0627" : 'Bienvenid@' }}</span>
                        <span class="apple-user-name">{{Auth::user()->name}}</span>
                    </div>
                </div>
                <div class="apple-header-right">
                    @if(Auth::user()->role === 'LIMPIEZA')
                    <a href="{{ route('limpiadora.cambiar-idioma', Auth::user()->idioma_preferido === 'ar' ? 'es' : 'ar') }}" class="apple-user-button" title="{{ Auth::user()->idioma_preferido === 'ar' ? 'Cambiar a Espanol' : 'التبديل إلى العربية' }}" style="font-size:16px;margin-right:6px;">
                        @if(Auth::user()->idioma_preferido === 'ar') &#127466;&#127480; @else &#127480;&#127462; @endif
                    </a>
                    @endif
                    <a href="{{ route('user.profile') }}" class="apple-user-button" title="Mi Perfil">
                        <i class="bi bi-person-fill"></i>
                    </a>
                    <a href="{{ route('logout') }}" class="apple-logout-button" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-power"></i>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hidden logout form -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>

        <main class="py-4 contendor" @if(Auth::check() && Auth::user()->idioma_preferido === 'ar') dir="rtl" @endif>
            @yield('content')
        </main>
        <footer class="apple-tab-bar">
            <div class="apple-tab-bar-content">
                @if(Auth::user()->role === 'MANTENIMIENTO')
                <a href="{{ route('mantenimiento.dashboard') }}" class="apple-tab-item {{ request()->routeIs('mantenimiento.dashboard') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-house-fill"></i>
                    </div>
                    <span class="apple-tab-label">Inicio</span>
                </a>
                <a href="{{ route('mantenimiento.incidencias.index') }}" class="apple-tab-item {{ request()->routeIs('mantenimiento.incidencias.*') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <span class="apple-tab-label">Incidencias</span>
                </a>
                @else
                <a href="{{ Auth::user()->role === 'LIMPIEZA' ? route('limpiadora.dashboard') : route('gestion.index') }}" class="apple-tab-item {{ request()->routeIs('limpiadora.dashboard') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-house-fill"></i>
                    </div>
                    <span class="apple-tab-label">Inicio</span>
                </a>

                <a href="{{route('gestion.mis-turnos')}}" class="apple-tab-item {{ request()->routeIs('gestion.mis-turnos') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <span class="apple-tab-label">Mis Turnos</span>
                </a>

                @if(Auth::user()->role === 'LIMPIEZA')
                <a href="{{ route('limpiadora.planificacion') }}" class="apple-tab-item {{ request()->routeIs('limpiadora.planificacion') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <span class="apple-tab-label">Plan</span>
                </a>
                @endif

                <a href="{{route('gestion.incidencias.index')}}" class="apple-tab-item {{ request()->routeIs('gestion.incidencias.*') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <span class="apple-tab-label">Incidencias</span>
                </a>
                
                <a href="{{route('gestion.reservas.index')}}" class="apple-tab-item {{ request()->routeIs('gestion.reservas.*') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <span class="apple-tab-label">Reservas</span>
                </a>
                
                <a href="{{route('holiday.index')}}" class="apple-tab-item {{ request()->routeIs('holiday.*') ? 'active' : '' }}">
                    <div class="apple-tab-icon">
                        <i class="bi bi-sun"></i>
                    </div>
                    <span class="apple-tab-label">Mis Vacaciones</span>
                </a>
                
                <a href="#" class="apple-tab-item">
                    <div class="apple-tab-icon">
                        <i class="bi bi-question-circle"></i>
                    </div>
                    <span class="apple-tab-label">Ayuda</span>
                </a>
                @endif
                <a href="{{ route('logout') }}" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                   class="apple-tab-item">
                    <div class="apple-tab-icon">
                        <i class="bi bi-power"></i>
                    </div>
                    <span class="apple-tab-label">Salir</span>
                </a>
            </div>
        </footer>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Scripts --}}
    <script>
        var sessionLifetime = {{ config('session.lifetime') * 60000 }};
        var alertShown = false; // Flag para controlar si la alerta se ha mostrado

        function startSessionTimer() {
            window.sessionTimeout = setTimeout(function() {
                if (!alertShown) { // Verifica si la alerta no se ha mostrado aún
                    alertShown = true; // Marca que la alerta se va a mostrar
                    alert('Tu sesión ha expirado. Serás redirigido a la página de login.');
                    window.location.href = '/login'; // Redirecciona después de que el usuario acepte la alerta
                }
            }, sessionLifetime);
        }

        function resetSessionTimer() {
            clearTimeout(window.sessionTimeout);
            alertShown = false; // Restablece la alerta al reiniciar el temporizador
            startSessionTimer();
        }

        // Inicia el temporizador de sesión
        startSessionTimer();

        // Reinicia el temporizador con cualquier interacción del usuario
        document.addEventListener('mousemove', resetSessionTimer);
        document.addEventListener('keypress', resetSessionTimer);
        document.addEventListener('click', resetSessionTimer);
    </script>

    <!-- jQuery (necesario para checklists de limpieza - sin defer porque scripts inline lo usan) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    @include('sweetalert::alert')

    <!-- Fotos rapidas para finalizacion de limpieza -->
    <script src="{{ asset('js/fotos-rapidas.js') }}?v={{ time() }}"></script>

    @stack('scripts')

    @yield('scripts')
</body>
</html>
