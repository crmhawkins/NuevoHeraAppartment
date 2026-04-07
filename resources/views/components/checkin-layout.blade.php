<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hawkins Check-In</title>
    <style>
        :root {
            --primary: #111827;
            --surface: #ffffff;
            --background: #f3f4f6;
            --border: #d1d5db;
            --text-main: #111827;
            --text-muted: #6b7280;
            --error: #ef4444;
            --focus: #3b82f6;
            --border-radius: 12px;
        }

        * { box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--background);
            color: var(--text-main);
            font-size: 18px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .logo { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }

        .lang-switch select {
            padding: 10px;
            font-size: 16px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            background: #f9fafb;
        }

        main { padding: 24px; flex-grow: 1; }

        h1 { font-size: 28px; margin-top: 0; margin-bottom: 24px; }

        .form-group { margin-bottom: 24px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 16px; }

        input[type="text"], input[type="email"], input[type="date"], input[type="tel"], select, textarea {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            background: #fff;
            appearance: none;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: #fff;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            margin-top: 16px;
            transition: opacity 0.2s;
        }

        .btn:active { opacity: 0.8; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: #e5e7eb; color: var(--text-main); }

        .file-upload-wrapper {
            border: 2px dashed var(--border);
            border-radius: var(--border-radius);
            padding: 30px 20px;
            text-align: center;
            background: #f9fafb;
            margin-bottom: 24px;
            position: relative;
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        .file-upload-text { font-size: 18px; font-weight: 600; color: var(--text-muted); }

        .error-message { color: var(--error); font-size: 14px; margin-top: 6px; font-weight: 500; }
        .alert-error { background: #fef2f2; color: var(--error); padding: 16px; border-radius: var(--border-radius); margin-bottom: 24px; border: 1px solid #fee2e2; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 15px; color: #1e40af; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">HAWKINS <span style="font-size: 10px; color: var(--border); font-weight: normal; vertical-align: top; margin-left: 4px;">v2.0</span></div>
            @if(isset($token))
            <div class="lang-switch">
                <select onchange="window.location.href='/checkin/{{ $token }}/lang/'+this.value">
                    <option value="es" {{ app()->getLocale() == 'es' ? 'selected' : '' }}>ES</option>
                    <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>EN</option>
                </select>
            </div>
            @endif
        </header>

        <main>
            @if(session('error'))
                <div class="alert-error">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>
