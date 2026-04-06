@extends('layouts.appAdmin')

@section('content')
<style>
    .inactive-sort {
        color: #0F1739;
        text-decoration: none;
    }
    .active-sort {
        color: #757191;
    }
</style>
<div class="container-fluid">
    <h2 class="mb-3">Agregar Ingreso</h2>
    {{-- route('admin.diarioCaja.create')route('admin.diarioCaja.create') --}}
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="col">
                @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif 
                <form method="POST" action="{{ route('admin.diarioCaja.store') }}" class="row" enctype="multipart/form-data" >
                        {{ csrf_field() }}       
                        <div class="col-lg col-md-12">
                            {{-- Asiento --}}
                            <div class="col-12 form-group mb-3">
                                <label for="asientoContable">Asiento Contable</label>
                                <input type="text" class="form-control" id="asientoContable" name="asientCcontable" value="{{$numeroAsiento}}" disabled >
                                <input type="hidden" name="asiento_contable" value="{{$numeroAsiento}}" >
                            </div>

                            {{-- Cuenta Contable --}}
                            <div class="col-12 form-group mb-3">
                                <label for="cuenta_id">Cuenta Contable</label>
                                <div class="input-group">
                                    <select name="cuenta_id" id="cuenta_id" class="select2 form-control {{ $errors->has('cuenta_id') ? 'is-invalid' : '' }}" data-show-subtext="true" data-live-search="true">
                                        <option value="{{null}}">-- Seleccione Cuenta Contable --</option>
                                        @foreach($response as $grupos)
                                            @foreach($grupos as $itemGroup)
                                            <option  value="">- {{$itemGroup['grupo']->numero .'. '. $itemGroup['grupo']->nombre}} -</option>
                                                @foreach($itemGroup['subGrupo'] as $subGrupo)
                                                    <option  value="">-- {{ $subGrupo['item']->numero .'. '. $subGrupo['item']->nombre}}  --</option>
                                                    @foreach($subGrupo['cuentas'] as $cuentas)
                                                        <option value="{{$cuentas['item']->numero}}">--- {{ $cuentas['item']->numero .'. '. $cuentas['item']->nombre}} ---</option>
                                                        @if(count($cuentas['subCuentas']) > 0)
                                                            @foreach($cuentas['subCuentas'] as $subCuentas)
                                                                <option value="{{$subCuentas['item']->numero}}">---- {{ $subCuentas['item']->numero .'. '. $subCuentas['item']->nombre}} ----</option>
                                                                @if(count($subCuentas['subCuentasHija']) > 0)
                                                                    @foreach($subCuentas['subCuentasHija'] as $subCuentasHijas)
                                                                        <option value="{{$subCuentasHijas->numero}}">---- {{ $subCuentasHijas->numero .'. '. $subCuentasHijas->nombre}} ----</option>
                                                                    @endforeach
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                @endforeach
                                            @endforeach  
                                        @endforeach

                                    
                                    </select>
                                    @error('cuenta_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Estado --}}
                            <div class="col-12 form-group mb-3">
                                <label for="estado_id">Estado</label>
                                <div class="input-group">
                                    <select name="estado_id" id="estado_id" class="select2 form-control {{ $errors->has('estado_id') ? 'is-invalid' : '' }}" data-show-subtext="true" data-live-search="true">
                                        <option value="{{null}}">-- Seleccione Estado --</option>
                                        @foreach($estados as $estado)
                                            <option  value="{{$estado->id}}">{{$estado->nombre}}</option> 
                                        @endforeach
                                    </select>
                                    @error('estado_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Ingreso --}}
                            <div class="col-12 form-group mb-3">
                                <label for="ingreso_id">Ingreso</label>
                                <div class="input-group">
                                    <select name="ingreso_id" id="ingreso_id" class="select2_ingresos form-control {{ $errors->has('ingreso_id') ? 'is-invalid' : '' }}" data-show-subtext="true" data-live-search="true">
                                        <option value="{{null}}">-- Seleccione Ingreo Asociado --</option>
                                        @if($ingresos)
                                            @foreach($ingresos as $grupo)
                                                <option value="{{$grupo->id}}">{{$grupo->title}}</option>
                                            @endforeach
                                        @endif
                                    
                                    </select>
                                    @error('ingreso_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Fecha --}}
                            <div class="col-12 form-group mb-3">
                                <label for="date">Fecha</label>
                                <input type="date" class="form-control {{ $errors->has('date') ? 'is-invalid' : '' }}" id="date" name="date" >
                                @error('date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Concepto --}}
                            <div class="col-12 form-group mb-3">
                                <label for="concepto">Concepto</label>
                                <input type="text" class="form-control {{ $errors->has('concepto') ? 'is-invalid' : '' }}" id="concepto" name="concepto" >
                                @error('concepto')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Debe --}}
                            {{-- <div class="col-12 form-group mb-3">
                                <label for="debe">Debe</label>
                                <input type="number" class="form-control" id="debe" name="debe" step="any" >
                            </div> --}}

                            {{-- Haber --}}
                            <div class="col-12 form-group mb-3">
                                <label for="haber">Importe</label>
                                <input type="number" class="form-control {{ $errors->has('haber') ? 'is-invalid' : '' }}" id="haber" name="haber" step="any" >
                                @error('haber')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>  
                             
                            <button type="submit" class="btn btn-primary w-100" >
                                Guardar
                            </button>     
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@include('sweetalert::alert')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Verificar si SweetAlert2 está definido
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
    }
    // In your Javascript (external .js resource or <script> tag)
    $(document).ready(function() {
        $('.select2').select2();
        $('.select2_ingresos').select2();
    });
  });
</script>
@endsection
@endsection
