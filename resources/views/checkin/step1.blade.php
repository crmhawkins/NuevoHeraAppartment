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

    {{-- Selector de tipo de documento --}}
    <div id="doc-type-selector" style="margin-bottom: 20px;">
        <p style="margin-bottom: 10px; color: var(--text-muted); font-size: 14px;">{{ __('Selecciona tu documento') }}</p>
        <div style="display: flex; gap: 10px;">
            <button type="button" id="btn-dni" onclick="selectDocType('dni')"
                style="flex: 1; padding: 14px 10px; border: 2px solid var(--primary, #003580); border-radius: 10px; background: var(--primary, #003580); color: white; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                {{ __('DNI / NIE') }}
            </button>
            <button type="button" id="btn-passport" onclick="selectDocType('passport')"
                style="flex: 1; padding: 14px 10px; border: 2px solid #ddd; border-radius: 10px; background: white; color: #333; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                {{ __('Pasaporte') }}
            </button>
        </div>
    </div>

    <div id="upload-form">
        {{-- Fotos DNI (frontal + reverso) --}}
        <div id="dni-uploads">
            <div class="file-upload-wrapper" id="front-wrapper">
                <input type="file" id="dni_front" accept="image/*" capture="environment">
                <div class="file-upload-text" id="front-text">
                    <span id="front-label">{{ __('Foto parte frontal') }}</span>
                </div>
            </div>

            <div class="file-upload-wrapper" id="back-wrapper">
                <input type="file" id="dni_back" accept="image/*" capture="environment">
                <div class="file-upload-text" id="back-text">{{ __('Foto parte trasera') }}</div>
            </div>
        </div>

        {{-- Foto pasaporte (solo 1) --}}
        <div id="passport-uploads" style="display: none;">
            <div class="file-upload-wrapper" id="passport-wrapper">
                <input type="file" id="passport_front" accept="image/*" capture="environment">
                <div class="file-upload-text" id="passport-text">{{ __('Foto de la pagina con tus datos') }}</div>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); text-align: center; margin-top: 8px;">
                {{ __('Abre tu pasaporte por la pagina donde aparece tu foto y datos personales') }}
            </p>
        </div>

        {{-- Barra de progreso --}}
        <div id="progress-bar" style="display: none; margin: 16px 0;">
            <div style="background: #e9ecef; border-radius: 8px; height: 8px; overflow: hidden;">
                <div id="progress-fill" style="background: var(--primary, #003580); height: 100%; width: 0%; transition: width 0.5s ease; border-radius: 8px;"></div>
            </div>
            <p id="progress-text" style="text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 8px;"></p>
        </div>

        <button type="button" class="btn" id="submit-btn" disabled>{{ __('Siguiente') }}</button>
    </div>

    <div id="loader" style="display: none; text-align: center; padding: 40px 0;">
        <div style="margin-bottom: 20px;">
            <div style="width: 50px; height: 50px; border: 4px solid #e9ecef; border-top: 4px solid var(--primary, #003580); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
        </div>
        <h2 id="loader-text" style="color: var(--text-muted); font-size: 18px;">{{ __('Procesando...') }}</h2>
        <div id="loader-progress" style="max-width: 300px; margin: 16px auto 0;">
            <div style="background: #e9ecef; border-radius: 8px; height: 8px; overflow: hidden;">
                <div id="loader-progress-fill" style="background: var(--primary, #003580); height: 100%; width: 0%; transition: width 0.5s ease; border-radius: 8px;"></div>
            </div>
        </div>
        <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
    </div>

    <input type="hidden" id="selected-doc-type" value="dni">

    <script>
        var docType = 'dni';
        var frontInput = document.getElementById('dni_front');
        var backInput = document.getElementById('dni_back');
        var passportInput = document.getElementById('passport_front');
        var submitBtn = document.getElementById('submit-btn');

        function selectDocType(type) {
            docType = type;
            document.getElementById('selected-doc-type').value = type;
            var btnDni = document.getElementById('btn-dni');
            var btnPass = document.getElementById('btn-passport');
            var dniUploads = document.getElementById('dni-uploads');
            var passUploads = document.getElementById('passport-uploads');

            if (type === 'dni') {
                btnDni.style.background = 'var(--primary, #003580)';
                btnDni.style.color = 'white';
                btnDni.style.borderColor = 'var(--primary, #003580)';
                btnPass.style.background = 'white';
                btnPass.style.color = '#333';
                btnPass.style.borderColor = '#ddd';
                dniUploads.style.display = 'block';
                passUploads.style.display = 'none';
            } else {
                btnPass.style.background = 'var(--primary, #003580)';
                btnPass.style.color = 'white';
                btnPass.style.borderColor = 'var(--primary, #003580)';
                btnDni.style.background = 'white';
                btnDni.style.color = '#333';
                btnDni.style.borderColor = '#ddd';
                dniUploads.style.display = 'none';
                passUploads.style.display = 'block';
            }
            // Reset files
            frontInput.value = ''; backInput.value = '';
            if (passportInput) passportInput.value = '';
            submitBtn.disabled = true;
            updateUI();
        }

        function updateUI() {
            if (docType === 'dni') {
                submitBtn.disabled = !frontInput.files.length;
                if (frontInput.files.length) {
                    document.getElementById('front-text').textContent = '✅ ' + frontInput.files[0].name;
                    document.getElementById('front-text').style.color = 'green';
                }
                if (backInput.files.length) {
                    document.getElementById('back-text').textContent = '✅ ' + backInput.files[0].name;
                    document.getElementById('back-text').style.color = 'green';
                }
            } else {
                submitBtn.disabled = !passportInput.files.length;
                if (passportInput.files.length) {
                    document.getElementById('passport-text').textContent = '✅ ' + passportInput.files[0].name;
                    document.getElementById('passport-text').style.color = 'green';
                }
            }
        }

        frontInput.addEventListener('change', updateUI);
        backInput.addEventListener('change', updateUI);
        if (passportInput) passportInput.addEventListener('change', updateUI);

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
            document.getElementById('progress-bar').style.display = 'none';
        }

        function setProgress(pct, text) {
            document.getElementById('loader').style.display = 'block';
            document.getElementById('loader-text').textContent = text;
            document.getElementById('loader-progress-fill').style.width = pct + '%';
        }

        submitBtn.addEventListener('click', function() {
            var formData = new FormData();
            formData.append('doc_type', docType);

            document.getElementById('upload-form').style.display = 'none';
            document.getElementById('loader').style.display = 'block';

            if (docType === 'passport') {
                // Pasaporte: solo 1 foto
                setProgress(10, '{{ __("Reescalando imagen...") }}');
                resizeImage(passportInput.files[0], 1920, function(resized) {
                    formData.append('dni_front', resized, 'passport.jpg');
                    setProgress(30, '{{ __("Subiendo y analizando pasaporte...") }}');
                    sendData(formData);
                });
            } else {
                // DNI: frontal obligatorio + reverso opcional
                setProgress(10, '{{ __("Reescalando imagen...") }}');
                resizeImage(frontInput.files[0], 1920, function(resizedFront) {
                    formData.append('dni_front', resizedFront, 'front.jpg');
                    if (backInput.files.length > 0) {
                        resizeImage(backInput.files[0], 1920, function(resizedBack) {
                            formData.append('dni_back', resizedBack, 'back.jpg');
                            setProgress(30, '{{ __("Subiendo y analizando documento...") }}');
                            sendData(formData);
                        });
                    } else {
                        setProgress(30, '{{ __("Subiendo y analizando documento...") }}');
                        sendData(formData);
                    }
                });
            }
        });

        function sendData(formData) {
            setProgress(50, '{{ __("Extrayendo datos con IA...") }}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/checkin/{{ $token }}/process', true);
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf.content);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var pct = 30 + Math.round((e.loaded / e.total) * 30);
                    setProgress(pct, '{{ __("Subiendo...") }} ' + Math.round(e.loaded/e.total*100) + '%');
                }
            };

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    setProgress(90, '{{ __("Datos extraidos, preparando formulario...") }}');
                    var result;
                    try { result = JSON.parse(xhr.responseText); } catch(e) {
                        alert('{{ __("Error del servidor, por favor intente de nuevo.") }}');
                        restoreUI(); return;
                    }
                    if (result.success || result.data) {
                        sessionStorage.setItem('ai_extracted_data', JSON.stringify(result.data || {}));
                        sessionStorage.setItem('doc_type', docType);
                        setProgress(100, '{{ __("Listo!") }}');
                        setTimeout(function() {
                            window.location.href = '/checkin/{{ $token }}/form';
                        }, 300);
                    } else { restoreUI(); alert('{{ __("Error procesando la imagen.") }}'); }
                } else if (xhr.status === 419) {
                    restoreUI();
                    alert('{{ __("Tu sesion ha caducado. La pagina se recargara.") }}');
                    window.location.reload();
                } else if (xhr.status === 422) {
                    restoreUI();
                    try {
                        var err = JSON.parse(xhr.responseText);
                        alert('{{ __("Error de validacion:") }} ' + (err.message || ''));
                    } catch(e) { alert('{{ __("Error de validacion en la imagen.") }}'); }
                } else {
                    restoreUI();
                    alert('{{ __("Error de red o servidor.") }} (' + xhr.status + ')');
                }
            };

            xhr.onerror = function() {
                restoreUI();
                alert('{{ __("Error de conexion. Comprueba tu conexion a internet.") }}');
            };

            xhr.send(formData);
        }
    </script>
</x-checkin-layout>
