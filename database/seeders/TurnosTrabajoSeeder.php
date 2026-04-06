<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoTarea;
use App\Models\EmpleadaHorario;
use App\Models\User;

class TurnosTrabajoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tipos de tareas por defecto
        $tiposTareas = [
            [
                'nombre' => 'Limpieza de Apartamento',
                'descripcion' => 'Limpieza completa de apartamento incluyendo habitaciones, baños, cocina y salón',
                'categoria' => 'limpieza_apartamento',
                'prioridad_base' => 8,
                'tiempo_estimado_minutos' => 120,
                'dias_max_sin_limpiar' => 1,
                'incremento_prioridad_por_dia' => 2,
                'prioridad_maxima' => 10,
                'requiere_apartamento' => true,
                'requiere_zona_comun' => false,
                'instrucciones' => 'Seguir checklist de limpieza del edificio. Verificar amenities y reportar averías.'
            ],
            [
                'nombre' => 'Limpieza de Zona Común',
                'descripcion' => 'Limpieza de zonas comunes como recepción, piscina, gimnasio, etc.',
                'categoria' => 'limpieza_zona_comun',
                'prioridad_base' => 6,
                'tiempo_estimado_minutos' => 90,
                'dias_max_sin_limpiar' => 2,
                'incremento_prioridad_por_dia' => 1,
                'prioridad_maxima' => 9,
                'requiere_apartamento' => false,
                'requiere_zona_comun' => true,
                'instrucciones' => 'Seguir checklist específico de la zona común. Verificar equipos y reportar incidencias.'
            ],
            [
                'nombre' => 'Limpieza de Oficina',
                'descripcion' => 'Limpieza de oficinas administrativas',
                'categoria' => 'limpieza_oficina',
                'prioridad_base' => 5,
                'tiempo_estimado_minutos' => 60,
                'dias_max_sin_limpiar' => 3,
                'incremento_prioridad_por_dia' => 1,
                'prioridad_maxima' => 8,
                'requiere_apartamento' => false,
                'requiere_zona_comun' => false,
                'instrucciones' => 'Limpiar escritorios, vaciar papeleras, aspirar suelo. No tocar documentos.'
            ],
            [
                'nombre' => 'Preparación de Amenities',
                'descripcion' => 'Preparar y reponer amenities en apartamentos',
                'categoria' => 'preparacion_amenities',
                'prioridad_base' => 4,
                'tiempo_estimado_minutos' => 45,
                'dias_max_sin_limpiar' => 1,
                'incremento_prioridad_por_dia' => 1,
                'prioridad_maxima' => 7,
                'requiere_apartamento' => false,
                'requiere_zona_comun' => false,
                'instrucciones' => 'Verificar stock de amenities y reponer según lista. Reportar faltantes.'
            ],
            [
                'nombre' => 'Planchado',
                'descripcion' => 'Planchado de ropa de cama y toallas',
                'categoria' => 'planchado',
                'prioridad_base' => 3,
                'tiempo_estimado_minutos' => 90,
                'dias_max_sin_limpiar' => 2,
                'incremento_prioridad_por_dia' => 1,
                'prioridad_maxima' => 6,
                'requiere_apartamento' => false,
                'requiere_zona_comun' => false,
                'instrucciones' => 'Planchar ropa de cama y toallas según necesidades. Verificar calidad del planchado.'
            ],
            [
                'nombre' => 'Mantenimiento Básico',
                'descripcion' => 'Tareas básicas de mantenimiento y reparación',
                'categoria' => 'mantenimiento',
                'prioridad_base' => 7,
                'tiempo_estimado_minutos' => 60,
                'dias_max_sin_limpiar' => 1,
                'incremento_prioridad_por_dia' => 2,
                'prioridad_maxima' => 10,
                'requiere_apartamento' => false,
                'requiere_zona_comun' => false,
                'instrucciones' => 'Realizar mantenimiento básico según lista. Reportar problemas complejos.'
            ]
        ];

        foreach ($tiposTareas as $tipoTarea) {
            TipoTarea::create($tipoTarea);
        }

        $this->command->info('✅ Tipos de tareas creados exitosamente');

        // Crear horarios por defecto para empleadas de limpieza existentes
        $empleadasLimpieza = User::where('role', 'LIMPIEZA')
            ->where('inactive', null)
            ->get();

        foreach ($empleadasLimpieza as $empleada) {
            // Verificar si ya tiene horario configurado
            if (!EmpleadaHorario::where('user_id', $empleada->id)->exists()) {
                EmpleadaHorario::create([
                    'user_id' => $empleada->id,
                    'horas_contratadas_dia' => 8,
                    'dias_libres_mes' => 2,
                    'hora_inicio_atencion' => '08:00:00',
                    'hora_fin_atencion' => '17:00:00',
                    'lunes' => true,
                    'martes' => true,
                    'miercoles' => true,
                    'jueves' => true,
                    'viernes' => true,
                    'sabado' => false,
                    'domingo' => false,
                    'activo' => true,
                    'observaciones' => 'Horario por defecto - configurar según necesidades'
                ]);
            }
        }

        $this->command->info('✅ Horarios de empleadas configurados exitosamente');
        $this->command->info("📊 Total empleadas configuradas: {$empleadasLimpieza->count()}");
    }
}