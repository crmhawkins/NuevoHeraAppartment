<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NormaCasa;
use Illuminate\Support\Facades\DB;

class NormasCasaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar normas existentes (opcional - descomenta si quieres resetear)
        // NormaCasa::truncate();

        $normas = [
            [
                'icono' => '<i class="fas fa-arrow-right"></i>',
                'titulo' => 'Entrada',
                'descripcion' => 'De las 15:00 a las 23:30<br><br>Tienes que decirle al alojamiento con antelación a qué hora vas a llegar.',
                'orden' => 10,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-arrow-left"></i>',
                'titulo' => 'Salida',
                'descripcion' => 'De las 8:00 a las 11:00',
                'orden' => 20,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-info-circle"></i>',
                'titulo' => 'Cancelación / prepago',
                'descripcion' => 'Las condiciones de cancelación y de pago por adelantado pueden variar según el tipo de alojamiento. <a href="#reservar" style="color: #0071C2; text-decoration: underline;"><strong>Introduce las fechas de tu estancia</strong></a> y consulta las condiciones de la opción que quieres reservar.',
                'orden' => 30,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-clock"></i>',
                'titulo' => 'Horario límite',
                'descripcion' => 'La entrada del alojamiento se cierra de 23:30 a 06:00',
                'orden' => 40,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-info-circle"></i>',
                'titulo' => 'Condiciones sobre daños en el alojamiento',
                'descripcion' => 'Si causas daños al alojamiento durante tu estancia, podrían pedirte un pago de hasta € 150 después del check-out, según las <a href="#" style="color: #0071C2; text-decoration: underline;"><strong>condiciones sobre daños en el alojamiento</strong></a>.',
                'orden' => 50,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-child"></i>',
                'titulo' => 'Camas para niños',
                'descripcion' => '<strong>Condiciones para estancias con niños</strong><br><br>Se pueden alojar niños de cualquier edad.<br><br>Para ver la información correcta sobre precios y ocupación, añade a la búsqueda el número de niños con los que viajas y sus edades.<br><br><strong>Condiciones sobre cunas y camas supletorias</strong><br><br>En este alojamiento no hay cunas ni camas supletorias.',
                'orden' => 60,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-user-lock"></i>',
                'titulo' => 'Restricción por edad',
                'descripcion' => 'Edad mínima para el check-in: 25',
                'orden' => 70,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-smoking-ban"></i>',
                'titulo' => 'Fumadores / No fumadores',
                'descripcion' => 'No se puede fumar.',
                'orden' => 80,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-birthday-cake"></i>',
                'titulo' => 'Fiestas',
                'descripcion' => 'No se pueden celebrar fiestas/eventos',
                'orden' => 90,
                'activo' => true,
            ],
            [
                'icono' => '<i class="fas fa-paw"></i>',
                'titulo' => 'Mascotas',
                'descripcion' => 'No se admiten.',
                'orden' => 100,
                'activo' => true,
            ],
        ];

        foreach ($normas as $norma) {
            NormaCasa::updateOrCreate(
                ['titulo' => $norma['titulo']], // Buscar por título
                $norma // Datos a insertar/actualizar
            );
        }

        $this->command->info('✅ Normas de la casa creadas correctamente: ' . count($normas) . ' normas');
    }
}
