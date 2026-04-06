@extends('layouts.public-booking')

@section('title', $politica->titulo . ' - Apartamentos Algeciras')

@section('styles')
<style>
    .politica-wrapper {
        background: #F5F5F5;
        min-height: calc(100vh - 200px);
        padding: 40px 0;
    }
    
    .politica-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 32px;
    }
    
    .politica-sidebar {
        background: white;
        border-radius: 8px;
        padding: 24px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }
    
    .politica-sidebar h3 {
        font-size: 16px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #E0E0E0;
    }
    
    .politica-sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .politica-sidebar li {
        margin-bottom: 8px;
    }
    
    .politica-sidebar a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        display: block;
        padding: 8px 0;
        transition: color 0.2s;
    }
    
    .politica-sidebar a:hover {
        color: #003580;
    }
    
    .politica-sidebar a.active {
        color: #003580;
        font-weight: 600;
    }
    
    .politica-main {
        background: white;
        border-radius: 8px;
        padding: 40px;
    }
    
    .politica-header {
        border-bottom: 2px solid #E0E0E0;
        padding-bottom: 24px;
        margin-bottom: 32px;
    }
    
    .politica-title {
        font-size: 36px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 8px;
    }
    
    .politica-date {
        color: #666;
        font-size: 14px;
        margin-bottom: 16px;
    }
    
    .politica-print {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #003580;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .politica-print:hover {
        color: #0056CC;
    }
    
    .politica-content {
        line-height: 1.8;
        color: #333;
        font-size: 16px;
    }
    
    .politica-content h2 {
        font-size: 24px;
        font-weight: 700;
        color: #003580;
        margin-top: 32px;
        margin-bottom: 16px;
        padding-top: 24px;
        border-top: 1px solid #E0E0E0;
    }
    
    .politica-content h2:first-of-type {
        border-top: none;
        padding-top: 0;
    }
    
    .politica-content h3 {
        font-size: 20px;
        font-weight: 600;
        color: #003580;
        margin-top: 24px;
        margin-bottom: 12px;
    }
    
    .politica-content p {
        margin-bottom: 16px;
    }
    
    .politica-content ul,
    .politica-content ol {
        margin-bottom: 16px;
        padding-left: 24px;
    }
    
    .politica-content li {
        margin-bottom: 8px;
    }
    
    .politica-content a {
        color: #003580;
        text-decoration: underline;
    }
    
    .politica-content a:hover {
        color: #0056CC;
    }
    
    .politica-content strong {
        font-weight: 600;
        color: #003580;
    }
    
    @media (max-width: 992px) {
        .politica-container {
            grid-template-columns: 1fr;
        }
        
        .politica-sidebar {
            position: static;
        }
    }
    
    @media (max-width: 768px) {
        .politica-wrapper {
            padding: 24px 0;
        }
        
        .politica-main {
            padding: 24px;
        }
        
        .politica-title {
            font-size: 28px;
        }
    }
</style>
@endsection

@section('content')
<div class="politica-wrapper">
    <div class="politica-container">
        <!-- Sidebar de Navegación -->
        <aside class="politica-sidebar">
            <h3>{{ __('legal.legal_information') }}</h3>
            <ul>
                @if(isset($paginasSidebar) && $paginasSidebar->count() > 0)
                    @foreach($paginasSidebar as $pagina)
                        <li>
                            <a href="{{ route('web.pagina-legal.show', $pagina->slug) }}" 
                               class="{{ request()->routeIs('web.pagina-legal.show') && request()->route('slug') === $pagina->slug ? 'active' : '' }}"
                               style="{{ request()->routeIs('web.pagina-legal.show') && request()->route('slug') === $pagina->slug ? 'color: #003580; font-weight: 600;' : '' }}">
                                {{ translate_dynamic($pagina->titulo) }}
                            </a>
                        </li>
                    @endforeach
                @endif
                <li><a href="{{ route('web.politica-cancelaciones') }}" 
                       class="{{ request()->routeIs('web.politica-cancelaciones') ? 'active' : '' }}"
                       style="{{ request()->routeIs('web.politica-cancelaciones') ? 'color: #003580; font-weight: 600;' : '' }}">
                    {{ __('legal.cancellation_policy') }}
                </a></li>
                <li><a href="{{ route('web.preguntas-frecuentes') }}" 
                       class="{{ request()->routeIs('web.preguntas-frecuentes') ? 'active' : '' }}"
                       style="{{ request()->routeIs('web.preguntas-frecuentes') ? 'color: #003580; font-weight: 600;' : '' }}">
                    {{ __('legal.frequently_asked_questions') }}
                </a></li>
            </ul>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="politica-main">
            <div class="politica-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h1 class="politica-title">{{ translate_dynamic($politica->titulo) }}</h1>
                        @if($politica->fecha_actualizacion)
                            <p class="politica-date">
                                {{ __('legal.updated_on') }} {{ $politica->fecha_actualizacion->format('d') }} {{ __('legal.month_' . strtolower($politica->fecha_actualizacion->format('F'))) }} {{ $politica->fecha_actualizacion->format('Y') }}
                            </p>
                        @endif
                    </div>
                    <a href="javascript:window.print()" class="politica-print">
                        <i class="fas fa-print"></i>
                        {{ __('legal.print') }}
                    </a>
                </div>
            </div>
            
            <div class="politica-content">
                {!! translate_dynamic($politica->contenido) !!}
            </div>
        </main>
    </div>
</div>
@endsection
