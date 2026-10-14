<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña actual es incorrecta.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'date_format' => 'El campo :attribute debe tener el formato :format.',
    'after' => 'El campo :attribute debe ser posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
    'max' => ['string' => 'El campo :attribute no puede superar los :max caracteres.'],
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.'],
    'attributes' => [
        'name' => 'nombre', 'email' => 'correo electrónico', 'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña', 'current_password' => 'contraseña actual',
        'form.email' => 'correo electrónico', 'form.password' => 'contraseña',
    ],
];
