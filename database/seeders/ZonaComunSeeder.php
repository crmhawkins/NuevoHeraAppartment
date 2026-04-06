<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ZonaComun;
use App\Models\ChecklistZonaComun;
use App\Models\ItemChecklistZonaComun;

class ZonaComunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear zonas comunes
        $zonasComunes = [
            [
                'nombre' => 'Recepción',
                'descripcion' => 'Área de recepción y atención al cliente',
                'ubicacion' => 'Planta baja',
                'tipo' => 'recepcion',
                'orden' => 1
            ],
            [
                'nombre' => 'Piscina',
                'descripcion' => 'Zona de piscina y área de baño',
                'ubicacion' => 'Exterior',
                'tipo' => 'piscina',
                'orden' => 2
            ],
            [
                'nombre' => 'Gimnasio',
                'descripcion' => 'Sala de gimnasio y equipos de ejercicio',
                'ubicacion' => 'Primer piso',
                'tipo' => 'gimnasio',
                'orden' => 3
            ],
            [
                'nombre' => 'Terraza',
                'descripcion' => 'Terraza común con vistas',
                'ubicacion' => 'Última planta',
                'tipo' => 'terraza',
                'orden' => 4
            ],
            [
                'nombre' => 'Área de Servicio',
                'descripcion' => 'Zona de lavandería y servicios',
                'ubicacion' => 'Sótano',
                'tipo' => 'area_servicio',
                'orden' => 5
            ]
        ];

        foreach ($zonasComunes as $zona) {
            ZonaComun::create($zona);
        }

        // Crear checklists para zonas comunes
        $checklists = [
            [
                'nombre' => 'Checklist Recepción',
                'descripcion' => 'Lista de verificación para la limpieza de recepción',
                'categoria' => 'recepcion',
                'orden' => 1
            ],
            [
                'nombre' => 'Checklist Piscina',
                'descripcion' => 'Lista de verificación para la limpieza de piscina',
                'categoria' => 'piscina',
                'orden' => 2
            ],
            [
                'nombre' => 'Checklist Gimnasio',
                'descripcion' => 'Lista de verificación para la limpieza de gimnasio',
                'categoria' => 'gimnasio',
                'orden' => 3
            ],
            [
                'nombre' => 'Checklist Terraza',
                'descripcion' => 'Lista de verificación para la limpieza de terraza',
                'categoria' => 'terraza',
                'orden' => 4
            ],
            [
                'nombre' => 'Checklist Área de Servicio',
                'descripcion' => 'Lista de verificación para la limpieza de área de servicio',
                'categoria' => 'area_servicio',
                'orden' => 5
            ]
        ];

        foreach ($checklists as $checklist) {
            ChecklistZonaComun::create($checklist);
        }

        // Crear items para cada checklist
        $itemsPorChecklist = [
            'recepcion' => [
                'Limpiar mostrador de recepción',
                'Aspirar suelo de recepción',
                'Limpiar cristales de entrada',
                'Revisar y limpiar área de espera',
                'Limpiar teléfono y equipos',
                'Revisar iluminación'
            ],
            'piscina' => [
                'Limpiar bordes de piscina',
                'Revisar cloro y pH',
                'Limpiar duchas exteriores',
                'Limpiar área de tumbonas',
                'Revisar equipos de piscina',
                'Limpiar vestuarios'
            ],
            'gimnasio' => [
                'Limpiar equipos de gimnasio',
                'Aspirar suelo del gimnasio',
                'Limpiar espejos',
                'Revisar ventilación',
                'Limpiar área de pesas',
                'Revisar iluminación'
            ],
            'terraza' => [
                'Limpiar suelo de terraza',
                'Limpiar mobiliario exterior',
                'Revisar drenajes',
                'Limpiar barandillas',
                'Revisar iluminación exterior',
                'Limpiar macetas y plantas'
            ],
            'area_servicio' => [
                'Limpiar lavadoras',
                'Limpiar secadoras',
                'Aspirar área de lavandería',
                'Limpiar fregaderos',
                'Revisar iluminación',
                'Limpiar armarios de almacenamiento'
            ]
        ];

        foreach ($itemsPorChecklist as $categoria => $items) {
            $checklist = ChecklistZonaComun::where('categoria', $categoria)->first();
            if ($checklist) {
                foreach ($items as $index => $item) {
                    ItemChecklistZonaComun::create([
                        'checklist_id' => $checklist->id,
                        'nombre' => $item,
                        'categoria' => $categoria,
                        'orden' => $index + 1
                    ]);
                }
            }
        }
    }
}
