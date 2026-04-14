<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas_pendientes', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Archivo
            $t->string('filename', 255);                       // nombre original subido
            $t->string('storage_path', 500);                   // ruta relativa a storage/app
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->string('mime_type', 100)->nullable();

            // Estado
            // pendiente = recien subida, aun no procesada
            // procesando = la IA la esta leyendo ahora mismo
            // asociada = ya casada con un gasto y movida a procesadas/
            // espera = IA OK pero ningun gasto matchea (reintentara)
            // error = IA fallo o >1 gasto matchea o >30 dias sin match
            $t->enum('status', ['pendiente','procesando','asociada','espera','error'])
              ->default('pendiente')
              ->index();

            // Datos extraidos por la IA
            $t->decimal('importe_detectado', 12, 2)->nullable();
            $t->date('fecha_detectada')->nullable();
            $t->string('proveedor_detectado', 255)->nullable();
            $t->string('numero_factura_detectado', 100)->nullable();
            $t->text('concepto_detectado')->nullable();
            $t->decimal('confianza_ia', 4, 2)->nullable(); // 0.00 - 1.00
            $t->json('ia_raw_response')->nullable();

            // Asociacion con gasto
            $t->unsignedBigInteger('gasto_id')->nullable()->index();
            // Candidatos detectados en caso de ambiguedad (array de ids)
            $t->json('candidatos_gasto_ids')->nullable();

            // Errores y reintentos
            $t->text('error_message')->nullable();
            $t->unsignedInteger('intentos')->default(0);
            $t->timestamp('last_attempt_at')->nullable();
            $t->timestamp('resolved_at')->nullable();

            // Trazabilidad
            $t->string('uploaded_from', 50)->nullable(); // 'mobile_web','ftp','manual','email'
            $t->string('uploaded_by_ip', 45)->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_pendientes');
    }
};
