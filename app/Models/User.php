<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ENCARGADO = 'encargado';

    public const ROLE_ESTUDIANTE = 'estudiante';

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->ensureCanBeDeleted();
        });
    }

    public function ensureCanBeDeleted(): void
    {
        if ($this->prestamos()->exists() || Reserva::where('estudiante_id', $this->id)->exists()) {
            throw ValidationException::withMessages([
                'password' => 'No puedes eliminar una cuenta con préstamos o reservas registrados. Se debe conservar su historial.',
            ]);
        }
    }

    /** El destino se decide con el rol guardado en la base de datos. */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ENCARGADO => 'dashboard.encargado',
            self::ROLE_ESTUDIANTE => 'dashboard.estudiante',
            default => abort(403, 'Tu cuenta no tiene un rol autorizado.'),
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'estudiante_id');
    }
}
