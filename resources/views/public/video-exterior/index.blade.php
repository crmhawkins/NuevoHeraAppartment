@extends('layouts.public-booking')

@section('title', 'Instrucciones de Acceso - Apartamentos Algeciras')

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">{{ __('breadcrumb.home') }}</a>
            <span class="booking-breadcrumb-separator">{{ __('breadcrumb.separator') }}</span>
            <strong>Instrucciones de Acceso</strong>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .video-instructions-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 16px;
    }
    
    .video-instructions-title {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 48px;
    }
    
    .video-instructions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
        margin-bottom: 40px;
    }
    
    @media (max-width: 768px) {
        .video-instructions-grid {
            grid-template-columns: 1fr;
            gap: 24px;
        }
    }
    
    .video-instruction-card {
        position: relative;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    
    .video-instruction-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }
    
    .video-instruction-image {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .video-instruction-button {
        position: absolute;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.85);
        color: white;
        padding: 12px 24px;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        border: 2px solid white;
        text-decoration: none;
        transition: all 0.3s ease;
        z-index: 10;
    }
    
    .video-instruction-button:hover {
        background: rgba(0, 53, 128, 0.95);
        color: white;
        text-decoration: none;
        transform: translateX(-50%) scale(1.05);
    }
    
    .video-instruction-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    
    /* Modal de video */
    #videoModal .modal-content {
        background: #000;
        border: none;
    }
    
    #videoModal .modal-header {
        background: #000;
        border-bottom: 1px solid #333;
        color: white;
    }
    
    #videoModal .btn-close {
        filter: invert(1);
    }
    
    #videoModal video {
        background: #000;
    }
</style>
@endsection

@section('content')
<div class="video-instructions-container">
    <h1 class="video-instructions-title">INSTRUCCIONES</h1>
    
    <div class="video-instructions-grid">
        <!-- Puerta Exterior -->
        <div class="video-instruction-card" id="card-exterior">
            <a href="#" class="video-instruction-link" 
               data-video-type="exterior" 
               data-video-code="{{ $videoCode }}">
                <img src="{{ asset('images/imagen-exterior.png') }}" 
                     alt="Instrucciones Puerta Exterior" 
                     class="video-instruction-image"
                     id="img-exterior"
                     onerror="this.style.display='none'; document.querySelector('#card-exterior .video-instruction-button').style.display='none';">
                <button class="video-instruction-button">VER VIDEO PUERTA EXTERIOR</button>
            </a>
        </div>
        
        <!-- Puerta Interior -->
        <div class="video-instruction-card" id="card-interior">
            <a href="#" class="video-instruction-link" 
               data-video-type="interior" 
               data-video-code="{{ $videoCode }}">
                <img src="{{ asset('images/imagen-interior.png') }}" 
                     alt="Instrucciones Puerta Interior" 
                     class="video-instruction-image"
                     id="img-interior"
                     onerror="this.style.display='none'; document.querySelector('#card-interior .video-instruction-button').style.display='none';">
                <button class="video-instruction-button">VER VIDEO PUERTA INTERIOR</button>
            </a>
        </div>
    </div>
</div>

<!-- Modal para reproducir video -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">Video de Instrucciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <video id="instructionVideo" controls style="width: 100%; height: 100%;">
                        <source id="videoSource" src="" type="video/mp4">
                        Tu navegador no soporta la reproducción de videos.
                    </video>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Mapeo de códigos de video a nombres de archivo
    const videoFiles = {
        'ESP': {
            'exterior': '{{ asset("videos/ESP FUERA.mp4") }}',
            'interior': '{{ asset("videos/ESP DENTRO.mp4") }}'
        },
        'ENG': {
            'exterior': '{{ asset("videos/ENG FUERA.mp4") }}',
            'interior': '{{ asset("videos/ENG DENTRO.mp4") }}'
        },
        'MOR': {
            'exterior': '{{ asset("videos/MOR FUERA.mp4") }}',
            'interior': '{{ asset("videos/MOR DENTRO.mp4") }}'
        }
    };
    
    // Función para obtener el video según el código y tipo
    function getVideoUrl(videoCode, videoType) {
        // Si existe el video para ese código, usarlo
        if (videoFiles[videoCode] && videoFiles[videoCode][videoType]) {
            return videoFiles[videoCode][videoType];
        }
        // Si no, usar inglés por defecto
        return videoFiles['ENG'][videoType];
    }
    
    // Agregar event listeners a todos los enlaces de video
    document.querySelectorAll('.video-instruction-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const videoCode = this.getAttribute('data-video-code');
            const videoType = this.getAttribute('data-video-type');
            
            // Obtener la URL del video
            const videoUrl = getVideoUrl(videoCode, videoType);
            
            // Configurar el video en el modal
            const videoSource = document.getElementById('videoSource');
            const video = document.getElementById('instructionVideo');
            
            videoSource.src = videoUrl;
            video.load();
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('videoModal'));
            modal.show();
            
            // Reproducir automáticamente cuando el modal se muestre
            document.getElementById('videoModal').addEventListener('shown.bs.modal', function() {
                video.play();
            }, { once: true });
            
            // Pausar y resetear cuando se cierre el modal
            document.getElementById('videoModal').addEventListener('hidden.bs.modal', function() {
                video.pause();
                video.currentTime = 0;
            });
        });
    });
</script>
@endsection

