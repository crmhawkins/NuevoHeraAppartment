@extends('admin.configuraciones.layout')

@section('config-title', 'SEO y SEM')
@section('config-subtitle', 'Configura los meta tags, Open Graph, Twitter Cards y datos estructurados para mejorar el posicionamiento en buscadores')

@section('config-content')
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-search"></i>
            Configuración de Meta Tags por Página
        </h5>
    </div>
    <div class="config-card-body">
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error de validación:</strong> Por favor, corrige los siguientes errores:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Tabs para cada página -->
        <ul class="nav nav-tabs mb-4" id="seoTabs" role="tablist">
            @foreach($routes as $routeName => $routeLabel)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                            id="tab-{{ str_replace('.', '-', $routeName) }}" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-{{ str_replace('.', '-', $routeName) }}" 
                            type="button" 
                            role="tab">
                        {{ $routeLabel }}
                    </button>
                </li>
            @endforeach
        </ul>

        <!-- Contenido de cada tab -->
        <div class="tab-content" id="seoTabsContent">
            @foreach($routes as $routeName => $routeLabel)
                @php
                    $seoMeta = $seoMetas->get($routeName) ?? new \App\Models\SeoMeta();
                @endphp
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                     id="content-{{ str_replace('.', '-', $routeName) }}" 
                     role="tabpanel">
                    <form action="{{ route('configuracion.seo.update') }}" method="POST" class="seo-form">
                        @csrf
                        <input type="hidden" name="route_name" value="{{ $routeName }}">
                        
                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-12 mb-4">
                                <h6 class="mb-3" style="color: #007AFF; font-weight: 600;">
                                    <i class="fas fa-info-circle me-2"></i>Información Básica
                                </h6>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Título de la Página (Title Tag)
                                </label>
                                <input type="text" 
                                       class="form-control @error('page_title') is-invalid @enderror" 
                                       name="page_title" 
                                       value="{{ old('page_title', $seoMeta->page_title) }}"
                                       placeholder="Ej: Apartamentos en Algeciras - Reserva Online">
                                @error('page_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Máximo 60 caracteres recomendado. Aparece en la pestaña del navegador y en los resultados de búsqueda.</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Meta Descripción
                                </label>
                                <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                          name="meta_description" 
                                          rows="3"
                                          placeholder="Ej: Reserva apartamentos turísticos en Algeciras. Mejores precios, ubicación céntrica y todas las comodidades.">{{ old('meta_description', $seoMeta->meta_description) }}</textarea>
                                @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Máximo 160 caracteres recomendado. Aparece debajo del título en los resultados de búsqueda.</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tags"></i>
                                    Palabras Clave (Keywords)
                                </label>
                                <input type="text" 
                                       class="form-control @error('meta_keywords') is-invalid @enderror" 
                                       name="meta_keywords" 
                                       value="{{ old('meta_keywords', $seoMeta->meta_keywords) }}"
                                       placeholder="Ej: apartamentos algeciras, alojamiento turístico, reserva online">
                                @error('meta_keywords')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Separa las palabras clave con comas. Ej: apartamentos, algeciras, turismo</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-link"></i>
                                    URL Canónica
                                </label>
                                <input type="url" 
                                       class="form-control @error('canonical_url') is-invalid @enderror" 
                                       name="canonical_url" 
                                       value="{{ old('canonical_url', $seoMeta->canonical_url) }}"
                                       placeholder="https://apartamentosalgeciras.com/web/...">
                                @error('canonical_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">URL canónica de esta página (opcional, se genera automáticamente si está vacío).</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-robot"></i>
                                    Meta Robots
                                </label>
                                <select class="form-select @error('robots') is-invalid @enderror" name="robots">
                                    <option value="">Por defecto (index, follow)</option>
                                    <option value="index, follow" {{ old('robots', $seoMeta->robots) == 'index, follow' ? 'selected' : '' }}>Index, Follow</option>
                                    <option value="noindex, follow" {{ old('robots', $seoMeta->robots) == 'noindex, follow' ? 'selected' : '' }}>Noindex, Follow</option>
                                    <option value="index, nofollow" {{ old('robots', $seoMeta->robots) == 'index, nofollow' ? 'selected' : '' }}>Index, Nofollow</option>
                                    <option value="noindex, nofollow" {{ old('robots', $seoMeta->robots) == 'noindex, nofollow' ? 'selected' : '' }}>Noindex, Nofollow</option>
                                </select>
                                @error('robots')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Open Graph (Facebook) -->
                            <div class="col-md-12 mb-4 mt-4">
                                <h6 class="mb-3" style="color: #007AFF; font-weight: 600;">
                                    <i class="fab fa-facebook me-2"></i>Open Graph (Facebook, LinkedIn)
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-heading"></i>
                                    OG Title
                                </label>
                                <input type="text" 
                                       class="form-control @error('og_title') is-invalid @enderror" 
                                       name="og_title" 
                                       value="{{ old('og_title', $seoMeta->og_title) }}"
                                       placeholder="Título para compartir en redes sociales">
                                @error('og_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-image"></i>
                                    OG Image URL
                                </label>
                                <input type="url" 
                                       class="form-control @error('og_image') is-invalid @enderror" 
                                       name="og_image" 
                                       value="{{ old('og_image', $seoMeta->og_image) }}"
                                       placeholder="https://apartamentosalgeciras.com/imagen.jpg">
                                @error('og_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Imagen recomendada: 1200x630px</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    OG Description
                                </label>
                                <textarea class="form-control @error('og_description') is-invalid @enderror" 
                                          name="og_description" 
                                          rows="2">{{ old('og_description', $seoMeta->og_description) }}</textarea>
                                @error('og_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    OG Type
                                </label>
                                <select class="form-select @error('og_type') is-invalid @enderror" name="og_type">
                                    <option value="website" {{ old('og_type', $seoMeta->og_type ?: 'website') == 'website' ? 'selected' : '' }}>Website</option>
                                    <option value="article" {{ old('og_type', $seoMeta->og_type) == 'article' ? 'selected' : '' }}>Article</option>
                                    <option value="product" {{ old('og_type', $seoMeta->og_type) == 'product' ? 'selected' : '' }}>Product</option>
                                </select>
                                @error('og_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Twitter Card -->
                            <div class="col-md-12 mb-4 mt-4">
                                <h6 class="mb-3" style="color: #007AFF; font-weight: 600;">
                                    <i class="fab fa-twitter me-2"></i>Twitter Card
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Twitter Card Type
                                </label>
                                <select class="form-select @error('twitter_card') is-invalid @enderror" name="twitter_card">
                                    <option value="summary" {{ old('twitter_card', $seoMeta->twitter_card ?: 'summary_large_image') == 'summary' ? 'selected' : '' }}>Summary</option>
                                    <option value="summary_large_image" {{ old('twitter_card', $seoMeta->twitter_card ?: 'summary_large_image') == 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                                </select>
                                @error('twitter_card')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Twitter Title
                                </label>
                                <input type="text" 
                                       class="form-control @error('twitter_title') is-invalid @enderror" 
                                       name="twitter_title" 
                                       value="{{ old('twitter_title', $seoMeta->twitter_title) }}"
                                       placeholder="Título para Twitter">
                                @error('twitter_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Twitter Description
                                </label>
                                <textarea class="form-control @error('twitter_description') is-invalid @enderror" 
                                          name="twitter_description" 
                                          rows="2">{{ old('twitter_description', $seoMeta->twitter_description) }}</textarea>
                                @error('twitter_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-image"></i>
                                    Twitter Image URL
                                </label>
                                <input type="url" 
                                       class="form-control @error('twitter_image') is-invalid @enderror" 
                                       name="twitter_image" 
                                       value="{{ old('twitter_image', $seoMeta->twitter_image) }}"
                                       placeholder="https://apartamentosalgeciras.com/imagen.jpg">
                                @error('twitter_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Imagen recomendada: 1200x675px</small>
                            </div>
                            
                            <!-- Hreflang (Idiomas) -->
                            <div class="col-md-12 mb-4 mt-4">
                                <h6 class="mb-3" style="color: #007AFF; font-weight: 600;">
                                    <i class="fas fa-language me-2"></i>Hreflang (URLs por Idioma)
                                </h6>
                                <small class="form-text text-muted mb-3 d-block">Especifica las URLs alternativas para cada idioma (opcional).</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Español (es)</label>
                                <input type="url" class="form-control" name="hreflang_es" value="{{ old('hreflang_es', $seoMeta->hreflang_es) }}" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Inglés (en)</label>
                                <input type="url" class="form-control" name="hreflang_en" value="{{ old('hreflang_en', $seoMeta->hreflang_en) }}" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Francés (fr)</label>
                                <input type="url" class="form-control" name="hreflang_fr" value="{{ old('hreflang_fr', $seoMeta->hreflang_fr) }}" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alemán (de)</label>
                                <input type="url" class="form-control" name="hreflang_de" value="{{ old('hreflang_de', $seoMeta->hreflang_de) }}" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Italiano (it)</label>
                                <input type="url" class="form-control" name="hreflang_it" value="{{ old('hreflang_it', $seoMeta->hreflang_it) }}" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Portugués (pt)</label>
                                <input type="url" class="form-control" name="hreflang_pt" value="{{ old('hreflang_pt', $seoMeta->hreflang_pt) }}" placeholder="https://...">
                            </div>
                            
                            <!-- Structured Data (JSON-LD) -->
                            <div class="col-md-12 mb-4 mt-4">
                                <h6 class="mb-3" style="color: #007AFF; font-weight: 600;">
                                    <i class="fas fa-code me-2"></i>Datos Estructurados (JSON-LD)
                                </h6>
                                <small class="form-text text-muted mb-3 d-block">Añade datos estructurados en formato JSON-LD para mejorar el SEO. Ejemplo: LocalBusiness, Product, etc.</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-code"></i>
                                    JSON-LD
                                </label>
                                <textarea class="form-control @error('structured_data') is-invalid @enderror" 
                                          name="structured_data" 
                                          rows="8"
                                          placeholder='{"@context": "https://schema.org", "@type": "LocalBusiness", "name": "...", ...}'>{{ old('structured_data', $seoMeta->structured_data ? json_encode($seoMeta->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                                @error('structured_data')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Formato JSON válido. Se validará al guardar.</small>
                            </div>
                            
                            <!-- Estado -->
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="active" 
                                           id="active-{{ str_replace('.', '-', $routeName) }}" 
                                           value="1"
                                           {{ old('active', $seoMeta->active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active-{{ str_replace('.', '-', $routeName) }}">
                                        <strong>Activo</strong> - Mostrar estos meta tags en la página
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Meta Tags
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar JSON antes de enviar
    document.querySelectorAll('.seo-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const structuredDataField = form.querySelector('textarea[name="structured_data"]');
            if (structuredDataField && structuredDataField.value.trim()) {
                try {
                    JSON.parse(structuredDataField.value);
                } catch (error) {
                    e.preventDefault();
                    alert('Error en el JSON-LD: ' + error.message);
                    structuredDataField.focus();
                    return false;
                }
            }
        });
    });
});
</script>
@endsection

