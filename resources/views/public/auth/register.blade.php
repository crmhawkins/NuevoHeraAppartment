@extends('layouts.public-booking')

@section('title', 'Registro - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="mb-2" style="color: #003580; font-weight: 700; font-size: 28px;">{{ __('register.title') }}</h2>
                        <p class="text-muted mb-0">{{ __('register.subtitle') }}</p>
                    </div>

                    <form method="POST" action="{{ route('web.register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">{{ __('register.name') }}</label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre') }}" 
                                   required 
                                   autocomplete="given-name" 
                                   autofocus
                                   placeholder="Juan">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="apellido1" class="form-label fw-semibold">{{ __('register.surname') }} <span class="text-muted">{{ __('register.surname_optional') }}</span></label>
                            <input type="text" 
                                   class="form-control @error('apellido1') is-invalid @enderror" 
                                   id="apellido1" 
                                   name="apellido1" 
                                   value="{{ old('apellido1') }}" 
                                   autocomplete="family-name"
                                   placeholder="Pérez">
                            @error('apellido1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">{{ __('register.email') }}</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autocomplete="email"
                                   placeholder="tu@email.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">{{ __('register.phone') }} <span class="text-muted">{{ __('register.phone_optional') }}</span></label>
                            <input type="tel" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone') }}" 
                                   autocomplete="tel"
                                   placeholder="+34 600 000 000">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('register.password') }}</label>
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
                            <label for="password_confirmation" class="form-label fw-semibold">{{ __('register.confirm_password') }}</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   autocomplete="new-password"
                                   placeholder="{{ __('register.confirm_password_placeholder') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background: #003580; border: none; padding: 12px; font-weight: 600;">
                            {{ __('register.submit') }}
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">
                            {{ __('register.already_have_account') }} 
                            <a href="{{ route('web.login') }}" class="text-decoration-none fw-semibold" style="color: #003580;">
                                {{ __('register.login_here') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

