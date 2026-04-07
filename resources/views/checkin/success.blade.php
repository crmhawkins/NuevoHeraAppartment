<x-checkin-layout :token="$token">
    <div style="padding: 32px 24px; text-align: center;">
        {{-- Success icon --}}
        <div style="width: 80px; height: 80px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 8px;">
            {{ __('¡Registro completado!') }}
        </h1>
        <p style="color: #6b7280; margin-bottom: 32px;">
            {{ __('Tus datos han sido registrados correctamente.') }}
        </p>

        @if($codigosAcceso && !empty($codigosAcceso['codigo_acceso']))
        {{-- Access codes card --}}
        <div id="access-card" style="background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 16px; padding: 24px; margin-bottom: 24px; text-align: left;">
            <p style="font-size: 0.875rem; font-weight: 600; color: #0369a1; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 16px;">
                {{ __('Tus códigos de acceso') }}
            </p>

            @if(!empty($codigosAcceso['clave_edificio']))
            <div style="margin-bottom: 16px;">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0 0 4px;">{{ __('Puerta principal del edificio') }}</p>
                <div style="font-size: 2.5rem; font-weight: 800; color: #0c4a6e; letter-spacing: 0.2em; font-family: monospace;">
                    {{ $codigosAcceso['clave_edificio'] }}
                </div>
            </div>
            @endif

            <div style="margin-bottom: 16px;">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0 0 4px;">
                    {{ __('Puerta de tu apartamento') }}
                    @if(!empty($codigosAcceso['apartamento_titulo']))
                        — <strong>{{ $codigosAcceso['apartamento_titulo'] }}</strong>
                    @endif
                </p>
                <div style="font-size: 3rem; font-weight: 900; color: #0c4a6e; letter-spacing: 0.3em; font-family: monospace; background: white; border-radius: 12px; padding: 16px; text-align: center; border: 2px dashed #0ea5e9;">
                    {{ $codigosAcceso['codigo_acceso'] }}
                </div>
            </div>

            @if(!empty($reserva) && ($reserva->fecha_entrada || $reserva->fecha_salida))
            <div style="background: #e0f2fe; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.8125rem; color: #0369a1;">
                {{ __('Entrada') }}: {{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : '' }}
                &nbsp;&middot;&nbsp;
                {{ __('Salida') }}: {{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : '' }}
            </div>
            @endif

            <div style="background: #fef3c7; border-radius: 8px; padding: 12px; margin-top: 8px;">
                <p style="color: #92400e; font-size: 0.8125rem; margin: 0;">
                    <strong>{{ __('Anota este código.') }}</strong> {{ __('Es válido desde las 15:00 del día de entrada hasta las 11:00 del día de salida.') }}
                </p>
            </div>
        </div>

        {{-- Action buttons --}}
        <div style="display: flex; gap: 12px; margin-bottom: 24px;">
            <button type="button" id="btn-save-image" onclick="saveAsImage()" style="flex: 1; padding: 16px; background: #0ea5e9; color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                {{ __('Guardar imagen') }}
            </button>
            <button type="button" id="btn-share" onclick="shareCard()" style="flex: 1; padding: 16px; background: #111827; color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; display: none; align-items: center; justify-content: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                {{ __('Compartir') }}
            </button>
        </div>

        @else
        {{-- No codes yet --}}
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin-bottom: 24px; text-align: left;">
            <p style="color: #374151; margin: 0;">
                {{ __('Recibirás los códigos de acceso por WhatsApp y email el día de tu llegada a las 12:00.') }}
            </p>
        </div>
        @endif
    </div>

    @if($codigosAcceso && !empty($codigosAcceso['codigo_acceso']))
    <script>
        // Show share button if Web Share API is supported
        if (navigator.share) {
            document.getElementById('btn-share').style.display = 'flex';
        }

        function generateCardCanvas(callback) {
            var canvas = document.createElement('canvas');
            var w = 600, h = 820;
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');

            // Background
            ctx.fillStyle = '#0c4a6e';
            ctx.fillRect(0, 0, w, h);

            // Top rounded card area
            roundRect(ctx, 20, 20, w - 40, h - 40, 24);
            ctx.fillStyle = '#ffffff';
            ctx.fill();

            // Header bar
            roundRectTop(ctx, 20, 20, w - 40, 90, 24);
            ctx.fillStyle = '#0ea5e9';
            ctx.fill();

            // Logo text
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 28px system-ui, -apple-system, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('HAWKINS', w / 2, 58);
            ctx.font = '14px system-ui, sans-serif';
            ctx.fillText('{{ __("Tarjeta de acceso") }}', w / 2, 82);

            // Apartment name
            ctx.fillStyle = '#0c4a6e';
            ctx.font = 'bold 22px system-ui, sans-serif';
            ctx.fillText({!! json_encode($codigosAcceso["apartamento_titulo"] ?? "Apartamento") !!}, w / 2, 150);

            // Dates
            ctx.fillStyle = '#6b7280';
            ctx.font = '16px system-ui, sans-serif';
            var fechaEntrada = '{{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format("d/m/Y") : "" }}';
            var fechaSalida = '{{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format("d/m/Y") : "" }}';
            ctx.fillText('{{ __("Entrada") }}: ' + fechaEntrada + '  ·  {{ __("Salida") }}: ' + fechaSalida, w / 2, 180);

            // Divider
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(60, 210);
            ctx.lineTo(w - 60, 210);
            ctx.stroke();

            var yPos = 260;

            @if(!empty($codigosAcceso['clave_edificio']))
            // Building code label
            ctx.fillStyle = '#6b7280';
            ctx.font = '14px system-ui, sans-serif';
            ctx.fillText('{{ __("Puerta principal del edificio") }}', w / 2, yPos);
            yPos += 10;

            // Building code
            ctx.fillStyle = '#0c4a6e';
            ctx.font = 'bold 56px monospace';
            ctx.fillText('{{ $codigosAcceso["clave_edificio"] }}', w / 2, yPos + 50);
            yPos += 100;

            // Divider
            ctx.strokeStyle = '#e5e7eb';
            ctx.beginPath();
            ctx.moveTo(60, yPos);
            ctx.lineTo(w - 60, yPos);
            ctx.stroke();
            yPos += 40;
            @endif

            // Apartment code label
            ctx.fillStyle = '#6b7280';
            ctx.font = '14px system-ui, sans-serif';
            ctx.fillText('{{ __("Puerta de tu apartamento") }}', w / 2, yPos);
            yPos += 10;

            // Dashed box for apartment code
            var boxW = 340, boxH = 100, boxX = (w - boxW) / 2, boxY = yPos + 10;
            ctx.strokeStyle = '#0ea5e9';
            ctx.lineWidth = 3;
            ctx.setLineDash([10, 6]);
            roundRect(ctx, boxX, boxY, boxW, boxH, 12);
            ctx.stroke();
            ctx.setLineDash([]);

            // Apartment code
            ctx.fillStyle = '#0c4a6e';
            ctx.font = 'bold 64px monospace';
            ctx.fillText('{{ $codigosAcceso["codigo_acceso"] }}', w / 2, boxY + 68);

            yPos = boxY + boxH + 40;

            // Warning
            roundRect(ctx, 50, yPos, w - 100, 70, 10);
            ctx.fillStyle = '#fef3c7';
            ctx.fill();
            ctx.fillStyle = '#92400e';
            ctx.font = 'bold 13px system-ui, sans-serif';
            ctx.fillText('{{ __("Es válido desde las 15:00 del día de entrada") }}', w / 2, yPos + 30);
            ctx.font = '13px system-ui, sans-serif';
            ctx.fillText('{{ __("hasta las 11:00 del día de salida.") }}', w / 2, yPos + 52);

            callback(canvas);
        }

        function roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        }

        function roundRectTop(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h);
            ctx.lineTo(x, y + h);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        }

        function saveAsImage() {
            var btn = document.getElementById('btn-save-image');
            btn.innerText = '{{ __("Generando...") }}';
            btn.disabled = true;

            generateCardCanvas(function(canvas) {
                canvas.toBlob(function(blob) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'acceso-{{ str_replace(" ", "-", $codigosAcceso["apartamento_titulo"] ?? "apartamento") }}.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> {{ __("Guardado") }}';
                    setTimeout(function() {
                        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> {{ __("Guardar imagen") }}';
                        btn.disabled = false;
                    }, 2000);
                }, 'image/png');
            });
        }

        function shareCard() {
            generateCardCanvas(function(canvas) {
                canvas.toBlob(function(blob) {
                    var file = new File([blob], 'acceso-apartamento.png', { type: 'image/png' });

                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        navigator.share({
                            title: '{{ __("Códigos de acceso") }} - ' + {!! json_encode($codigosAcceso["apartamento_titulo"] ?? "Apartamento") !!},
                            text: '{{ __("Código apartamento") }}: {{ $codigosAcceso["codigo_acceso"] }}' + @if(!empty($codigosAcceso['clave_edificio'])) ' | {{ __("Código edificio") }}: {{ $codigosAcceso["clave_edificio"] }}' + @endif '',
                            files: [file]
                        }).catch(function(err) {
                            // User cancelled or error - try text-only share
                            if (err.name !== 'AbortError') {
                                navigator.share({
                                    title: '{{ __("Códigos de acceso") }}',
                                    text: '{{ __("Código apartamento") }}: {{ $codigosAcceso["codigo_acceso"] }}' + @if(!empty($codigosAcceso['clave_edificio'])) '\n{{ __("Código edificio") }}: {{ $codigosAcceso["clave_edificio"] }}' + @endif ''
                                }).catch(function() {});
                            }
                        });
                    } else {
                        // Fallback: text-only share
                        navigator.share({
                            title: '{{ __("Códigos de acceso") }}',
                            text: '{{ __("Código apartamento") }}: {{ $codigosAcceso["codigo_acceso"] }}' + @if(!empty($codigosAcceso['clave_edificio'])) '\n{{ __("Código edificio") }}: {{ $codigosAcceso["clave_edificio"] }}' + @endif ''
                        }).catch(function() {});
                    }
                }, 'image/png');
            });
        }
    </script>
    @endif
</x-checkin-layout>
