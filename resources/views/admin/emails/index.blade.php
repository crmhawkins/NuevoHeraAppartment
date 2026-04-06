@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Bandeja de Entrada</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 col-sm-12">
                    <div class="card-body">
                        <div class="bg-success w-auto p-2 rounded text-white text-center justify-content-center d-flex align-items-center" style="max-width: 60px; min-height:60px; cursor: pointer;">
                            <i class="fa-solid fa-plus fs-5"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-10 col-sm-12">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col"></th>
                                <th scope="col">Sender</th>
                                <th scope="col">Contenido</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($emails as $email)
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- Si tienes una imagen o icono asociado -->
                                        {{-- <img src="https://via.placeholder.com/50" class="rounded-circle me-2" alt="User Image"> --}}
                                        <div>{{ $email->sender }}</div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.emails.show', $email->id) }}" class="text-decoration-none">
                                        {{-- Imprimir carateres especiales --}}
                                        {{-- =?us-ascii?Q?[Wordfence_Alert]_Problems_found_on_ontarifa.co?= =?us-ascii?Q?m?= --}}
                                        @php
                                            $subject = str_replace('[', '&#91;', $email->subject);
                                            $subject = str_replace(']', '&#93;', $subject);
                                            $textoDecodificado = mb_decode_mimeheader($email->subject);

                                            echo $textoDecodificado;
                                        @endphp
                                            {{ $email->subject }}

                                        {{-- @if (strpos($email->subject, '[') !== false)
                                        @else
                                        @endif --}}
                                        {{-- {{ $email->subject }} --}}
                                    </a>
                                </td>
                                <td>
                                    @if (isset($email->category->name))
                                        {{$email->category->name}}
                                    @else
                                        <span class="badge bg-danger">Sin categoria</span>
                                    @endif
                                </td>
                                <td>
                                    @if (isset($email->status->name))
                                        <!-- Mostrar estado con colores o etiquetas -->
                                        <span class="badge bg-{{ $email->status->color }}">{{ $email->status->name }}</span>
                                    @else
                                        <span class="badge bg-danger">Sin estado</span>
                                        
                                    @endif
                                    <!-- Mostrar estado con colores o etiquetas -->
                                    {{-- <span class="badge bg-{{ $email->status->color }}">{{ $email->status->name }}</span> --}}
                                </td>
                                <td>{{ $email->created_at->format('g:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>    
                </div>
            </div>
            

            <!-- Paginación -->
            {{ $emails->links() }}
        </div>
    </div>
</div>
@endsection
