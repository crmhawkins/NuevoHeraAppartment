@extends('admin.configuraciones.layout')

@section('config-title', 'Prompt IA')
@section('config-subtitle', 'Configura el prompt del asistente de inteligencia artificial')

@section('config-content')
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-robot"></i>
            Prompt - Asistente de la Inteligencia Artificial
        </h5>
    </div>
    <div class="config-card-body">
        <form action="{{ route('configuracion.prompt-ia.update') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-code"></i>
                    Editar Prompt (Markdown)
                </label>
                <textarea 
                    name="prompt" 
                    id="prompt" 
                    class="prompt-editor"
                    rows="15"
                    placeholder="Escribe aquí el prompt del asistente...">@if (count($prompt) > 0){{ $prompt[0]->prompt }}@endif</textarea>
                <small class="form-text text-muted">Puedes usar formato Markdown para formatear el texto</small>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Actualizar Prompt
            </button>
        </form>

        <hr class="my-4" style="border-color: #E5E5EA;">

        <div class="mb-3">
            <h6 class="fw-semibold d-flex align-items-center gap-2">
                <i class="fas fa-eye" style="color: #007AFF;"></i>
                Vista Previa (formato Markdown)
            </h6>
            <small class="text-muted">Actualiza el prompt para ver la vista previa</small>
        </div>
        <div class="prompt-preview" id="promptPreview">
            {!! \Illuminate\Support\Str::markdown(count($prompt) > 0 ? $prompt[0]->prompt : 'No hay contenido para mostrar. Edita el prompt arriba y guarda para ver la vista previa.') !!}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const promptTextarea = document.getElementById('prompt');
    const promptPreview = document.getElementById('promptPreview');
    
    if (promptTextarea && promptPreview) {
        promptTextarea.addEventListener('blur', function() {
            const content = this.value || 'No hay contenido para mostrar.';
            fetch('/markdown-preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ content: content })
            }).then(response => response.text())
            .then(html => {
                promptPreview.innerHTML = html;
            }).catch(() => {
                promptPreview.innerHTML = content.replace(/\n/g, '<br>');
            });
        });
    }
});
</script>
@endsection

