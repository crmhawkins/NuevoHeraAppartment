<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Subir factura - Apartamentos Algeciras</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #333;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,.2);
            padding: 32px 24px;
            max-width: 420px;
            width: 100%;
        }
        h1 {
            font-size: 22px;
            margin-bottom: 8px;
            color: #1a202c;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-camera {
            display: block;
            width: 100%;
            padding: 20px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, background 0.2s;
            margin-bottom: 12px;
            text-align: center;
        }
        .btn-camera:hover { background: #5a67d8; }
        .btn-camera:active { transform: scale(0.98); }
        .btn-gallery {
            display: block;
            width: 100%;
            padding: 16px;
            background: #fff;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 16px;
            text-align: center;
        }
        input[type="file"] { display: none; }
        .preview {
            display: none;
            margin-top: 16px;
            text-align: center;
        }
        .preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .preview-name {
            font-size: 13px;
            color: #718096;
            margin-top: 8px;
            word-break: break-all;
        }
        .btn-send {
            display: block;
            width: 100%;
            padding: 16px;
            background: #48bb78;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: background 0.2s;
        }
        .btn-send:hover { background: #38a169; }
        .btn-send:disabled { background: #a0aec0; cursor: not-allowed; }
        .spinner {
            display: none;
            text-align: center;
            padding: 20px;
            color: #667eea;
        }
        .footer-note {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #a0aec0;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Subir factura</h1>
        <p class="subtitle">Haz una foto a la factura y se adjuntara al gasto automaticamente.</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $err) {{ $err }}<br> @endforeach
            </div>
        @endif

        <form id="uploadForm" method="POST" action="{{ route('facturas.subir.store', ['token' => $token]) }}" enctype="multipart/form-data">
            @csrf

            <label for="cameraInput" class="btn-camera">
                Hacer foto a la factura
            </label>
            <input id="cameraInput" type="file" name="factura" accept="image/*" capture="environment">

            <label for="galleryInput" class="btn-gallery">
                Elegir desde la galeria
            </label>
            <input id="galleryInput" type="file" name="factura_gallery" accept="image/*,application/pdf">

            <div id="preview" class="preview">
                <img id="previewImg" src="" alt="Previsualizacion">
                <div id="previewName" class="preview-name"></div>
                <button type="submit" class="btn-send" id="btnSend">Enviar factura</button>
            </div>

            <div id="spinner" class="spinner">
                Subiendo factura...
            </div>
        </form>

        <p class="footer-note">
            Apartamentos Algeciras<br>
            Tamano maximo: 10 MB. Formatos: JPG, PNG, WEBP, HEIC, PDF.
        </p>
    </div>

    <script>
        const cameraInput = document.getElementById('cameraInput');
        const galleryInput = document.getElementById('galleryInput');
        const preview = document.getElementById('preview');
        const previewImg = document.getElementById('previewImg');
        const previewName = document.getElementById('previewName');
        const form = document.getElementById('uploadForm');
        const btnSend = document.getElementById('btnSend');
        const spinner = document.getElementById('spinner');

        function handleFile(input) {
            const f = input.files[0];
            if (!f) return;

            // Reemplazar el input de la camara por el de galeria si hace falta
            // para que el POST lleve el campo correcto como 'factura'.
            if (input.id === 'galleryInput') {
                // Transferir el archivo al input principal
                cameraInput.files = input.files;
                input.value = '';
            }

            previewName.textContent = f.name + ' (' + (f.size / 1024).toFixed(0) + ' KB)';

            if (f.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => { previewImg.src = e.target.result; previewImg.style.display = 'block'; };
                reader.readAsDataURL(f);
            } else {
                previewImg.style.display = 'none';
            }
            preview.style.display = 'block';
        }

        cameraInput.addEventListener('change', () => handleFile(cameraInput));
        galleryInput.addEventListener('change', () => handleFile(galleryInput));

        form.addEventListener('submit', (e) => {
            btnSend.disabled = true;
            btnSend.textContent = 'Enviando...';
            spinner.style.display = 'block';
        });
    </script>
</body>
</html>
