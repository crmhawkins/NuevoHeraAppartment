@extends('layouts.public-booking')

@section('title', 'Establecer Contraseña - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="mb-2" style="color: #003580; font-weight: 700; font-size: 28px;">{{ __('set_password.title') }}</h2>
                        <p class="text-muted mb-0">{{ __('set_password.subtitle') }}</p>
                    </div>

                    @if(session('info'))
                        <div class="alert alert-info">
                            {{ session('info') }}
                        </div>
                    @endif

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>{{ __('set_password.data_found') }}</strong><br>
                        <strong>{{ __('set_password.name_label') }}</strong> {{ $cliente->nombre }} {{ $cliente->apellido1 }}<br>
                        <strong>{{ __('set_password.email_label') }}</strong> {{ $cliente->email_principal }}
                    </div>

                    <form method="POST" action="{{ route('web.establecer-password') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="verificacion_email" class="form-label fw-semibold">{{ __('set_password.verify_email') }}</label>
                            <input type="email" 
                                   class="form-control @error('verificacion_email') is-invalid @enderror" 
                                   id="verificacion_email" 
                                   name="verificacion_email" 
                                   value="{{ old('verificacion_email') }}" 
                                   required 
                                   placeholder="{{ __('set_password.verify_email_placeholder') }}">
                            <small class="form-text text-muted">{{ __('set_password.verify_email_hint') }}</small>
                            @error('verificacion_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('set_password.new_password') }}</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   autocomplete="new-password"
                                   placeholder="{{ __('register.password_placeholder') }}">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">{{ __('register.password_min') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">{{ __('set_password.confirm_password') }}</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   autocomplete="new-password"
                                   placeholder="{{ __('register.confirm_password_placeholder') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background: #003580; border: none; padding: 12px; font-weight: 600;">
                            <i class="fas fa-key me-2"></i>{{ __('set_password.submit') }}
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">
                            <a href="{{ route('web.login') }}" class="text-decoration-none" style="color: #003580;">
                                {{ __('set_password.back_to_login') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


