<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            color: #dc3545;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .error-message {
            color: #6c757d;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            text-align: left;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚨</div>
        <h1 class="error-title">Error en la Aplicación</h1>
        <div class="error-message">{{ $message ?? 'Error desconocido' }}</div>
        <a href="javascript:history.back()" class="back-button">Volver Atrás</a>
    </div>
</body>
</html>
