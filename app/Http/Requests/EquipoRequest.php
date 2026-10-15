<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $equipo = $this->route('equipo');

            if (! $equipo instanceof Equipo || $validator->errors()->isNotEmpty()) {
                return;
            }

            $prestado = $equipo->prestamos()->whereNull('fecha_devolucion')->exists();
            $reservado = $equipo->reservas()->where('estado', Reserva::APROBADA)->where('fecha_fin', '>', now())->exists();

            if ($prestado && $this->input('estado') !== Equipo::PRESTADO) {
                $validator->errors()->add('estado', 'Registra la devolución antes de cambiar el estado de un equipo prestado.');
            } elseif (! $prestado && $reservado && $this->input('estado') !== Equipo::RESERVADO) {
                $validator->errors()->add('estado', 'El equipo tiene una reserva aprobada. Gestiona la reserva antes de cambiar su estado.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'codigo.unique' => 'Este código ya está registrado.',
            'estado.in' => 'El estado debe ser Disponible, Prestado o Reservado.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['codigo' => 'código', 'categoria' => 'categoría'];
    }
}
