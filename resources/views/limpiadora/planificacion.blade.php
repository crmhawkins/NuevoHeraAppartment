@extends('layouts.appPersonal')

@section('title', Auth::user()->idioma_preferido === 'ar' ? "\u062A\u062E\u0637\u064A\u0637\u064A" : 'Mi Planificacion')

@section('content')
@php
    $isAr = Auth::user()->idioma_preferido === 'ar';
    $mesesEs = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $mesesAr = ["\u064A\u0646\u0627\u064A\u0631","\u0641\u0628\u0631\u0627\u064A\u0631","\u0645\u0627\u0631\u0633","\u0623\u0628\u0631\u064A\u0644","\u0645\u0627\u064A\u0648","\u064A\u0648\u0646\u064A\u0648","\u064A\u0648\u0644\u064A\u0648","\u0623\u063A\u0633\u0637\u0633","\u0633\u0628\u062A\u0645\u0628\u0631","\u0623\u0643\u062A\u0648\u0628\u0631","\u0646\u0648\u0641\u0645\u0628\u0631","\u062F\u064A\u0633\u0645\u0628\u0631"];
    $nombreMes = $isAr ? $mesesAr[$mes->month - 1] : $mesesEs[$mes->month - 1];
    $diasSemanaEs = ['L','M','X','J','V','S','D'];
    $diasSemanaAr = ["\u0627","\u062B","\u0623","\u062E","\u062C","\u0633","\u062D"];
    $diasSemanaLabels = $isAr ? $diasSemanaAr : $diasSemanaEs;

    $hoy = \Carbon\Carbon::today()->format('Y-m-d');
    $inicioMes = $mes->copy()->startOfMonth();
    $finMes = $mes->copy()->endOfMonth();

    // dayOfWeekIso: 1=Monday ... 7=Sunday
    $primerDiaSemana = $inicioMes->dayOfWeekIso;
    $totalDias = $finMes->day;

    $diasTrabajados = collect($diasTrabajo)->filter(fn($v) => $v)->count();
    $diasLibres = collect($diasTrabajo)->filter(fn($v) => !$v)->count();

    $mesAnterior = $mes->copy()->subMonth()->format('Y-m');
    $mesSiguiente = $mes->copy()->addMonth()->format('Y-m');
@endphp

<div class="apple-container" style="max-width: 600px;">
    {{-- Title --}}
    <h4 class="text-center mb-3 fw-bold" style="color: #1C1C1E;">
        {{ $isAr ? "\u062A\u062E\u0637\u064A\u0637\u064A" : 'Mi Planificacion' }}
    </h4>

    {{-- Month selector --}}
    <div class="d-flex align-items-center justify-content-between mb-3 px-2">
        <a href="{{ route('limpiadora.planificacion', ['mes' => $mesAnterior]) }}" class="btn btn-sm btn-outline-primary rounded-circle" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-chevron-{{ $isAr ? 'right' : 'left' }}"></i>
        </a>
        <div class="text-center">
            <span class="fw-bold fs-5" style="color: #1C1C1E;">{{ $nombreMes }} {{ $mes->year }}</span>
        </div>
        <a href="{{ route('limpiadora.planificacion', ['mes' => $mesSiguiente]) }}" class="btn btn-sm btn-outline-primary rounded-circle" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-chevron-{{ $isAr ? 'left' : 'right' }}"></i>
        </a>
    </div>

    {{-- Calendar grid --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.08);overflow:hidden;padding:12px;">
        {{-- Day of week headers --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:8px;">
            @foreach($diasSemanaLabels as $d)
                <div style="text-align:center;font-size:13px;font-weight:700;color:#8E8E93;padding:6px 0;">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Day cells --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
            {{-- Empty cells for offset --}}
            @for($i = 1; $i < $primerDiaSemana; $i++)
                <div></div>
            @endfor

            @for($dia = 1; $dia <= $totalDias; $dia++)
                @php
                    $fechaStr = $mes->format('Y-m') . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                    $trabaja = $diasTrabajo[$fechaStr] ?? false;
                    $esHoy = $fechaStr === $hoy;

                    if ($trabaja) {
                        $bgColor = '#E8F5E9';
                        $textColor = '#2E7D32';
                        $icon = '&#10003;';
                    } else {
                        $bgColor = '#FFEBEE';
                        $textColor = '#C62828';
                        $icon = '&#10007;';
                    }

                    $borderStyle = $esHoy ? 'border: 3px solid #007AFF;' : '';
                @endphp
                <div style="background:{{ $bgColor }};border-radius:12px;text-align:center;padding:8px 4px;min-height:52px;display:flex;flex-direction:column;align-items:center;justify-content:center;{{ $borderStyle }}">
                    <span style="font-size:15px;font-weight:700;color:{{ $textColor }};">{{ $dia }}</span>
                    <span style="font-size:12px;">{!! $icon !!}</span>
                </div>
            @endfor
        </div>
    </div>

    {{-- Summary --}}
    <div class="d-flex justify-content-around mt-3 mb-4">
        <div style="background:#E8F5E9;border-radius:12px;padding:12px 20px;text-align:center;flex:1;margin:0 6px;">
            <div style="font-size:24px;font-weight:700;color:#2E7D32;">{{ $diasTrabajados }}</div>
            <div style="font-size:12px;color:#2E7D32;font-weight:600;">
                {{ $isAr ? "\u0623\u064A\u0627\u0645 \u0627\u0644\u0639\u0645\u0644" : 'Dias trabajados' }}
            </div>
        </div>
        <div style="background:#FFEBEE;border-radius:12px;padding:12px 20px;text-align:center;flex:1;margin:0 6px;">
            <div style="font-size:24px;font-weight:700;color:#C62828;">{{ $diasLibres }}</div>
            <div style="font-size:12px;color:#C62828;font-weight:600;">
                {{ $isAr ? "\u0623\u064A\u0627\u0645 \u0627\u0644\u0631\u0627\u062D\u0629" : 'Dias libres' }}
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div style="background:#fff;border-radius:12px;padding:12px 16px;box-shadow:0 1px 6px rgba(0,0,0,0.06);">
        <div class="d-flex align-items-center mb-2">
            <span style="display:inline-block;width:16px;height:16px;background:#E8F5E9;border-radius:4px;margin-{{ $isAr ? 'left' : 'right' }}:8px;"></span>
            <span style="font-size:13px;color:#333;">{{ $isAr ? "\u064A\u0648\u0645 \u0639\u0645\u0644" : 'Dia de trabajo' }}</span>
        </div>
        <div class="d-flex align-items-center mb-2">
            <span style="display:inline-block;width:16px;height:16px;background:#FFEBEE;border-radius:4px;margin-{{ $isAr ? 'left' : 'right' }}:8px;"></span>
            <span style="font-size:13px;color:#333;">{{ $isAr ? "\u064A\u0648\u0645 \u0631\u0627\u062D\u0629" : 'Dia libre' }}</span>
        </div>
        <div class="d-flex align-items-center">
            <span style="display:inline-block;width:16px;height:16px;border:3px solid #007AFF;border-radius:4px;margin-{{ $isAr ? 'left' : 'right' }}:8px;"></span>
            <span style="font-size:13px;color:#333;">{{ $isAr ? "\u0627\u0644\u064A\u0648\u0645" : 'Hoy' }}</span>
        </div>
    </div>
</div>
@endsection
