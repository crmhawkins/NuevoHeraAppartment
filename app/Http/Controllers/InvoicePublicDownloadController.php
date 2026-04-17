<?php

namespace App\Http\Controllers;

use App\Models\Invoices;
use App\Models\InvoiceDownloadToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-17] Descarga publica de facturas por token. El token se genera al
 * pulsar "Enviar al cliente" desde el detalle de factura y se envia por
 * WhatsApp/email. No requiere autenticacion, pero si token valido + no
 * caducado.
 */
class InvoicePublicDownloadController extends Controller
{
    public function download($token)
    {
        $downloadToken = InvoiceDownloadToken::where('token', $token)->first();

        if (!$downloadToken) {
            abort(404, 'Enlace no valido.');
        }

        if (!$downloadToken->isValid()) {
            abort(410, 'Este enlace ha caducado. Contacta con nosotros para recibir uno nuevo.');
        }

        $invoice = Invoices::with(['facturaOriginal'])->find($downloadToken->invoice_id);
        if (!$invoice) {
            abort(404, 'Factura no disponible.');
        }

        $downloadToken->downloaded_at = now();
        $downloadToken->save();

        Log::info('Descarga publica de factura', [
            'invoice_id' => $invoice->id,
            'reference' => $invoice->reference,
            'token_id' => $downloadToken->id,
            'ip' => request()->ip(),
        ]);

        $data = [
            'title' => 'Factura ' . $invoice->reference,
        ];
        $safeFileName = preg_replace('/[\/\\\\]/', '-', $invoice->reference ?? ('factura_' . $invoice->id));
        $pdf = Pdf::loadView('admin.invoices.previewPDF', compact('invoice', 'data'));

        return $pdf->download('factura_' . $safeFileName . '.pdf');
    }
}
