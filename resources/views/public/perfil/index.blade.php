@extends('layouts.public-booking')

@section('title', 'Mi Perfil - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4" style="color: #003580; font-weight: 700; font-size: 32px;">{{ __('profile.title') }}</h1>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="perfilTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                <i class="fas fa-user me-2"></i>{{ __('profile.personal_info') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reservas-activas-tab" data-bs-toggle="tab" data-bs-target="#reservas-activas" type="button" role="tab">
                <i class="fas fa-calendar-check me-2"></i>{{ __('profile.active_reservations') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reservas-anteriores-tab" data-bs-toggle="tab" data-bs-target="#reservas-anteriores" type="button" role="tab">
                <i class="fas fa-history me-2"></i>{{ __('profile.past_reservations') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="metodos-pago-tab" data-bs-toggle="tab" data-bs-target="#metodos-pago" type="button" role="tab">
                <i class="fas fa-credit-card me-2"></i>{{ __('profile.payment_methods') }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="perfilTabsContent">
        <!-- Tab: Información Personal -->
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4" style="color: #003580; font-weight: 600;">{{ __('profile.personal_info') }}</h3>
                    
                    <form method="POST" action="{{ route('web.perfil.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label fw-semibold">{{ __('profile.name') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="{{ old('nombre', $cliente->nombre) }}" 
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="apellido1" class="form-label fw-semibold">{{ __('profile.first_surname') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="apellido1" 
                                       name="apellido1" 
                                       value="{{ old('apellido1', $cliente->apellido1) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="apellido2" class="form-label fw-semibold">{{ __('profile.second_surname') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="apellido2" 
                                       name="apellido2" 
                                       value="{{ old('apellido2', $cliente->apellido2) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">{{ __('profile.email') }}</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $cliente->email_principal) }}" 
                                       required>
                                <small class="form-text text-muted">{{ __('profile.main_email', ['email' => $cliente->email_principal]) }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label fw-semibold">{{ __('profile.phone') }}</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono" 
                                       value="{{ old('telefono', $cliente->telefono) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telefono_movil" class="form-label fw-semibold">{{ __('profile.mobile_phone') }} <span class="text-danger">*</span></label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono_movil" 
                                       name="telefono_movil" 
                                       value="{{ old('telefono_movil', $cliente->telefono_movil) }}">
                                <small class="form-text text-muted">{{ __('profile.mobile_phone_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="direccion" class="form-label fw-semibold">{{ __('profile.address') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="direccion" 
                                       name="direccion" 
                                       value="{{ old('direccion', $cliente->direccion) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="localidad" class="form-label fw-semibold">{{ __('profile.city') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="localidad" 
                                       name="localidad" 
                                       value="{{ old('localidad', $cliente->localidad) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="codigo_postal" class="form-label fw-semibold">{{ __('profile.postal_code') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="codigo_postal" 
                                       name="codigo_postal" 
                                       value="{{ old('codigo_postal', $cliente->codigo_postal) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="provincia" class="form-label fw-semibold">{{ __('profile.province') }} <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="provincia" 
                                       name="provincia" 
                                       value="{{ old('provincia', $cliente->provincia) }}">
                                <small class="form-text text-muted">{{ __('profile.province_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_nacimiento" class="form-label fw-semibold">{{ __('profile.birth_date') }} <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_nacimiento" 
                                       name="fecha_nacimiento" 
                                       value="{{ old('fecha_nacimiento', $cliente->fecha_nacimiento ? (is_string($cliente->fecha_nacimiento) ? $cliente->fecha_nacimiento : $cliente->fecha_nacimiento->format('Y-m-d')) : '') }}">
                                <small class="form-text text-muted">{{ __('profile.birth_date_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lugar_nacimiento" class="form-label fw-semibold">{{ __('profile.birth_place') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="lugar_nacimiento" 
                                       name="lugar_nacimiento" 
                                       placeholder="{{ __('profile.birth_place_placeholder') }}"
                                       value="{{ old('lugar_nacimiento', $cliente->lugar_nacimiento) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nacionalidad" class="form-label fw-semibold">{{ __('profile.nationality') }} <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nacionalidad" 
                                       name="nacionalidad" 
                                       maxlength="3"
                                       placeholder="ES"
                                       value="{{ old('nacionalidad', $cliente->nacionalidad) }}">
                                <small class="form-text text-muted">{{ __('profile.nationality_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sexo" class="form-label fw-semibold">{{ __('profile.gender') }} <span class="text-danger">*</span></label>
                                <select class="form-control" id="sexo" name="sexo">
                                    <option value="">{{ __('common.select') }}</option>
                                    <option value="Masculino" {{ old('sexo', $cliente->sexo) == 'Masculino' ? 'selected' : '' }}>{{ __('profile.gender_male') }}</option>
                                    <option value="Femenino" {{ old('sexo', $cliente->sexo) == 'Femenino' ? 'selected' : '' }}>{{ __('profile.gender_female') }}</option>
                                </select>
                                <small class="form-text text-muted">{{ __('profile.gender_required') }}</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h4 class="mb-3" style="color: #003580; font-weight: 600;">{{ __('profile.id_document') }} <span class="text-danger">*</span></h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_documento" class="form-label fw-semibold">{{ __('profile.id_type') }}</label>
                                <select class="form-control" id="tipo_documento" name="tipo_documento">
                                    <option value="">{{ __('common.select') }}</option>
                                    <option value="DNI" {{ old('tipo_documento', $cliente->tipo_documento) == 'DNI' ? 'selected' : '' }}>DNI</option>
                                    <option value="NIE" {{ old('tipo_documento', $cliente->tipo_documento) == 'NIE' ? 'selected' : '' }}>NIE</option>
                                    <option value="PASAPORTE" {{ old('tipo_documento', $cliente->tipo_documento) == 'PASAPORTE' ? 'selected' : '' }}>Pasaporte</option>
                                </select>
                                <small class="form-text text-muted">{{ __('profile.id_type_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="num_identificacion" class="form-label fw-semibold">{{ __('profile.id_number') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="num_identificacion" 
                                       name="num_identificacion" 
                                       value="{{ old('num_identificacion', $cliente->num_identificacion) }}">
                                <small class="form-text text-muted">{{ __('profile.id_number_required') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_expedicion_doc" class="form-label fw-semibold">{{ __('profile.id_issue_date') }}</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_expedicion_doc" 
                                       name="fecha_expedicion_doc" 
                                       max="{{ now()->format('Y-m-d') }}"
                                       value="{{ old('fecha_expedicion_doc', $cliente->fecha_expedicion_doc ? (is_string($cliente->fecha_expedicion_doc) ? $cliente->fecha_expedicion_doc : $cliente->fecha_expedicion_doc->format('Y-m-d')) : '') }}">
                                <small class="form-text text-muted">{{ __('profile.id_issue_date_required') }}</small>
                            </div>
                        </div>

                        @if($returnToReserva && $reservaParams)
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>{{ __('profile.complete_fields') }}</strong>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary" style="background: #003580; border: none;">
                            <i class="fas fa-save me-2"></i>{{ __('profile.save_changes') }}
                        </button>
                        @if($returnToReserva && $reservaParams)
                            <a href="{{ route('web.reservas.formulario', [
                                'apartamento' => $reservaParams['apartamento_id'],
                                'fecha_entrada' => $reservaParams['fecha_entrada'],
                                'fecha_salida' => $reservaParams['fecha_salida'],
                                'adultos' => $reservaParams['adultos'],
                                'ninos' => $reservaParams['ninos'],
                                'es_para_mi' => $reservaParams['es_para_mi'],
                            ]) }}" class="btn btn-success ms-2">
                                <i class="fas fa-arrow-left me-2"></i>{{ __('profile.back_to_reservation') }}
                            </a>
                        @endif
                    </form>

                    <hr class="my-4">

                    <h4 class="mb-3" style="color: #003580; font-weight: 600;">{{ __('profile.change_password') }}</h4>
                    
                    <form method="POST" action="{{ route('web.perfil.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label fw-semibold">{{ __('profile.current_password') }}</label>
                                <input type="password" 
                                       class="form-control @error('current_password') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label fw-semibold">{{ __('profile.new_password') }}</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">{{ __('profile.confirm_password') }}</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="background: #003580; border: none;">
                            <i class="fas fa-key me-2"></i>{{ __('profile.change_password_btn') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: Reservas Activas -->
        <div class="tab-pane fade" id="reservas-activas" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4" style="color: #003580; font-weight: 600;">{{ __('profile.active_reservations') }}</h3>
                    
                    @if($reservasActivas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('profile.reservations_code') }}</th>
                                        <th>{{ __('profile.reservations_apartment') }}</th>
                                        <th>{{ __('profile.reservations_checkin') }}</th>
                                        <th>{{ __('profile.reservations_checkout') }}</th>
                                        <th>{{ __('profile.reservations_status') }}</th>
                                        <th>{{ __('profile.reservations_price') }}</th>
                                        <th>{{ __('profile.reservations_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservasActivas as $reserva)
                                        <tr>
                                            <td><strong>{{ $reserva->codigo_reserva }}</strong></td>
                                            <td>{{ $reserva->apartamento->nombre ?? 'N/A' }}</td>
                                            <td>{{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $reserva->estado->color ?? 'secondary' }}">
                                                    {{ $reserva->estado->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($reserva->precio ?? 0, 2) }} €</td>
                                            <td>
                                                <a href="{{ route('web.perfil.reserva.show', $reserva->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Ver Detalles
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('profile.no_active_reservations') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab: Reservas Anteriores -->
        <div class="tab-pane fade" id="reservas-anteriores" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4" style="color: #003580; font-weight: 600;">{{ __('profile.past_reservations') }}</h3>
                    
                    @if($reservasAnteriores->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Apartamento</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Estado</th>
                                        <th>Precio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservasAnteriores as $reserva)
                                        <tr>
                                            <td><strong>{{ $reserva->codigo_reserva }}</strong></td>
                                            <td>{{ $reserva->apartamento->nombre ?? 'N/A' }}</td>
                                            <td>{{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $reserva->estado->color ?? 'secondary' }}">
                                                    {{ $reserva->estado->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($reserva->precio ?? 0, 2) }} €</td>
                                            <td>
                                                <a href="{{ route('web.perfil.reserva.show', $reserva->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Ver Detalles
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('profile.no_past_reservations') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab: Métodos de Pago -->
        <div class="tab-pane fade" id="metodos-pago" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4" style="color: #003580; font-weight: 600;">{{ __('profile.payment_methods') }}</h3>
                    
                    @if(config('services.stripe.key'))
                        <div id="payment-methods-list" class="mb-4">
                            @if(isset($paymentMethods) && is_object($paymentMethods) && isset($paymentMethods->data) && count($paymentMethods->data) > 0)
                                @foreach($paymentMethods->data as $paymentMethod)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-credit-card me-2"></i>
                                                    <strong>{{ strtoupper($paymentMethod->card->brand) }}</strong>
                                                    •••• {{ $paymentMethod->card->last4 }}
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ __('profile.expires') }}: {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                                    </small>
                                                </div>
                                                <form action="{{ route('web.perfil.metodo-pago.delete', $paymentMethod->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('profile.delete_payment_confirm') }}')">
                                                        <i class="fas fa-trash"></i> {{ __('profile.delete_payment_method') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">{{ __('profile.no_payment_methods') }}</p>
                            @endif
                        </div>

                        <div id="payment-form-container">
                            <h5 class="mb-3">{{ __('profile.add_payment_method') }}</h5>
                            <div id="card-element" class="form-control mb-3" style="padding: 12px; height: 45px;"></div>
                            <div id="card-errors" role="alert" class="text-danger mb-3"></div>
                            <button id="save-payment-method" class="btn btn-primary" style="background: #003580; border: none;">
                                <i class="fas fa-save me-2"></i>{{ __('profile.save_payment_method') }}
                            </button>
                        </div>

                        <script src="https://js.stripe.com/v3/"></script>
                        <script>
                            const stripe = Stripe('{{ config("services.stripe.key") }}');
                            const elements = stripe.elements();
                            const cardElement = elements.create('card', {
                                style: {
                                    base: {
                                        fontSize: '16px',
                                        color: '#424770',
                                    },
                                },
                            });
                            cardElement.mount('#card-element');

                            const cardErrors = document.getElementById('card-errors');
                            cardElement.on('change', function(event) {
                                if (event.error) {
                                    cardErrors.textContent = event.error.message;
                                } else {
                                    cardErrors.textContent = '';
                                }
                            });

                            document.getElementById('save-payment-method').addEventListener('click', async function() {
                                const {paymentMethod, error} = await stripe.createPaymentMethod({
                                    type: 'card',
                                    card: cardElement,
                                });

                                if (error) {
                                    cardErrors.textContent = error.message;
                                } else {
                                    // Enviar paymentMethod.id al servidor
                                    const form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = '{{ route("web.perfil.metodo-pago") }}';
                                    
                                    const csrfToken = document.createElement('input');
                                    csrfToken.type = 'hidden';
                                    csrfToken.name = '_token';
                                    csrfToken.value = '{{ csrf_token() }}';
                                    form.appendChild(csrfToken);

                                    const paymentMethodInput = document.createElement('input');
                                    paymentMethodInput.type = 'hidden';
                                    paymentMethodInput.name = 'payment_method_id';
                                    paymentMethodInput.value = paymentMethod.id;
                                    form.appendChild(paymentMethodInput);

                                    document.body.appendChild(form);
                                    form.submit();
                                }
                            });
                        </script>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Los métodos de pago no están disponibles en este momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

