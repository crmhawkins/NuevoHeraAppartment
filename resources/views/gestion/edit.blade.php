@extends('layouts.appPersonal')

@section('title')
    {{ __('Realizando el Apartamento - ') . $apartamentoLimpieza->apartamento->nombre}}
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{Auth::user()->name}}</h5>
@endsection

@section('content')
<div class="apple-container">
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">{{ $apartamentoLimpieza->apartamento->nombre }}</h1>
                            <p class="apartment-subtitle">Gestión de Limpieza</p>
                        </div>
                    </div>
                    

                    
                </div>
            </div>
            
        </div>
        <div class="progress-badge mt-3 w-75 mx-auto mb-0" id="checklistProgress">
            <div class="progress-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="progress-text">
                <span class="progress-count"><span id="checklistCount">0</span>/<span id="checklistTotal">0</span></span>
                <span class="progress-label">Items</span>
            </div>
        </div>
        
        <!-- Información de la Siguiente Reserva -->
        @php
            $siguienteReserva = null;
            if (isset($apartamentoLimpieza->apartamento)) {
                $siguienteReserva = \App\Models\Reserva::with(['cliente', 'estado'])
                    ->where('apartamento_id', $apartamentoLimpieza->apartamento->id)
                    ->where('fecha_entrada', '>', now()->toDateString())
                    ->where(function($query) {
                        $query->where('estado_id', '!=', 4)
                              ->orWhereNull('estado_id');
                    })
                    ->orderBy('fecha_entrada', 'asc')
                    ->first();
            }
        @endphp
        
        @if($siguienteReserva)
        <div class="siguiente-reserva-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border: 2px solid #2196F3;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        ">
            <!-- Header de la Reserva -->
            <div class="d-flex align-items-center justify-content-center mb-3">
                <i class="fas fa-calendar-alt text-primary me-2" style="font-size: 1.5em;"></i>
                <div>
                    <strong class="text-primary" style="font-size: 1.1em;">Próxima Reserva</strong>
                    <span class="text-dark ms-2" style="font-size: 1.1em;">{{ \Carbon\Carbon::parse($siguienteReserva->fecha_entrada)->format('d/m/Y') }}</span>
                </div>
            </div>
            
            <!-- Información del Cliente -->
            @if($siguienteReserva->cliente)
            <div class="cliente-info mb-3">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-user text-primary me-2"></i>
                    <strong class="text-primary">{{ $siguienteReserva->cliente->nombre ?? 'N/A' }}</strong>
                    @if($siguienteReserva->cliente->telefono)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siguienteReserva->cliente->telefono) }}" 
                           target="_blank" 
                           class="btn btn-primary btn-sm ms-2">
                            <i class="fab fa-whatsapp"></i>
                            WhatsApp
                        </a>
                    @endif
                </div>
                @if($siguienteReserva->cliente->email)
                <div class="cliente-email mt-1">
                    <i class="fas fa-envelope text-info me-1"></i>
                    <span class="text-dark">{{ $siguienteReserva->cliente->email }}</span>
                </div>
                @endif
            </div>
            @endif
            
            <!-- Estadísticas de la Reserva - Compactas -->
            <div class="reserva-stats-compact mb-3">
                <div class="stats-row">
                    <div class="stat-item">
                        <i class="fas fa-users text-primary"></i>
                        <span class="stat-number">{{ $siguienteReserva->numero_personas }}</span>
                        <span class="stat-label">Adultos</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fas fa-baby text-primary"></i>
                        <span class="stat-number">{{ $siguienteReserva->numero_ninos }}</span>
                        <span class="stat-label">Niños</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fas fa-clock text-primary"></i>
                        <span class="stat-number">
                            @if($siguienteReserva->fecha_entrada == now()->toDateString())
                                <span class="badge bg-primary badge-sm">HOY</span>
                            @else
                                {{ \Carbon\Carbon::parse($siguienteReserva->fecha_entrada)->diffForHumans() }}
                            @endif
                        </span>
                        <span class="stat-label">Entrada</span>
                    </div>
                </div>
            </div>
            
            <!-- Información de Niños -->
            @if($siguienteReserva->numero_ninos > 0 && $siguienteReserva->edades_ninos)
            <div class="edades-ninos mt-3">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-child text-primary me-2"></i>
                    <strong class="text-primary">Edades de los niños:</strong>
                    <span class="text-dark ms-2">
                        @if(is_array($siguienteReserva->edades_ninos) && !empty($siguienteReserva->edades_ninos))
                            @foreach($siguienteReserva->edades_ninos as $index => $edad)
                                {{ $edad }} años
                                @if($index < count($siguienteReserva->edades_ninos) - 1)
                                    , 
                                @endif
                            @endforeach
                        @else
                            <span class="text-muted">Sin edades especificadas</span>
                        @endif
                    </span>
                </div>
            </div>
            @endif
            
            <!-- Notas de Niños -->
            @if($siguienteReserva->notas_ninos)
            <div class="notas-ninos mt-2">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-sticky-note text-primary me-2"></i>
                    <strong class="text-primary">Notas niños:</strong>
                    <span class="text-dark ms-2">{{ $siguienteReserva->notas_ninos }}</span>
                </div>
            </div>
            @endif
            
            <!-- Enlaces de Gestión -->
            <div class="gestion-links mt-3">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ route('gestion.reservas.show', $siguienteReserva->id) }}" 
                           class="btn btn-primary btn-sm w-100" style="border-radius: 8px;">
                            <i class="fas fa-eye me-2"></i>
                            Ver Detalles Completos
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estado de la Reserva -->
            @if($siguienteReserva->estado)
            <div class="estado-reserva mt-2">
                <span class="badge bg-{{ $siguienteReserva->estado->color ?? 'secondary' }}">
                    {{ $siguienteReserva->estado->nombre ?? 'Estado no definido' }}
                </span>
            </div>
            @endif
        </div>
        @endif
        
        
        <!-- Cartel Informativo -->
        <div class="info-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border: 2px solid #2196F3;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        ">
            <div class="d-flex align-items-center justify-content-center">
                <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.2em;"></i>
                <div>
                    <strong class="text-primary">Información Importante:</strong>
                    <span class="text-dark ms-2">Puedes finalizar la limpieza sin completar todos los checklists, pero asegúrate de revisar la calidad general.</span>
                </div>
            </div>
        </div>
        <div class="apple-card-body">
                    <form action="{{ route('gestion.update', $apartamentoLimpieza) }}" method="POST" id="formPrincipalLimpieza">
                        @csrf
                        <input type="hidden" name="id" value="{{ $apartamentoLimpieza->id }}">

                        @foreach ($checklists as $checklist)
                        @php
                        // Normaliza el nombre para usar como identificador
                        $nombreHabitacion = strtolower(str_replace(' ', '_', $checklist->nombre));
                        // Quitar tildes manualmente
                        $nombreHabitacion = strtr($nombreHabitacion, [
                            'á' => 'a', 'é' => 'e', 'í' => 'i',
                            'ó' => 'o', 'ú' => 'u',
                            'Á' => 'a', 'É' => 'e', 'Í' => 'i',
                            'Ó' => 'o', 'Ú' => 'u',
                            'ñ' => 'n', 'Ñ' => 'n',
                        ]);

                        // Lista de nombres que deben excluir cámara (sin acentos y en minúscula)
                        $excluirCamara = in_array($nombreHabitacion, ['canape', 'armario', 'perchero', 'amenities', 'ascensor', 'escalera']);
                    @endphp

                            <div class="checklist-section">
                                <div class="section-header">
                                    <div class="section-title-container">
                                        @php
                                            $iconClass = 'fa-solid fa-home';
                                            switch(strtolower($checklist->nombre)) {
                                                case 'salon':
                                                case 'sala':
                                                    $iconClass = 'fa-solid fa-couch';
                                                    break;
                                                case 'dormitorio':
                                                case 'habitacion':
                                                    $iconClass = 'fa-solid fa-bed';
                                                    break;
                                                case 'cocina':
                                                    $iconClass = 'fa-solid fa-utensils';
                                                    break;
                                                case 'baño':
                                                case 'bano':
                                                case 'aseo':
                                                    $iconClass = 'fa-solid fa-bath';
                                                    break;
                                                case 'comedor':
                                                    $iconClass = 'fa-solid fa-utensils';
                                                    break;
                                                case 'terraza':
                                                case 'balcon':
                                                    $iconClass = 'fa-solid fa-umbrella-beach';
                                                    break;
                                                case 'escalera':
                                                    $iconClass = 'fa-solid fa-stairs';
                                                    break;
                                                case 'ascensor':
                                                    $iconClass = 'fa-solid fa-elevator';
                                                    break;
                                                case 'amenities':
                                                    $iconClass = 'fa-solid fa-gift';
                                                    break;
                                                case 'armario':
                                                    $iconClass = 'fa-solid fa-door-closed';
                                                    break;
                                                case 'canape':
                                                    $iconClass = 'fa-solid fa-couch';
                                                    break;
                                                case 'perchero':
                                                    $iconClass = 'fa-solid fa-hanger';
                                                    break;
                                                default:
                                                    $iconClass = 'fa-solid fa-check-square';
                                            }
                                        @endphp
                                        <i class="{{ $iconClass }} section-icon"></i>
                                        <h3 class="section-title">{{ strtoupper($checklist->nombre) }}</h3>
                                    </div>
                                    <div class="section-controls">
                                        <div class="apple-switch-container">
                                            @php
                                            $isChecklistChecked = isset($checklistsExistentes[$checklist->id]) && $checklistsExistentes[$checklist->id] == 1;
                                            @endphp
                                            <input
                                            {{ isset($checklistsExistentes[$checklist->id]) && $checklistsExistentes[$checklist->id] == 1 ? 'checked' : '' }}
                                            class="apple-switch category-switch"
                                            value="1"
                                            name="checklist[{{ $checklist->id }}]"
                                            type="checkbox"
                                            data-habitacion="{{ $checklist->id }}"
                                            data-type="checklist"
                                            data-id="{{ $checklist->id }}"
                                            >
                                            @if (!$excluirCamara)
                                            @php
                                                // Intentar usar ruta específica, si no existe usar ruta genérica
                                                try {
                                                    $fotoRuta = route('fotos.' . $nombreHabitacion, [
                                                        'id' => $apartamentoLimpieza->id,
                                                        'cat' => $checklist->id,
                                                    ]);
                                                } catch (Exception $e) {
                                                    // Si no existe la ruta específica, usar la genérica
                                                    $fotoRuta = route('fotos.checklist', [
                                                        'id' => $apartamentoLimpieza->id,
                                                        'cat' => $checklist->id,
                                                    ]);
                                                }
                                            @endphp
                                                <a id="camara{{ $checklist->id }}" href="{{ $fotoRuta }}" class="camera-button" style="display: none">
                                                    <i class="fa-solid fa-camera"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="section-content">
                                    @foreach ($checklist->items as $item)
                                        <div class="checklist-item">
                                            <input type="hidden" name="items[{{ $item->id }}]" value="0">
                                            @php
                                                $isChecked = isset($itemsExistentes[$item->id]) && $itemsExistentes[$item->id] == 1;
                                            @endphp
                                            <input class="apple-switch item-switch" type="checkbox"
                                                id="item_{{ $item->id }}"
                                                name="items[{{ $item->id }}]"
                                                value="1"
                                                data-type="item"
                                                data-id="{{ $item->id }}"
                                                {{ $isChecked ? 'checked' : '' }}>

                                            <label class="item-label" for="item_{{ $item->id }}">{{ $item->nombre }}</label>
                                            
                                            @if($item->tiene_stock && $item->articulo)
                                                <div class="item-actions">
                                                    <button type="button" 
                                                            class="btn-reponer"
                                                            data-item-id="{{ $item->id }}"
                                                            data-articulo-id="{{ $item->articulo->id }}"
                                                            data-articulo-nombre="{{ $item->articulo->nombre }}"
                                                            data-tipo-descuento="{{ $item->articulo->tipo_descuento }}"
                                                            data-stock-actual="{{ $item->articulo->stock_actual }}"
                                                            data-cantidad-requerida="{{ $item->cantidad_requerida }}"
                                                            data-apartamento-limpieza-id="{{ $apartamentoLimpieza->id }}"
                                                            title="Reponer {{ $item->articulo->nombre }}">
                                                        <i class="fas fa-plus-circle"></i>
                                                        <span>Reponer</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="observations-section">
                            <textarea name="observacion" id="observacion" class="observations-textarea" placeholder="Escriba alguna observación..." rows="6">{{ $apartamentoLimpieza->observacion }}</textarea>
                        </div>
                        
                        <!-- Sección de Amenities - Diseño Apple-Inspired -->
                        <div class="amenities-section">
                            <div class="amenities-header">
                                <div class="amenities-title">
                                    <div class="title-icon">
                                        <i class="fa-solid fa-gift"></i>
                                    </div>
                                    <div class="title-content">
                                        <h3>Gestión de Amenities</h3>
                                        <p>Control de productos y suministros</p>
                                    </div>
                                </div>
                                <div class="amenities-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="amenitiesToggle" class="amenities-switch">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="amenities-content" id="amenitiesContent" style="display: none;">
                                <!-- Debug: Verificar si se cargan los amenities -->
                                @if(isset($amenitiesConRecomendaciones) && count($amenitiesConRecomendaciones) > 0)
                                    <div class="alert alert-info">
                                        <strong>Debug:</strong> Amenities cargados: {{ count($amenitiesConRecomendaciones) }} categorías
                                    </div>
                                    <form action="{{ route('gestion.update', $apartamentoLimpieza->id) }}" method="POST" id="amenityForm">
                                        @csrf
                                        
                                        <!-- Resumen de Amenities -->
                                        <div class="amenities-summary">
                                            <div class="summary-item">
                                                <div class="summary-icon">
                                                    <i class="fas fa-gift"></i>
                                                </div>
                                                <div class="summary-content">
                                                    <h4>{{ count($amenitiesConRecomendaciones) }}</h4>
                                                    <p>Categorías</p>
                                                </div>
                                            </div>
                                            
                                            <div class="summary-item">
                                                @php
                                                    $totalAmenities = collect($amenitiesConRecomendaciones)->flatten(1)->count();
                                                    $amenitiesAnadidos = collect($amenitiesConRecomendaciones)->flatten(1)->filter(function($item) {
                                                        return $item['consumo_existente'] && $item['consumo_existente']->cantidad_consumida > 0;
                                                    })->count();
                                                @endphp
                                                <div class="summary-icon">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <div class="summary-content">
                                                    <h4>{{ $amenitiesAnadidos }}/{{ $totalAmenities }}</h4>
                                                    <p>Amenities Añadidos</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Lista de Amenities por Categoría -->
                                        @foreach($amenitiesConRecomendaciones as $categoria => $amenities)
                                        <div class="amenity-category">
                                            <div class="category-header">
                                                <div class="category-icon">
                                                    @php
                                                        $iconClass = 'fa-tags';
                                                        $iconColor = 'primary';
                                                        switch($categoria) {
                                                            case 'higiene':
                                                                $iconClass = 'fa-soap';
                                                                $iconColor = 'success';
                                                                break;
                                                            case 'alimentacion':
                                                                $iconClass = 'fa-utensils';
                                                                $iconColor = 'warning';
                                                                break;
                                                            case 'limpieza':
                                                                $iconClass = 'fa-broom';
                                                                $iconColor = 'info';
                                                                break;
                                                            default:
                                                                $iconClass = 'fa-gift';
                                                                $iconColor = 'primary';
                                                        }
                                                    @endphp
                                                    <i class="fas {{ $iconClass }} text-{{ $iconColor }}"></i>
                                                </div>
                                                <div class="category-info">
                                                    <h4>{{ ucfirst($categoria) }}</h4>
                                                    <span class="category-count">{{ count($amenities) }} items</span>
                                                </div>
                                            </div>
                                            
                                            <div class="amenities-grid">
                                                @foreach($amenities as $item)
                                                @php
                                                    $amenity = $item['amenity'];
                                                    $cantidadRecomendada = $item['cantidad_recomendada'];
                                                    $consumoExistente = $item['consumo_existente'];
                                                    $stockDisponible = $item['stock_disponible'];
                                                    
                                                    // Si ya se añadió este amenity, mostrar la cantidad puesta
                                                    // Si no, mostrar la cantidad recomendada
                                                    $cantidadActual = $consumoExistente ? $consumoExistente->cantidad_consumida : 0;
                                                    $cantidadMostrar = $cantidadActual > 0 ? $cantidadActual : $cantidadRecomendada;
                                                    
                                                    // Determinar si ya se añadió
                                                    $yaAnadido = $consumoExistente && $consumoExistente->cantidad_consumida > 0;
                                                @endphp
                                                <div class="amenity-card">
                                                    <div class="amenity-header">
                                                        <div class="amenity-name">
                                                            <h5>
                                                                {{ $amenity->nombre }}
                                                                @if(isset($item['es_automatico_ninos']) && $item['es_automatico_ninos'])
                                                                    <span class="badge bg-success ms-2">
                                                                        <i class="fas fa-baby me-1"></i>
                                                                        Niño
                                                                    </span>
                                                                @endif
                                                            </h5>
                                                            <p>{{ $amenity->descripcion }}</p>
                                                            @if(isset($item['es_automatico_ninos']) && $item['es_automatico_ninos'])
                                                                <div class="amenity-motivo-ninos">
                                                                    <small class="text-success">
                                                                        <i class="fas fa-info-circle me-1"></i>
                                                                        {{ $item['motivo_ninos'] }}
                                                                    </small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="amenity-type">
                                                            @php
                                                                $tipoConsumo = '';
                                                                $typeColor = 'info';
                                                                switch($amenity->tipo_consumo) {
                                                                    case 'por_reserva':
                                                                        $tipoConsumo = 'Por Reserva';
                                                                        $typeColor = 'primary';
                                                                        break;
                                                                    case 'por_persona':
                                                                        $tipoConsumo = 'Por Persona';
                                                                        $typeColor = 'success';
                                                                        break;
                                                                    case 'por_tiempo':
                                                                        $tipoConsumo = 'Por Tiempo';
                                                                        $typeColor = 'warning';
                                                                        break;
                                                                    default:
                                                                        $tipoConsumo = 'N/A';
                                                                        $typeColor = 'secondary';
                                                                }
                                                            @endphp
                                                            <span class="type-badge bg-{{ $typeColor }}">{{ $tipoConsumo }}</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="amenity-details">
                                                        <div class="detail-row">
                                                            <div class="detail-item">
                                                                <span class="detail-label">Recomendado</span>
                                                                <span class="detail-value recommended">{{ $cantidadRecomendada }}</span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="detail-label">Stock</span>
                                                                <span class="detail-value stock-{{ $stockDisponible > 0 ? 'available' : 'unavailable' }}">
                                                                    {{ $stockDisponible }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="detail-row">
                                                            <div class="detail-item full-width">
                                                                <span class="detail-label">
                                                                    Cantidad Dejada
                                                                    @if($yaAnadido)
                                                                        <span class="badge bg-success ms-2">
                                                                            <i class="fas fa-check"></i> Ya añadido
                                                                        </span>
                                                                        <small class="text-muted d-block mt-1">
                                                                            <i class="fas fa-info-circle"></i> 
                                                                            Modificando cantidad existente
                                                                        </small>
                                                                    @else
                                                                        <small class="text-muted d-block mt-1">
                                                                            <i class="fas fa-plus-circle"></i> 
                                                                            Añadiendo nuevo amenity
                                                                        </small>
                                                                    @endif
                                                                </span>
                                                                <div class="quantity-input-container">
                                                                    <input type="number" 
                                                                           name="amenities[{{ $amenity->id }}][cantidad_dejada]" 
                                                                           value="{{ $cantidadMostrar }}"
                                                                           min="0" 
                                                                           max="{{ $stockDisponible }}"
                                                                           class="quantity-input {{ $yaAnadido ? 'input-anadido' : '' }}"
                                                                           data-amenity-id="{{ $amenity->id }}"
                                                                           data-stock="{{ $stockDisponible }}"
                                                                           data-ya-anadido="{{ $yaAnadido ? 'true' : 'false' }}"
                                                                           required>
                                                                    <input type="hidden" name="amenities[{{ $amenity->id }}][amenity_id]" value="{{ $amenity->id }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="detail-row">
                                                            <div class="detail-item full-width">
                                                                <span class="detail-label">Observaciones</span>
                                                                <textarea name="amenities[{{ $amenity->id }}][observaciones]" 
                                                                          class="observations-input" 
                                                                          rows="2" 
                                                                          placeholder="Notas adicionales...">{{ $consumoExistente ? $consumoExistente->observaciones : '' }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                        
                                        <!-- Botón de Guardado -->
                                        <div class="amenities-save">
                                            <button type="submit" class="save-button" id="btnGuardarAmenities">
                                                <i class="fas fa-save"></i>
                                                <span>Guardar Amenities</span>
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="amenities-empty">
                                        <div class="empty-icon">
                                            <i class="fas fa-box-open"></i>
                                        </div>
                                        <h4>No hay amenities configurados</h4>
                                        <p>Para este edificio no se han configurado amenities de limpieza.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="actions-section">
                            <div class="d-flex gap-3 mb-3">
                                <button type="button" class="apple-btn apple-btn-primary" onclick="guardarCambios()">Guardar Limpieza</button>
                                <button type="button" class="apple-btn apple-btn-success" onclick="validarYFinalizar()">Terminar</button>
                            </div>
                            
                            <!-- Mensaje informativo sobre los botones -->
                            <div class="info-banner" style="
                                background: linear-gradient(135deg, #E8F5E8 0%, #D4EDDA 100%);
                                border: 2px solid #28A745;
                                border-radius: 15px;
                                padding: 15px;
                                text-align: center;
                                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
                            ">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-info-circle text-success me-2" style="font-size: 1.2em;"></i>
                                    <div>
                                        <strong class="text-success">Información:</strong>
                                        <span class="text-dark ms-2"><strong>"Guardar Limpieza"</strong> funciona siempre para guardar el progreso. <strong>"Terminar"</strong> finaliza la limpieza y te pide 5 fotos rapidas.</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Consent section removed - replaced by quick SweetAlert flow -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón para descuento de artículo roto -->
    @if(isset($articulosActivos) && count($articulosActivos) > 0)
    <div class="text-center my-3">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalDescontarArticulo">
            <i class="fas fa-exclamation-triangle"></i> Descontar Artículo Roto
        </button>
    </div>

    <!-- Modal Descontar Artículo Roto -->
    <div class="modal fade" id="modalDescontarArticulo" tabindex="-1" aria-labelledby="modalDescontarArticuloLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
          <div class="modal-header" style="border-bottom: 1px solid #e9ecef; padding: 1.25rem;">
            <h5 class="modal-title" id="modalDescontarArticuloLabel">
              <i class="fas fa-exclamation-triangle text-warning me-2"></i>Descontar Artículo Roto
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body" style="padding: 1.5rem;">
            <form id="formDescontarArticulo">
              <div class="mb-3 text-start">
                <label class="form-label fw-semibold">Artículo</label>
                <select name="articulo_id" class="form-select" required style="border-radius: 8px;">
                  <option value="">Seleccionar artículo...</option>
                  @foreach($articulosActivos as $articulo)
                    <option value="{{ $articulo->id }}">{{ $articulo->nombre }} (Stock: {{ $articulo->stock_actual }})</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3 text-start">
                <label class="form-label fw-semibold">Motivo</label>
                <select name="motivo" class="form-select" required style="border-radius: 8px;">
                  <option value="roto">Roto</option>
                  <option value="danado">Dañado</option>
                  <option value="perdido">Perdido</option>
                  <option value="desgastado">Desgastado</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              <div class="mb-3 text-start">
                <label class="form-label fw-semibold">Observaciones (opcional)</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalles (opcional)" style="border-radius: 8px;"></textarea>
              </div>
            </form>
            <div class="alert alert-info mt-3 mb-0" style="border-radius: 8px; font-size: 0.875rem;">
              <i class="fas fa-info-circle me-2"></i>
              <small>Se repondrá automáticamente una unidad del mismo artículo si hay stock disponible.</small>
            </div>
          </div>
          <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1rem 1.5rem;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
              <i class="fas fa-times me-2"></i>Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="btnRegistrarDescuento" style="border-radius: 8px;">
              <i class="fas fa-check me-2"></i>Registrar Descuento
            </button>
          </div>
        </div>
      </div>
    </div>
    @endif
</div>

<form id="formFinalizar" action="{{ route('gestion.finalizar', $apartamentoLimpieza->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="consentimiento_finalizacion" id="consentimientoFinalizarHidden" value="true">
                        <input type="hidden" name="motivo_consentimiento" id="motivoConsentimientoHidden" value="Finalizado con flujo rapido">
                        <input type="hidden" name="fecha_consentimiento" id="fechaConsentimientoHidden" value="">
                    </form>

<!-- Photo Capture Overlay - Fullscreen sequential capture -->
<div id="photoCaptureOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#000; z-index:9999; color:#fff;">
    <div style="text-align:center; padding-top:20px; height:100%; display:flex; flex-direction:column; align-items:center;">
        <h2 id="photoAreaName" style="font-size:24px; margin-bottom:10px; font-weight:600;"></h2>
        <p id="photoCounter" style="font-size:14px; color:#aaa; margin:0;">Foto 1 de 5</p>

        <!-- Progress dots -->
        <div id="progressDots" style="margin:15px 0;">
            <span class="photo-dot" style="font-size:18px; margin:0 4px; color:#444;">&#9679;</span>
            <span class="photo-dot" style="font-size:18px; margin:0 4px; color:#444;">&#9679;</span>
            <span class="photo-dot" style="font-size:18px; margin:0 4px; color:#444;">&#9679;</span>
            <span class="photo-dot" style="font-size:18px; margin:0 4px; color:#444;">&#9679;</span>
            <span class="photo-dot" style="font-size:18px; margin:0 4px; color:#444;">&#9679;</span>
        </div>

        <!-- Preview area -->
        <div id="photoPreview" style="flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:200px; max-height:60vh;">
            <img id="previewImage" style="max-width:90%; max-height:55vh; border-radius:12px; display:none;">
            <div id="cameraPrompt" style="font-size:100px;"></div>
        </div>

        <!-- Camera input (hidden) -->
        <input type="file" id="photoCaptureInput" accept="image/*" capture="environment" style="display:none;">

        <!-- Big capture button -->
        <button id="captureBtn" onclick="document.getElementById('photoCaptureInput').click()"
                style="width:80px; height:80px; border-radius:50%; background:#0891b2; border:4px solid #fff; color:#fff; font-size:30px; margin-top:10px; cursor:pointer;">
            &#128248;
        </button>

        <!-- Skip button (small) -->
        <div style="margin-top:15px; margin-bottom:30px;">
            <button onclick="skipPhoto()" style="background:none; border:none; color:#666; font-size:12px; text-decoration:underline; cursor:pointer;">
                Omitir esta foto
            </button>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
        <div class="loading-text">
            <h3>Actualizando...</h3>
            <p>Por favor, espera mientras se procesa tu solicitud</p>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0%</span>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    // FUNCIÓN GLOBAL DE VERIFICACIÓN - SOLUCIÓN DEFINITIVA
    function verificarFormularioAmenities() {
        // Buscar el formulario directamente
        const formulario = document.getElementById('amenityForm');
        console.log('🔍 Buscando formulario amenityForm:', formulario);
        
        if (!formulario) {
            console.log('❌ Formulario de amenities no encontrado');
            return null;
        }
        
        // Verificar que el formulario esté visible
        const amenitiesContent = document.getElementById('amenitiesContent');
        console.log('🔍 Amenities content:', amenitiesContent);
        console.log('🔍 Display style:', amenitiesContent ? amenitiesContent.style.display : 'no content');
        
        if (amenitiesContent && amenitiesContent.style.display === 'none') {
            console.log('❌ Formulario de amenities está oculto');
            return null;
        }
        
        // Verificar que el formulario tenga todos los elementos necesarios
        const inputs = formulario.querySelectorAll('input[name^="amenities["]');
        console.log('🔍 Inputs encontrados:', inputs.length);
        
        if (inputs.length === 0) {
            console.log('❌ Formulario encontrado pero sin inputs de amenities');
            return null;
        }
        
        console.log('✅ Formulario de amenities verificado y disponible con', inputs.length, 'inputs');
        return formulario;
    }
    
    // FUNCIÓN SEGURA PARA ENVIAR FORMULARIO
    function enviarFormularioAmenitiesSeguro() {
        const formulario = verificarFormularioAmenities();
        if (formulario) {
            formulario.submit();
        } else {
            console.log('No se puede enviar el formulario: no está disponible');
            // Mostrar mensaje al usuario
            mostrarModalError('El formulario de amenities no está disponible en este momento. Por favor, recarga la página.');
        }
    }
    
    // Consent system removed - replaced by quick flow
    function inicializarConsentimiento() {
        // No-op: consent system has been replaced by quick SweetAlert + photo flow
    }

    // FUNCIÓN PARA VERIFICAR SI TODOS LOS CHECKLISTS ESTÁN COMPLETOS
    function verificarTodosChecklistsCompletos() {
        const checklists = document.querySelectorAll('.category-switch');
        let todosCompletos = true;
        checklists.forEach(function(checklist) {
            if (!checklist.checked) {
                todosCompletos = false;
            }
        });
        return todosCompletos;
    }

    // validarBotonTerminar is no longer needed - button always enabled
    function validarBotonTerminar() {}
    
    // ============================================================
    // NEW QUICK FINALIZATION FLOW (replaces consent system)
    // ============================================================
    const _photoAreas = [
        { key: 'cocina', name: 'Cocina', emoji: '\uD83C\uDF73' },
        { key: 'salon', name: 'Sal\u00f3n', emoji: '\uD83D\uDECB\uFE0F' },
        { key: 'comedor', name: 'Comedor', emoji: '\uD83E\uDE91' },
        { key: 'dormitorio', name: 'Dormitorio', emoji: '\uD83D\uDECF\uFE0F' },
        { key: 'bano', name: 'Ba\u00f1o', emoji: '\uD83D\uDEBF' },
    ];
    let _currentPhotoIndex = 0;
    let _limpiezaIdGlobal = {{ $apartamentoLimpieza->id }};
    const _csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '{{ csrf_token() }}';

    function validarYFinalizar() {
        // Count checklists
        const checklists = document.querySelectorAll('.category-switch');
        const checklistsFaltantes = [];
        checklists.forEach(function(ch) {
            if (!ch.checked) {
                const secTitle = ch.closest('.checklist-section');
                const nombre = secTitle ? secTitle.querySelector('.section-title').textContent.trim() : 'Checklist';
                checklistsFaltantes.push(nombre);
            }
        });

        if (checklistsFaltantes.length > 0) {
            // Some items unchecked - show SweetAlert warning
            Swal.fire({
                title: '!Cuidado!',
                text: 'Hay ' + checklistsFaltantes.length + ' checks sin revisar',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Continuar sin revisar',
                cancelButtonText: 'Revisar',
            }).then(function(result) {
                if (result.isConfirmed) {
                    mostrarCaptureFotos();
                }
            });
        } else {
            // All items checked - go directly to photos
            mostrarCaptureFotos();
        }
    }

    function mostrarCaptureFotos() {
        _currentPhotoIndex = 0;
        document.getElementById('photoCaptureOverlay').style.display = 'block';
        actualizarPhotoUI();
    }

    var _areaIcons = {
        cocina: '\uD83C\uDF73', salon: '\uD83D\uDECB\uFE0F', comedor: '\uD83C\uDF7D\uFE0F', dormitorio: '\uD83D\uDECF\uFE0F', bano: '\uD83D\uDEBF'
    };

    function actualizarPhotoUI() {
        var area = _photoAreas[_currentPhotoIndex];
        document.getElementById('photoAreaName').textContent = area.name;
        document.getElementById('photoCounter').textContent = 'Foto ' + (_currentPhotoIndex + 1) + ' de 5';
        document.getElementById('previewImage').style.display = 'none';
        document.getElementById('cameraPrompt').style.display = 'block';
        document.getElementById('cameraPrompt').textContent = _areaIcons[area.key] || '\uD83D\uDCF7';
        document.getElementById('captureBtn').style.display = 'inline-block';

        // Update progress dots
        var dots = document.querySelectorAll('#progressDots .photo-dot');
        dots.forEach(function(dot, i) {
            dot.style.color = i <= _currentPhotoIndex ? '#0891b2' : '#444';
        });
    }

    // Listen for photo capture
    document.addEventListener('DOMContentLoaded', function() {
        var inputEl = document.getElementById('photoCaptureInput');
        if (inputEl) {
            inputEl.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;

                // Show preview briefly
                var preview = URL.createObjectURL(file);
                document.getElementById('previewImage').src = preview;
                document.getElementById('previewImage').style.display = 'block';
                document.getElementById('cameraPrompt').style.display = 'none';
                document.getElementById('captureBtn').style.display = 'none';

                // Upload in background (compress first)
                uploadPhotoInBackground(file, _photoAreas[_currentPhotoIndex].key);

                // Auto-advance after 1 second
                setTimeout(function() {
                    URL.revokeObjectURL(preview);
                    _currentPhotoIndex++;
                    if (_currentPhotoIndex >= 5) {
                        finalizarYVolver();
                    } else {
                        actualizarPhotoUI();
                        // Reset file input
                        document.getElementById('photoCaptureInput').value = '';
                    }
                }, 1000);
            });
        }
    });

    function skipPhoto() {
        _currentPhotoIndex++;
        if (_currentPhotoIndex >= 5) {
            finalizarYVolver();
        } else {
            actualizarPhotoUI();
        }
    }

    function uploadPhotoInBackground(file, areaKey) {
        compressImage(file, 1200, 0.7).then(function(compressed) {
            var formData = new FormData();
            formData.append('image', compressed, 'photo.jpg');
            formData.append('area', areaKey);

            fetch('/gestion/limpieza/' + _limpiezaIdGlobal + '/foto-rapida', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': _csrfToken },
                body: formData
            }).catch(function(err) { console.error('Upload error:', err); });
        });
    }

    function finalizarYVolver() {
        // Hide overlay immediately
        document.getElementById('photoCaptureOverlay').style.display = 'none';

        // Set consent hidden fields for backend compatibility
        document.getElementById('consentimientoFinalizarHidden').value = 'true';
        document.getElementById('motivoConsentimientoHidden').value = 'Finalizacion rapida con fotos';
        document.getElementById('fechaConsentimientoHidden').value = new Date().toISOString();

        // Send finalization via the existing form (POST to gestion.finalizar)
        showLoadingOverlay('Finalizando limpieza...');
        document.getElementById('formFinalizar').submit();
    }

    // Image compression utility
    function compressImage(file, maxWidth, quality) {
        return new Promise(function(resolve) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var canvas = document.createElement('canvas');
                    var w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round(h * maxWidth / w); w = maxWidth; }
                    canvas.width = w;
                    canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(function(blob) { resolve(blob); }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
    // ============================================================
    // END QUICK FINALIZATION FLOW
    // ============================================================
    
    function mostrarNotificacion(mensaje, tipo) {
        // Intentar usar toastr si está disponible
        if (typeof toastr !== 'undefined') {
            if (tipo === 'success') {
                toastr.success(mensaje);
                // Actualizar contador después de mostrar toastr
                setTimeout(() => {
                    actualizarContadorChecklists();
                }, 100);
            } else {
                toastr.error(mensaje);
            }
        } else {
            // Crear notificación personalizada
            const notificacion = `
                <div class="notificacion-toast ${tipo === 'success' ? 'notificacion-success' : 'notificacion-error'}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    max-width: 300px;
                ">
                    <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    ${mensaje}
                </div>
            `;
            
            // Remover notificaciones anteriores
            $('.notificacion-toast').remove();
            
            // Añadir nueva notificación
            $('body').append(notificacion);
            
            // Mostrar con animación
            setTimeout(() => {
                $('.notificacion-toast').css('transform', 'translateX(0)');
            }, 100);
            
            // Ocultar después de 3 segundos
            setTimeout(() => {
                $('.notificacion-toast').css('transform', 'translateX(100%)');
                setTimeout(() => {
                    $('.notificacion-toast').remove();
                    // Actualizar contador después de cerrar notificación personalizada
                    if (tipo === 'success') {
                        actualizarContadorChecklists();
                    }
                }, 300);
            }, 3000);
        }
    }

    function enviarFormulario() {
        document.getElementById('formFinalizar').submit();
    }

    // Función para actualizar el contador de items (global)
    function actualizarContadorChecklists() {
        const totalItems = $('.item-switch').length;
        const itemsMarcados = $('.item-switch:checked').length;
        
        $('#checklistCount').text(itemsMarcados);
        $('#checklistTotal').text(totalItems);
        
        // Cambiar color del badge según el progreso
        const badge = $('#checklistProgress');
        if (itemsMarcados === totalItems) {
            badge.removeClass('progress-badge').addClass('progress-badge success');
        } else if (itemsMarcados > 0) {
            badge.removeClass('progress-badge success').addClass('progress-badge warning');
        } else {
            badge.removeClass('progress-badge warning success').addClass('progress-badge');
        }
    }

    // Función para actualizar el estado del botón de finalizar (global)
    function actualizarEstadoBotonFinalizar() {
        const totalItems = $('.item-switch').length;
        const itemsMarcados = $('.item-switch:checked').length;
        const btnTerminar = $('.apple-btn-success');
        const terminarMessage = $('#terminarMessage');
        
        // Button always enabled - quick flow handles incomplete checklists via SweetAlert
        btnTerminar.removeClass('apple-btn-secondary').addClass('apple-btn-success');
        btnTerminar.prop('disabled', false);
        if (itemsMarcados === totalItems && totalItems > 0) {
            terminarMessage.hide();
        } else {
            terminarMessage.show();
        }
    }
    
    // Inicializar el consentimiento cuando se carga la página
    $(document).ready(function() {
        // Inicializar el sistema de consentimiento
        setTimeout(() => {
            inicializarConsentimiento();
            validarBotonTerminar();
        }, 500);
    });

    $(document).ready(function () {
        console.log('Limpieza de Apartamento by Hawkins.')

        // Función para manejar los cambios de checkbox
        function handleCheckboxChange(checkbox) {
            const type = checkbox.data('type');
            const id = checkbox.data('id');
            const isChecked = checkbox.is(':checked');
            const limpiezaId = {{ $apartamentoLimpieza->id }};
            
            // Mostrar overlay de carga
            showLoadingOverlay('Actualizando checklist...');
            
            $.ajax({
                url: '{{ route("gestion.updateCheckbox") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    type: type,
                    id: id,
                    checked: isChecked ? 1 : 0,
                    limpieza_id: limpiezaId,
                    tarea_id: {{ $apartamentoLimpieza->tarea_asignada_id ?? 'null' }},
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar notificación de éxito
                        mostrarNotificacion('Estado actualizado correctamente', 'success');
                        // Actualizar contador inmediatamente
                        actualizarContadorChecklists();
                    } else {
                        // Si hay error, revertir el checkbox
                        checkbox.prop('checked', !isChecked);
                        mostrarNotificacion('Error al actualizar el estado', 'error');
                    }
                    // Ocultar overlay
                    hideLoadingOverlay();
                },
                error: function() {
                    // Si hay error, revertir el checkbox
                    checkbox.prop('checked', !isChecked);
                    mostrarNotificacion('Error al actualizar el estado', 'error');
                    // Ocultar overlay
                    hideLoadingOverlay();
                }
            });
        }

        // Manejar cambios en checkboxes de checklist
        $('.category-switch').on('change', function() {
            const habitacion = $(this).data('habitacion');
            const selectorCamara = '#camara' + habitacion;

            if ($(this).is(':checked')) {
                $(selectorCamara).show();
            } else {
                $(selectorCamara).hide();
            }

            handleCheckboxChange($(this));
        });

        // Manejar cambios en checkboxes de items
        $('.item-switch').on('change', function() {
            handleCheckboxChange($(this));
        });

        // Mostrar cámaras inicialmente si los checklists están marcados
        $('.category-switch').each(function () {
            const habitacion = $(this).data('habitacion');
            const selectorCamara = '#camara' + habitacion;

            if ($(this).is(':checked')) {
                $(selectorCamara).show();
            }
        });
        
        // Actualizar contador inicial
        actualizarContadorChecklists();
        actualizarEstadoBotonFinalizar();
        
        // Actualizar contador cuando cambien los checklists
        $('.category-switch').on('change', function() {
            actualizarContadorChecklists();
            actualizarEstadoBotonFinalizar();
        });
        
        // Actualizar contador cuando cambien los items individuales
        $('.item-switch').on('change', function() {
            // Pequeño delay para asegurar que el estado del checkbox se actualice
            setTimeout(() => {
                actualizarContadorChecklists();
            }, 50);
        });
        
        // Actualizar estado inicial del botón
        actualizarEstadoBotonFinalizar();
    });

    // Funciones para el Overlay de Carga
    function showLoadingOverlay(message = 'Actualizando...') {
        const overlay = document.getElementById('loadingOverlay');
        const messageElement = overlay.querySelector('h3');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        messageElement.textContent = message;
        progressFill.style.width = '0%';
        progressText.textContent = '0%';
        
        overlay.style.display = 'flex';
        
        // Simular progreso
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            progressFill.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
        }, 200);
        
        // Guardar el intervalo para poder limpiarlo
        overlay.dataset.progressInterval = progressInterval;
    }

    function hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        // Completar la barra de progreso
        progressFill.style.width = '100%';
        progressText.textContent = '100%';
        
        // Limpiar el intervalo de progreso
        if (overlay.dataset.progressInterval) {
            clearInterval(overlay.dataset.progressInterval);
        }
        
        // Ocultar después de un pequeño delay para mostrar el 100%
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 500);
    }

    function updateLoadingProgress(percentage) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    if (progressFill && progressText) {
        progressFill.style.width = percentage + '%';
        progressText.textContent = Math.round(percentage) + '%';
    }
}

