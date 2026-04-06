@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">Subir archivo CSV</h2>
    <hr class="mb-5">

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('upload.csvBooking') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="csv_file">Selecciona archivo CSV</label>
            <input type="file" class="form-control" name="csv_file" required>
        </div>
        <button type="submit" class="btn bg-color-primero">Subir y procesar CSV</button>
    </form>
</div>
@endsection
