@extends('layouts.appPersonal')

<meta name="csrf-token" content="{{ csrf_token() }}">

@section('title')
    {{ __('Fotos de limpieza') }}
@endsection

@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="bi bi-chevron-left"></i></button>
@endsection

@section('content')

<div class="photos-container">
    <div class="photos-header">
        <h4><i class="bi bi-camera"></i> Fotos de limpieza</h4>
        <p class="photos-subtitle">{{ $limpieza->apartamento->nombre ?? 'Apartamento' }}</p>
    </div>

    <div class="photos-grid">
        @php
            $slots = [
                ['key' => 'cocina', 'icon' => "\u{1F373}", 'label' => 'Cocina'],
                ['key' => 'mesa_comedor', 'icon' => "\u{1FA91}", 'label' => 'Mesa Comedor'],
                ['key' => 'sofa', 'icon' => "\u{1F6CB}", 'label' => 'Sofa'],
                ['key' => 'cama', 'icon' => "\u{1F6CF}", 'label' => 'Cama'],
                ['key' => 'banio', 'icon' => "\u{1F6BF}", 'label' => 'Bano'],
            ];

            // Map categorias by a normalized key so we can match slots to existing DB records
            $catMap = [];
            $imgMap = [];
            foreach ($categorias as $categoria) {
                $catMap[$categoria->id] = $categoria;
            }
            // imagenes is keyed by item_id (categoria id)
        @endphp

        @foreach ($slots as $index => $slot)
            @php
                // Try to find a matching categoria for this slot
                $matchedCat = null;
                $existingPhoto = null;
                foreach ($categorias as $categoria) {
                    $normalizedName = strtolower(trim($categoria->nombre));
                    $slotKey = strtolower($slot['key']);
                    $slotLabel = strtolower($slot['label']);
                    if (
                        str_contains($normalizedName, $slotKey) ||
                        str_contains($normalizedName, $slotLabel) ||
                        str_contains($normalizedName, str_replace('_', ' ', $slotKey))
                    ) {
                        $matchedCat = $categoria;
                        break;
                    }
                }
                // If no match found, use the categoria at this index position if available
                if (!$matchedCat && isset($categorias[$index])) {
                    $matchedCat = $categorias[$index];
                }
                $catId = $matchedCat ? $matchedCat->id : ($index + 1);
                $existingPhoto = isset($imagenes[$catId]) ? $imagenes[$catId] : null;
            @endphp

            <div class="photo-card" id="card-{{ $slot['key'] }}">
                <div class="photo-card-header">
                    <span class="photo-card-icon">{{ $slot['icon'] }}</span>
                    <span class="photo-card-label">{{ $slot['label'] }}</span>
                    <span class="photo-card-status" id="status-{{ $slot['key'] }}">
                        @if($existingPhoto && $existingPhoto->photo_url)
                            <i class="bi bi-check-circle-fill text-success"></i>
                        @else
                            <i class="bi bi-circle text-muted"></i>
                        @endif
                    </span>
                </div>

                <div class="photo-card-body" id="body-{{ $slot['key'] }}" onclick="triggerInput('{{ $slot['key'] }}')">
                    @if($existingPhoto && $existingPhoto->photo_url)
                        <img id="preview-{{ $slot['key'] }}" class="photo-preview" src="{{ asset($existingPhoto->photo_url) }}" alt="{{ $slot['label'] }}" loading="lazy">
                        <div class="photo-retake-overlay" id="overlay-{{ $slot['key'] }}">
                            <i class="bi bi-arrow-clockwise"></i> Repetir
                        </div>
                    @else
                        <img id="preview-{{ $slot['key'] }}" class="photo-preview" style="display:none;" alt="{{ $slot['label'] }}" loading="lazy">
                        <div class="photo-placeholder" id="placeholder-{{ $slot['key'] }}">
                            <i class="bi bi-camera" style="font-size:2em;"></i>
                            <span>Tomar foto</span>
                        </div>
                    @endif
                </div>

                <input type="file"
                    accept="image/*"
                    capture="environment"
                    class="photo-file-input"
                    id="input-{{ $slot['key'] }}"
                    data-category="{{ $slot['key'] }}"
                    data-cat-id="{{ $catId }}"
                    onchange="handlePhoto(this, '{{ $slot['key'] }}', {{ $catId }})">
            </div>
        @endforeach
    </div>

    <div class="photos-footer">
        <a href="{{ route('gestion.edit', $id) }}" class="btn-back-gestion">
            <i class="bi bi-arrow-left"></i> Volver a gestion
        </a>
    </div>
