@extends('layouts.appUser')

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center">Rellene el formulario para confirmar su reserva</h5>
@endsection

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .form-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 20px;
        text-align: center;
    }
    
    .form-header h3 {
        margin: 0;
        font-weight: 600;
        font-size: 1.5rem;
    }
    
    .form-floating {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .form-control, .form-select {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        height: auto;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
        transform: translateY(-2px);
    }
    
    .form-floating label {
        position: absolute;
        top: 15px;
        left: 15px;
        color: #6c757d;
        transition: all 0.3s ease;
        pointer-events: none;
        background: white;
        padding: 0 5px;
        font-size: 0.9rem;
    }
    
    .form-control:focus + label,
    .form-control:not(:placeholder-shown) + label,
    .form-select:focus + label,
    .form-select:not([value=""]) + label {
        top: -10px;
        left: 10px;
        font-size: 0.8rem;
        color: #667eea;
        font-weight: 600;
    }
    
    .btn-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        color: white;
        width: 100%;
        max-width: 300px;
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .btn-modern:disabled {
        opacity: 0.6;
        transform: none;
    }
    
    .btn-secondary-modern {
        background: #6c757d;
        border: none;
        border-radius: 15px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
    }
    
    .btn-secondary-modern:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: white;
    }
    
    .alert-modern {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        padding: 15px;
        margin: 20px 0;
        color: #495057;
        text-align: center;
        border: none;
        font-weight: 500;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .alert-modern i {
        margin-right: 8px;
        color: #667eea;
    }
    
    .progress-bar-container {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        height: 8px;
        margin: 20px 0;
        overflow: hidden;
    }
    
    .progress-bar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 100%;
        border-radius: 10px;
        transition: width 0.5s ease;
    }
    
    .step-indicator {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }
    
    .step {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .step.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transform: scale(1.1);
    }
    
    .step.completed {
        background: #28a745;
    }
    
    .loading {
        display: none;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
    
    @media (max-width: 768px) {
        .form-header h3 {
            font-size: 1.3rem;
        }
        
        .form-control, .form-select {
            font-size: 0.9rem;
            padding: 12px;
        }
        
        .btn-modern {
            padding: 12px 30px;
            font-size: 1rem;
        }
    }


.form-floating>label {

height: fit-content !important;
padding: 5px 5px !important;

}
</style>

<div class="container">
    @if ($reserva->numero_personas == 0 || $reserva->numero_personas == null)
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="https://apartamentosalgeciras.com/wp-content/uploads/2022/09/Logo-Hawkins-Suites.svg" alt="Hawkins Suites" class="img-fluid mb-3" style="max-width: 300px;">
                </div>

                @if(!isset($cliente) || !($cliente->idioma_establecido ?? false))
                <div class="form-card" id="cardIdioma">
                    <div class="form-header">
                        <h3><i class="fa-solid fa-language me-2"></i>Selecciona tu idioma</h3>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <select id="idioma" class="form-select" onchange="cambiarIdioma(this.value)">
                                <option value="">-- Selecciona tu idioma --</option>
                                <option value="es" {{ session('locale', 'es') == 'es' ? 'selected' : '' }}>🇪🇸 Español</option>
                                <option value="en" {{ session('locale') == 'en' ? 'selected' : '' }}>🇺🇸 English</option>
                                <option value="fr" {{ session('locale') == 'fr' ? 'selected' : '' }}>🇫🇷 Français</option>
                                <option value="de" {{ session('locale') == 'de' ? 'selected' : '' }}>🇩🇪 Deutsch</option>
                                <option value="it" {{ session('locale') == 'it' ? 'selected' : '' }}>🇮🇹 Italiano</option>
                                <option value="pt" {{ session('locale') == 'pt' ? 'selected' : '' }}>🇵🇹 Português</option>
                            </select>
                        </div>
                        
                        <div class="alert-modern">
                            <i class="fa-solid fa-info-circle"></i>
                            <span id="textoIdioma">
                                Selecciona tu idioma preferido para continuar con el proceso de registro.
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="form-card mt-4" id="cardNumeroPersonas" style="{{ isset($cliente) && ($cliente->idioma_establecido ?? false) ? '' : 'display: none;' }}">
                    <div class="form-header">
                        <h3><i class="fa-solid fa-users me-2"></i>Número de personas</h3>
                    </div>
                    <div class="p-4">
                        <div class="alert-modern">
                            <i class="fa-solid fa-info-circle"></i>
                            <span id="tituloNumeroPersonas">
                                Para poder continuar debes decirnos el número de adultos (mayores de 18 años) que van a ocupar la reserva.
                            </span>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold" id="labelNumeroAdultos">
                                    Número de Adultos:
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="number" id="numero" value="1" min="1" step="1" class="form-control">
                                <input type="hidden" name="idReserva" id="idReserva" value="{{$id}}">
                            </div>
                            <div class="col-3">
                                <button id="sumar" class="btn btn-secondary-modern w-100">+</button>
                            </div>
                            <div class="col-3">
                                <button id="restar" class="btn btn-secondary-modern w-100">-</button>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <!-- Botón de test temporal -->
                            <button type="button" onclick="testBotones()" class="btn btn-warning mb-2">Test Botones</button>
                            
                            <button id="enviar" class="btn btn-modern" id="btnEnviar">
                                <span class="loading">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                </span>
                                <span class="btn-text">Continuar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($reserva->numero_personas != 0 || $reserva->numero_personas != null)
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="https://apartamentosalgeciras.com/wp-content/uploads/2022/09/Logo-Hawkins-Suites.svg" alt="Hawkins Suites" class="img-fluid mb-3" style="max-width: 300px;">
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    @for ($step = 1; $step <= $reserva->numero_personas; $step++)
                        <div class="step" id="step{{$step}}">{{$step}}</div>
                    @endfor
                </div>

                <div class="form-card">
                    <div class="form-header">
                        <h3><i class="fa-solid fa-id-card me-2"></i>{{$textos['Inicio']}}</h3>
                    </div>
                    
                    @if (session('alerta'))
                        <div class="alert alert-warning m-4">
                            {{ session('alerta') }}
                        </div>
                    @endif
                    
                    <div class="p-4">
                        @php
                            $nacionalidadComun = $data[0]->nacionalidad;
                        @endphp
                        
                        <div id="formularios">
                            <form action="{{route('dni.store')}}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{$id}}">
                                
                                @for ($i = 0; $i < $reserva->numero_personas; $i++)
                                    <div class="person-form" id="personForm{{$i}}" style="{{ $i > 0 ? 'display: none;' : '' }}">
                                        <div class="text-center mb-4">
                                            <h4 class="text-dark">
                                                @if ($i == 0)
                                                    <i class="fa-solid fa-user me-2"></i>{{$textos['Huesped.Principal']}}
                                                @else
                                                    <i class="fa-solid fa-user-plus me-2"></i>{{$textos['Acompañante']}} {{$i}}
                                                @endif
                                            </h4>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="nombre_{{$i}}"
                                                    type="text"
                                                    class="form-control"
                                                    id="nombre_{{$i}}"
                                                    placeholder="{{$textos['Nombre']}}"
                                                    value="{{ $i == 0 || isset($data[$i]) ? $data[$i]->nombre : '' }}"
                                                    required>
                                                    <label for="nombre_{{$i}}">{{$textos['Nombre']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['nombre_obli']}}
                                                    </div>
                                                    @error('nombre_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="apellido1_{{$i}}"
                                                    type="text"
                                                    class="form-control"
                                                    id="apellido1_{{$i}}"
                                                    value="{{ $i != 0 && isset($data[$i]) ? $data[$i]->primer_apellido : (isset($data[$i]->apellido1) ? $data[$i]->apellido1 : '') }}"
                                                    placeholder="{{$textos['Primer.Apellido']}}" required>
                                                    <label for="apellido1_{{$i}}">{{$textos['Primer.Apellido']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['apellido_obli']}}
                                                    </div>
                                                    @error('apellido1_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="apellido2_{{$i}}"
                                                    type="text"
                                                    class="form-control"
                                                    id="apellido2_{{$i}}"
                                                    value="{{ $i != 0 && isset($data[$i]) ? $data[$i]->segundo_apellido : (isset($data[$i]->apellido2) ? $data[$i]->apellido2 : '') }}"
                                                    placeholder="{{$textos['Segundo.Apellido']}}">
                                                    <label for="apellido2_{{$i}}">{{$textos['Segundo.Apellido']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        El primer apellido es obligatorio.
                                                    </div>
                                                    @error('apellido2_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="fecha_nacimiento_{{$i}}"
                                                    type="date"
                                                    class="form-control"
                                                    id="fecha_nacimiento_{{$i}}"
                                                    value="{{ isset($data[$i]) ? $data[$i]->fecha_nacimiento : '' }}"
                                                    placeholder="{{$textos['Fecha.Nacimiento']}}"
                                                    required>
                                                    <label for="fecha_nacimiento_{{$i}}">{{$textos['Fecha.Nacimiento']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['fecha_naci_obli']}}
                                                    </div>
                                                    @error('fecha_nacimiento_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select
                                                    name="nacionalidad_{{$i}}"
                                                    id="nacionalidad_{{$i}}"
                                                    class="form-select js-example-basic-single{{$i}} nacionalidad"
                                                    placeholder="{{$textos['Pais']}}">
                                                        @foreach ($paises as $pais)
                                                            <option value="{{$pais}}"
                                                                {{
                                                                    (isset($nacionalidadComun) && $nacionalidadComun == $pais) ||
                                                                    (old('nacionalidad_'.$i) == $pais) ||
                                                                    (empty(old('nacionalidad_'.$i)) && !isset($nacionalidadComun) && $pais == 'España')
                                                                    ? 'selected' : ''
                                                                }}
                                                            >
                                                                {{$pais}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="nacionalidad_{{$i}}">{{$textos['Pais']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['pais_obli']}}
                                                    </div>
                                                    @error('nacionalidad_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select
                                                    name="tipo_documento_{{$i}}"
                                                    id="tipo_documento_{{$i}}"
                                                    class="form-select"
                                                    placeholder="{{$textos['Tipo.Documento']}}"
                                                    required>
                                                        <option value="">{{$textos['Tipo.Documento']}}</option>
                                                        <option value="1" {{ (old('tipo_documento_'.$i) == '1' ? 'selected' : '') }}>{{$textos['Dni']}}</option>
                                                        <option value="2" {{ (old('tipo_documento_'.$i) == '2' ? 'selected' : '') }}>{{$textos['Pasaporte']}}</option>
                                                    </select>
                                                    <label for="tipo_documento_{{$i}}">{{$textos['Tipo.Documento']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['tipo_obli']}}
                                                    </div>
                                                    @error('tipo_documento_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="num_identificacion_{{$i}}"
                                                    type="text"
                                                    class="form-control"
                                                    id="num_identificacion_{{$i}}"
                                                    value="{{ isset($data[$i]) ? $data[$i]->num_identificacion : '' }}"
                                                    placeholder="{{$textos['Numero.Identificacion']}}"
                                                    required>
                                                    <label for="num_identificacion_{{$i}}">{{$textos['Numero.Identificacion']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['numero_obli']}}
                                                    </div>
                                                    @error('num_identificacion_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="fecha_expedicion_{{$i}}"
                                                    type="date"
                                                    class="form-control"
                                                    id="fecha_expedicion_{{$i}}"
                                                    value="{{ isset($data[$i]) ? $data[$i]->fecha_expedicion : '' }}"
                                                    placeholder="{{$textos['Fecha.Expedicion']}}"
                                                    required>
                                                    <label for="fecha_expedicion_{{$i}}">{{$textos['Fecha.Expedicion']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['fecha_obli']}}
                                                    </div>
                                                    @error('fecha_expedicion_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select
                                                    name="sexo_{{$i}}"
                                                    id="sexo_{{$i}}"
                                                    class="form-select"
                                                    placeholder="{{$textos['Sexo']}}"
                                                    required>
                                                        <option value="">{{$textos['Sexo']}}</option>
                                                        <option value="Masculino" {{ (old('sexo_'.$i) == 'Masculino' ? 'selected' : '') }}>{{$textos['Masculino']}}</option>
                                                        <option value="Femenino" {{ (old('sexo_'.$i) == 'Femenino' ? 'selected' : '') }}>{{$textos['Femenino']}}</option>
                                                    </select>
                                                    <label for="sexo_{{$i}}">{{$textos['Sexo']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['sexo_obli']}}
                                                    </div>
                                                    @error('sexo_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input
                                                    name="email_{{$i}}"
                                                    type="email"
                                                    class="form-control"
                                                    id="email_{{$i}}"
                                                    value="{{ isset($data[$i]) ? $data[$i]->email : '' }}"
                                                    placeholder="{{$textos['Correo.Electronico']}}"
                                                    required>
                                                    <label for="email_{{$i}}">{{$textos['Correo.Electronico']}}</label>
                                                    <div class="valid-feedback">
                                                        {{$textos['Correcto']}}
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        {{$textos['email_obli']}}
                                                    </div>
                                                    @error('email_{{$i}}')
                                                        <div class="alert alert-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botones de navegación -->
                                        <div class="text-center mt-4">
                                            @if($i > 0)
                                                <button type="button" class="btn btn-secondary-modern me-3" onclick="previousPerson({{$i}})">
                                                    <i class="fa-solid fa-arrow-left me-2"></i>Anterior
                                                </button>
                                            @endif
                                            
                                            @if($i < $reserva->numero_personas - 1)
                                                <button type="button" class="btn btn-modern" onclick="nextPerson({{$i}})">
                                                    Siguiente<i class="fa-solid fa-arrow-right ms-2"></i>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-modern">
                                                    <span class="loading">
                                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                    </span>
                                                    <span class="btn-text">{{$textos['Enviar']}}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let currentPerson = 0;
    const totalPersons = {{ $reserva->numero_personas ?? 0 }};
    
    // Función de test global (ANTES del document.ready)
    window.testBotones = function() {
        console.log('=== TEST DE BOTONES ===');
        console.log('jQuery disponible:', typeof $ !== 'undefined');
        console.log('Elemento #sumar:', $('#sumar').length);
        console.log('Elemento #restar:', $('#restar').length);
        console.log('Elemento #numero:', $('#numero').length);
        console.log('Valor actual del input:', $('#numero').val());
        
        // Test manual
        let valor = parseInt($('#numero').val()) || 1;
        $('#numero').val(valor + 1);
        console.log('Valor después de incremento manual:', $('#numero').val());
    };
    
    // Inicializar todo cuando el DOM esté listo
    $(document).ready(function() {
        console.log('DOM listo, inicializando...');
        
        // Mostrar formulario de número de personas si ya hay un idioma seleccionado o establecido
        @if(isset($cliente) && ($cliente->idioma_establecido ?? false))
            $('#cardNumeroPersonas').show();
            $('#cardIdioma').hide();
        @else
            if ($('#idioma').val() && $('#idioma').val() !== '') {
                $('#cardNumeroPersonas').show();
                $('#cardIdioma').hide();
            }
        @endif
        
        // Inicializar Select2 después de un pequeño delay
        setTimeout(function() {
            if (typeof $.fn.select2 !== 'undefined') {
                for (let i = 0; i < totalPersons; i++) {
                    try {
                        $('.js-example-basic-single' + i).select2({
                            theme: 'bootstrap-5',
                            width: '100%'
                        });
                    } catch (error) {
                        console.warn('Error inicializando Select2 para índice ' + i + ':', error);
                    }
                }
            }
        }, 100);
        
        // Actualizar progreso inicial
        updateProgress();
    });
    
    // Función para cambiar idioma
    function cambiarIdioma(idioma) {
        $('#idioma').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("dni.cambiarIdioma") }}',
            type: 'POST',
            data: {
                idioma: idioma,
                token: '{{ $reserva->token }}',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    const overlay = $('<div class="transition-overlay"></div>');
                    $('body').append(overlay);
                    overlay.fadeIn(300);
                    
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 500);
                } else {
                    showError('Error al cambiar el idioma: ' + response.message);
                    $('#idioma').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cambiar idioma:', { xhr, status, error });
                let errorMessage = 'Error al cambiar el idioma';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += ': ' + xhr.responseJSON.message;
                }
                
                showError(errorMessage);
                $('#idioma').prop('disabled', false);
            }
        });
    }
    
    // Funciones de navegación entre personas
    function nextPerson(currentIndex) {
        if (validatePersonForm(currentIndex)) {
            $('#personForm' + currentIndex).hide();
            $('#personForm' + (currentIndex + 1)).show();
            currentPerson = currentIndex + 1;
            updateProgress();
        }
    }
    
    function previousPerson(currentIndex) {
        $('#personForm' + currentIndex).hide();
        $('#personForm' + (currentIndex - 1)).show();
        currentPerson = currentIndex - 1;
        updateProgress();
    }
    
    // Validar formulario de persona
    function validatePersonForm(index) {
        const requiredFields = [
            'nombre_' + index,
            'apellido1_' + index,
            'fecha_nacimiento_' + index,
            'nacionalidad_' + index,
            'tipo_documento_' + index,
            'num_identificacion_' + index,
            'fecha_expedicion_' + index,
            'sexo_' + index,
            'email_' + index
        ];
        
        let isValid = true;
        
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else if (field) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        if (!isValid) {
            showError('Por favor, completa todos los campos obligatorios');
        }
        
        return isValid;
    }
    
    // Actualizar barra de progreso
    function updateProgress() {
        const progress = ((currentPerson + 1) / totalPersons) * 100;
        $('#progressBar').css('width', progress + '%');
        
        // Actualizar indicadores de paso
        for (let i = 0; i < totalPersons; i++) {
            const step = $('#step' + (i + 1));
            if (i < currentPerson) {
                step.removeClass('active').addClass('completed');
            } else if (i === currentPerson) {
                step.removeClass('completed').addClass('active');
            } else {
                step.removeClass('active completed');
            }
        }
    }
    
        // Funciones para número de personas
        console.log('Inicializando botones de número de personas...');
        
        // Verificar que los elementos existen
        console.log('Elemento #sumar existe:', $('#sumar').length);
        console.log('Elemento #restar existe:', $('#restar').length);
        console.log('Elemento #numero existe:', $('#numero').length);
        
        // Usar on() en lugar de click() para mayor compatibilidad
        $(document).on('click', '#sumar', function(e) {
            e.preventDefault();
            console.log('Botón sumar clickeado');
            let input = $('#numero');
            let valor = parseInt(input.val()) || 1;
            console.log('Valor actual:', valor);
            input.val(valor + 1);
            console.log('Nuevo valor:', valor + 1);
        });
        
        $(document).on('click', '#restar', function(e) {
            e.preventDefault();
            console.log('Botón restar clickeado');
            let input = $('#numero');
            let valor = parseInt(input.val()) || 1;
            console.log('Valor actual:', valor);
            if (valor > 1) {
                input.val(valor - 1);
                console.log('Nuevo valor:', valor - 1);
            } else {
                console.log('No se puede restar más, valor mínimo es 1');
            }
        });
        
        // Función alternativa con JavaScript puro
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, configurando botones con JS puro...');
            
            const btnSumar = document.getElementById('sumar');
            const btnRestar = document.getElementById('restar');
            const inputNumero = document.getElementById('numero');
            
            if (btnSumar) {
                btnSumar.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón sumar clickeado (JS puro)');
                    let valor = parseInt(inputNumero.value) || 1;
                    inputNumero.value = valor + 1;
                    console.log('Nuevo valor (JS puro):', valor + 1);
                });
            }
            
            if (btnRestar) {
                btnRestar.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón restar clickeado (JS puro)');
                    let valor = parseInt(inputNumero.value) || 1;
                    if (valor > 1) {
                        inputNumero.value = valor - 1;
                        console.log('Nuevo valor (JS puro):', valor - 1);
                    }
                });
            }
        });
        
        $('#enviar').click(function() {
        const numero = $('#numero').val();
        const idReserva = $('#idReserva').val();
        
        console.log('Enviando datos:', { numero, idReserva });
        
        $('.loading').show();
        $('.btn-text').hide();
        $(this).prop('disabled', true);
        
        $.ajax({
            url: '{{ route("dni.storeNumeroPersonas") }}',
            type: 'POST',
            data: {
                numero: numero,
                idReserva: idReserva,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    showError('Error al actualizar el número de personas');
                    $('.loading').hide();
                    $('.btn-text').show();
                    $('#enviar').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', { xhr, status, error });
                showError('Error al actualizar el número de personas: ' + error);
                $('.loading').hide();
                $('.btn-text').show();
                $('#enviar').prop('disabled', false);
            }
        });
    });
    
    // Función para mostrar errores
    function showError(message) {
        const notification = $(`
            <div class="error-notification">
                <i class="fas fa-exclamation-triangle"></i>
                <span>${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        notification.slideDown(300);
        
        setTimeout(function() {
            notification.slideUp(300, function() {
                notification.remove();
            });
        }, 5000);
        
        notification.find('.close-notification').click(function() {
            notification.slideUp(300, function() {
                notification.remove();
            });
        });
    }
    
    // Efectos de hover en campos
    $('.form-control, .form-select').focus(function() {
        $(this).addClass('border-primary');
    }).blur(function() {
        $(this).removeClass('border-primary');
    });
</script>
@endpush

<style>
    /* Estilos adicionales para transiciones */
    .transition-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    
    .transition-overlay::after {
        content: '';
        width: 50px;
        height: 50px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top: 3px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        z-index: 10000;
        display: none;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
    }
    
    .error-notification i {
        margin-right: 10px;
        color: #ffc107;
    }
    
    .error-notification .close-notification {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        margin-left: 15px;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .error-notification .close-notification:hover {
        opacity: 1;
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
</style>
@endsection