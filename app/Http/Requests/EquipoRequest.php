<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'encargado';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $codigoUnico = Rule::unique('equipos', 'codigo');

        if ($this->route('equipo') instanceof Equipo) {
            $codigoUnico->ignore($this->route('equipo'));
        }

        return [
            'codigo' => ['required', 'string', 'max:255', $codigoUnico],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'estado' => ['required', Rule::in(Equipo::ESTADOS)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'codigo.unique' => 'Este código ya está registrado.',
            'estado.in' => 'El estado debe ser Disponible o Prestado.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['codigo' => 'código', 'categoria' => 'categoría'];
    }
}
