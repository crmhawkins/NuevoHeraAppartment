<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hawkins Suites - Acceso Limpiadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 50%, #155e75 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-card h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0e7490;
            text-align: center;
            margin-bottom: 5px;
        }
        .login-card p {
            font-size: 13px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 25px;
        }
        .login-card label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .login-card .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            border: 2px solid #e5e7eb;
        }
        .login-card .form-control:focus {
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8,145,178,0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover { background: linear-gradient(135deg, #0e7490, #155e75); color: white; }
        .error-msg {
            background: #fef2f2;
            color: #dc2626;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 15px;
            text-align: center;
        }
        .brand { text-align: center; margin-bottom: 20px; }
        .brand span { font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <h1>HAWKINS SUITES</h1>
            <span>Panel de Limpieza</span>
        </div>
        <p>Introduce tu nombre y contraseña</p>

        @if($errors->any())
            <div class="error-msg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('limpiadora.login.post') }}">
            @csrf
            <div class="mb-3">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" placeholder="Tu nombre" value="{{ old('nombre') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn btn-login">Entrar</button>
        </form>
    </div>
</body>
</html>
