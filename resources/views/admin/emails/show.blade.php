@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $email->subject }}</h1>

            <div class="mb-4">
                <strong>From:</strong> {{ $email->user->name }} <br>
                <strong>Category:</strong> {{ $email->category->name }} <br>
                <strong>Status:</strong> <span class="badge bg-{{ $email->status->color }}">{{ $email->status->name }}</span> <br>
                <strong>Received:</strong> {{ $email->created_at->format('F d, Y h:i A') }}
            </div>

            <div class="card">
                <div class="card-body">
                    <p>{{ $email->body }}</p>
                </div>
            </div>

            <!-- Botón para volver a la lista de correos -->
            <a href="{{ route('admin.emails.index') }}" class="btn btn-primary mt-3">Back to Inbox</a>
        </div>
    </div>
</div>
@endsection
