<x-checkin-layout :token="$token">
    <h1>{{ __('Paso 2 - Confirmación de datos') }}</h1>
    <p style="color: var(--text-muted); margin-bottom: 24px;">{{ __('Por favor, revisa y completa los datos.') }}</p>

    @if(isset($reserva) && $reserva)
    <div class="alert-info">
        <strong>{{ $reserva->apartamento->titulo ?? '' }}</strong>
        &nbsp;&middot;&nbsp;
        {{ __('Entrada') }}: {{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : '' }}
        &nbsp;&middot;&nbsp;
        {{ __('Salida') }}: {{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : '' }}
    </div>
    @endif

    @if ($errors->any())
        <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
            <ul style="margin:0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/checkin/{{ $token }}/store" id="checkin-form">
        @csrf

        {{-- AI Prefill Script --}}
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var dataStr = sessionStorage.getItem('ai_extracted_data');
                if (dataStr) {
                    try {
                        var data = JSON.parse(dataStr);
                        for (var key in data) {
                            if (data.hasOwnProperty(key) && data[key]) {
                                var field = document.querySelector('[name="guests[0][' + key + ']"]');
                                if (field) {
                                    // Siempre sobrescribir con dato de IA (es mas fiable que el valor previo)
                                    field.value = data[key];
                                }
                            }
                        }
                        sessionStorage.removeItem('ai_extracted_data');

                        // Ocultar campo soporte si es pasaporte
                        var docTypeStored = sessionStorage.getItem('doc_type');
                        if (docTypeStored === 'passport') {
                            var supportGroup = document.getElementById('support-number-group');
                            if (supportGroup) supportGroup.style.display = 'none';
                        }
                        sessionStorage.removeItem('doc_type');
                    } catch (e) { console.error('Error parsing AI data', e); }
                }
            });
        </script>

        <div id="guests-container">
            <div class="guest-block" id="guest-block-0" style="padding-bottom: 20px;">
                <h3 style="margin-bottom: 16px; color: var(--primary);">{{ __('Huésped 1 (Titular)') }}</h3>

                <div class="form-group" id="relationship-group-0" style="display: none;">
                    <label>{{ __('Parentesco') }} *</label>
                    <select name="guests[0][relationship]" class="relationship-input">
                        <option value="">-- {{ __('Seleccione parentesco') }} --</option>
                        <option value="Cónyuge">{{ __('Cónyuge') }}</option>
                        <option value="Hijo/a">{{ __('Hijo/a') }}</option>
                        <option value="Padre o Madre">{{ __('Padre o Madre') }}</option>
                        <option value="Hermano/a">{{ __('Hermano/a') }}</option>
                        <option value="Abuelo/a">{{ __('Abuelo/a') }}</option>
                        <option value="Nieto/a">{{ __('Nieto/a') }}</option>
                        <option value="Tío/a">{{ __('Tío/a') }}</option>
                        <option value="Sobrino/a">{{ __('Sobrino/a') }}</option>
                        <option value="Cuñado/a">{{ __('Cuñado/a') }}</option>
                        <option value="Suegro/a">{{ __('Suegro/a') }}</option>
                        <option value="Yerno o Nuera">{{ __('Yerno o Nuera') }}</option>
                        <option value="Tutor">{{ __('Tutor') }}</option>
                        <option value="Otro">{{ __('Otro') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{ __('Nombre') }} *</label>
                    <input type="text" name="guests[0][first_name]" required value="{{ old('guests.0.first_name', $cliente->nombre ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Apellidos') }} *</label>
                    <input type="text" name="guests[0][last_name]" required value="{{ old('guests.0.last_name', trim(($cliente->apellido1 ?? '') . ' ' . ($cliente->apellido2 ?? ''))) }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Sexo') }} *</label>
                    <select name="guests[0][gender]" required>
                        <option value="">--</option>
                        <option value="M" {{ old('guests.0.gender') == 'M' ? 'selected' : '' }}>{{ __('Masculino') }}</option>
                        <option value="F" {{ old('guests.0.gender') == 'F' ? 'selected' : '' }}>{{ __('Femenino') }}</option>
                        <option value="O" {{ old('guests.0.gender') == 'O' ? 'selected' : '' }}>{{ __('Otro') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{ __('Fecha de nacimiento') }} *</label>
                    <input type="date" name="guests[0][birth_date]" required value="{{ old('guests.0.birth_date', $cliente->fecha_nacimiento ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Nacionalidad') }} *</label>
                    <input type="text" name="guests[0][nationality]" required value="{{ old('guests.0.nationality', $cliente->nacionalidadStr ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Tipo de documento') }} *</label>
                    <select name="guests[0][document_type]" required>
                        <option value="DNI" {{ old('guests.0.document_type', $cliente->tipo_documento_str ?? 'DNI') == 'DNI' ? 'selected' : '' }}>DNI</option>
                        <option value="NIE" {{ old('guests.0.document_type', $cliente->tipo_documento_str ?? '') == 'NIE' ? 'selected' : '' }}>NIE</option>
                        <option value="Passport" {{ old('guests.0.document_type', $cliente->tipo_documento_str ?? '') == 'Passport' ? 'selected' : '' }}>{{ __('Pasaporte') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{ __('Número de documento') }} *</label>
                    <input type="text" name="guests[0][document_number]" required value="{{ old('guests.0.document_number', $cliente->num_identificacion ?? '') }}">
                </div>

                <div class="form-group" id="support-number-group">
                    <label>{{ __('Número de soporte') }}</label>
                    <input type="text" name="guests[0][document_support_number]" value="{{ old('guests.0.document_support_number') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Fecha de expedición') }}</label>
                    <input type="date" name="guests[0][exp_date]" value="{{ old('guests.0.exp_date', $cliente->fecha_expedicion_doc ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Fecha de caducidad') }} *</label>
                    <input type="date" name="guests[0][expiry_date]" required value="{{ old('guests.0.expiry_date') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Dirección') }} *</label>
                    <input type="text" name="guests[0][address]" required value="{{ old('guests.0.address', $cliente->direccion ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Código postal') }} *</label>
                    <input type="text" name="guests[0][postal_code]" required value="{{ old('guests.0.postal_code', $cliente->codigo_postal ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Ciudad') }} *</label>
                    <input type="text" name="guests[0][city]" required value="{{ old('guests.0.city', $cliente->localidad ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('País') }} *</label>
                    <input type="text" name="guests[0][country]" required value="{{ old('guests.0.country', 'España') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Teléfono') }} *</label>
                    <input type="tel" name="guests[0][phone]" required value="{{ old('guests.0.phone', $cliente->telefono_movil ?? '') }}">
                </div>

                <div class="form-group">
                    <label>{{ __('Email') }} *</label>
                    <input type="email" name="guests[0][email]" required value="{{ old('guests.0.email', $cliente->email ?? '') }}">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 32px 0;">
            </div>
        </div>

        {{-- Signature and Terms --}}
        <div class="form-group" style="background:#f9fafb; padding:20px; border-radius:12px; border:1px solid var(--border);">
            <label style="display: flex; align-items: start; gap:12px; font-weight: normal; margin-bottom: 8px;">
                <input type="checkbox" name="terms_accepted" id="terms_accepted" required style="width: 24px; height: 24px; margin-top: 2px;">
                <span>{{ __('Al aceptar, usted acepta las condiciones de uso de nuestras instalaciones') }}</span>
            </label>

            <label style="margin-top: 20px;">{{ __('Firma aquí:') }} *</label>
            <div style="border: 2px solid var(--border); border-radius: var(--border-radius); background: #fff; overflow:hidden;">
                <canvas id="signature-pad" style="width: 100%; height: 200px; touch-action: none; display: block;"></canvas>
            </div>
            <button type="button" id="clear-signature" class="btn btn-secondary" style="margin-top: 12px; font-size: 16px; padding: 12px;">{{ __('Limpiar firma') }}</button>
            <input type="hidden" name="signature_data" id="signature_data" value="{{ old('signature_data') }}">

            <div style="margin-top: 24px; text-align: center;">
                <button type="button" id="add-guest-btn" class="btn btn-secondary" style="background-color: #e2e8f0; color: #1e293b; border: 1px dashed #94a3b8; font-weight: bold; width: auto; padding: 12px 24px; border-radius: 8px;">
                    + {{ __('Añadir más huéspedes') }}
                </button>
            </div>
        </div>

        <button type="submit" class="btn" id="submit-form">{{ __('Confirmar y guardar') }}</button>
    </form>

    <script>
        // --- Multi-Guest Logic ---
        var guestCount = 1;
        var addGuestBtn = document.getElementById('add-guest-btn');
        var guestsContainer = document.getElementById('guests-container');

        addGuestBtn.addEventListener('click', function() {
            if (guestCount >= 10) {
                alert('{{ __("Máximo 10 huéspedes por reserva.") }}');
                return;
            }
            var original = document.getElementById('guest-block-0');
            var clone = original.cloneNode(true);
            clone.id = 'guest-block-' + guestCount;

            var h3 = clone.querySelector('h3');
            if (h3) h3.innerText = '{{ __("Huésped") }} ' + (guestCount + 1);

            // Show relationship field
            var relGroup = clone.querySelector('#relationship-group-0');
            if (relGroup) {
                relGroup.id = 'relationship-group-' + guestCount;
                relGroup.style.display = 'block';
                var relInput = relGroup.querySelector('.relationship-input');
                if (relInput) relInput.required = true;
            }

            // Update names and clear values
            var inputs = clone.querySelectorAll('input, select');
            for (var i = 0; i < inputs.length; i++) {
                var inp = inputs[i];
                if (inp.name) inp.name = inp.name.replace('guests[0]', 'guests[' + guestCount + ']');
                if (inp.tagName === 'INPUT' && inp.type !== 'hidden' && inp.type !== 'checkbox') inp.value = '';
                else if (inp.tagName === 'SELECT') inp.selectedIndex = 0;
            }

            // Remove existing remove buttons from clone
            var oldBtns = clone.querySelectorAll('.remove-guest-btn');
            for (var j = 0; j < oldBtns.length; j++) oldBtns[j].parentNode.removeChild(oldBtns[j]);

            // Add remove button
            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'btn btn-secondary remove-guest-btn';
            rmBtn.style.cssText = 'margin-top:16px; background:#fee2e2; color:#b91c1c; border:1px solid #f87171;';
            rmBtn.innerText = '{{ __("Eliminar huésped") }}';
            rmBtn.onclick = function() { guestsContainer.removeChild(clone); };
            clone.appendChild(rmBtn);

            guestsContainer.appendChild(clone);
            guestCount++;
            if (clone.scrollIntoView) clone.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // --- Signature Canvas ---
        var canvas = document.getElementById('signature-pad');
        var submitForm = document.getElementById('checkin-form');
        var signatureData = document.getElementById('signature_data');
        var clearBtn = document.getElementById('clear-signature');
        var submitBtnEl = document.getElementById('submit-form');
        var ctx = canvas.getContext('2d');
        var isDrawing = false, hasSignature = false, isSubmitting = false;

        function resizeCanvas() {
            var dataUrl = hasSignature ? canvas.toDataURL() : null;
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            ctx.scale(ratio, ratio);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            if (dataUrl) {
                var img = new Image();
                img.onload = function() { ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight); };
                img.src = dataUrl;
            }
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        if (signatureData.value) {
            var oldImg = new Image();
            oldImg.onload = function() { ctx.drawImage(oldImg, 0, 0, canvas.offsetWidth, canvas.offsetHeight); hasSignature = true; };
            oldImg.src = signatureData.value;
        }

        function getCoords(e) {
            var r = canvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        }
        function startDraw(e) { isDrawing = true; hasSignature = true; var p = getCoords(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function draw(e) { if (!isDrawing) return; var p = getCoords(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function stopDraw() { isDrawing = false; }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('mouseout', stopDraw);
        canvas.addEventListener('touchstart', startDraw);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDraw);

        clearBtn.addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            signatureData.value = '';
        });

        submitForm.addEventListener('submit', function(e) {
            if (isSubmitting) { e.preventDefault(); return; }
            if (!hasSignature) { e.preventDefault(); alert('{{ __("La firma es obligatoria.") }}'); return; }
            signatureData.value = canvas.toDataURL('image/png');
            isSubmitting = true;
            submitBtnEl.innerText = '{{ __("Procesando, por favor espere...") }}';
            submitBtnEl.style.opacity = '0.7';
            submitBtnEl.style.cursor = 'not-allowed';
        });

        // Restore additional guests on validation error
        document.addEventListener("DOMContentLoaded", function() {
            var oldGuests = @json(old('guests'));
            if (oldGuests && typeof oldGuests === 'object') {
                var keys = Object.keys(oldGuests);
                for (var i = 1; i < keys.length; i++) {
                    addGuestBtn.click();
                    var idx = guestCount - 1;
                    var g = oldGuests[keys[i]];
                    if (g) {
                        for (var f in g) {
                            if (g.hasOwnProperty(f)) {
                                var el = document.querySelector('[name="guests[' + idx + '][' + f + ']"]');
                                if (el) el.value = g[f];
                            }
                        }
                    }
                }
            }
        });
    </script>
</x-checkin-layout>