// Función para guardar cambios con overlay
function guardarCambios() {
    // Mostrar overlay de carga
    showLoadingOverlay('Guardando cambios...');
    
    // Simular un pequeño delay para mostrar el overlay
    setTimeout(() => {
        // SOLO el formulario principal de limpieza (checklists)
        const formularioLimpieza = document.getElementById('formPrincipalLimpieza');
        console.log('🔍 Buscando formulario principal de limpieza:', formularioLimpieza);
        
        if (formularioLimpieza) {
            console.log('✅ Formulario de limpieza encontrado, enviando...');
            formularioLimpieza.submit();
        } else {
            // Si no existe el formulario de limpieza, mostrar error
            hideLoadingOverlay();
            console.log('❌ No se encontró el formulario de limpieza');
            mostrarModalError('No se pudo encontrar el formulario de limpieza. Por favor, recarga la página.');
        }
    }, 500);
}

// Gestión de Amenities
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== INICIALIZANDO AMENITIES ===');
    
    // Verificar que Bootstrap esté disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está disponible. Esperando...');
        return;
    }
    
    const amenitiesToggle = document.getElementById('amenitiesToggle');
    const amenitiesContent = document.getElementById('amenitiesContent');
    
    console.log('Toggle encontrado:', amenitiesToggle);
    console.log('Content encontrado:', amenitiesContent);
    
    if (amenitiesToggle) {
        amenitiesToggle.addEventListener('change', function() {
            console.log('Toggle cambiado:', this.checked);
            if (this.checked) {
                amenitiesContent.style.display = 'block';
                console.log('Amenities content mostrado');
                // Una vez que se muestra el contenido, inicializar los amenities
                setTimeout(() => {
                    console.log('Inicializando amenities después de mostrar...');
                    inicializarAmenities();
                    
                    // Verificar que el formulario esté disponible después de la inicialización
                    const formulario = verificarFormularioAmenities();
                    if (formulario) {
                        console.log('✅ Formulario de amenities inicializado correctamente');
                    } else {
                        console.log('❌ Error: Formulario de amenities no se pudo inicializar');
                        // Intentar de nuevo con más delay
                        setTimeout(() => {
                            const formularioRetry = verificarFormularioAmenities();
                            if (formularioRetry) {
                                console.log('✅ Formulario de amenities encontrado en segundo intento');
                            } else {
                                console.log('❌ Error persistente: Formulario de amenities no encontrado');
                            }
                        }, 1000);
                    }
                }, 1000); // Aumentado el delay para asegurar que el DOM esté completamente listo
            } else {
                amenitiesContent.style.display = 'none';
                console.log('Amenities content oculto');
            }
        });
    } else {
        console.log('Toggle de amenities no encontrado aún, esperando...');
    }
    
    // Función para inicializar amenities solo cuando estén visibles
    function inicializarAmenities() {
        // Obtener referencias a los elementos del formulario de forma segura
        const amenitiesForm = verificarFormularioAmenities();
        if (!amenitiesForm) {
            return; // La función ya maneja el logging
        }
        
        const amenitiesInputs = document.querySelectorAll('input[name^="amenities["]');
        
        if (!amenitiesInputs || amenitiesInputs.length === 0) {
            console.log('Inputs de amenities no encontrados aún, esperando a que se muestren...');
            return;
        }
        
        console.log('Formulario encontrado:', amenitiesForm);
        console.log('Inputs de amenities encontrados:', amenitiesInputs.length);
        
        // Agregar event listeners a los inputs de amenities ya añadidos
        amenitiesInputs.forEach(input => {
            const yaAnadido = input.dataset.yaAnadido === 'true';
            
            if (yaAnadido) {
                input.addEventListener('change', function() {
                    const nuevaCantidad = parseInt(this.value);
                    const cantidadAnterior = parseInt(this.dataset.cantidadAnterior || 0);
                    
                    if (nuevaCantidad !== cantidadAnterior) {
                        // Mostrar modal de confirmación de modificación
                        mostrarModalConfirmacionModificacion(cantidadAnterior, nuevaCantidad, this);
                    }
                });
                
                // Guardar la cantidad inicial para comparar
                input.dataset.cantidadAnterior = input.value;
            }
        });
        
        // Validación antes de enviar el formulario
        amenitiesForm.addEventListener('submit', function(e) {
            const amenitiesConCantidad = Array.from(amenitiesInputs).filter(input => {
                return parseInt(input.value) > 0;
            });
            
            if (amenitiesConCantidad.length === 0) {
                e.preventDefault();
                mostrarModalError('Debes añadir al menos un amenity antes de guardar.');
                return false;
            }
            
            // Mostrar overlay de carga
            showLoadingOverlay('Guardando amenities...');
            
            // Mostrar modal de confirmación de envío
            e.preventDefault();
            mostrarModalConfirmacionEnvio();
        });
    }
    
    // Validación en tiempo real de cantidades
    const cantidadInputs = document.querySelectorAll('.quantity-input');
    
    cantidadInputs.forEach(input => {
        input.addEventListener('input', function() {
            const stock = parseInt(this.dataset.stock);
            const valor = parseInt(this.value);
            
            if (valor > stock) {
                this.value = stock;
                this.classList.add('is-invalid');
            } else if (valor < 0) {
                this.value = 0;
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Manejo del formulario de amenities de forma segura
    const form = verificarFormularioAmenities();
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que todas las cantidades sean válidas
            let isValid = true;
            cantidadInputs.forEach(input => {
                const stock = parseInt(input.dataset.stock);
                const valor = parseInt(input.value);
                
                if (valor < 0 || valor > stock) {
                    isValid = false;
                    input.classList.add('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Por favor, corrige las cantidades inválidas antes de continuar.');
                return;
            }
            
            // Mostrar overlay de carga
            showLoadingOverlay('Guardando amenities...');
            
            // Enviar formulario
            this.submit();
        });
    }
    
    // Overlay para el formulario principal de limpieza
    const formPrincipal = document.getElementById('formPrincipalLimpieza');
    if (formPrincipal) {
        formPrincipal.addEventListener('submit', function() {
            showLoadingOverlay('Guardando limpieza...');
        });
    }
    
    // Overlay para el botón de guardar amenities
    const btnGuardarAmenities = document.getElementById('btnGuardarAmenities');
    if (btnGuardarAmenities) {
        btnGuardarAmenities.addEventListener('click', function() {
            showLoadingOverlay('Guardando amenities...');
        });
    }
});

    // Prevenir duplicados de amenities
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener referencias a los elementos del formulario
        // Obtener referencias a los elementos del formulario de forma segura
        const amenitiesForm = verificarFormularioAmenities();
        if (!amenitiesForm) {
            return; // La función ya maneja el logging
        }
        
        const amenitiesInputs = document.querySelectorAll('input[name^="amenities["]');
        
        if (!amenitiesInputs || amenitiesInputs.length === 0) {
            console.error('Error: No se encontraron inputs de amenities.');
            return;
        }
        
        console.log('Formulario encontrado:', amenitiesForm);
        console.log('Inputs de amenities encontrados:', amenitiesInputs.length);
        
        // Agregar event listeners a los inputs de amenities ya añadidos
        amenitiesInputs.forEach(input => {
            const yaAnadido = input.dataset.yaAnadido === 'true';
            
            if (yaAnadido) {
                input.addEventListener('change', function() {
                    const nuevaCantidad = parseInt(this.value);
                    const cantidadAnterior = parseInt(this.dataset.cantidadAnterior || 0);
                    
                    if (nuevaCantidad !== cantidadAnterior) {
                        // Mostrar modal de confirmación de modificación
                        mostrarModalConfirmacionModificacion(cantidadAnterior, nuevaCantidad, this);
                    }
                });
                
                // Guardar la cantidad inicial para comparar
                input.dataset.cantidadAnterior = input.value;
            }
        });
        
        // Validación antes de enviar el formulario
        amenitiesForm.addEventListener('submit', function(e) {
            const amenitiesConCantidad = Array.from(amenitiesInputs).filter(input => {
                return parseInt(input.value) > 0;
            });
            
            if (amenitiesConCantidad.length === 0) {
                e.preventDefault();
                mostrarModalError('Debes añadir al menos un amenity antes de guardar.');
                return false;
            }
            
            // Mostrar overlay de carga
            showLoadingOverlay('Guardando amenities...');
            
            // Mostrar modal de confirmación de envío
            e.preventDefault();
            mostrarModalConfirmacionEnvio();
        });
        
        // Función para mostrar modal de confirmación de envío
        function mostrarModalConfirmacionEnvio() {
            var modal = new bootstrap.Modal(document.getElementById('confirmacionEnvioModal'));
            modal.show();
        }
        
        // Función para confirmar envío
        function confirmarEnvioAmenities() {
            // Cerrar el modal
            var modal = bootstrap.Modal.getInstance(document.getElementById('confirmacionEnvioModal'));
            modal.hide();
            
            // Mostrar overlay de carga
            showLoadingOverlay('Guardando amenities...');
            
            // Simular un pequeño delay para mostrar el overlay
            setTimeout(() => {
                // Enviar el formulario de forma segura
                enviarFormularioAmenitiesSeguro();
            }, 500);
        }
        
        // Función para mostrar modal de error
        function mostrarModalError(mensaje) {
            document.getElementById('errorMessage').textContent = mensaje;
            var modal = new bootstrap.Modal(document.getElementById('errorModal'));
            modal.show();
        }
        
        // Función para mostrar modal de confirmación de modificación
        function mostrarModalConfirmacionModificacion(cantidadAnterior, nuevaCantidad, inputElement) {
            // Actualizar el mensaje del modal
            document.getElementById('modificacionCantidadMessage').textContent = 
                `¿Estás seguro de que quieres modificar la cantidad de este amenity de ${cantidadAnterior} a ${nuevaCantidad}?`;
            
            // Guardar referencia al input para poder actualizarlo después
            window.inputElementModificacion = inputElement;
            window.cantidadAnteriorModificacion = cantidadAnterior;
            window.nuevaCantidadModificacion = nuevaCantidad;
            
            // Mostrar el modal
            var modal = new bootstrap.Modal(document.getElementById('modificacionCantidadModal'));
            modal.show();
        }
        
        // Función para confirmar modificación
        function confirmarModificacionCantidad() {
            if (window.inputElementModificacion) {
                // Mostrar overlay de carga
                showLoadingOverlay('Actualizando cantidad...');
                
                // Simular un pequeño delay para mostrar el overlay
                setTimeout(() => {
                    // Actualizar el dataset para la próxima comparación
                    window.inputElementModificacion.dataset.cantidadAnterior = window.nuevaCantidadModificacion;
                    
                    // Cerrar el modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modificacionCantidadModal'));
                    modal.hide();
                    
                    // Ocultar el overlay
                    hideLoadingOverlay();
                }, 500);
            }
        }
        
        // Función para cancelar modificación
        function cancelarModificacionCantidad() {
            if (window.inputElementModificacion) {
                // Mostrar overlay de carga
                showLoadingOverlay('Revertiendo cambios...');
                
                // Simular un pequeño delay para mostrar el overlay
                setTimeout(() => {
                    // Revertir al valor anterior
                    window.inputElementModificacion.value = window.cantidadAnteriorModificacion;
                    
                    // Cerrar el modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modificacionCantidadModal'));
                    modal.hide();
                    
                    // Ocultar el overlay
                    hideLoadingOverlay();
                }, 500);
            }
        }
    });

    // Función para mostrar modal de error
    function mostrarModalError(mensaje) {
        // Mostrar overlay de carga
        showLoadingOverlay('Validando...');
        
        // Simular un pequeño delay para mostrar el overlay
        setTimeout(() => {
            // Ocultar el overlay
            hideLoadingOverlay();
            
            // Mostrar el modal de error
            document.getElementById('errorMessage').textContent = mensaje;
            var modal = new bootstrap.Modal(document.getElementById('errorModal'));
            modal.show();
        }, 500);
    }

    // ===== SISTEMA DE REPOSICIÓN DE ARTÍCULOS =====
    
    // Variables globales para reposición
    let reposicionData = {};
    
    // Inicializar sistema de reposición
    function inicializarSistemaReposicion() {
        // Event listeners para botones de reposición
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-reponer')) {
                e.preventDefault();
                const button = e.target.closest('.btn-reponer');
                abrirModalReposicion(button);
            }
        });
        
        // Event listener para confirmar reposición
        document.getElementById('btnConfirmarReposicion').addEventListener('click', confirmarReposicion);
        
        // Event listener para validar cantidad
        document.getElementById('cantidad_reponer').addEventListener('input', validarCantidadReposicion);
    }
    
    // Abrir modal de reposición
    function abrirModalReposicion(button) {
        // Recopilar datos del botón
        reposicionData = {
            itemId: button.dataset.itemId,
            articuloId: button.dataset.articuloId,
            articuloNombre: button.dataset.articuloNombre,
            tipoDescuento: button.dataset.tipoDescuento,
            stockActual: parseFloat(button.dataset.stockActual),
            cantidadRequerida: parseFloat(button.dataset.cantidadRequerida),
            apartamentoLimpiezaId: button.dataset.apartamentoLimpiezaId
        };
        
        // Llenar el modal
        document.getElementById('articulo_nombre_display').textContent = reposicionData.articuloNombre;
        document.getElementById('cantidad_recomendada').textContent = reposicionData.cantidadRequerida;
        document.getElementById('cantidad_reponer').value = reposicionData.cantidadRequerida;
        document.getElementById('reposicion_item_id').value = reposicionData.itemId;
        document.getElementById('reposicion_apartamento_id').value = reposicionData.apartamentoLimpiezaId;
        
        // Configurar tipo de descuento
        const tipoInfo = document.getElementById('tipo_descuento_info');
        const tipoText = document.getElementById('tipo_descuento_text');
        
        if (reposicionData.tipoDescuento === 'reposicion') {
            tipoInfo.className = 'alert alert-info';
            tipoText.textContent = 'Solo reposición física (toallas, sábanas, etc.) - NO se descuenta del stock general';
        } else if (reposicionData.tipoDescuento === 'consumo') {
            tipoInfo.className = 'alert alert-warning';
            tipoText.textContent = 'Descuenta del stock general (cubiertos, vajilla, etc.) - Se registra como consumo';
        }
        
        // Validar stock inicial
        validarCantidadReposicion();
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('reposicionModal'));
        modal.show();
    }
    
    // Validar cantidad de reposición
    function validarCantidadReposicion() {
        const cantidad = parseFloat(document.getElementById('cantidad_reponer').value) || 0;
        const stockActual = reposicionData.stockActual;
        const warningDiv = document.getElementById('stock_warning');
        const warningText = document.getElementById('stock_warning_text');
        
        if (reposicionData.tipoDescuento === 'consumo' && cantidad > stockActual) {
            warningDiv.style.display = 'block';
            warningText.textContent = `No hay suficiente stock. Stock disponible: ${stockActual}. Se intentará reponer ${cantidad}.`;
            warningDiv.className = 'alert alert-danger';
        } else if (reposicionData.tipoDescuento === 'consumo' && stockActual < reposicionData.cantidadRequerida) {
            warningDiv.style.display = 'block';
            warningText.textContent = `Stock bajo. Stock actual: ${stockActual}, cantidad recomendada: ${reposicionData.cantidadRequerida}.`;
            warningDiv.className = 'alert alert-warning';
        } else {
            warningDiv.style.display = 'none';
        }
    }
    
    // Confirmar reposición
    function confirmarReposicion() {
        const cantidad = parseFloat(document.getElementById('cantidad_reponer').value);
        const observaciones = document.getElementById('observaciones_reposicion').value;
        
        if (!cantidad || cantidad <= 0) {
            mostrarModalError('Debe especificar una cantidad válida para reponer.');
            return;
        }
        
        // Mostrar overlay de carga
        showLoadingOverlay('Registrando reposición...');
        
        // Preparar datos para envío
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('apartamento_limpieza_id', reposicionData.apartamentoLimpiezaId);
        formData.append('item_checklist_id', reposicionData.itemId);
        formData.append('cantidad_reponer', cantidad);
        formData.append('observaciones', observaciones);
        
        // Enviar petición
        fetch('{{ route("reposicion.store") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingOverlay();
            
            if (data.success) {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('reposicionModal'));
                modal.hide();
                
                // Mostrar notificación de éxito
                mostrarNotificacionReposicion(data);
                
                // Limpiar formulario
                document.getElementById('reposicionForm').reset();
            } else {
                mostrarModalError(data.message || 'Error al registrar la reposición.');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Error:', error);
            mostrarModalError('Error de conexión al registrar la reposición.');
        });
    }
    
    // Mostrar notificación de reposición exitosa
    function mostrarNotificacionReposicion(data) {
        const tipoDescuento = data.data.tipo_descuento;
        const stockDescontado = data.data.stock_descontado;
        const articulo = data.data.articulo;
        
        let mensaje = `Reposición registrada correctamente.`;
        
        if (tipoDescuento === 'consumo' && stockDescontado) {
            mensaje += ` Stock descontado: ${articulo.stock_actual} unidades disponibles.`;
        } else if (tipoDescuento === 'reposicion') {
            mensaje += ` Solo reposición física (no se descuenta del stock general).`;
        }
        
        mostrarNotificacion(mensaje, 'success');
    }
    
    // Inicializar sistema de reposición cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        inicializarSistemaReposicion();
    });
