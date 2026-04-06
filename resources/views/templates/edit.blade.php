@extends('layouts.appAdmin')

@section('content')
<style>
    .whatsapp-preview {
        background: #e5ddd5;
        border-radius: 10px;
        padding: 20px;
        width: 100%;
        font-family: 'Segoe UI', sans-serif;
    }

    .whatsapp-message {
        background-color: #ffffff;
        padding: 10px 15px;
        border-radius: 10px;
        position: relative;
        line-height: 1.5;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .whatsapp-message .header {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .whatsapp-message .button {
        margin-top: 10px;
    }

    .whatsapp-message .button a {
        display: inline-block;
        background-color: #25d366;
        color: white;
        padding: 8px 12px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        font-size: 14px;
    }

    .whatsapp-meta {
        font-size: 10px;
        color: #888;
        text-align: right;
        margin-top: 5px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0 encabezado_top">
            <i class="fa-solid fa-pen-to-square me-2"></i>
            Editar plantilla: {{ $template->name }} ({{ $template->language }})
        </h2>
        <a href="{{ route('templates.index') }}" class="btn bg-color-segundo">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>

    <hr>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-9">
            <form method="POST" action="{{ route('templates.update', $template) }}" id="edit-template-form">
                @csrf
                @method('PUT')

                <input type="hidden" name="template_id" value="{{ $template->template_id }}">

                @php
                    $header = collect($template->components)->firstWhere('type', 'HEADER');
                    $body = collect($template->components)->firstWhere('type', 'BODY');
                    $buttons = collect($template->components)->firstWhere('type', 'BUTTONS')['buttons'] ?? [];
                @endphp

                <div class="mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <input type="text" class="form-control" name="status" value="{{ $template->status }}" required>
                </div>

                <div class="mb-3">
                    <label for="header_text" class="form-label">Encabezado (HEADER)</label>
                    <input type="text" class="form-control" name="header_text" id="header_text" value="{{ $header['text'] ?? '' }}">
                </div>

                <div class="mb-3">
                    <label for="body_text" class="form-label">Mensaje (BODY)</label>
                    <textarea name="body_text" rows="6" class="form-control" id="body_text" required>{{ $body['text'] ?? '' }}</textarea>
                </div>

                @foreach ($buttons as $i => $btn)
                    <div class="mb-3">
                        <label class="form-label">Botón {{ $i + 1 }}</label>
                        <div class="input-group">
                            <input type="text" name="buttons[{{ $i }}][text]" class="form-control button-text" placeholder="Texto" value="{{ $btn['text'] }}">
                            <input type="url" name="buttons[{{ $i }}][url]" class="form-control" placeholder="URL" value="{{ $btn['url'] }}">
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn bg-color-primero me-2">
                        <i class="fa-solid fa-paper-plane me-1"></i>
                        Guardar y reenviar a WhatsApp
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-3">
            <label class="form-label"><strong>Vista previa</strong></label>
            <div class="whatsapp-preview" id="preview-box">
                <div class="whatsapp-message">
                    <div class="header" id="preview-header">{{ $header['text'] ?? '' }}</div>
                    <div class="body" id="preview-body">{{ $body['text'] ?? '' }}</div>

                    @if (!empty($buttons))
                        <div class="button" id="preview-buttons">
                            @foreach ($buttons as $btn)
                                <a href="#" onclick="event.preventDefault();">{{ $btn['text'] }}</a>
                            @endforeach
                        </div>
                    @endif

                    <div class="whatsapp-meta">10:04</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const headerInput = document.getElementById('header_text');
        const bodyInput = document.getElementById('body_text');
        const previewHeader = document.getElementById('preview-header');
        const previewBody = document.getElementById('preview-body');
        const buttonInputs = document.querySelectorAll('.button-text');
        const previewButtons = document.getElementById('preview-buttons');

        function updatePreview() {
            previewHeader.textContent = headerInput.value;
            previewBody.textContent = bodyInput.value;

            if (previewButtons) {
                previewButtons.innerHTML = '';
                buttonInputs.forEach(input => {
                    const text = input.value;
                    if (text.trim() !== '') {
                        const btn = document.createElement('a');
                        btn.href = '#';
                        btn.textContent = text;
                        btn.className = '';
                        previewButtons.appendChild(btn);
                    }
                });
            }
        }

        headerInput.addEventListener('input', updatePreview);
        bodyInput.addEventListener('input', updatePreview);
        buttonInputs.forEach(input => input.addEventListener('input', updatePreview));
    });
</script>
@endsection
