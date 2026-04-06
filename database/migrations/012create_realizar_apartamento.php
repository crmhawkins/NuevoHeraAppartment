<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apartamento_limpieza', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->tinyInteger('bano')->nullable();
            $table->tinyInteger('bano_toallas_aseos')->nullable();
            $table->tinyInteger('bano_toallas_mano')->nullable();
            $table->tinyInteger('bano_alfombra')->nullable();
            $table->tinyInteger('bano_secador')->nullable();
            $table->tinyInteger('bano_papel')->nullable();
            $table->tinyInteger('bano_rellenar_gel')->nullable();
            $table->tinyInteger('bano_espejo')->nullable();
            $table->tinyInteger('bano_ganchos')->nullable();
            $table->tinyInteger('bano_muebles')->nullable();
            $table->tinyInteger('bano_desague')->nullable();
            $table->tinyInteger('dormitorio')->nullable();
            $table->tinyInteger('dormitorio_sabanas')->nullable();
            $table->tinyInteger('dormitorio_cojines')->nullable();
            $table->tinyInteger('dormitorio_edredon')->nullable();
            $table->tinyInteger('dormitorio_funda_edredon')->nullable();
            $table->tinyInteger('dormitorio_canape')->nullable();
            $table->tinyInteger('dormitorio_manta_cubrepies')->nullable();
            $table->tinyInteger('dormitorio_papel_plancha')->nullable();
            $table->tinyInteger('dormitorio_toallas_rulo')->nullable();
            $table->tinyInteger('dormitorio_revision_pelos')->nullable();
            $table->tinyInteger('armario')->nullable();
            $table->tinyInteger('armario_perchas')->nullable();
            $table->tinyInteger('armario_almohada_repuesto_sofa')->nullable();
            $table->tinyInteger('armario_edredon_repuesto_sofa')->nullable();
            $table->tinyInteger('armario_funda_repuesto_edredon')->nullable();
            $table->tinyInteger('armario_sabanas_repuesto')->nullable();
            $table->tinyInteger('armario_plancha')->nullable();
            $table->tinyInteger('armario_tabla_plancha')->nullable();
            $table->tinyInteger('armario_toalla')->nullable();
            $table->tinyInteger('canape')->nullable();
            $table->tinyInteger('canape_almohada')->nullable();
            $table->tinyInteger('canape_gel')->nullable();
            $table->tinyInteger('canape_sabanas')->nullable();
            $table->tinyInteger('canape_toallas')->nullable();
            $table->tinyInteger('canape_papel_wc')->nullable();
            $table->tinyInteger('canape_estropajo')->nullable();
            $table->tinyInteger('canape_bayeta')->nullable();
            $table->tinyInteger('canape_antihumedad')->nullable();
            $table->tinyInteger('canape_ambientador')->nullable();
            $table->tinyInteger('salon')->nullable();
            $table->tinyInteger('salon_cojines')->nullable();
            $table->tinyInteger('salon_sofa_cama')->nullable();
            $table->tinyInteger('salon_planta_cesta')->nullable();
            $table->tinyInteger('salon_mandos')->nullable();
            $table->tinyInteger('salon_tv')->nullable();
            $table->tinyInteger('salon_cortinas')->nullable();
            $table->tinyInteger('salon_sillas')->nullable();
            $table->tinyInteger('salon_salvamanteles')->nullable();
            $table->tinyInteger('salon_estanteria')->nullable();
            $table->tinyInteger('salon_decoracion')->nullable();
            $table->tinyInteger('salon_ambientador')->nullable();
            $table->tinyInteger('salon_libros_juego')->nullable();
            $table->tinyInteger('cocina')->nullable();
            $table->tinyInteger('cocina_vitroceramica')->nullable();
            $table->tinyInteger('cocina_vajilla')->nullable();
            $table->tinyInteger('cocina_vasos')->nullable();
            $table->tinyInteger('cocina_tazas')->nullable();
            $table->tinyInteger('cocina_tapadera')->nullable();
            $table->tinyInteger('cocina_sartenes')->nullable();
            $table->tinyInteger('cocina_paño_cocina')->nullable();
            $table->tinyInteger('cocina_cuberteria')->nullable();
            $table->tinyInteger('cocina_cuchillo')->nullable();
            $table->tinyInteger('cocina_ollas')->nullable();
            $table->tinyInteger('cocina_papel_cocina')->nullable();
            $table->tinyInteger('cocina_tapadera_micro')->nullable();
            $table->tinyInteger('cocina_estropajo')->nullable();
            $table->tinyInteger('cocina_mistol')->nullable();
            $table->tinyInteger('cocina_tostadora')->nullable();
            $table->tinyInteger('cocina_bolsa_basura')->nullable();
            $table->tinyInteger('cocina_tabla_cortar')->nullable();
            $table->tinyInteger('cocina_escurreplatos')->nullable();
            $table->tinyInteger('cocina_bol_escurridor')->nullable();
            $table->tinyInteger('cocina_utensilios_cocina')->nullable();
            $table->tinyInteger('cocina_dolcegusto')->nullable();
            $table->tinyInteger('amenities')->nullable();
            $table->tinyInteger('amenities_gafas')->nullable();
            $table->tinyInteger('amenities_nota_agradecimiento')->nullable();
            $table->tinyInteger('amenities_magdalenas')->nullable();
            $table->tinyInteger('amenities_caramelos')->nullable();
            $table->tinyInteger('amenities_')->nullable();
            $table->timestamp('fecha_comienzo')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamp('dormitorio_photo')->nullable();
            $table->timestamp('bano_photo')->nullable();
            $table->timestamp('armario_photo')->nullable();
            $table->timestamp('canape_photo')->nullable();
            $table->timestamp('salon_photo')->nullable();
            $table->timestamp('cocina_photo')->nullable();

            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('apartamento_id')->references('id')->on('apartamentos');
            $table->foreign('status_id')->references('id')->on('apartamento_estado');
            $table->foreign('reserva_id')->references('id')->on('reservas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_limpieza');
        
    }
};
