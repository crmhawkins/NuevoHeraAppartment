<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AlertasCentralController extends Controller
{
    /**
     * Panel central de alertas del sistema.
     */
    public function index()
    {
        $alertTypes = $this->getAlertTypes();

        // Historial reciente: alertas CRM
        $recentAlerts = Alert::orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Historial reciente: notificaciones CRM
        $recentNotifications = Notification::orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Estadisticas de hoy
        $hoy = Carbon::today();
        $alertasHoy = Alert::whereDate('created_at', $hoy)->count();
        $notificacionesHoy = Notification::whereDate('created_at', $hoy)->count();
        $totalHoy = $alertasHoy + $notificacionesHoy;

        // Contar tipos internos/externos
        $internas = collect($alertTypes)->where('grupo', 'Internas')->count();
        $externas = collect($alertTypes)->where('grupo', 'Externas')->count();

        // Contar canales unicos
        $canalesCount = [];
        foreach ($alertTypes as $at) {
            foreach ($at['canales'] as $canal) {
                $canalesCount[$canal] = ($canalesCount[$canal] ?? 0) + 1;
            }
        }

        return view('admin.comunicacion.alertas-central', compact(
            'alertTypes',
            'recentAlerts',
            'recentNotifications',
            'totalHoy',
            'alertasHoy',
            'notificacionesHoy',
            'internas',
            'externas',
            'canalesCount'
        ));
    }

    /**
     * Historial paginado AJAX.
     */
    public function historial(Request $request)
    {
        $tipo = $request->get('tipo', 'todos'); // todos | alertas | notificaciones
        $leidas = $request->get('leidas', 'todas'); // todas | leidas | no_leidas
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $page = (int) $request->get('page', 1);
        $perPage = 25;

        $items = collect();

        if ($tipo === 'todos' || $tipo === 'alertas') {
            $query = Alert::orderBy('created_at', 'desc');
            if ($desde) $query->whereDate('created_at', '>=', $desde);
            if ($hasta) $query->whereDate('created_at', '<=', $hasta);
            if ($leidas === 'leidas') $query->where('is_read', true);
            if ($leidas === 'no_leidas') $query->where('is_read', false);

            $alerts = $query->limit(500)->get()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'fecha' => $a->created_at->format('d/m/Y H:i'),
                    'tipo' => $a->type ?? 'info',
                    'titulo' => $a->title,
                    'destinatario' => $a->user ? $a->user->name : 'Sistema',
                    'canal' => 'crm',
                    'estado' => $a->is_read ? 'leida' : 'no_leida',
                    'origen' => 'alert',
                    'action_url' => $a->action_url,
                    'created_at' => $a->created_at,
                ];
            });
            $items = $items->merge($alerts);
        }

        if ($tipo === 'todos' || $tipo === 'notificaciones') {
            $query = Notification::orderBy('created_at', 'desc');
            if ($desde) $query->whereDate('created_at', '>=', $desde);
            if ($hasta) $query->whereDate('created_at', '<=', $hasta);
            if ($leidas === 'leidas') $query->whereNotNull('read_at');
            if ($leidas === 'no_leidas') $query->whereNull('read_at');

            $notifications = $query->limit(500)->get()->map(function ($n) {
                return [
                    'id' => $n->id,
                    'fecha' => $n->created_at->format('d/m/Y H:i'),
                    'tipo' => $n->type ?? 'info',
                    'titulo' => $n->title,
                    'destinatario' => $n->user ? $n->user->name : 'Sistema',
                    'canal' => $n->type === 'whatsapp' ? 'whatsapp' : 'crm',
                    'estado' => $n->read_at ? 'leida' : 'no_leida',
                    'origen' => 'notification',
                    'action_url' => $n->action_url,
                    'created_at' => $n->created_at,
                ];
            });
            $items = $items->merge($notifications);
        }

        // Ordenar por fecha descendente y paginar
        $items = $items->sortByDesc('created_at')->values();
        $total = $items->count();
        $paged = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $paged,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    /**
     * Detalle de una alerta/notificacion (AJAX modal).
     */
    public function detalle(Request $request)
    {
        $tipo = $request->get('tipo'); // 'alert' or 'notification'
        $id = $request->get('id');

        if ($tipo === 'alert') {
            $item = \App\Models\Alert::find($id);
            if ($item && !$item->is_read) {
                $item->update(['is_read' => true]);
            }
        } else {
            $item = \DB::table('notifications')->where('id', $id)->first();
            if ($item && !$item->read_at) {
                \DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);
            }
        }

        return response()->json(['success' => true, 'item' => $item]);
    }

    /**
     * WhatsApp templates list.
     */
    public function plantillas()
    {
        try {
            $templates = \App\Models\WhatsappTemplate::all();
            return response()->json(['success' => true, 'templates' => $templates]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'templates' => [], 'error' => 'Tabla no disponible']);
        }
    }

    /**
     * Channex/OTA messages history.
     */
    public function mensajesOTA(Request $request)
    {
        try {
            $mensajes = \DB::table('whatsapp_mensaje_chatgpt')
                ->orderBy('created_at', 'desc')
                ->paginate(30);
            return response()->json(['success' => true, 'mensajes' => $mensajes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensajes' => ['data' => []], 'error' => 'Tabla no disponible']);
        }
    }

    /**
     * Email history.
     */
    public function emailsEnviados(Request $request)
    {
        try {
            $emails = \DB::table('emails')
                ->orderBy('created_at', 'desc')
                ->paginate(30);
            return response()->json(['success' => true, 'emails' => $emails]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'emails' => ['data' => []], 'error' => 'Tabla no disponible']);
        }
    }

    /**
     * Catalogo maestro de alertas del sistema.
     */
    private function getAlertTypes(): array
    {
        return [
            // INTERNAS
            [
                'grupo' => 'Internas',
                'nombre' => 'Pago Abandonado',
                'descripcion' => 'Huesped inicio reserva web pero no completo el pago',
                'trigger' => 'Reserva web sin pagar tras timeout',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::pagoAbandonado()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'MIR Fallido',
                'descripcion' => 'Error enviando datos al Ministerio del Interior',
                'trigger' => 'Fallo en transmision MIR tras checkin',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::mirFallo()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Scraper Bankinter Fallido',
                'descripcion' => 'Error importando movimientos bancarios',
                'trigger' => 'Fallo en scraper automatico',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::scraperFallo()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Nueva Reserva Web',
                'descripcion' => 'Huesped completo pago de reserva web',
                'trigger' => 'Pago Stripe completado',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp'],
                'servicio' => 'AlertaEquipoService::nuevaReservaWeb()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Stock Bajo Amenities',
                'descripcion' => 'Amenity por debajo del stock minimo',
                'trigger' => 'Al descontar stock en limpieza o cron diario',
                'destinatarios' => 'Elena, David + Admin CRM',
                'canales' => ['whatsapp', 'email', 'crm'],
                'servicio' => 'AlertaEquipoService + NotificationService',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Doble Reserva Detectada',
                'descripcion' => 'Dos reservas solapadas en el mismo apartamento',
                'trigger' => 'Webhook Channex o cron deteccion diaria',
                'destinatarios' => 'Elena, David + Admins configurados',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService + WhatsappNotificationService',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Cancelacion No Procesada',
                'descripcion' => 'Channex envio cancelacion pero no se encontro la reserva',
                'trigger' => 'Webhook cancelacion sin match',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::alertar()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Turnos Sobrecargados',
                'descripcion' => 'Mas checkouts que horas disponibles de limpiadoras',
                'trigger' => 'Generacion diaria de turnos a las 07:00',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::alertar()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Early/Late Checkout Contratado',
                'descripcion' => 'Huesped compro servicio extra, priorizar limpieza',
                'trigger' => 'Pago Stripe de servicio extra',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::alertar()',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Incidencia Limpieza',
                'descripcion' => 'Limpiadora reporta averia o problema',
                'trigger' => 'Limpiadora usa boton reportar averia',
                'destinatarios' => 'Elena, David + Tecnicos + Admin CRM',
                'canales' => ['whatsapp', 'email', 'crm'],
                'servicio' => 'AlertaEquipoService + TecnicoNotificationService + AlertService',
                'editable' => false,
            ],
            [
                'grupo' => 'Internas',
                'nombre' => 'Fallo Envio Asesoria',
                'descripcion' => 'Error enviando informe trimestral a la asesoria fiscal',
                'trigger' => 'Cron trimestral o envio manual',
                'destinatarios' => 'Elena, David',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'AlertaEquipoService::asesoriaFallo()',
                'editable' => false,
            ],
            // EXTERNAS
            [
                'grupo' => 'Externas',
                'nombre' => 'Limpieza Completada',
                'descripcion' => 'Notifica al huesped que su apartamento esta listo',
                'trigger' => 'Limpiadora finaliza limpieza de apartamento',
                'destinatarios' => 'Huesped de la proxima reserva (3 dias)',
                'canales' => ['whatsapp', 'email'],
                'servicio' => 'GuestCleaningNotificationService',
                'editable' => false,
            ],
            [
                'grupo' => 'Externas',
                'nombre' => 'Reparacion Asignada',
                'descripcion' => 'Notifica al tecnico que tiene una reparacion',
                'trigger' => 'Incidencia creada o asignada por admin',
                'destinatarios' => 'Tecnicos activos',
                'canales' => ['whatsapp'],
                'servicio' => 'TecnicoNotificationService',
                'editable' => false,
            ],
        ];
    }
}
