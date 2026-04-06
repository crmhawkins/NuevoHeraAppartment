<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Reservas</title>
</head>
<body>
    <h1>Importar Reservas desde CSV</h1>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif

    <form action="{{ route('importarReservasDesdeCsv') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="archivo">Seleccionar archivo CSV:</label>
        <input type="file" name="archivo" id="archivo" required>
        <button type="submit">Subir y Procesar</button>
    </form>
</body>
</html>
