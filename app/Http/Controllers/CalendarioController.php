<?php
namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Response;
use Carbon\Carbon;

class CalendarioController extends Controller
{

    public function ics($id)
{
    // Excluimos las reservas canceladas (estado_id = 4) y temporales (estado_id = 7)
    // Solo enviamos reservas de OTAs, NO reservas web ni presenciales
    $reservas = Reserva::where('apartamento_id', $id)
        ->whereNotIn('estado_id', [4, 7])
        ->where('origen', '!=', 'Web')
        ->whereNotNull('origen')
        ->get();

    $calendario = "BEGIN:VCALENDAR\r\n";
    $calendario .= "VERSION:2.0\r\n";
    $calendario .= "PRODID:-//TuEmpresa//ReservasApartamentos//ES\r\n";
    $calendario .= "CALSCALE:GREGORIAN\r\n";
    $calendario .= "METHOD:PUBLISH\r\n";

    foreach ($reservas as $reserva) {
        $entrada = Carbon::parse($reserva->fecha_entrada)->format('Ymd');
        $salida = Carbon::parse($reserva->fecha_salida)->format('Ymd');

        $uid = md5("reserva-" . $reserva->id . "@tu-dominio.com");

        $calendario .= "BEGIN:VEVENT\r\n";
        $calendario .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
        $calendario .= "DTSTART;VALUE=DATE:$entrada\r\n";
        $calendario .= "DTEND;VALUE=DATE:$salida\r\n";
        $calendario .= "UID:$uid\r\n";
        $calendario .= "SUMMARY:RESERVADO\r\n";
        $calendario .= "END:VEVENT\r\n";
    }

    $calendario .= "END:VCALENDAR\r\n";

    return response($calendario, 200)
        ->header('Content-Type', 'text/calendar')
        ->header('Content-Disposition', 'inline; filename=apartamento_' . $id . '.ics');
}

    public function ics2($id)
    {
        // Solo enviamos reservas de OTAs, NO reservas web ni presenciales
        $reservas = Reserva::where('apartamento_id', $id)
            ->whereNotIn('origen', ['Web', 'Prese0
            0ncial'])
            ->get();
        // dd($reservas);
        $calendario = "BEGIN:VCALENDAR\r\n";
        $calendario .= "VERSION:2.0\r\n";
        $calendario .= "PRODID:-//TuEmpresa//ReservasApartamentos//ES\r\n";
        $calendario .= "CALSCALE:GREGORIAN\r\n";
        $calendario .= "METHOD:PUBLISH\r\n";

        foreach ($reservas as $reserva) {
            $entrada = Carbon::parse($reserva->fecha_entrada)->format('Ymd');
            $salida = Carbon::parse($reserva->fecha_salida)->format('Ymd');

            $uid = md5("reserva-" . $reserva->id . "@tu-dominio.com");

            $calendario .= "BEGIN:VEVENT\r\n";
            $calendario .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
            $calendario .= "DTSTART;VALUE=DATE:$entrada\r\n";
            $calendario .= "DTEND;VALUE=DATE:$salida\r\n";
            $calendario .= "UID:$uid\r\n";
            $calendario .= "SUMMARY:RESERVADO\r\n";
            $calendario .= "END:VEVENT\r\n";
        }

        $calendario .= "END:VCALENDAR\r\n";

        return response($calendario, 200)
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'inline; filename=apartamento_'.$id.'.ics');
    }
}
