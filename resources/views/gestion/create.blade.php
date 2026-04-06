@extends('layouts.appPersonal')

@section('title')
{{ __('Realizando el Apartamento - ') . $apartamentoLimpieza->apartamento->nombre}}
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{Auth::user()->name}}</h5>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-color-segundo">
                    <i class="fa-solid fa-spray-can-sparkles"></i>
                    <span class="ms-2 text-uppercase fw-bold">{{ __('Apartamento - ') .  $apartamentoLimpieza->apartamento->nombre}}</span>
                </div>
                <div class="card-body">
                    <form action="{{route('gestion.store')}}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{$apartamentoLimpieza->id}}">
                        <input type="hidden" name="idReserva" value="{{$id}}">
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">DORMITORIO</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="dormitorio" name="dormitorio">
                                            <label class="form-check-label" for="dormitorio">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_sabanas" name="dormitorio_sabanas">
                                    <label class="form-check-label" for="dormitorio_sabanas">Sabanas</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_cojines" name="dormitorio_cojines">
                                    <label class="form-check-label" for="dormitorio_cojines">Cojines (4 uds)</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_edredon" name="dormitorio_edredon">
                                    <label class="form-check-label" for="dormitorio_edredon">Edredrón</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_funda_edredon" name="dormitorio_funda_edredon">
                                    <label class="form-check-label" for="dormitorio_funda_edredon">Funda de edredrón</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_canape" name="dormitorio_canape">
                                    <label class="form-check-label" for="dormitorio_canape">Canapé</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_manta_cubrepies" name="dormitorio_manta_cubrepies">
                                    <label class="form-check-label" for="dormitorio_manta_cubrepies">Manta gris</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_papel_plancha" name="dormitorio_papel_plancha">
                                    <label class="form-check-label" for="dormitorio_papel_plancha">Papel plancha</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_toallas_rulo" name="dormitorio_toallas_rulo">
                                    <label class="form-check-label" for="dormitorio_toallas_rulo">Toallas Rulo (2 uds.)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="dormitorio_revision_pelos" name="dormitorio_revision_pelos">
                                    <label class="form-check-label" for="dormitorio_revision_pelos">Revisión Pelos</label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">ARMARIO</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="armario" name="armario">
                                            <label class="form-check-label" for="armario">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_perchas" name="armario_perchas">
                                    <label class="form-check-label" for="armario_perchas">Perchas(5 uds.)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_almohada_repuesto_sofa" name="armario_almohada_repuesto_sofa">
                                    <label class="form-check-label" for="armario_almohada_repuesto_sofa">Almohada de repuesto sofá</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_edredon_repuesto_sofa" name="armario_edredon_repuesto_sofa">
                                    <label class="form-check-label" for="armario_edredon_repuesto_sofa">Edredón de respuesto sofá</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_funda_repuesto_edredon" name="armario_funda_repuesto_edredon">
                                    <label class="form-check-label" for="armario_funda_repuesto_edredon">FFunda de repuesto edred.</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_sabanas_repuesto" name="armario_sabanas_repuesto">
                                    <label class="form-check-label" for="armario_sabanas_repuesto">Sábanas de repuesto</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_plancha" name="armario_plancha">
                                    <label class="form-check-label" for="armario_plancha">Plancha</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_tabla_plancha" name="armario_tabla_plancha">
                                    <label class="form-check-label" for="armario_tabla_plancha">Ambientador</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="armario_toalla" name="armario_toalla">
                                    <label class="form-check-label" for="armario_toalla">Toallas</label>
                                </div>

                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">CANAPE</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="canape" name="canape">
                                            <label class="form-check-label" for="canape">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_almohada" name="canape_almohada">
                                    <label class="form-check-label" for="canape_almohada">Almohada</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_gel" name="canape_gel">
                                    <label class="form-check-label" for="canape_gel">Gel</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_sabanas" name="canape_sabanas">
                                    <label class="form-check-label" for="canape_sabanas">Sabanas</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_toallas" name="canape_toallas">
                                    <label class="form-check-label" for="canape_toallas">2 Toallas</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_papel_wc" name="canape_papel_wc">
                                    <label class="form-check-label" for="canape_papel_wc">Papel WC</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_estropajo" name="canape_estropajo">
                                    <label class="form-check-label" for="canape_estropajo">Estropajo</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_bayeta" name="canape_bayeta">
                                    <label class="form-check-label" for="canape_bayeta">Bayeta</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_antihumedad" name="canape_antihumedad">
                                    <label class="form-check-label" for="canape_antihumedad">Antihumedad</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="canape_ambientador" name="canape_ambientador">
                                    <label class="form-check-label" for="canape_ambientador">Ambientador</label>
                                </div>

                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">SALÓN</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="salon" name="salon">
                                            <label class="form-check-label" for="salon">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_cojines" name="salon_cojines">
                                    <label class="form-check-label" for="salon_cojines">Cojines (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_sofa_cama" name="salon_sofa_cama">
                                    <label class="form-check-label" for="salon_sofa_cama">Sofá cama daño o sucio</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_planta_cesta" name="salon_planta_cesta">
                                    <label class="form-check-label" for="salon_planta_cesta">Planta con cesta</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_mandos" name="salon_mandos">
                                    <label class="form-check-label" for="salon_mandos">Mando (TV y Aire Acondicionado)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_tv" name="salon_tv">
                                    <label class="form-check-label" for="salon_tv">Probar TV (Encender y apagar)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_cortinas" name="salon_cortinas">
                                    <label class="form-check-label" for="salon_cortinas">Cortinas (Limpias y bien engachadas)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_sillas" name="salon_sillas">
                                    <label class="form-check-label" for="salon_sillas">Sillas y mesa</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_salvamanteles" name="salon_salvamanteles">
                                    <label class="form-check-label" for="salon_salvamanteles">Salvamanteles (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_estanteria" name="salon_estanteria">
                                    <label class="form-check-label" for="salon_estanteria">Estantería</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_decoracion" name="salon_decoracion">
                                    <label class="form-check-label" for="salon_decoracion">Decoración</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_ambientador" name="salon_ambientador">
                                    <label class="form-check-label" for="salon_ambientador">Ambientador</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="salon_libros_juego" name="salon_libros_juego">
                                    <label class="form-check-label" for="salon_libros_juego">Libros y juegos</label>
                                </div>

                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">COCINA</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="cocina" name="cocina">
                                            <label class="form-check-label" for="cocina">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_vitroceramica" name="cocina_vitroceramica">
                                    <label class="form-check-label" for="cocina_vitroceramica">Vitrocerámica</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_vajilla" name="cocina_vajilla">
                                    <label class="form-check-label" for="cocina_vajilla">Vajilla</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_vasos" name="cocina_vasos">
                                    <label class="form-check-label" for="cocina_vasos">Vasos (4 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_tazas" name="cocina_tazas">
                                    <label class="form-check-label" for="cocina_tazas">Tazas (4 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_tapadera" name="cocina_tapadera">
                                    <label class="form-check-label" for="cocina_tapadera">Tapadera (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_sartenes" name="cocina_sartenes">
                                    <label class="form-check-label" for="cocina_sartenes">Sartenes (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_paño_cocina" name="cocina_paño_cocina">
                                    <label class="form-check-label" for="cocina_paño_cocina">Paño cocina (1 ud)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_cuberteria" name="cocina_cuberteria">
                                    <label class="form-check-label" for="cocina_cuberteria">Cubertería (4 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_cuchillo" name="cocina_cuchillo">
                                    <label class="form-check-label" for="cocina_cuchillo">Cuchillo (1 ud)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_ollas" name="cocina_ollas">
                                    <label class="form-check-label" for="cocina_ollas">Ollas</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_papel_cocina" name="cocina_papel_cocina">
                                    <label class="form-check-label" for="cocina_papel_cocina">Papel de cocina</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_tapadera_micro" name="cocina_tapadera_micro">
                                    <label class="form-check-label" for="cocina_tapadera_micro">Tapadera Micro</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_estropajo" name="cocina_estropajo">
                                    <label class="form-check-label" for="cocina_estropajo">Estropajo/Bayeta</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_mistol" name="cocina_mistol">
                                    <label class="form-check-label" for="cocina_mistol">Mistol</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_tostadora" name="cocina_tostadora">
                                    <label class="form-check-label" for="cocina_tostadora">Tostadora</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_bolsa_basura" name="cocina_bolsa_basura">
                                    <label class="form-check-label" for="cocina_bolsa_basura">Bolsa de basura</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_tabla_cortar" name="cocina_tabla_cortar">
                                    <label class="form-check-label" for="cocina_tabla_cortar">Tabla de cortar</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_escurreplatos" name="cocina_escurreplatos">
                                    <label class="form-check-label" for="cocina_escurreplatos">Escurreplatos</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_bol_escurridor" name="cocina_bol_escurridor">
                                    <label class="form-check-label" for="cocina_bol_escurridor">Bol y Escurridorr</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_utensilios_cocina" name="cocina_utensilios_cocina">
                                    <label class="form-check-label" for="cocina_utensilios_cocina">Utensilios(Pinza, cucharón y espumadera)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="cocina_dolcegusto" name="cocina_dolcegusto">
                                    <label class="form-check-label" for="cocina_dolcegusto">Dolcegusto (3 Capsulas)</label>
                                </div>

                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">BAÑO</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="bano" name="bano">
                                            <label class="form-check-label" for="bano">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_toallas_aseos" name="bano_toallas_aseos">
                                    <label class="form-check-label" for="bano_toallas_aseos">Toallas de Baño (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_toallas_mano" name="bano_toallas_mano">
                                    <label class="form-check-label" for="bano_toallas_mano">Toallas mano (1 ud)</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_alfombra" name="bano_alfombra">
                                    <label class="form-check-label" for="bano_alfombra">Alfombra (1 ud)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_secador" name="bano_secador">
                                    <label class="form-check-label" for="bano_secador">Secador</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_papel" name="bano_papel">
                                    <label class="form-check-label" for="bano_papel">Papel</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_rellenar_gel" name="bano_rellenar_gel">
                                    <label class="form-check-label" for="bano_rellenar_gel">Gel</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_espejo" name="bano_espejo">
                                    <label class="form-check-label" for="bano_espejo">Espejo</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_ganchos" name="bano_ganchos">
                                    <label class="form-check-label" for="bano_ganchos">Ganchos puerta (2 uds)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_muebles" name="bano_muebles">
                                    <label class="form-check-label" for="bano_muebles">Revisar mueble</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="bano_desague" name="bano_desague">
                                    <label class="form-check-label" for="bano_desague">Revisar desagüe (pelos)</label>
                                </div>

                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="header_sub mb-3">
                                <div class="row bg-color-quinto m-1 text-white align-items-center">
                                    <div class="col-8">
                                        <h3 class="titulo mb-0">AMENITIES</h3>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="amenities" name="amenities">
                                            <label class="form-check-label" for="amenities">Listo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="content-check mx-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="amenities_nota_agradecimiento" name="amenities_nota_agradecimiento">
                                    <label class="form-check-label" for="amenities_nota_agradecimiento">Nota de agradecimiento</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="amenities_magdalenas" name="amenities_magdalenas">
                                    <label class="form-check-label" for="amenities_magdalenas">3 magdalenas</label>
                                </div>

                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="amenities_caramelos" name="amenities_caramelos">
                                    <label class="form-check-label" for="amenities_caramelos">Caramelos</label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="fila">
                            <div class="content-check mx-2">
                                <textarea name="observacion" id="observacion" cols="30" rows="6" placeholder="Escriba alguna observacion..." style="width: 100%"></textarea>
                            </div>
                        </div>
                        <div class="fila mt-2">
                            <button type="submit" class="btn btn-guardar w-100 text-uppercase fw-bold">Guardar</button>
                            <button class="btn btn-terminar w-100 mt-2 text-uppercase fw-bold">Terminar</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    console.log('Limpieza de Apartamento by Hawkins.')
</script>
@endsection
