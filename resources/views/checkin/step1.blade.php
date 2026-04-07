<x-checkin-layout :token="$token">
    <h1>{{ __('Paso 1 - Toma de datos') }}</h1>

    @if(isset($reserva) && $reserva)
    <div class="alert-info">
        <strong>{{ $reserva->apartamento->titulo ?? '' }}</strong>
        &nbsp;&middot;&nbsp;
        {{ __('Entrada') }}: {{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : '' }}
        &nbsp;&middot;&nbsp;
        {{ __('Salida') }}: {{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : '' }}
    </div>
    @endif

    <div id="upload-form">
        <div class="file-upload-wrapper" id="front-wrapper">
            <input type="file" id="dni_front" accept="image/*">
            <div class="file-upload-text" id="front-text">{{ __('Foto DNI parte frontal') }}</div>
        </div>

        <div class="file-upload-wrapper" id="back-wrapper">
            <input type="file" id="dni_back" accept="image/*">
            <div class="file-upload-text" id="back-text">{{ __('Foto DNI parte trasera (opcional)') }}</div>
        </div>

        <button type="button" class="btn" id="submit-btn" disabled>{{ __('Siguiente') }}</button>
    </div>

    <div id="loader" style="display: none; text-align: center; padding: 40px 0;">
        <h2 id="loader-text" style="color: var(--text-muted);">{{ __('Procesando...') }}</h2>
    </div>

    <script>
        var frontInput = document.getElementById('dni_front');
        var backInput = document.getElementById('dni_back');
        var frontText = document.getElementById('front-text');
        var backText = document.getElementById('back-text');
        var submitBtn = document.getElementById('submit-btn');

        function updateUI() {
            if (frontInput.files.length > 0) {
                frontText.textContent = frontInput.files[0].name;
                frontText.style.color = "green";
                submitBtn.disabled = false;
            }
            if (backInput.files.length > 0) {
                backText.textContent = backInput.files[0].name;
                backText.style.color = "green";
            }
        }

        frontInput.addEventListener('change', updateUI);
        backInput.addEventListener('change', updateUI);

        function resizeImage(file, maxDim, callback) {
            if (!file || !file.type.match(/image.*/)) return callback(file, false);
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var w = img.width, h = img.height;
                    if (w <= maxDim && h <= maxDim) return callback(file, false);
                    if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
                    else { w = Math.round((w * maxDim) / h); h = maxDim; }
                    var c = document.createElement('canvas');
                    c.width = w; c.height = h;
                    c.getContext('2d').drawImage(img, 0, 0, w, h);
                    if (c.toBlob) { c.toBlob(function(b) { callback(b || file, !!b); }, 'image/jpeg', 0.85); }
                    else callback(file, false);
                };
                img.onerror = function() { callback(file, false); };
                img.src = e.target.result;
            };
            reader.onerror = function() { callback(file, false); };
            reader.readAsDataURL(file);
        }

        function restoreUI() {
            document.getElementById('upload-form').style.display = 'block';
            document.getElementById('loader').style.display = 'none';
        }

        submitBtn.addEventListener('click', function() {
            if (frontInput.files.length === 0) return;
            document.getElementById('upload-form').style.display = 'none';
            document.getElementById('loader').style.display = 'block';
            var loaderText = document.getElementById('loader-text');
            loaderText.innerText = '{{ __("Reescalando imagen...") }}';

            var formData = new FormData();

            var sendData = function() {
                loaderText.innerText = '{{ __("Subiendo y procesando...") }}';
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '/checkin/{{ $token }}/process', true);
                var csrf = document.querySelector('meta[name="csrf-token"]');
                if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf.content);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        var result;
                        try { result = JSON.parse(xhr.responseText); } catch(e) {
                            alert('{{ __("Error del servidor, por favor intente de nuevo.") }}');
                            restoreUI(); return;
                        }
                        if (result.success || result.data) {
                            sessionStorage.setItem('ai_extracted_data', JSON.stringify(result.data || {}));
                            window.location.href = '/checkin/{{ $token }}/form';
                        } else { restoreUI(); alert('{{ __("Error procesando la imagen.") }}'); }
                    } else if (xhr.status === 419) {
                        restoreUI();
                        alert('{{ __("Tu sesión ha caducado. La página se recargará.") }}');
                        window.location.reload();
                    } else if (xhr.status === 422) {
                        restoreUI();
                        try {
                            var err = JSON.parse(xhr.responseText);
                            alert('{{ __("Error de validación:") }} ' + (err.message || ''));
                        } catch(e) { alert('{{ __("Error de validación en la imagen.") }}'); }
                    } else {
                        restoreUI();
                        alert('{{ __("Error de red o servidor.") }} (' + xhr.status + ')');
                    }
                };

                xhr.onerror = function() { restoreUI(); alert('{{ __("Error de conexión.") }}'); };
                xhr.send(formData);
            };

            resizeImage(frontInput.files[0], 1920, function(frontBlob, isResized) {
                var frontName = frontInput.files[0].name;
                if (isResized && frontBlob.type === 'image/jpeg' && !frontName.toLowerCase().match(/\.(jpg|jpeg)$/)) {
                    frontName = frontName.replace(/\.[^/.]+$/, "") + ".jpg";
                }
                formData.append('dni_front', frontBlob, frontName);

                if (backInput.files.length > 0) {
                    resizeImage(backInput.files[0], 1920, function(backBlob, isBackResized) {
                        var backName = backInput.files[0].name;
                        if (isBackResized && backBlob.type === 'image/jpeg' && !backName.toLowerCase().match(/\.(jpg|jpeg)$/)) {
                            backName = backName.replace(/\.[^/.]+$/, "") + ".jpg";
                        }
                        formData.append('dni_back', backBlob, backName);
                        sendData();
                    });
                } else {
                    sendData();
                }
            });
        });
    </script>
</x-checkin-layout>
