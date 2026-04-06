@extends('layouts.public-booking')

@section('title', 'Iniciar Sesión - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="mb-2" style="color: #003580; font-weight: 700; font-size: 28px;">{{ __('login.title') }}</h2>
                        <p class="text-muted mb-0">{{ __('login.subtitle') }}</p>
                    </div>

                    <form method="POST" action="{{ route('web.login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="identificador" class="form-label fw-semibold">{{ __('login.email_or_phone') }}</label>
                            <input type="text" 
                                   class="form-control @error('identificador') is-invalid @enderror" 
                                   id="identificador" 
                                   name="identificador" 
                                   value="{{ old('identificador') }}" 
                                   required 
                                   autofocus
                                   placeholder="{{ __('login.email_or_phone_placeholder') }}">
                            <small class="form-text text-muted">{{ __('login.email_or_phone_hint') }}</small>
                            @error('identificador')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('login.password') }} <span class="text-muted">({{ __('login.password_hint') }})</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   autocomplete="current-password"
                                   placeholder="{{ __('login.password_placeholder') }}">
                            <small class="form-text text-muted">{{ __('login.password_hint2') }}</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                {{ __('login.remember') }}
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background: #003580; border: none; padding: 12px; font-weight: 600;">
                            {{ __('login.submit') }}
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-2">
                            <a href="{{ route('web.verificar-cuenta') }}" class="text-decoration-none fw-semibold" style="color: #003580;">
                                <i class="fas fa-key me-1"></i>{{ __('login.no_password') }}
                            </a>
                        </p>
                        <p class="mb-2">
                            <a href="{{ route('password.request') }}" class="text-decoration-none" style="color: #003580;">
                                {{ __('login.forgot_password') }}
                            </a>
                        </p>
                        <p class="mb-0">
                            {{ __('login.no_account') }} 
                            <a href="{{ route('web.register') }}" class="text-decoration-none fw-semibold" style="color: #003580;">
                                {{ __('login.register') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

