<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChannexController extends Controller
{
    /**
     * Verify the Channex webhook secret if configured.
     * Returns a JSON error response if verification fails, or null if OK.
     */
    private function verifyWebhookSecret(Request $request, string $eventType)
    {
        $secret = $request->header('X-Webhook-Secret') ?? $request->header('X-Channex-Secret');
        $expectedSecret = config('services.channex.webhook_secret');

        // SECURITY WARNING: When $expectedSecret is null/empty (not configured),
        // ALL webhook requests pass through without verification. Configure
        // CHANNEX_WEBHOOK_SECRET in production .env to enable signature validation.
        if ($expectedSecret && $secret !== $expectedSecret) {
            Log::warning("[Channex Webhook] Invalid signature for [{$eventType}]", [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return null;
    }

    public function webhook(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'webhook')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("Channex-WebHook_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function ariChanges(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'ari-changes')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("ari-changes_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function bookingAny(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'booking-any')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("booking-any_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function newBooking(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'new-booking')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("new-booking_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function modificationBooking(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'modification-booking')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("modification-booking_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function cancellationBooking(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'cancellation-booking')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("cancellation-booking_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function channelSyncError(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'channel-sync-error')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("channel-sync-error_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function reservationRequest(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'reservation-request')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("reservation-request_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function bookingUnamappedRoom(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'booking-unmapped-room')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("booking-unamapped-room_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function bookingUnamappedRate(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'booking-unmapped-rate')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("booking-unamapped-rate_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function syncWarning(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'sync-warning')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("sync-warning_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function newMessage(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'new-message')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("new-message_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function newReview(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'new-review')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("new-review_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function alterationRequest(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'alteration-request')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("alteration-request_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function airbnbInquiry(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'airbnb-inquiry')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("airbnb-inquiry_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function disconnectChannel(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'disconnect-channel')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("disconnect-channel_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function disconnectListing(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'disconnect-listing')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("disconnect-listing_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function rateError(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'rate-error')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("rate-error_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function acceptedReservation(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'accepted-reservation')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("accepted-reservation{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function declineReservation(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'decline-reservation')) return $error;
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("decline-reservation_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }

    public function fullSync(Request $request){
        if ($error = $this->verifyWebhookSecret($request, 'full-sync')) return $error;

        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites

        Storage::disk('publico')->put("decline-reservation_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Enviado correctamente', 200);
    }


}