</script>

@if(isset($articulosActivos) && count($articulosActivos) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnRegistrarDescuento');
    if (!btn) return;
    btn.addEventListener('click', function() {
        const form = document.getElementById('formDescontarArticulo');
        const articuloId = form.querySelector('[name="articulo_id"]').value;
        const motivo = form.querySelector('[name="motivo"]').value;
        const observaciones = form.querySelector('[name="observaciones"]').value;
        if (!articuloId || !motivo) {
            alert('Selecciona el artículo y el motivo.');
            return;
        }
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        fetch('{{ route('gestion.articulo-descuento', $apartamentoLimpieza->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ articulo_id: articuloId, motivo: motivo, observaciones: observaciones })
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                alert(data.message || 'Movimiento registrado');
                form.reset();
                const modalEl = document.getElementById('modalDescontarArticulo');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                }
            } else {
                alert(data.message || 'Error al registrar el movimiento');
            }
        })
        .catch(() => alert('Error de red'))
        .finally(() => { btn.disabled = false; btn.innerHTML = originalText; });
    });
});
</script>
@endif

<style>
/* Variables CSS Apple */
:root {
    --apple-blue: #007AFF;
    --apple-blue-dark: #0056CC;
    --apple-blue-light: #4DA3FF;
    --system-gray: #8E8E93;
    --system-gray-2: #AEAEB2;
    --system-gray-3: #C7C7CC;
    --system-gray-4: #D1D1D6;
    --system-gray-5: #E5E5EA;
    --system-gray-6: #F2F2F7;
    --success-green: #34C759;
    --warning-orange: #FF9500;
    --error-red: #FF3B30;
    --info-blue: #5AC8FA;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --border-radius-sm: 8px;
    --border-radius-md: 12px;
    --border-radius-lg: 16px;
}

