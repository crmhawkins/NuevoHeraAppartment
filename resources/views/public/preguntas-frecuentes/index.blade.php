@extends('layouts.public-booking')

@section('title', __('legal.frequently_asked_questions') . ' - Apartamentos Algeciras')

@section('styles')
<style>
    .faqs-wrapper {
        background: #F5F5F5;
        min-height: calc(100vh - 200px);
        padding: 40px 0;
    }
    
    .faqs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 32px;
    }
    
    .faqs-sidebar {
        background: white;
        border-radius: 8px;
        padding: 24px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }
    
    .faqs-sidebar h3 {
        font-size: 16px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #E0E0E0;
    }
    
    .faqs-sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .faqs-sidebar li {
        margin-bottom: 8px;
    }
    
    .faqs-sidebar a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        display: block;
        padding: 8px 0;
        transition: color 0.2s;
    }
    
    .faqs-sidebar a:hover {
        color: #003580;
    }
    
    .faqs-sidebar a.active {
        color: #003580;
        font-weight: 600;
    }
    
    .faqs-main {
        background: white;
        border-radius: 8px;
        padding: 40px;
    }
    
    .faqs-header {
        border-bottom: 2px solid #E0E0E0;
        padding-bottom: 24px;
        margin-bottom: 32px;
    }
    
    .faqs-title {
        font-size: 36px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 16px;
    }
    
    .faqs-subtitle {
        color: #666;
        font-size: 16px;
        line-height: 1.6;
    }
    
    .faqs-category {
        margin-bottom: 40px;
    }
    
    .faqs-category-title {
        font-size: 24px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #E0E0E0;
    }
    
    .faq-item {
        border-bottom: 1px solid #E0E0E0;
        margin-bottom: 0;
    }
    
    .faq-item:last-child {
        border-bottom: none;
    }
    
    .faq-question {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        cursor: pointer;
        user-select: none;
        transition: color 0.2s;
    }
    
    .faq-question:hover {
        color: #003580;
    }
    
    .faq-question-text {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        flex: 1;
        padding-right: 20px;
    }
    
    .faq-question:hover .faq-question-text {
        color: #003580;
    }
    
    .faq-icon {
        font-size: 20px;
        color: #003580;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }
    
    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }
    
    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
        padding: 0 0 0 0;
    }
    
    .faq-item.active .faq-answer {
        max-height: 1000px;
        padding: 0 0 20px 0;
    }
    
    .faq-answer-content {
        color: #666;
        font-size: 16px;
        line-height: 1.8;
    }
    
    .faq-answer-content p {
        margin-bottom: 12px;
    }
    
    .faq-answer-content p:last-child {
        margin-bottom: 0;
    }
    
    .faq-answer-content ul,
    .faq-answer-content ol {
        margin-bottom: 12px;
        padding-left: 24px;
    }
    
    .faq-answer-content li {
        margin-bottom: 8px;
    }
    
    .faq-answer-content a {
        color: #003580;
        text-decoration: underline;
    }
    
    .faq-answer-content a:hover {
        color: #0056CC;
    }
    
    .faqs-empty {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .faqs-empty-icon {
        font-size: 64px;
        color: #CCC;
        margin-bottom: 16px;
    }
    
    @media (max-width: 992px) {
        .faqs-container {
            grid-template-columns: 1fr;
        }
        
        .faqs-sidebar {
            position: static;
        }
    }
    
    @media (max-width: 768px) {
        .faqs-wrapper {
            padding: 24px 0;
        }
        
        .faqs-main {
            padding: 24px;
        }
        
        .faqs-title {
            font-size: 28px;
        }
        
        .faqs-category-title {
            font-size: 20px;
        }
        
        .faq-question-text {
            font-size: 16px;
        }
    }
</style>
@endsection

@section('content')
<div class="faqs-wrapper">
    <div class="faqs-container">
        <!-- Sidebar de Navegación -->
        <aside class="faqs-sidebar">
            <h3>{{ __('legal.legal_information') }}</h3>
            <ul>
                @if(isset($paginasSidebar) && $paginasSidebar->count() > 0)
                    @foreach($paginasSidebar as $paginaSidebar)
                        <li>
                            <a href="{{ route('web.pagina-legal.show', $paginaSidebar->slug) }}">
                                {{ translate_dynamic($paginaSidebar->titulo) }}
                            </a>
                        </li>
                    @endforeach
                @endif
                <li>
                    <a href="{{ route('web.politica-cancelaciones') }}">
                        {{ __('legal.cancellation_policy') }}
                    </a>
                </li>
                <li>
                    <a href="{{ route('web.preguntas-frecuentes') }}" class="active">
                        {{ __('legal.frequently_asked_questions') }}
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="faqs-main">
            <div class="faqs-header">
                <h1 class="faqs-title">{{ __('legal.frequently_asked_questions') }}</h1>
                <p class="faqs-subtitle">
                    {{ __('legal.faqs_subtitle') }}
                </p>
            </div>
            
            @if($preguntas->count() > 0)
                @if($preguntasPorCategoria->count() > 1)
                    @foreach($preguntasPorCategoria as $categoria => $preguntasCategoria)
                        <div class="faqs-category">
                            @if($categoria)
                                <h2 class="faqs-category-title">{{ translate_dynamic($categoria) }}</h2>
                            @endif
                            
                            @foreach($preguntasCategoria as $pregunta)
                                <div class="faq-item" data-faq-id="{{ $pregunta->id }}">
                                    <div class="faq-question" onclick="toggleFaq({{ $pregunta->id }})">
                                        <span class="faq-question-text">{{ translate_dynamic($pregunta->pregunta) }}</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            {!! translate_dynamic($pregunta->respuesta) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    @foreach($preguntas as $pregunta)
                        <div class="faq-item" data-faq-id="{{ $pregunta->id }}">
                            <div class="faq-question" onclick="toggleFaq({{ $pregunta->id }})">
                                <span class="faq-question-text">{{ translate_dynamic($pregunta->pregunta) }}</span>
                                <i class="fas fa-chevron-down faq-icon"></i>
                            </div>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    {!! translate_dynamic($pregunta->respuesta) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @else
                <div class="faqs-empty">
                    <div class="faqs-empty-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <p>{{ __('faqs.no_questions') }}</p>
                </div>
            @endif
        </main>
    </div>
</div>

<script>
function toggleFaq(id) {
    const faqItem = document.querySelector(`[data-faq-id="${id}"]`);
    const isActive = faqItem.classList.contains('active');
    
    // Cerrar todas las FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Abrir la FAQ clickeada si no estaba activa
    if (!isActive) {
        faqItem.classList.add('active');
    }
}
</script>
@endsection

