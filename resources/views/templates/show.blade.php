@extends('layouts.appAdmin')

@section('content')
<style>
    .whatsapp-preview {
        background: #e5ddd5;
        border-radius: 10px;
        padding: 20px;
        width: 360px;
        max-width: 100%;
        font-family: 'Segoe UI', sans-serif;
        position: relative;
    }

    .whatsapp-message {
        background-color: #ffffff;
        padding: 10px 15px;
        border-radius: 10px;
        position: relative;
        margin-top: 10px;
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
            <i class="fa-solid fa-eye me-2"></i>
            Ver plantilla: {{ $template->name }}
        </h2>
        <a href="{{ route('templates.index') }}" class="btn bg-color-segundo">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-8">
            <p><strong>Idioma:</strong> {{ $template->language }}</p>
            <p><strong>Categoría:</strong> {{ $template->category }}</p>
            <p><strong>Estado:</strong> {{ $template->status }}</p>

            <div class="mb-3">
                <label class="form-label"><strong>Vista previa del mensaje:</strong></label>
                <div class="whatsapp-preview">
                    <div class="whatsapp-message">
                        @php
                            $header = collect($template->components)->firstWhere('type', 'HEADER');
                            $body = collect($template->components)->firstWhere('type', 'BODY');
                            $buttons = collect($template->components)->firstWhere('type', 'BUTTONS')['buttons'] ?? [];
                        @endphp

                        @if ($header)
                            <div class="header">{{ $header['text'] }}</div>
                        @endif

                        <div class="body">{{ $body['text'] ?? '...' }}</div>

                        @if (!empty($buttons))
                            <div class="button">
                                @foreach ($buttons as $btn)
                                    <a href="#" onclick="event.preventDefault();">{{ $btn['text'] }}</a>
                                @endforeach
                            </div>
                        @endif

                        <div class="whatsapp-meta">10:04</div>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <a href="{{ route('templates.edit', $template) }}" class="btn bg-color-quinto me-2">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                </a>
                <a href="{{ route('templates.checkStatus', $template) }}" class="btn bg-color-tercero me-2">
                    <i class="fa-solid fa-sync me-1"></i> Actualizar estado
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