/* Contenedor Principal */
.apple-container {
    max-width: 100%;
    margin: 0 auto;
    padding: var(--spacing-md);
    background: var(--system-gray-6);
    min-height: 100vh;
}

@media (min-width: 768px) {
    .apple-container {
        max-width: 720px;
        padding: var(--spacing-lg);
    }
}

@media (min-width: 992px) {
    .apple-container {
        max-width: 960px;
        padding: calc(var(--spacing-lg) * 2);
    }
}

/* Tarjeta Principal */
.apple-card {
    background: #FFFFFF;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: var(--spacing-lg);
}

/* Header de Tarjeta */
.apple-card-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: var(--spacing-lg) var(--spacing-lg) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
}

.header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
    flex: 1;
}

.header-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex: 1;
}

.apartment-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.apartment-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.apartment-details {
    flex: 1;
}

.apartment-title {
    font-size: 20px;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.apartment-subtitle {
    font-size: 14px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    letter-spacing: -0.01em;
}



/* Cuerpo de Tarjeta */
.apple-card-body {
    padding: var(--spacing-lg);
    background: #FFFFFF;
}

/* Badge de Progreso */
.progress-badge {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 12px 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: fit-content;
    margin-bottom: 24px;
}

.progress-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(52, 199, 89, 0.1);
}

