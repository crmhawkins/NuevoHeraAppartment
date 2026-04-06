<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicReservasController extends Controller
{
    /**
     * Página inicial del portal público (sin búsqueda)
     */
    public function index()
    {
        return view('public.reservas.index');
    }

    /**
     * Lista todos los apartamentos con sus características
     */
    public function apartamentos()
    {
        $apartamentos = \App\Models\Apartamento::query()
            ->whereNotNull('id_channex')
            ->with(['edificioName', 'photos'])
            ->orderBy('titulo')
            ->get();

        return view('public.apartamentos.index', compact('apartamentos'));
    }

    /**
     * Página "Sobre Nosotros"
     */
    public function sobreNosotros()
    {
        return view('public.sobre-nosotros.index');
    }

    /**
     * Página "Contacto"
     */
    public function contacto()
    {
        return view('public.contacto.index');
    }

    /**
     * Página "Video Exterior" - Instrucciones de acceso
     */
    public function videoExterior()
    {
        // Obtener el idioma actual de la sesión o aplicación
        $locale = app()->getLocale() ?: session('locale', 'es');
        
        // Mapear locale a código de video
        $videoCode = $this->getVideoCodeFromLocale($locale);
        
        return view('public.video-exterior.index', [
            'videoCode' => $videoCode,
            'locale' => $locale
        ]);
    }
    
    /**
     * Obtener el código de video según el locale
     * MOR = árabe, ESP = español, ENG = inglés (por defecto)
     */
    private function getVideoCodeFromLocale($locale)
    {
        $mapping = [
            'es' => 'ESP',
            'en' => 'ENG',
            'ar' => 'MOR', // Árabe
            'mor' => 'MOR', // Por si acaso viene como 'mor'
        ];
        
        return $mapping[$locale] ?? 'ENG'; // Por defecto inglés
    }

    /**
     * Procesar envío del formulario de contacto
     */
    public function enviarContacto(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'asunto' => 'required|string|max:255',
            'mensaje' => 'required|string|max:5000',
        ];

        // Validar reCAPTCHA solo si está configurado
        $recaptchaSecret = config('services.recaptcha.secret_key');
        if ($recaptchaSecret) {
            $rules['g-recaptcha-response'] = 'required';
        } else {
            // Si no hay reCAPTCHA configurado, validar el checkbox simple
            $rules['captcha_checkbox'] = 'required|accepted';
        }

        $request->validate($rules);

        // Validar reCAPTCHA v3 si está configurado
        if ($recaptchaSecret) {
            $recaptchaResponse = $request->input('g-recaptcha-response');
            if ($recaptchaResponse) {
                $recaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
                $recaptchaData = json_decode($recaptcha);
                
                if (!$recaptchaData || !$recaptchaData->success) {
                    $errorMessage = 'Por favor, verifica que no eres un robot.';
                    if (isset($recaptchaData->{'error-codes'})) {
                        \Log::warning('reCAPTCHA error codes: ' . implode(', ', $recaptchaData->{'error-codes'}));
                    }
                    return back()->with('error', $errorMessage)->withInput();
                }
                
                // Para reCAPTCHA v3, también verificar el score (debe ser > 0.5)
                if (isset($recaptchaData->score) && $recaptchaData->score < 0.5) {
                    \Log::warning('reCAPTCHA score too low: ' . $recaptchaData->score);
                    return back()->with('error', 'La verificación de seguridad falló. Por favor, inténtalo de nuevo.')->withInput();
                }
            } else {
                return back()->with('error', 'Por favor, completa la verificación de seguridad.')->withInput();
            }
        }

        try {
            // Guardar en base de datos
            $contacto = \App\Models\ContactoWeb::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'asunto' => $request->asunto,
                'mensaje' => $request->mensaje,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Crear notificación para administradores
            \App\Models\Notification::createForAdmins(
                \App\Models\Notification::TYPE_SISTEMA,
                'Nuevo Contacto desde la Web',
                "Nuevo mensaje de contacto de {$request->nombre} ({$request->email}): {$request->asunto}",
                [
                    'contacto_id' => $contacto->id,
                    'nombre' => $request->nombre,
                    'email' => $request->email,
                    'asunto' => $request->asunto,
                ],
                \App\Models\Notification::PRIORITY_MEDIUM,
                \App\Models\Notification::CATEGORY_INFO,
                route('admin.contactos-web.show', $contacto->id)
            );

            // Enviar email
            \Illuminate\Support\Facades\Mail::raw(
                "Nombre: {$request->nombre}\nEmail: {$request->email}\nAsunto: {$request->asunto}\n\nMensaje:\n{$request->mensaje}",
                function ($message) use ($request) {
                    $message->to(config('mail.from.address', 'info@apartamentosalgeciras.com'))
                            ->subject('Contacto Web: ' . $request->asunto)
                            ->replyTo($request->email, $request->nombre);
                }
            );

            return back()->with('success', '¡Mensaje enviado correctamente! Nos pondremos en contacto contigo pronto.');
        } catch (\Exception $e) {
            \Log::error('Error al enviar formulario de contacto: ' . $e->getMessage());
            return back()->with('error', 'Hubo un error al enviar el mensaje. Por favor, inténtalo de nuevo o contáctanos directamente por teléfono.')->withInput();
        }
    }

    /**
     * Muestra el iframe del buscador de reservas
     */
    public function iframe()
    {
        return view('public.reservas.iframe');
    }

    /**
     * Procesa la búsqueda y redirige al portal con los parámetros
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'fecha_entrada' => 'required|date|after_or_equal:today',
            'fecha_salida' => 'required|date|after:fecha_entrada',
            'adultos' => 'required|integer|min:1|max:20',
            'ninos' => 'nullable|integer|min:0|max:10',
        ]);

        // Construir URL del portal con parámetros
        $params = http_build_query([
            'fecha_entrada' => $request->fecha_entrada,
            'fecha_salida' => $request->fecha_salida,
            'adultos' => $request->adultos,
            'ninos' => $request->ninos ?? 0,
        ]);

        $portalUrl = route('web.reservas.portal') . '?' . $params;

        // Si es una petición AJAX (desde el iframe), devolver JSON con la URL
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $portalUrl,
                'url' => $portalUrl
            ]);
        }

        // Redirección normal
        return redirect($portalUrl);
    }

    /**
     * Portal público de reservas (resultados con o sin búsqueda)
     */
    public function portal(Request $request)
    {
        // Obtener parámetros de la URL
        $fechaEntrada = $request->get('fecha_entrada');
        $fechaSalida = $request->get('fecha_salida');
        $adultos = $request->get('adultos', 1);
        $ninos = $request->get('ninos', 0);

        $apartamentosDisponibles = collect();

        if ($fechaEntrada && $fechaSalida) {
            $totalHuespedes = max(1, intval($adultos) + intval($ninos));
            $entrada = \Carbon\Carbon::parse($fechaEntrada);
            $salida = \Carbon\Carbon::parse($fechaSalida);

            // Buscar apartamentos disponibles
            // Solo apartamentos con id_channex (sincronizados con Channex)
            $apartamentosDisponibles = \App\Models\Apartamento::query()
                ->whereNotNull('id_channex')
                // Filtro de capacidad: si tiene max_guests, debe ser >= totalHuespedes
                ->where(function ($q) use ($totalHuespedes) {
                    $q->whereNull('max_guests')
                      ->orWhere('max_guests', '>=', $totalHuespedes);
                })
                // Excluir apartamentos con reservas activas que solapen el rango
                ->whereDoesntHave('reservas', function ($q) use ($fechaEntrada, $fechaSalida) {
                    $q->where(function ($reserva) use ($fechaEntrada, $fechaSalida) {
                        // Excluir canceladas (4) y temporales (7)
                        $reserva->where('estado_id', '!=', 4)
                               ->where('estado_id', '!=', 7)
                               // Lógica de solapamiento: la reserva solapa si:
                               // - Su fecha_entrada es anterior a nuestra fecha_salida
                               // - Y su fecha_salida es posterior a nuestra fecha_entrada
                               ->where(function ($solapa) use ($fechaEntrada, $fechaSalida) {
                                   $solapa->where('fecha_entrada', '<', $fechaSalida)
                                          ->where('fecha_salida', '>', $fechaEntrada);
                               });
                    });
                })
                ->with(['edificioName', 'tarifas'])
                ->orderBy('titulo')
                ->get();
            
            // Calcular precio por noche para cada apartamento
            $apartamentosDisponibles = $apartamentosDisponibles->map(function ($apartamento) use ($entrada, $salida) {
                $precioPorNoche = $this->calcularPrecioPorNoche($apartamento, $entrada, $salida);
                $apartamento->precio_por_noche = $precioPorNoche;
                return $apartamento;
            });
        } else {
            // Si no hay parámetros de búsqueda, mostrar todos los apartamentos disponibles
            $apartamentosDisponibles = \App\Models\Apartamento::query()
                ->whereNotNull('id_channex')
                ->with(['edificioName'])
                ->orderBy('titulo')
                ->get();
            
            // Sin fechas, no hay precio
            $apartamentosDisponibles = $apartamentosDisponibles->map(function ($apartamento) {
                $apartamento->precio_por_noche = null;
                return $apartamento;
            });
        }

        return view('public.reservas.portal', [
            'fechaEntrada' => $fechaEntrada,
            'fechaSalida' => $fechaSalida,
            'adultos' => $adultos,
            'ninos' => $ninos,
            'apartamentosDisponibles' => $apartamentosDisponibles,
        ]);
    }

    /**
     * Mostrar detalles de un apartamento
     */
    public function show($id, Request $request)
    {
        $apartamento = \App\Models\Apartamento::with(['photos', 'edificioName', 'roomTypes', 'tarifas', 'normasCasa', 'servicios', 'lugaresCercanos', 'faqs'])
            ->whereNotNull('id_channex')
            ->findOrFail($id);

        // Parámetros de búsqueda para reservar (si vienen)
        $fechaEntrada = $request->get('fecha_entrada');
        $fechaSalida = $request->get('fecha_salida');
        $adultos = $request->get('adultos', 1);
        $ninos = $request->get('ninos', 0);

        // Verificar disponibilidad y calcular precio si hay fechas
        $disponible = null;
        $precioTotal = null;
        $precioPorNoche = null;
        $noches = 0;
        
        if ($fechaEntrada && $fechaSalida) {
            $entrada = \Carbon\Carbon::parse($fechaEntrada);
            $salida = \Carbon\Carbon::parse($fechaSalida);
            $noches = $entrada->diffInDays($salida);
            
            // Verificar disponibilidad del apartamento
            $disponible = $this->verificarDisponibilidad($apartamento, $entrada, $salida);
            
            if ($disponible) {
                // Intentar obtener precio de tarifas
                $precioPorNoche = $this->calcularPrecioPorNoche($apartamento, $entrada, $salida);
                
                if ($precioPorNoche) {
                    $precioTotal = $precioPorNoche * $noches;
                    
                    // Aplicar impuestos si aplican
                    if ($apartamento->tourist_tax && !$apartamento->tourist_tax_included) {
                        $precioTotal += ($apartamento->tourist_tax * $noches * ($adultos + $ninos));
                    }
                    if ($apartamento->city_tax && !$apartamento->city_tax_included) {
                        $precioTotal += ($apartamento->city_tax * $noches * ($adultos + $ninos));
                    }
                    if ($apartamento->cleaning_fee) {
                        $precioTotal += $apartamento->cleaning_fee;
                    }
                }
            }
        }

        // Traducir TODO el contenido dinámico ANTES de enviar a la vista
        // IMPORTANTE: NO modificar el HTML en absoluto, solo traducir el texto plano y reemplazarlo
        $locale = app()->getLocale();
        $translationService = app(\App\Services\TranslationService::class);
        
        // Función helper para limpiar estilos inline problemáticos del HTML
        // Elimina font-weight: 700/bold y text-decoration: underline (excepto en enlaces)
        $cleanInlineStyles = function($html) {
            if (strpos($html, 'style=') === false) {
                return $html; // No tiene estilos, devolver tal cual
            }
            
            // Procesar TODOS los atributos style, sin importar el orden de los atributos
            $cleaned = preg_replace_callback('/style="([^"]*)"/i', function($matches) use ($html) {
                $styleContent = $matches[1];
                
                // Eliminar font-weight: 700 o font-weight: bold
                $styleContent = preg_replace('/font-weight\s*:\s*(700|bold)\s*;?\s*/i', '', $styleContent);
                
                // Eliminar text-decoration: underline (excepto en enlaces, pero lo hacemos globalmente)
                // Nota: No podemos saber si es un enlace aquí, así que lo eliminamos siempre
                // Los enlaces se manejan con CSS
                $styleContent = preg_replace('/text-decoration\s*:\s*underline\s*;?\s*/i', '', $styleContent);
                
                // Limpiar punto y coma dobles o al inicio
                $styleContent = preg_replace('/;\s*;/', ';', $styleContent);
                $styleContent = preg_replace('/^\s*;\s*/', '', $styleContent);
                $styleContent = trim($styleContent, ' ;');
                
                // Si queda vacío, devolver string vacío para eliminar el atributo
                if (empty($styleContent)) {
                    return '';
                }
                return 'style="' . $styleContent . '"';
            }, $html);
            
            // Eliminar atributos style vacíos que quedaron
            $cleaned = preg_replace('/\s+style=""/', '', $cleaned);
            $cleaned = preg_replace('/style=""\s+/', '', $cleaned);
            
            return $cleaned;
        };
        
        // Función helper para traducir contenido preservando HTML EXACTAMENTE igual
        // Usa regex para extraer y reemplazar solo el texto, sin tocar tags ni atributos
        $translatePreservingHtml = function($html, $locale) use ($translationService, $cleanInlineStyles) {
            if (empty($html)) {
                return $html;
            }
            
            // SIEMPRE limpiar estilos problemáticos primero (incluso en español)
            $html = $cleanInlineStyles($html);
            
            // Si es español, devolver HTML limpio sin traducir
            if ($locale === 'es') {
                return $html;
            }
            
            // Si NO tiene HTML, traducir directamente
            if (strpos($html, '<') === false && strpos($html, '>') === false) {
                $textPlain = trim($html);
                if (empty($textPlain)) {
                    return $html;
                }
                
                $identifier = 'dynamic_' . md5($textPlain . 'es');
                $translated = $translationService->getFromJson($identifier, $locale);
                
                if (!$translated) {
                    $translated = $translationService->getOrTranslate($identifier, $textPlain, $locale);
                }
                
                return $translated ?: $html;
            }
            
            // Tiene HTML: extraer texto entre tags y traducirlo, preservando estructura EXACTA
            // Usar regex para encontrar texto entre tags, sin tocar los tags
            $translatedHtml = preg_replace_callback(
                '/>([^<]+)</',
                function($matches) use ($translationService, $locale) {
                    $text = trim($matches[1]);
                    if (empty($text)) {
                        return $matches[0]; // Devolver original si está vacío
                    }
                    
                    // Traducir el texto
                    $identifier = 'dynamic_' . md5($text . 'es');
                    $translated = $translationService->getFromJson($identifier, $locale);
                    
                    if (!$translated) {
                        $translated = $translationService->getOrTranslate($identifier, $text, $locale);
                    }
                    
                    // Si hay traducción, reemplazar; si no, mantener original
                    if ($translated && $translated !== $text) {
                        return '>' . $translated . '<';
                    }
                    
                    return $matches[0]; // Mantener original
                },
                $html
            );
            
            return $translatedHtml ?: $html;
        };
        
        // Traducir host_descripcion preservando HTML exactamente igual
        $hostDescripcion = \App\Models\Setting::get('host_descripcion', 'Alojamientos de calidad en el corazón de Algeciras');
        if (!empty($hostDescripcion)) {
            $hostDescripcion = $translatePreservingHtml($hostDescripcion, $locale);
        }
        
        // Traducir campos del apartamento si no es español
        // IMPORTANTE: Siempre establecer las variables *_translated para que la vista pueda usarlas
        if ($locale !== 'es') {
            // Traducir descripción del apartamento
            if ($apartamento->description) {
                $apartamento->description_translated = $translatePreservingHtml($apartamento->description, $locale);
            } else {
                $apartamento->description_translated = null;
            }
            
            // Traducir instrucciones y políticas
            if ($apartamento->check_in_instructions) {
                $apartamento->check_in_instructions_translated = $translatePreservingHtml($apartamento->check_in_instructions, $locale);
            } else {
                $apartamento->check_in_instructions_translated = null;
            }
            if ($apartamento->check_out_instructions) {
                $apartamento->check_out_instructions_translated = $translatePreservingHtml($apartamento->check_out_instructions, $locale);
            } else {
                $apartamento->check_out_instructions_translated = null;
            }
            if ($apartamento->cancellation_policy) {
                $apartamento->cancellation_policy_translated = $translatePreservingHtml($apartamento->cancellation_policy, $locale);
            } else {
                $apartamento->cancellation_policy_translated = null;
            }
            if ($apartamento->house_rules) {
                $apartamento->house_rules_translated = $translatePreservingHtml($apartamento->house_rules, $locale);
            } else {
                $apartamento->house_rules_translated = null;
            }
            if ($apartamento->important_information) {
                $apartamento->important_information_translated = $translatePreservingHtml($apartamento->important_information, $locale);
            } else {
                $apartamento->important_information_translated = null;
            }
            
            // Traducir normas de casa
            if ($apartamento->normasCasa) {
                $apartamento->normasCasa->each(function($norma) use ($locale, $translationService, $translatePreservingHtml) {
                    if ($norma->titulo) {
                        $norma->titulo_translated = $translatePreservingHtml($norma->titulo, $locale);
                    } else {
                        $norma->titulo_translated = $norma->titulo;
                    }
                    if ($norma->descripcion) {
                        $norma->descripcion_translated = $translatePreservingHtml($norma->descripcion, $locale);
                    } else {
                        $norma->descripcion_translated = $norma->descripcion;
                    }
                });
            }
            
            // Traducir servicios
            if ($apartamento->servicios) {
                $apartamento->servicios->each(function($servicio) use ($locale, $translationService, $translatePreservingHtml) {
                    if ($servicio->nombre) {
                        $servicio->nombre_translated = $translatePreservingHtml($servicio->nombre, $locale);
                    } else {
                        $servicio->nombre_translated = $servicio->nombre;
                    }
                    if ($servicio->descripcion) {
                        $servicio->descripcion_translated = $translatePreservingHtml($servicio->descripcion, $locale);
                    } else {
                        $servicio->descripcion_translated = $servicio->descripcion;
                    }
                });
            }
        } else {
            // Si es español, establecer las variables traducidas como las originales
            $apartamento->description_translated = $apartamento->description;
            $apartamento->check_in_instructions_translated = $apartamento->check_in_instructions;
            $apartamento->check_out_instructions_translated = $apartamento->check_out_instructions;
            $apartamento->cancellation_policy_translated = $apartamento->cancellation_policy;
            $apartamento->house_rules_translated = $apartamento->house_rules;
            $apartamento->important_information_translated = $apartamento->important_information;
            
            // Establecer normas y servicios como originales
            if ($apartamento->normasCasa) {
                $apartamento->normasCasa->each(function($norma) {
                    $norma->titulo_translated = $norma->titulo;
                    $norma->descripcion_translated = $norma->descripcion;
                });
            }
            if ($apartamento->servicios) {
                $apartamento->servicios->each(function($servicio) {
                    $servicio->nombre_translated = $servicio->nombre;
                    $servicio->descripcion_translated = $servicio->descripcion;
                });
            }
        }
        
        return view('public.reservas.show-booking', compact(
            'apartamento',
            'fechaEntrada',
            'fechaSalida',
            'adultos',
            'ninos',
            'disponible',
            'precioTotal',
            'precioPorNoche',
            'noches',
            'hostDescripcion'
        ));
    }
    
    /**
     * Calcular precio por noche basado en tarifas asignadas al apartamento
     * 
     * Busca tarifas que:
     * 1. Estén asignadas al apartamento (tabla pivot apartamento_tarifa con activo = true)
     * 2. La tarifa esté activa
     * 3. El rango de fechas de la reserva esté dentro del rango de vigencia de la tarifa
     */
    private function calcularPrecioPorNoche($apartamento, $fechaEntrada, $fechaSalida)
    {
        // Obtener todas las tarifas asignadas al apartamento que estén activas
        $tarifasAsignadas = $apartamento->tarifas()
            ->wherePivot('activo', true)  // Pivot activo (asignación activa)
            ->where('tarifas.activo', true)  // Tarifa activa
            ->get();
        
        if ($tarifasAsignadas->isEmpty()) {
            return null;
        }
        
        // Buscar tarifas cuyo rango de fechas cubra el rango de la reserva
        // La tarifa debe cubrir todo el período de la reserva
        $tarifaVigente = $tarifasAsignadas->first(function ($tarifa) use ($fechaEntrada, $fechaSalida) {
            $fechaInicioTarifa = \Carbon\Carbon::parse($tarifa->fecha_inicio);
            $fechaFinTarifa = \Carbon\Carbon::parse($tarifa->fecha_fin);
            
            // Verificar que el rango de la reserva esté completamente dentro del rango de la tarifa
            return $fechaInicioTarifa->lte($fechaEntrada) && $fechaFinTarifa->gte($fechaSalida);
        });
        
        // Si encontramos una tarifa que cubre todo el rango, usar su precio
        if ($tarifaVigente) {
            return floatval($tarifaVigente->precio);
        }
        
        // Si ninguna tarifa cubre todo el rango, intentar calcular precio promedio
        // Buscar tarifas que solapen parcialmente con el rango de reserva
        $tarifasSolapadas = $tarifasAsignadas->filter(function ($tarifa) use ($fechaEntrada, $fechaSalida) {
            $fechaInicioTarifa = \Carbon\Carbon::parse($tarifa->fecha_inicio);
            $fechaFinTarifa = \Carbon\Carbon::parse($tarifa->fecha_fin);
            
            // Verificar solapamiento: la tarifa solapa si sus fechas se cruzan con el rango de reserva
            return $fechaInicioTarifa->lte($fechaSalida) && $fechaFinTarifa->gte($fechaEntrada);
        });
        
        if ($tarifasSolapadas->isNotEmpty()) {
            // Si hay múltiples tarifas, usar la primera (o podríamos calcular un promedio ponderado)
            // Por simplicidad, usamos la tarifa que cubre el día de entrada
            $tarifaEntrada = $tarifasSolapadas->first(function ($tarifa) use ($fechaEntrada) {
                $fechaInicioTarifa = \Carbon\Carbon::parse($tarifa->fecha_inicio);
                $fechaFinTarifa = \Carbon\Carbon::parse($tarifa->fecha_fin);
                
                return $fechaInicioTarifa->lte($fechaEntrada) && $fechaFinTarifa->gte($fechaEntrada);
            });
            
            if ($tarifaEntrada) {
                return floatval($tarifaEntrada->precio);
            }
            
            // Si no hay tarifa específica para el día de entrada, usar la primera disponible
            return floatval($tarifasSolapadas->first()->precio);
        }
        
        // Si no hay tarifa, devolver null (se mostrará "Pendiente de calcular")
        return null;
    }
    
    /**
     * Verificar si el apartamento está disponible para las fechas dadas
     */
    private function verificarDisponibilidad($apartamento, $fechaEntrada, $fechaSalida)
    {
        // Buscar reservas que se solapen con el rango de fechas
        $reservasSolapadas = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
            ->whereIn('estado_id', [1, 2, 3]) // Solo estados activos (confirmada, pendiente, en curso)
            ->where(function ($query) use ($fechaEntrada, $fechaSalida) {
                $query->where(function ($q) use ($fechaEntrada, $fechaSalida) {
                    // La reserva empieza antes y termina después del inicio de la búsqueda
                    $q->where('fecha_entrada', '<=', $fechaEntrada)
                      ->where('fecha_salida', '>', $fechaEntrada);
                })->orWhere(function ($q) use ($fechaEntrada, $fechaSalida) {
                    // La reserva empieza dentro del rango de búsqueda
                    $q->where('fecha_entrada', '>=', $fechaEntrada)
                      ->where('fecha_entrada', '<', $fechaSalida);
                })->orWhere(function ($q) use ($fechaEntrada, $fechaSalida) {
                    // La reserva contiene completamente el rango de búsqueda
                    $q->where('fecha_entrada', '<=', $fechaEntrada)
                      ->where('fecha_salida', '>=', $fechaSalida);
                });
            })
            ->exists();
        
        // El apartamento está disponible si NO hay reservas que se solapen
        return !$reservasSolapadas;
    }
}

