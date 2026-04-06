@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Channel Propiedades') }}</h2>
    </div>
    <hr>

    <iframe
    src="https://app.channex.io/auth/exchange?oauth_session_key={{$token}}&app_mo
    de=headless&redirect_to=/channels&property_id=d8c3391c-548e-41a9-ab5e-defca58825e8"
    style="    width: 100%;
    min-height: 558px;"
    >
    </iframe>


</div>
@endsection
