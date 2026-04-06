<?php

namespace App\Helpers;

class TimezoneHelper 
{
    /**
     * Ajustar datetime a la zona horaria local (ya manejado por Laravel)
     */
    public static function adjustToLocal($datetime)
    {
        if (!$datetime) return null;
        // Laravel ya maneja la zona horaria correctamente, no necesitamos ajuste manual
        return \Carbon\Carbon::parse($datetime);
    }
    
    /**
     * Detectar si una columna es de fecha/hora
     */
    public static function isDateTimeColumn($columnName)
    {
        $dateTimeColumns = [
            'created_at', 'updated_at', 'deleted_at',
            'hora_entrada', 'hora_salida',
            'fecha_comienzo', 'fecha_fin',
            'fecha_entrada', 'fecha_salida',
            'fecha_emision', 'fecha_vencimiento',
            'fecha_limpieza', 'fecha_consentimiento',
            'fecha_analisis', 'fecha_pausa', 'fin_pausa',
            'inicio_pausa', 'fecha_inicio_real', 'fecha_fin_real',
            'fecha_creacion', 'fecha_asignacion'
        ];
        
        return in_array($columnName, $dateTimeColumns) || 
               str_contains($columnName, '_at') || 
               str_contains($columnName, 'fecha_') ||
               str_contains($columnName, 'hora_') ||
               str_contains($columnName, 'inicio_') ||
               str_contains($columnName, 'fin_');
    }
}