</div>

@endsection

@section('styles')
<style>
    .photos-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 12px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .photos-header {
        background: linear-gradient(135deg, #2d8659, #34a065);
        color: #fff;
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 16px;
        text-align: center;
    }

    .photos-header h4 {
        margin: 0 0 4px 0;
        font-size: 18px;
        font-weight: 700;
    }

    .photos-subtitle {
        margin: 0;
        font-size: 14px;
        opacity: 0.9;
    }

    .photos-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .photo-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e8e8e8;
    }

    .photo-card-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #f9fafb;
        border-bottom: 1px solid #eee;
        gap: 10px;
    }

    .photo-card-icon {
        font-size: 22px;
        line-height: 1;
    }

    .photo-card-label {
        flex: 1;
        font-size: 16px;
        font-weight: 600;
        color: #1a1a1a;
    }

    .photo-card-status {
        font-size: 18px;
    }

    .photo-card-status .text-success {
        color: #2d8659 !important;
    }

    .photo-card-status .text-muted {
        color: #ccc !important;
    }

    .photo-card-status .text-danger {
        color: #dc3545 !important;
    }

    .photo-card-body {
        position: relative;
        min-height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: #fafafa;
    }

    .photo-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #999;
        padding: 30px;
        width: 100%;
        min-height: 160px;
    }

    .photo-placeholder i {
        color: #bbb;
    }

    .photo-placeholder span {
        font-size: 14px;
        font-weight: 500;
    }

    .photo-preview {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        display: block;
    }

    .photo-retake-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.5);
        color: #fff;
        text-align: center;
        padding: 8px;
        font-size: 13px;
        font-weight: 500;
    }

    .photo-file-input {
        display: none !important;
    }

    .photos-footer {
        margin-top: 20px;
        text-align: center;
        padding-bottom: 30px;
    }

    .btn-back-gestion {
        display: inline-block;
        padding: 12px 28px;
        background: #2d8659;
        color: #fff;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: background 0.2s;
    }

    .btn-back-gestion:hover {
        background: #246e49;
        color: #fff;
        text-decoration: none;
    }

    /* Spinner animation */
    @keyframes bi-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .spinning {
        animation: bi-spin 1s infinite linear;
        display: inline-block;
    }
</style>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const limpiezaId = {{ $id }};
    const checklistId = {{ $cat }};

    function triggerInput(key) {
        document.getElementById('input-' + key).click();
    }

    function compressImage(file, maxWidth, quality) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    if (width > maxWidth) {
                        height = Math.round(height * maxWidth / width);
                        width = maxWidth;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        resolve(blob);
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function handlePhoto(input, key, catId) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];

        // Show local preview immediately
        const preview = document.getElementById('preview-' + key);
        const placeholder = document.getElementById('placeholder-' + key);
        const body = document.getElementById('body-' + key);

        const previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.style.display = 'block';

        // Free memory once the preview image has loaded
        preview.onload = function() {
            URL.revokeObjectURL(previewUrl);
        };

        if (placeholder) {
            placeholder.style.display = 'none';
        }

        // Add retake overlay if not present
        let overlay = document.getElementById('overlay-' + key);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'photo-retake-overlay';
            overlay.id = 'overlay-' + key;
            overlay.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Repetir';
            body.appendChild(overlay);
        }

        // Show spinner in status
        document.getElementById('status-' + key).innerHTML = '<i class="bi bi-arrow-repeat spinning" style="color:#f0ad4e;"></i>';

        // Upload in background
        uploadPhoto(file, catId, key);
    }

    async function uploadPhoto(file, catId, key) {
        // Compress image before upload (max 1200px, quality 0.7)
        const compressed = await compressImage(file, 1200, 0.7);

        const formData = new FormData();
        formData.append('image', compressed, 'photo.jpg');
        formData.append('item_id', catId);
        formData.append('checklist_id', checklistId);
        formData.append('_token', csrfToken);

        fetch('/fotos-checklist-store/' + limpiezaId + '/' + checklistId, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                document.getElementById('status-' + key).innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            } else {
                document.getElementById('status-' + key).innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
                console.error('Upload error:', data.message);
            }
        })
        .catch(function(err) {
            document.getElementById('status-' + key).innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
            console.error('Upload failed:', err);
        });
    }
</script>
@endsection
