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

    {{-- Guia visual (siempre visible) — instrucciones de como hacer la foto --}}
    <div id="guide-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
        <p style="font-weight: 600; margin: 0 0 10px 0; color: #333; font-size: 14px;">
            <span id="guide-icon">📷</span> <span id="guide-title">{{ __('Coloca tu DNI en HORIZONTAL') }}</span>
        </p>
        <div style="display: flex; justify-content: center; margin: 10px 0;">
            <svg id="guide-svg-dni" width="180" height="115" viewBox="0 0 180 115" xmlns="http://www.w3.org/2000/svg">
                <rect x="4" y="4" width="172" height="107" rx="8" fill="#fff" stroke="#003580" stroke-width="2.5" stroke-dasharray="6,4"/>
                <rect x="14" y="14" width="44" height="56" rx="3" fill="#dee2e6"/>
                <text x="36" y="46" font-family="Arial" font-size="10" fill="#888" text-anchor="middle">FOTO</text>
                <line x1="68" y1="22" x2="160" y2="22" stroke="#cfd4da" stroke-width="2"/>
                <line x1="68" y1="34" x2="160" y2="34" stroke="#cfd4da" stroke-width="2"/>
                <line x1="68" y1="46" x2="140" y2="46" stroke="#cfd4da" stroke-width="2"/>
                <line x1="14" y1="84" x2="160" y2="84" stroke="#cfd4da" stroke-width="2"/>
                <line x1="14" y1="96" x2="120" y2="96" stroke="#cfd4da" stroke-width="2"/>
            </svg>
            <svg id="guide-svg-passport" width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" style="display:none;">
                <rect x="4" y="4" width="112" height="112" rx="8" fill="#fff" stroke="#003580" stroke-width="2.5" stroke-dasharray="6,4"/>
                <rect x="20" y="14" width="40" height="50" rx="3" fill="#dee2e6"/>
                <text x="40" y="42" font-family="Arial" font-size="9" fill="#888" text-anchor="middle">FOTO</text>
                <line x1="68" y1="20" x2="105" y2="20" stroke="#cfd4da" stroke-width="2"/>
                <line x1="68" y1="32" x2="105" y2="32" stroke="#cfd4da" stroke-width="2"/>
                <line x1="68" y1="44" x2="100" y2="44" stroke="#cfd4da" stroke-width="2"/>
                <line x1="14" y1="78" x2="105" y2="78" stroke="#cfd4da" stroke-width="2"/>
                <line x1="14" y1="92" x2="80" y2="92" stroke="#cfd4da" stroke-width="2"/>
                <line x1="14" y1="105" x2="60" y2="105" stroke="#cfd4da" stroke-width="2"/>
            </svg>
        </div>
        <ul style="margin: 6px 0 0 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>{{ __('Llena el marco completo') }}</li>
            <li>{{ __('Buena iluminacion, sin sombras') }}</li>
            <li>{{ __('Evita reflejos y desenfoque') }}</li>
        </ul>
    </div>

    <div id="upload-form">
        {{-- Fotos DNI (frontal + reverso) --}}
        <div id="dni-uploads">
            <div class="capture-slot" data-slot="front" id="slot-front">
                <input type="file" id="dni_front" accept="image/*" capture="environment" hidden>
                <button type="button" class="capture-btn" id="btn-capture-front">
                    <span class="capture-icon">📷</span>
                    <span class="capture-label" id="front-label">{{ __('Foto parte FRONTAL') }}</span>
                </button>
                <div class="capture-preview" id="preview-front" style="display:none;">
                    <img alt="frontal">
                    <button type="button" class="retake-btn" data-target="front">{{ __('Repetir') }}</button>
                </div>
            </div>

            <div class="capture-slot" data-slot="back" id="slot-back">
                <input type="file" id="dni_back" accept="image/*" capture="environment" hidden>
                <button type="button" class="capture-btn" id="btn-capture-back">
                    <span class="capture-icon">📷</span>
                    <span class="capture-label">{{ __('Foto parte TRASERA') }}</span>
                </button>
                <div class="capture-preview" id="preview-back" style="display:none;">
                    <img alt="trasera">
                    <button type="button" class="retake-btn" data-target="back">{{ __('Repetir') }}</button>
                </div>
            </div>
        </div>

        {{-- Foto pasaporte (solo 1) --}}
        <div id="passport-uploads" style="display: none;">
            <div class="capture-slot" data-slot="passport" id="slot-passport">
                <input type="file" id="passport_front" accept="image/*" capture="environment" hidden>
                <button type="button" class="capture-btn" id="btn-capture-passport">
                    <span class="capture-icon">📷</span>
                    <span class="capture-label">{{ __('Foto de la pagina con tus datos') }}</span>
                </button>
                <div class="capture-preview" id="preview-passport" style="display:none;">
                    <img alt="pasaporte">
                    <button type="button" class="retake-btn" data-target="passport">{{ __('Repetir') }}</button>
                </div>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); text-align: center; margin-top: 8px;">
                {{ __('Abre tu pasaporte por la pagina donde aparece tu foto y datos personales') }}
            </p>
        </div>

        {{-- Aviso si la imagen capturada parece estar rotada --}}
        <div id="orientation-warn" style="display:none; background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:10px; border-radius:8px; margin-top:10px; font-size:13px;">
            ⚠️ <span>{{ __('La foto parece estar en vertical. Para mejor reconocimiento, gira el documento a horizontal y vuelve a hacer la foto.') }}</span>
            <div style="margin-top:8px;">
                <button type="button" id="btn-orient-retry" style="background:#664d03; color:white; border:none; padding:8px 14px; border-radius:6px; font-size:13px; cursor:pointer;">{{ __('Volver a hacer la foto') }}</button>
                <button type="button" id="btn-orient-ignore" style="background:transparent; color:#664d03; border:1px solid #664d03; padding:8px 14px; border-radius:6px; font-size:13px; cursor:pointer; margin-left:6px;">{{ __('Continuar igualmente') }}</button>
            </div>
        </div>

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

    {{-- ===== MODAL CAMARA WebRTC (solo se usa si getUserMedia disponible) ===== --}}
    <div id="camera-modal" style="display:none; position:fixed; inset:0; top:0; left:0; right:0; bottom:0; background:#000; z-index:9999;">
        <video id="camera-video" autoplay playsinline muted style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; background:#000;"></video>

        <div id="camera-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;">
            <svg id="overlay-svg" width="100%" height="100%" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="display:block;"></svg>
        </div>

        <div style="position:absolute; top:24px; left:0; right:0; text-align:center; pointer-events:none;">
            <span id="camera-guide-text" style="background:rgba(0,0,0,0.55); color:white; padding:8px 16px; border-radius:18px; font-size:14px; font-weight:500;">
                {{ __('Encuadra el documento dentro del marco') }}
            </span>
        </div>

        <div style="position:absolute; bottom:0; left:0; right:0; padding:24px 16px calc(env(safe-area-inset-bottom, 0px) + 24px); display:flex; justify-content:space-between; align-items:center; background:linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);">
            <button type="button" id="cam-btn-cancel" style="background:transparent; color:white; border:1px solid rgba(255,255,255,0.45); padding:10px 16px; border-radius:8px; font-size:14px;">
                {{ __('Cancelar') }}
            </button>
            <button type="button" id="cam-btn-shoot" aria-label="{{ __('Capturar') }}" style="width:74px; height:74px; border-radius:50%; background:white; border:5px solid rgba(255,255,255,0.55); cursor:pointer;"></button>
            <button type="button" id="cam-btn-switch" aria-label="{{ __('Cambiar camara') }}" style="background:transparent; color:white; border:1px solid rgba(255,255,255,0.45); padding:10px 12px; border-radius:8px; font-size:13px;">🔄</button>
        </div>

        <canvas id="camera-canvas" style="display:none;"></canvas>
    </div>

    <input type="hidden" id="selected-doc-type" value="dni">

    <style>
        .capture-slot { margin-bottom: 12px; }
        .capture-btn {
            width: 100%; padding: 16px 12px;
            border: 2px dashed #ced4da; background: #f8f9fa; color: #333;
            border-radius: 10px; font-size: 15px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            cursor: pointer; transition: all 0.2s;
        }
        .capture-btn:hover, .capture-btn:focus { background:#e9ecef; border-color:#adb5bd; }
        .capture-btn:disabled { opacity:0.5; cursor:not-allowed; }
        .capture-icon { font-size: 22px; }
        .capture-preview { position:relative; margin-top:8px; }
        .capture-preview img { width:100%; border-radius:8px; border:1px solid #dee2e6; max-height:240px; object-fit:cover; }
        .retake-btn { position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.7); color:white; border:none; padding:6px 12px; border-radius:6px; font-size:12px; cursor:pointer; }
        .capture-slot.has-image .capture-btn { display:none; }
        .capture-slot.has-image .capture-preview { display:block !important; }
    </style>

    <script>
    (function() {
        // Estado
        var docType = 'dni';
        var slots = {
            front:    { input: document.getElementById('dni_front'),     btn: document.getElementById('btn-capture-front'),    previewBox: document.getElementById('preview-front'),    blob: null, required: true },
            back:     { input: document.getElementById('dni_back'),      btn: document.getElementById('btn-capture-back'),     previewBox: document.getElementById('preview-back'),     blob: null, required: false },
            passport: { input: document.getElementById('passport_front'),btn: document.getElementById('btn-capture-passport'), previewBox: document.getElementById('preview-passport'), blob: null, required: true }
        };
        var submitBtn = document.getElementById('submit-btn');
        var orientationWarn = document.getElementById('orientation-warn');
        var pendingOrientationSlot = null;

        // ===== FEATURE DETECTION =====
        var hasGetUserMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        var isSecure = window.isSecureContext === true || ['localhost','127.0.0.1'].indexOf(location.hostname) !== -1;
        var canUseInBrowserCamera = hasGetUserMedia && isSecure;

        // Si la camara no esta disponible (HTTP, navegador antiguo, webview limitado),
        // todos los botones disparan directamente <input capture=environment> que abre
        // la camara nativa. Compatibilidad MAXIMA garantizada.

        // ===== SELECT DOC TYPE =====
        window.selectDocType = function(type) {
            docType = type;
            document.getElementById('selected-doc-type').value = type;
            var btnDni = document.getElementById('btn-dni');
            var btnPass = document.getElementById('btn-passport');
            var dniUploads = document.getElementById('dni-uploads');
            var passUploads = document.getElementById('passport-uploads');
            var svgDni = document.getElementById('guide-svg-dni');
            var svgPass = document.getElementById('guide-svg-passport');
            var guideTitle = document.getElementById('guide-title');

            if (type === 'dni') {
                btnDni.style.background = 'var(--primary, #003580)'; btnDni.style.color = 'white'; btnDni.style.borderColor = 'var(--primary, #003580)';
                btnPass.style.background = 'white'; btnPass.style.color = '#333'; btnPass.style.borderColor = '#ddd';
                dniUploads.style.display = 'block'; passUploads.style.display = 'none';
                if (svgDni) svgDni.style.display = ''; if (svgPass) svgPass.style.display = 'none';
                if (guideTitle) guideTitle.textContent = "{{ __('Coloca tu DNI en HORIZONTAL') }}";
            } else {
                btnPass.style.background = 'var(--primary, #003580)'; btnPass.style.color = 'white'; btnPass.style.borderColor = 'var(--primary, #003580)';
                btnDni.style.background = 'white'; btnDni.style.color = '#333'; btnDni.style.borderColor = '#ddd';
                dniUploads.style.display = 'none'; passUploads.style.display = 'block';
                if (svgDni) svgDni.style.display = 'none'; if (svgPass) svgPass.style.display = '';
                if (guideTitle) guideTitle.textContent = "{{ __('Coloca tu pasaporte abierto') }}";
            }
            for (var k in slots) { slots[k].blob = null; if (slots[k].input) slots[k].input.value = ''; resetSlotUI(k); }
            updateSubmitState();
            hideOrientationWarn();
        };

        function resetSlotUI(key) {
            var slotEl = document.getElementById('slot-' + key);
            if (slotEl) slotEl.classList.remove('has-image');
            if (slots[key] && slots[key].previewBox) slots[key].previewBox.style.display = 'none';
        }

        function updateSubmitState() {
            submitBtn.disabled = (docType === 'dni') ? !slots.front.blob : !slots.passport.blob;
        }

        // ===== BIND BOTONES CAPTURA =====
        function bindSlot(key) {
            var slot = slots[key];
            if (!slot.btn) return;
            slot.btn.addEventListener('click', function() {
                if (canUseInBrowserCamera) {
                    openCameraModal(key);
                } else {
                    slot.input.click();
                }
            });
            slot.input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    handleCapturedFile(key, e.target.files[0]);
                }
            });
        }
        bindSlot('front'); bindSlot('back'); bindSlot('passport');

        // Botones repetir
        var retakeBtns = document.querySelectorAll('.retake-btn');
        for (var i = 0; i < retakeBtns.length; i++) {
            (function(btn){
                btn.addEventListener('click', function() {
                    var key = btn.getAttribute('data-target');
                    slots[key].blob = null;
                    if (slots[key].input) slots[key].input.value = '';
                    resetSlotUI(key); updateSubmitState();
                    if (canUseInBrowserCamera) openCameraModal(key);
                    else slots[key].input.click();
                });
            })(retakeBtns[i]);
        }

        // ===== PROCESAR CAPTURA (validacion + preview) =====
        function handleCapturedFile(key, file) {
            var slot = slots[key];
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function() {
                var ratio = img.width / img.height;
                var rotada = false;
                if (key === 'front' || key === 'back') {
                    if (ratio < 0.85) rotada = true;
                } else if (key === 'passport') {
                    if (ratio < 0.7) rotada = true;
                }
                slot.blob = file;
                showPreview(key, url);
                updateSubmitState();
                if (rotada) showOrientationWarn(key);
                else hideOrientationWarn();
            };
            img.onerror = function() {
                slot.blob = file; showPreview(key, url); updateSubmitState(); hideOrientationWarn();
            };
            img.src = url;
        }

        function showPreview(key, url) {
            var slot = slots[key];
            if (!slot.previewBox) return;
            var img = slot.previewBox.querySelector('img');
            if (img) img.src = url;
            slot.previewBox.style.display = 'block';
            var slotEl = document.getElementById('slot-' + key);
            if (slotEl) slotEl.classList.add('has-image');
        }

        function showOrientationWarn(slot) { pendingOrientationSlot = slot; orientationWarn.style.display = 'block'; }
        function hideOrientationWarn() { pendingOrientationSlot = null; orientationWarn.style.display = 'none'; }

        var btnOrientRetry = document.getElementById('btn-orient-retry');
        var btnOrientIgnore = document.getElementById('btn-orient-ignore');
        if (btnOrientRetry) btnOrientRetry.addEventListener('click', function() {
            if (!pendingOrientationSlot) return;
            var key = pendingOrientationSlot;
            slots[key].blob = null;
            if (slots[key].input) slots[key].input.value = '';
            resetSlotUI(key); updateSubmitState(); hideOrientationWarn();
            if (canUseInBrowserCamera) openCameraModal(key);
            else slots[key].input.click();
        });
        if (btnOrientIgnore) btnOrientIgnore.addEventListener('click', hideOrientationWarn);

        // ===== CAMARA WebRTC =====
        var camera = {
            stream: null,
            video: document.getElementById('camera-video'),
            modal: document.getElementById('camera-modal'),
            canvas: document.getElementById('camera-canvas'),
            svg: document.getElementById('overlay-svg'),
            currentSlot: null,
            facingMode: 'environment'
        };

        function openCameraModal(slotKey) {
            camera.currentSlot = slotKey;
            renderOverlay(slotKey);
            camera.modal.style.display = 'block';
            startCameraStream();
        }

        function renderOverlay(slotKey) {
            var aspectTarget = (slotKey === 'passport') ? 1.4 : 1.58;
            var W = 100, H = 160;
            var marginX = 5;
            var rectW = W - 2 * marginX;
            var rectH = rectW / aspectTarget;
            var rectX = marginX;
            var rectY = (H - rectH) / 2;
            camera.svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
            camera.svg.innerHTML = ''
                + '<defs>'
                +   '<mask id="cutout">'
                +     '<rect width="100%" height="100%" fill="white"/>'
                +     '<rect x="' + rectX + '" y="' + rectY + '" width="' + rectW + '" height="' + rectH + '" rx="2" fill="black"/>'
                +   '</mask>'
                + '</defs>'
                + '<rect width="100%" height="100%" fill="rgba(0,0,0,0.5)" mask="url(#cutout)"/>'
                + '<rect x="' + rectX + '" y="' + rectY + '" width="' + rectW + '" height="' + rectH + '" rx="2" fill="none" stroke="#00d27a" stroke-width="0.5"/>'
                + cornerMarker(rectX, rectY, 'TL') + cornerMarker(rectX + rectW, rectY, 'TR')
                + cornerMarker(rectX, rectY + rectH, 'BL') + cornerMarker(rectX + rectW, rectY + rectH, 'BR');
        }

        function cornerMarker(x, y, pos) {
            var s = 4;
            switch (pos) {
                case 'TL': return '<line x1="'+x+'" y1="'+y+'" x2="'+(x+s)+'" y2="'+y+'" stroke="#00d27a" stroke-width="1.2"/><line x1="'+x+'" y1="'+y+'" x2="'+x+'" y2="'+(y+s)+'" stroke="#00d27a" stroke-width="1.2"/>';
                case 'TR': return '<line x1="'+x+'" y1="'+y+'" x2="'+(x-s)+'" y2="'+y+'" stroke="#00d27a" stroke-width="1.2"/><line x1="'+x+'" y1="'+y+'" x2="'+x+'" y2="'+(y+s)+'" stroke="#00d27a" stroke-width="1.2"/>';
                case 'BL': return '<line x1="'+x+'" y1="'+y+'" x2="'+(x+s)+'" y2="'+y+'" stroke="#00d27a" stroke-width="1.2"/><line x1="'+x+'" y1="'+y+'" x2="'+x+'" y2="'+(y-s)+'" stroke="#00d27a" stroke-width="1.2"/>';
                case 'BR': return '<line x1="'+x+'" y1="'+y+'" x2="'+(x-s)+'" y2="'+y+'" stroke="#00d27a" stroke-width="1.2"/><line x1="'+x+'" y1="'+y+'" x2="'+x+'" y2="'+(y-s)+'" stroke="#00d27a" stroke-width="1.2"/>';
            }
            return '';
        }

        function startCameraStream() {
            stopCameraStream();
            var constraints = {
                video: { facingMode: { ideal: camera.facingMode }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            };
            navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
                camera.stream = stream;
                camera.video.srcObject = stream;
                var p = camera.video.play();
                if (p && p.catch) p.catch(function(){});
            }).catch(function(err) {
                // Fallback automatico al input file
                console.warn('[Camera] getUserMedia fallo:', err && err.name);
                closeCameraModal();
                if (camera.currentSlot && slots[camera.currentSlot]) {
                    slots[camera.currentSlot].input.click();
                }
            });
        }

        function stopCameraStream() {
            if (camera.stream) {
                camera.stream.getTracks().forEach(function(t) { try { t.stop(); } catch(e){} });
                camera.stream = null;
            }
            try { camera.video.srcObject = null; } catch(e) {}
        }

        function closeCameraModal() {
            stopCameraStream();
            camera.modal.style.display = 'none';
        }

        function shootCamera() {
            if (!camera.stream) return;
            var v = camera.video, c = camera.canvas;
            var w = v.videoWidth, h = v.videoHeight;
            if (!w || !h) {
                alert('{{ __("La camara no esta lista. Intenta de nuevo.") }}');
                return;
            }
            c.width = w; c.height = h;
            c.getContext('2d').drawImage(v, 0, 0, w, h);
            c.toBlob(function(blob) {
                if (!blob) { alert('{{ __("No se pudo capturar la imagen.") }}'); return; }
                var key = camera.currentSlot;
                closeCameraModal();
                if (key && slots[key]) {
                    var fakeFile;
                    try { fakeFile = new File([blob], key + '.jpg', { type: 'image/jpeg' }); }
                    catch(e) { fakeFile = blob; fakeFile.name = key + '.jpg'; }
                    handleCapturedFile(key, fakeFile);
                }
            }, 'image/jpeg', 0.92);
        }

        var btnShoot = document.getElementById('cam-btn-shoot');
        var btnCancel = document.getElementById('cam-btn-cancel');
        var btnSwitch = document.getElementById('cam-btn-switch');
        if (btnShoot) btnShoot.addEventListener('click', shootCamera);
        if (btnCancel) btnCancel.addEventListener('click', closeCameraModal);
        if (btnSwitch) btnSwitch.addEventListener('click', function() {
            camera.facingMode = (camera.facingMode === 'environment') ? 'user' : 'environment';
            startCameraStream();
        });

        window.addEventListener('pagehide', stopCameraStream);
        window.addEventListener('beforeunload', stopCameraStream);

        // ===== RESIZE Y SUBIDA (mismo contrato backend que antes) =====
        function resizeImage(blob, maxDim, callback) {
            if (!blob) return callback(null, false);
            var url = URL.createObjectURL(blob);
            var img = new Image();
            img.onload = function() {
                var w = img.width, h = img.height;
                if (w <= maxDim && h <= maxDim) { URL.revokeObjectURL(url); return callback(blob, false); }
                if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
                else        { w = Math.round((w * maxDim) / h); h = maxDim; }
                var c = document.createElement('canvas');
                c.width = w; c.height = h;
                c.getContext('2d').drawImage(img, 0, 0, w, h);
                URL.revokeObjectURL(url);
                if (c.toBlob) c.toBlob(function(b) { callback(b || blob, !!b); }, 'image/jpeg', 0.85);
                else callback(blob, false);
            };
            img.onerror = function() { URL.revokeObjectURL(url); callback(blob, false); };
            img.src = url;
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
                if (!slots.passport.blob) { restoreUI(); alert("{{ __('Falta la foto del pasaporte') }}"); return; }
                setProgress(10, '{{ __("Reescalando imagen...") }}');
                resizeImage(slots.passport.blob, 1920, function(resized) {
                    formData.append('dni_front', resized, 'passport.jpg');
                    setProgress(30, '{{ __("Subiendo y analizando pasaporte...") }}');
                    sendData(formData);
                });
            } else {
                if (!slots.front.blob) { restoreUI(); alert("{{ __('Falta la foto frontal del DNI') }}"); return; }
                setProgress(10, '{{ __("Reescalando imagen...") }}');
                resizeImage(slots.front.blob, 1920, function(resizedFront) {
                    formData.append('dni_front', resizedFront, 'front.jpg');
                    if (slots.back.blob) {
                        resizeImage(slots.back.blob, 1920, function(resizedBack) {
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
                        alert('{{ __("Error del servidor, por favor intente de nuevo.") }}'); restoreUI(); return;
                    }
                    if (result.success || result.data) {
                        sessionStorage.setItem('ai_extracted_data', JSON.stringify(result.data || {}));
                        sessionStorage.setItem('doc_type', docType);
                        setProgress(100, '{{ __("Listo!") }}');
                        setTimeout(function() { window.location.href = '/checkin/{{ $token }}/form'; }, 300);
                    } else { restoreUI(); alert('{{ __("Error procesando la imagen.") }}'); }
                } else if (xhr.status === 419) {
                    restoreUI(); alert('{{ __("Tu sesion ha caducado. La pagina se recargara.") }}'); window.location.reload();
                } else if (xhr.status === 422) {
                    restoreUI();
                    try { var err = JSON.parse(xhr.responseText); alert('{{ __("Error de validacion:") }} ' + (err.message || '')); }
                    catch(e) { alert('{{ __("Error de validacion en la imagen.") }}'); }
                } else {
                    restoreUI(); alert('{{ __("Error de red o servidor.") }} (' + xhr.status + ')');
                }
            };
            xhr.onerror = function() { restoreUI(); alert('{{ __("Error de conexion.") }}'); };
            xhr.send(formData);
        }
    })();
    </script>
</x-checkin-layout>
