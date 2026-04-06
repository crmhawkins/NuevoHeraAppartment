<?php

namespace App\Traits;

use App\Helpers\TimezoneHelper;

trait HasLocalTimezone
{
    /**
     * Override del método getAttribute para ajustar automáticamente las fechas
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        // Si es una columna de fecha/hora, ajustarla automáticamente
        if (TimezoneHelper::isDateTimeColumn($key) && $value) {
            return TimezoneHelper::adjustToLocal($value);
        }
        
        return $value;
    }
}