.progress-icon i {
    font-size: 12px;
    color: var(--success-green);
}

.progress-text {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.progress-count {
    font-size: 16px;
    font-weight: 700;
    color: #1D1D1F;
    line-height: 1;
}

.progress-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--system-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-badge.success {
    background: linear-gradient(135deg, var(--success-green), #30D158);
    animation: pulse 2s infinite;
}

.progress-badge.success .progress-icon {
    background: rgba(255, 255, 255, 0.2);
}

.progress-badge.success .progress-icon i {
    color: #FFFFFF;
}

.progress-badge.success .progress-count,
.progress-badge.success .progress-label {
    color: #FFFFFF;
}

.progress-badge.warning {
    background: linear-gradient(135deg, var(--warning-orange), #FF9F0A);
}

.progress-badge.warning .progress-icon {
    background: rgba(255, 255, 255, 0.2);
}

.progress-badge.warning .progress-icon i {
    color: #FFFFFF;
}

.progress-badge.warning .progress-count,
.progress-badge.warning .progress-label {
    color: #FFFFFF;
}

/* Secciones de Checklist */
.checklist-section {
    margin-bottom: var(--spacing-lg);
    border-radius: var(--border-radius-md);
    overflow: hidden;
    background: #FFFFFF;
    box-shadow: 0 1px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
}

/* Header de Sección - Version 1.0.4 */
.section-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: 12px var(--spacing-lg) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    min-height: 48px !important;
}

.section-title-container {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.section-icon {
    font-size: 16px;
    color: #FFFFFF;
    opacity: 0.9;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #FFFFFF;
    margin: 0;
    letter-spacing: -0.01em;
    line-height: 1.2;
}

.section-controls {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.apple-switch-container {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

/* Contenido de Sección */
.section-content {
    padding: var(--spacing-md) var(--spacing-lg);
    background: #FFFFFF;
}

/* Switches Apple */
.apple-switch {
    appearance: none;
    width: 51px;
    height: 31px;
    background: var(--system-gray-2);
    border-radius: 16px;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    outline: none;
}

.apple-switch:checked {
    background: var(--success-green);
}

.apple-switch::after {
    content: '';
    position: absolute;
    width: 27px;
    height: 27px;
    background: #FFFFFF;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.apple-switch:checked::after {
    transform: translateX(20px);
}

.category-switch:checked {
    background: var(--success-green);
}

.item-switch:checked {
    background: var(--apple-blue);
}

/* Items de Checklist */
.checklist-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
}

.checklist-item:last-child {
    border-bottom: none;
}

.checklist-item:hover {
    background: rgba(0, 122, 255, 0.02);
}

.item-label {
    font-size: 15px;
    font-weight: 400;
    color: #1D1D1F;
    margin-left: 12px;
    flex: 1;
    line-height: 1.3;
    cursor: pointer;
}

/* Acciones de Items */
.item-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.btn-reponer {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    color: #FFFFFF;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.btn-reponer:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    color: #FFFFFF;
}

.btn-reponer:active {
    transform: translateY(0);
}

.btn-reponer i {
    font-size: 11px;
}

.btn-reponer span {
    font-size: 11px;
    font-weight: 600;
}

/* Botón de Cámara */
.camera-button {
    background: linear-gradient(135deg, var(--error-red) 0%, #FF453A 100%);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(255, 59, 48, 0.3);
    text-decoration: none;
}

.camera-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(255, 59, 48, 0.4);
    color: #FFFFFF;
}

.camera-button:active {
    transform: scale(0.95);
}

/* Sección de Observaciones */
.observations-section {
    margin: var(--spacing-lg) 0;
}

.observations-textarea {
    width: 100%;
    min-height: 120px;
    padding: var(--spacing-md);
    border: 1px solid var(--system-gray-4);
    border-radius: var(--border-radius-md);
    font-size: 16px;
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
    background: #FFFFFF;
    color: #1D1D1F;
    resize: vertical;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.observations-textarea:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.observations-textarea::placeholder {
    color: var(--system-gray);
    font-style: italic;
}

/* Sección de Acciones */
.actions-section {
    margin: var(--spacing-lg) 0;
}

/* Botones Apple */
.apple-btn {
    width: 100%;
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: 17px;
    font-weight: 600;
    border: none;
    border-radius: var(--border-radius-md);
    transition: all 0.3s ease;
    cursor: pointer;
    text-transform: none;
    letter-spacing: -0.01em;
}

.apple-btn-primary {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
}

.apple-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
}

.apple-btn-primary:active {
    transform: translateY(0);
}

.apple-btn-success {
    background: linear-gradient(135deg, var(--success-green) 0%, #30D158 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.apple-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(52, 199, 89, 0.4);
}

.apple-btn-secondary {
    background: var(--system-gray-6);
    border: 1px solid var(--system-gray-4);
    color: var(--system-gray);
}

.apple-btn-secondary:hover {
    background: var(--system-gray-5);
    color: #1D1D1F;
}

.apple-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Banner de Siguiente Reserva */
.siguiente-reserva-banner {
    animation: fadeIn 0.3s ease;
}

.cliente-info {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    padding: 15px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.cliente-info .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.cliente-info .btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.cliente-email {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Estadísticas Compactas */
.reserva-stats-compact {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    padding: 15px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.stats-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    padding: 8px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.stat-item i {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1D1D1F;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #6C6C70;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
    margin: 0 5px;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 12px;
}

/* Estilos antiguos para compatibilidad */
.reserva-stat {
    padding: 10px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.reserva-stat:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.5);
}

.reserva-stat h5 {
    font-weight: 700;
    margin-bottom: 5px;
}

.reserva-stat p {
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

.edades-ninos, .notas-ninos {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    padding: 10px 15px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.edades-ninos strong, .notas-ninos strong {
    font-size: 0.9rem;
}

.edades-ninos span, .notas-ninos span {
    font-size: 0.9rem;
}

.gestion-links {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 15px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.gestion-links .btn {
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.gestion-links .btn:hover {
    transform: translateY(-2px);
}

.estado-reserva {
    margin-top: 15px;
}

.estado-reserva .badge {
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 20px;
}

/* Mensaje de Terminar */
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

/* Animaciones */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(52, 199, 89, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 199, 89, 0);
    }
}

/* FORZAR LIMPIEZA DEL ESTADO MODAL - SOLUCIÓN CRÍTICA CSS */
/* Anular overflow:hidden cuando no hay modales visibles */
body.modal-open {
    overflow: auto !important;
    padding-right: 0 !important;
}

/* Permitir scroll siempre */
body {
    overflow: auto !important;
}

/* Estilos mínimos para el contenido del modal (solo cuando está visible) */
#modalDescontarArticulo .modal-content {
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    border: none;
}

.list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.list-group-item i {
    font-size: 1.1rem;
}

.alert-success {
    background-color: rgba(52, 199, 89, 0.1);
    border: 1px solid rgba(52, 199, 89, 0.2);
    color: var(--success-green);
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Notificaciones */
.notificacion-success {
    background: linear-gradient(135deg, var(--success-green), #30D158);
}

.notificacion-error {
    background: linear-gradient(135deg, var(--error-red), #FF453A);
}

.notificacion-toast i {
    margin-right: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: var(--spacing-md);
        text-align: center;
    }
    
    .header-main {
        flex-direction: column;
        gap: var(--spacing-md);
        align-items: center;
    }
    
    .apartment-title {
        font-size: 18px;
        text-align: center;
    }
    
    .apartment-subtitle {
        font-size: 13px;
        text-align: center;
    }
    
    .progress-badge {
        padding: 10px 14px;
    }
    
    .progress-count {
        font-size: 14px;
    }
    
    .progress-label {
        font-size: 10px;
    }
    
    .section-header {
        padding: 10px var(--spacing-md);
        min-height: 44px;
    }
    
    .section-title {
        font-size: 15px;
    }
    
    .section-icon {
        font-size: 14px;
    }
    
    .section-controls {
        justify-content: center;
    }
    
    .apple-card-header {
        padding: var(--spacing-md);
    }
    
    .apple-card-body {
        padding: var(--spacing-md);
    }
    
    .section-content {
        padding: var(--spacing-md);
    }
    
    .checklist-item {
        padding: 8px 0;
    }
    
    .item-label {
        font-size: 14px;
    }
    
    .siguiente-reserva-banner {
        width: 95% !important;
        padding: 15px !important;
    }
    
    .siguiente-reserva-banner .row .col-md-4 {
        margin-bottom: 15px;
    }
    
    .reserva-stat {
        padding: 8px;
    }
    
    .reserva-stat h5 {
        font-size: 1.1rem;
    }
    
    .reserva-stat p {
        font-size: 0.8rem;
    }
    
    .edades-ninos, .notas-ninos {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
    
    .cliente-info {
        padding: 10px;
    }
    
    .cliente-info .btn-success {
        padding: 4px 8px;
        font-size: 0.7rem;
    }
    
    .gestion-links {
        padding: 10px;
    }
    
    .gestion-links .btn {
        font-size: 0.8rem;
        padding: 8px 12px;
    }
    
    .estado-reserva .badge {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    .reserva-stats-compact {
        padding: 10px;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 8px;
    }
    
    .stat-item {
        padding: 6px;
    }
    
    .stat-item i {
        font-size: 1rem;
    }
    
    .stat-number {
        font-size: 1rem;
    }
    
    .stat-label {
        font-size: 0.7rem;
    }
    
    .stat-divider {
        width: 100%;
        height: 1px;
        margin: 5px 0;
    }
}

/* Overlay de Carga */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
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

/* Sección de Amenities - Diseño Apple-Inspired Moderno */
.amenities-section {
    margin-top: var(--spacing-lg);
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

/* Header de Amenities */
.amenities-header {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #FFFFFF;
}

.amenities-title {
    display: flex;
    align-items: center;
    gap: 16px;
}

.title-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.title-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.title-content h3 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
}

.title-content p {
    font-size: 14px;
    margin: 0;
    opacity: 0.8;
    font-weight: 400;
}

/* Toggle Switch Moderno */
.amenities-toggle {
    display: flex;
    align-items: center;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.2);
    transition: 0.3s;
    border-radius: 34px;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 2px;
    bottom: 2px;
    background: #FFFFFF;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.amenities-switch:checked + .toggle-slider {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.amenities-switch:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* Contenido de Amenities */
.amenities-content {
    padding: 24px;
    background: #FFFFFF;
}

/* Resumen de Amenities */
.amenities-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.summary-card {
    background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.summary-icon {
    width: 48px;
    height: 48px;
    background: var(--apple-blue);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}

.summary-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.summary-content h4 {
    font-size: 24px;
    font-weight: 700;
    color: #1D1D1F;
    margin: 0 0 4px 0;
}

.summary-content p {
    font-size: 14px;
    color: #6C6C70;
    margin: 0;
    font-weight: 500;
}

/* Categorías de Amenities */
.amenity-category {
    margin-bottom: 32px;
    background: #FFFFFF;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.category-header {
    background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.category-icon {
    width: 40px;
    height: 40px;
    background: #FFFFFF;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.category-icon i {
    font-size: 16px;
}

.category-info h4 {
    font-size: 18px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 4px 0;
}

.category-count {
    font-size: 13px;
    color: #6C6C70;
    font-weight: 500;
}

/* Grid de Amenities */
.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    padding: 20px;
}

/* Tarjeta de Amenity */
.amenity-card {
    background: #FFFFFF;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.amenity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 122, 255, 0.2);
}

.amenity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.amenity-name h5 {
    font-size: 16px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 6px 0;
}

.amenity-name p {
    font-size: 13px;
    color: #6C6C70;
    margin: 0;
    line-height: 1.4;
}

.type-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Detalles del Amenity */
.amenity-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.detail-row {
    display: flex;
    gap: 16px;
}

.detail-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-item.full-width {
    flex: 1;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: #6C6C70;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 8px;
    text-align: center;
    min-width: 60px;
}

.detail-value.recommended {
    background: #E8F5E8;
    color: #2D7D32;
}

.detail-value.stock-available {
    background: #E3F2FD;
    color: #1565C0;
}

.detail-value.stock-unavailable {
    background: #FFEBEE;
    color: #C62828;
}

/* Inputs */
.quantity-input-container {
    display: flex;
    align-items: center;
}

.quantity-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5EA;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    background: #FFFFFF;
}

.quantity-input:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.quantity-input.is-invalid {
    border-color: #FF3B30;
    box-shadow: 0 0 0 3px rgba(255, 59, 48, 0.1);
}

.observations-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5EA;
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.3s ease;
    background: #FFFFFF;
}

.observations-input:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

/* Botón de Guardado */
.amenities-save {
    text-align: center;
    padding: 32px 20px 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    margin-top: 32px;
}

.save-button {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%);
    color: #FFFFFF;
    border: none;
    padding: 16px 32px;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
}

.save-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 122, 255, 0.4);
}

.save-button:active {
    transform: translateY(0);
}

.save-button i {
    font-size: 18px;
}

/* Estado Vacío */
.amenities-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6C6C70;
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: #F2F2F7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.empty-icon i {
    font-size: 32px;
    color: #C7C7CC;
}

.amenities-empty h4 {
    font-size: 20px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 12px 0;
}

.amenities-empty p {
    font-size: 16px;
    margin: 0;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .amenities-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .amenities-summary {
        grid-template-columns: 1fr;
    }
    
    .amenities-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 16px;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 12px;
    }
    
    .detail-item.full-width {
        flex: none;
    }
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

        /* Estilos para amenities ya añadidos */
        .input-anadido {
            background-color: #d4edda !important;
            border-color: #28a745 !important;
            color: #155724 !important;
        }
        
        .input-anadido:focus {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        
        .badge.bg-success {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
        
        .badge.bg-success i {
            margin-right: 0.25em;
        }
        
        /* Estilos para el modal de confirmación */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header.bg-success {
            border-radius: 16px 16px 0 0;
            border-bottom: none;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .success-icon {
            animation: bounceIn 0.6s ease-out;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-body {
            padding: 2rem 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem 1.5rem;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        /* Estilos para los nuevos modales */
        .warning-icon, .info-icon, .error-icon {
            animation: bounceIn 0.6s ease-out;
        }
        
        .modal-header.bg-warning {
            background: linear-gradient(135deg, #FF9500, #FFB340) !important;
        }
        
        .modal-header.bg-primary {
            background: linear-gradient(135deg, #007AFF, #5AC8FA) !important;
        }
        
        .modal-header.bg-danger {
            background: linear-gradient(135deg, #FF3B30, #FF6B6B) !important;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FF9500, #FFB340);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 149, 0, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007AFF, #5AC8FA);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #FF3B30, #FF6B6B);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 59, 48, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #8E8E93, #AEAEB2);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(142, 142, 147, 0.3);
        }
        
        /* Estilos para los botones de motivos estándar */
        .motivos-estandar .btn-outline-primary {
            transition: all 0.3s ease;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
        
        .motivos-estandar .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            background-color: #0d6efd;
            color: white;
        }
        
        .motivos-estandar .btn-outline-primary:active {
            transform: translateY(0);
        }
        
        /* Animación para la notificación de copia */
        .notificacion-copia .alert {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Estilos para textarea auto-ajustable */
#motivoConsentimiento {
    resize: none;
    overflow: hidden;
    min-height: 38px;
    transition: height 0.2s ease, border-color 0.2s ease;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    line-height: 1.4;
}

/* Estilos para amenities automáticos de niños - SIGUIENDO EL ESTILO DE NUESTRA PLATAFORMA */
.amenity-motivo-ninos {
    margin-top: 12px;
    padding: 12px 16px;
    background: #E8F5E8;
    border: 1px solid rgba(45, 125, 50, 0.2);
    border-radius: 12px;
    border-left: 3px solid #2D7D32;
    transition: all 0.3s ease;
}

.amenity-motivo-ninos small {
    font-size: 13px;
    font-weight: 600;
    color: #2D7D32;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amenity-card.amenity-ninos-automatico {
    border: 2px solid rgba(45, 125, 50, 0.3);
    background: #FFFFFF;
    box-shadow: 0 4px 15px rgba(45, 125, 50, 0.15);
}

.amenity-card.amenity-ninos-automatico:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(45, 125, 50, 0.2);
    border-color: rgba(45, 125, 50, 0.5);
}

.amenity-card.amenity-ninos-automatico .amenity-header {
    background: #F1F8E9;
    border-bottom: 1px solid rgba(45, 125, 50, 0.2);
    border-radius: 12px 12px 0 0;
    margin: -20px -20px 20px -20px;
    padding: 20px;
}

.amenity-card.amenity-ninos-automatico .type-badge {
    background: #2D7D32 !important;
    color: #FFFFFF;
}
        
        #motivoConsentimiento:focus {
            border-color: var(--apple-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 255, 0.25);
            outline: none;
        }
        
        #motivoConsentimiento::placeholder {
            color: #adb5bd;
            font-style: italic;
        }
        
        /* Animación suave para el cambio de altura */
        #motivoConsentimiento {
            animation: textareaExpand 0.2s ease-out;
        }
        
        @keyframes textareaExpand {
            from {
                opacity: 0.8;
                transform: scale(0.98);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
</style>

<!-- Modal de Confirmación de Amenities -->
<div class="modal fade" id="amenitiesSuccessModal" tabindex="-1" aria-labelledby="amenitiesSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="amenitiesSuccessModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Amenities Guardados
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="success-icon mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-success mb-2">¡Operación Completada!</h6>
                    <p class="text-muted mb-0" id="amenitiesSuccessMessage">
                        Los amenities han sido guardados correctamente.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Modificación de Cantidad -->
<div class="modal fade" id="modificacionCantidadModal" tabindex="-1" aria-labelledby="modificacionCantidadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modificacionCantidadModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Modificación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="warning-icon mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-warning mb-2">Modificar Cantidad</h6>
                    <p class="text-muted mb-0" id="modificacionCantidadMessage">
                        ¿Estás seguro de que quieres modificar la cantidad de este amenity?
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cancelarModificacionCantidad()">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-warning" onclick="confirmarModificacionCantidad()">
                    <i class="fas fa-check me-2"></i>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Envío -->
<div class="modal fade" id="confirmacionEnvioModal" tabindex="-1" aria-labelledby="confirmacionEnvioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmacionEnvioModalLabel">
                    <i class="fas fa-save me-2"></i>
                    Confirmar Guardado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="info-icon mb-3">
                        <i class="fas fa-info-circle text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-primary mb-2">Guardar Amenities</h6>
                    <p class="text-muted mb-0">
                        ¿Estás seguro de que quieres guardar los amenities?
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmarEnvioAmenities()">
                    <i class="fas fa-save me-2"></i>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Error -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="error-icon mb-3">
                        <i class="fas fa-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-danger mb-2">Error de Validación</h6>
                    <p class="text-muted mb-0" id="errorMessage">
                        Ha ocurrido un error.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reposición de Artículos -->
<div class="modal fade" id="reposicionModal" tabindex="-1" aria-labelledby="reposicionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="reposicionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Reponer Artículo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reposicionForm">
                    @csrf
                    <input type="hidden" id="reposicion_item_id" name="item_checklist_id">
                    <input type="hidden" id="reposicion_apartamento_id" name="apartamento_limpieza_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            Artículo
                        </label>
                        <div class="form-control-plaintext" id="articulo_nombre_display"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Tipo de Descuento
                        </label>
                        <div class="alert alert-info" id="tipo_descuento_info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="tipo_descuento_text"></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-hashtag me-2 text-primary"></i>
                            Cantidad a Reponer
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="cantidad_reponer" 
                               name="cantidad_reponer" 
                               min="0.01" 
                               step="0.01" 
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Cantidad recomendada: <span id="cantidad_recomendada"></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-sticky-note me-2 text-primary"></i>
                            Observaciones
                        </label>
                        <textarea class="form-control" 
                                  id="observaciones_reposicion" 
                                  name="observaciones" 
                                  rows="3" 
                                  placeholder="Motivo de la reposición..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning" id="stock_warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> <span id="stock_warning_text"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarReposicion">
                    <i class="fas fa-check me-2"></i>
                    Confirmar Reposición
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script para mostrar el modal automáticamente -->
@if(isset($mensajeAmenities) && $mensajeAmenities)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar el mensaje del modal
    document.getElementById('amenitiesSuccessMessage').textContent = '{{ $mensajeAmenities }}';
    
    // Mostrar el modal automáticamente
    var modal = new bootstrap.Modal(document.getElementById('amenitiesSuccessModal'));
    modal.show();
});
</script>
@endif

<script>
// SOLUCIÓN AGRESIVA: Limpiar estado de modal de forma inmediata y periódica
(function() {
    function limpiarEstadoModal() {
        const modales = document.querySelectorAll('.modal.show');
        // Si no hay modales visibles, limpiar el estado del body
        if (modales.length === 0) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            // También limpiar atributos data de Bootstrap
            document.body.removeAttribute('data-bs-overflow');
            document.body.removeAttribute('data-bs-padding-right');
        }
    }
    
    // Ejecutar inmediatamente (sin esperar DOMContentLoaded)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            limpiarEstadoModal();
            // Ejecutar varias veces para asegurar
            setTimeout(limpiarEstadoModal, 50);
            setTimeout(limpiarEstadoModal, 200);
            setTimeout(limpiarEstadoModal, 500);
        });
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        limpiarEstadoModal();
        setTimeout(limpiarEstadoModal, 50);
        setTimeout(limpiarEstadoModal, 200);
        setTimeout(limpiarEstadoModal, 500);
    }
    
    // Limpiar cuando la página está completamente cargada
    window.addEventListener('load', function() {
        limpiarEstadoModal();
        setTimeout(limpiarEstadoModal, 100);
    });
    
    // Agregar listeners a todos los modales para limpiar el estado cuando se cierren
    function agregarListenersModal() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                limpiarEstadoModal();
            });
            modal.addEventListener('hide.bs.modal', function() {
                // También limpiar cuando comienza a ocultarse
                setTimeout(limpiarEstadoModal, 100);
            });
        });
    }
    
    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', agregarListenersModal);
    } else {
        agregarListenersModal();
    }
    
    // Re-agregar listeners si se añaden modales dinámicamente
    setTimeout(agregarListenersModal, 500);
})();
</script>

@endsection
