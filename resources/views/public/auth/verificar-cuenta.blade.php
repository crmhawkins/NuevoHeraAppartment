@extends('layouts.public-booking')

@section('title', 'Verificar Cuenta - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="mb-2" style="color: #003580; font-weight: 700; font-size: 28px;">{{ __('verify.title') }}</h2>
                        <p class="text-muted mb-0">{{ __('verify.subtitle') }}</p>
                    </div>

                    @if(session('info'))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            {{ session('info') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('web.verificar-cuenta') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="identificador" class="form-label fw-semibold">{{ __('verify.email_or_phone') }}</label>
                            <input type="text" 
                                   class="form-control @error('identificador') is-invalid @enderror" 
                                   id="identificador" 
                                   name="identificador" 
                                   value="{{ old('identificador') }}" 
                                   required 
                                   autofocus
                                   placeholder="{{ __('verify.email_or_phone_placeholder') }}">
                            <small class="form-text text-muted">{{ __('verify.email_or_phone_hint') }}</small>
                            @error('identificador')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background: #003580; border: none; padding: 12px; font-weight: 600;">
                            <i class="fas fa-search me-2"></i>{{ __('verify.submit') }}
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-2">
                            <a href="{{ route('web.login') }}" class="text-decoration-none" style="color: #003580;">
                                <i class="fas fa-arrow-left me-1"></i>{{ __('verify.back_to_login') }}
                            </a>
                        </p>
                        <p class="mb-0">
                            {{ __('verify.no_account') }} 
                            <a href="{{ route('web.register') }}" class="text-decoration-none fw-semibold" style="color: #003580;">
                                {{ __('verify.register_here') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


