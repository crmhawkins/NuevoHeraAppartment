<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings; // Añade esta interfaz

class InvoicesExport implements FromCollection, WithHeadings
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * Retorna los datos a exportar.
     */
    public function collection()
    {
        return $this->invoices->map(function($invoice) {
            // Lógica para obtener el nombre completo del cliente
            $nombreCliente = '';
            if ($invoice->cliente) {
                $nombre = trim($invoice->cliente->nombre ?? '');
                $apellido1 = trim($invoice->cliente->apellido1 ?? '');
                $apellido2 = trim($invoice->cliente->apellido2 ?? '');

                // Si tiene nombre y al menos un apellido, usar nombre completo
                if (!empty($nombre) && (!empty($apellido1) || !empty($apellido2))) {
                    $nombreCliente = trim($nombre . ' ' . $apellido1 . ' ' . $apellido2);
                } else {
                    // Si no tiene nombre y apellidos, usar el alias
                    $nombreCliente = $invoice->cliente->alias ?? 'Sin información';
                }
            } else {
                $nombreCliente = 'Sin información';
            }

            return [
                'reference' => $invoice->reference,
                'cliente' => $nombreCliente,
                'num_identificacion' => optional($invoice->cliente)->num_identificacion ?? 'Sin información',
                'concepto' => $invoice->concepto ?? 'Sin información',
                'fecha_entrada' => $invoice->reserva && $invoice->reserva->fecha_entrada
                    ? Carbon::parse($invoice->reserva->fecha_entrada)->format('d/m/Y')
                    : 'Sin información',

                'fecha_salida' => $invoice->reserva && $invoice->reserva->fecha_salida
                    ? Carbon::parse($invoice->reserva->fecha_salida)->format('d/m/Y')
                    : 'Sin información',

                'fecha' => $invoice->fecha
                    ? Carbon::parse($invoice->fecha)->format('d/m/Y')
                    : 'Sin información',
                'base' => $invoice->base ?? 0,
                'iva' => $invoice->iva ?? 0,
                'total' => $invoice->total,
                'estado' => optional($invoice->estado)->name ?? 'Sin información',
            ];
        });
    }

    /**
     * Retorna los encabezados de las columnas.
     */
    public function headings(): array
    {
        return [
            'Referencia',
            'Cliente',
            'Número de Identificación',
            'Concepto',
            'Fecha de Entrada',
            'Fecha de Salida',
            'Fecha de Factura',
            'Base Imponible',
            'IVA',
            'Total',
            'Estado',
        ];
    }
}
