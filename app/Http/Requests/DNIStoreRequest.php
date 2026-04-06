<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DNIStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reserva = \App\Models\Reserva::find($this->get('id'));
        $numeroPersonas = $reserva ? $reserva->numero_personas : 1;
        
        $rules = [];
        
        // Reglas para cada persona
        for ($i = 0; $i < $numeroPersonas; $i++) {
            $rules["nombre_{$i}"] = 'required|string|max:255';
            $rules["apellido1_{$i}"] = 'required|string|max:255';
            $rules["apellido2_{$i}"] = 'nullable|string|max:255';
            $rules["fecha_nacimiento_{$i}"] = 'required|date|before:today|after:1900-01-01';
            $rules["nacionalidad_{$i}"] = 'required|string|max:255';
            $rules["tipo_documento_{$i}"] = 'required|string|in:D,P';
            $rules["num_identificacion_{$i}"] = 'required|string|max:20|regex:/^[A-Za-z0-9\-]+$/';
            $rules["numero_soporte_{$i}"] = 'nullable|required_if:tipo_documento_' . $i . ',D|string|max:20';
            $rules["fecha_expedicion_doc_{$i}"] = 'required|date|before_or_equal:today';
            $rules["sexo_{$i}"] = 'required|string|in:Masculino,Femenino';
            $rules["email_{$i}"] = 'required|email|max:255';
            // Campos obligatorios MIR para el titular (persona 0)
            if ($i === 0) {
                $rules["telefono_{$i}"] = 'required|string|max:20';
                $rules["direccion_{$i}"] = 'required|string|max:500';
                $rules["codigo_postal_{$i}"] = 'required|string|max:10';
            } else {
                $rules["telefono_{$i}"] = 'nullable|string|max:20';
                $rules["direccion_{$i}"] = 'nullable|string|max:500';
                $rules["codigo_postal_{$i}"] = 'nullable|string|max:10';
            }
            
            // Reglas para archivos según tipo de documento
            $tipoDocumento = $this->get("tipo_documento_{$i}");
            
            if ($tipoDocumento === 'P') {
                // Pasaporte - solo una imagen (opcional)
                $rules["pasaporte_{$i}"] = 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:min_width=100,min_height=100'; // 5MB max
            } else {
                // DNI - frontal y trasera (opcionales)
                $rules["fontal_{$i}"] = 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:min_width=100,min_height=100'; // 5MB max
                $rules["trasera_{$i}"] = 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:min_width=100,min_height=100'; // 5MB max
            }
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $reserva = \App\Models\Reserva::find($this->get('id'));
        $numeroPersonas = $reserva ? $reserva->numero_personas : 1;
        
        $messages = [];
        
        for ($i = 0; $i < $numeroPersonas; $i++) {
            $persona = $i === 0 ? 'Huésped principal' : "Acompañante {$i}";
            
            $messages["nombre_{$i}.required"] = "El nombre del {$persona} es obligatorio.";
            $messages["apellido1_{$i}.required"] = "El primer apellido del {$persona} es obligatorio.";
            $messages["fecha_nacimiento_{$i}.required"] = "La fecha de nacimiento del {$persona} es obligatoria.";
            $messages["fecha_nacimiento_{$i}.before"] = "La fecha de nacimiento del {$persona} debe ser anterior a hoy.";
            $messages["nacionalidad_{$i}.required"] = "La nacionalidad del {$persona} es obligatoria.";
            $messages["tipo_documento_{$i}.required"] = "El tipo de documento del {$persona} es obligatorio.";
            $messages["tipo_documento_{$i}.in"] = "El tipo de documento del {$persona} debe ser DNI o Pasaporte.";
            $messages["num_identificacion_{$i}.required"] = "El número de identificación del {$persona} es obligatorio.";
            $messages["num_identificacion_{$i}.regex"] = "El número de identificación del {$persona} solo puede contener letras, números y guiones.";
            $messages["numero_soporte_{$i}.required_if"] = "El número de soporte del documento del {$persona} es obligatorio para DNI.";
            $messages["fecha_nacimiento_{$i}.after"] = "La fecha de nacimiento del {$persona} debe ser posterior a 01/01/1900.";
            $messages["telefono_{$i}.required"] = "El teléfono del {$persona} es obligatorio.";
            $messages["direccion_{$i}.required"] = "La dirección del {$persona} es obligatoria.";
            $messages["codigo_postal_{$i}.required"] = "El código postal del {$persona} es obligatorio.";
            $messages["fecha_expedicion_doc_{$i}.required"] = "La fecha de expedición del {$persona} es obligatoria.";
            $messages["fecha_expedicion_doc_{$i}.before_or_equal"] = "La fecha de expedición del {$persona} no puede ser futura.";
            $messages["sexo_{$i}.required"] = "El sexo del {$persona} es obligatorio.";
            $messages["sexo_{$i}.in"] = "El sexo del {$persona} debe ser Masculino o Femenino.";
            $messages["email_{$i}.required"] = "El email del {$persona} es obligatorio.";
            $messages["email_{$i}.email"] = "El email del {$persona} debe ser válido.";
            
            // Mensajes para archivos (opcionales)
            $messages["pasaporte_{$i}.image"] = "El archivo del pasaporte del {$persona} debe ser una imagen válida.";
            $messages["pasaporte_{$i}.mimes"] = "El pasaporte del {$persona} debe ser un archivo JPEG, JPG, PNG o WEBP.";
            $messages["pasaporte_{$i}.max"] = "El pasaporte del {$persona} no puede superar los 5MB.";
            $messages["pasaporte_{$i}.dimensions"] = "La imagen del pasaporte del {$persona} debe tener al menos 100x100 píxeles.";
            
            $messages["fontal_{$i}.image"] = "El archivo frontal del DNI del {$persona} debe ser una imagen válida.";
            $messages["fontal_{$i}.mimes"] = "La imagen frontal del DNI del {$persona} debe ser un archivo JPEG, JPG, PNG o WEBP.";
            $messages["fontal_{$i}.max"] = "La imagen frontal del DNI del {$persona} no puede superar los 5MB.";
            $messages["fontal_{$i}.dimensions"] = "La imagen frontal del DNI del {$persona} debe tener al menos 100x100 píxeles.";
            
            $messages["trasera_{$i}.image"] = "El archivo trasero del DNI del {$persona} debe ser una imagen válida.";
            $messages["trasera_{$i}.mimes"] = "La imagen trasera del DNI del {$persona} debe ser un archivo JPEG, JPG, PNG o WEBP.";
            $messages["trasera_{$i}.max"] = "La imagen trasera del DNI del {$persona} no puede superar los 5MB.";
            $messages["trasera_{$i}.dimensions"] = "La imagen trasera del DNI del {$persona} debe tener al menos 100x100 píxeles.";
        }
        
        return $messages;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verificar que el post_max_size no se haya excedido
            if ($_SERVER['CONTENT_LENGTH'] > $this->getMaxPostSize()) {
                $validator->errors()->add('files', 'El tamaño total de los archivos excede el límite permitido.');
            }
        });
    }

    /**
     * Get the maximum post size in bytes.
     *
     * @return int
     */
    private function getMaxPostSize()
    {
        $maxPostSize = ini_get('post_max_size');
        $unit = strtoupper(substr($maxPostSize, -1));
        $value = (int) $maxPostSize;
        
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }
}
