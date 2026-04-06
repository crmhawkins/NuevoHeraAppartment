@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Channex Full Sync') }}</h2>
    </div>

    {{$message}}
    @isset($error)
    {{$error}}

    @endisset

</div>
@endsection
