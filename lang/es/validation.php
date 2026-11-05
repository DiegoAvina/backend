<?php

/*
| Mensajes de validación en español (México) para las reglas que usa la API.
| Las claves ausentes caen al idioma de respaldo (inglés).
*/

return [
    'accepted' => 'Debes aceptar el aviso de privacidad para continuar.',
    'array' => 'El campo :attribute no tiene un formato válido.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'email' => 'Ingresa un correo electrónico válido.',
    'enum' => 'Selecciona una opción válida para :attribute.',
    'exists' => 'Selecciona una opción válida para :attribute.',
    'in' => 'Selecciona una opción válida para :attribute.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'Debe tener como máximo :max caracteres.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'Debe tener al menos :min caracteres.',
    ],
    'prohibited' => 'El campo :attribute no está permitido.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'uuid' => 'El campo :attribute no tiene un formato válido.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
        'company' => 'empresa',
        'role' => 'puesto',
        'consent' => 'aviso de privacidad',
        'subject' => 'tema',
        'message' => 'mensaje',
        'service' => 'servicio',
        'budget' => 'presupuesto',
        'timeline' => 'plazo',
        'product' => 'producto',
        'company_size' => 'tamaño de la empresa',
        'topic' => 'tema del diagnóstico',
        'contact_method' => 'modalidad de reunión',
        'availability' => 'disponibilidad',
        'status' => 'estado',
        'group' => 'línea de servicio',
        'type' => 'tipo',
        'category' => 'categoría',
        'featured' => 'destacado',
        'sort' => 'orden',
        'per_page' => 'resultados por página',
        'page' => 'página',
    ],
];
