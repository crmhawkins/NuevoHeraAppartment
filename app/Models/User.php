<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Reserva;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'inactive',
        'avatar',
        'phone',
        'address',
        'birth_date',
        'emergency_contact',
        'emergency_phone',
        'idioma_preferido'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'stripe_payment_methods' => 'array',
    ];

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // /**
    //  * The roles that belong to the user.
    //  */
    // Relaciones
    public function empleadaHorario()
    {
        return $this->hasOne(EmpleadaHorario::class);
    }
    
    // public function roles()
    // {
    //     // Asumiendo que existe una tabla 'roles' y la relación es muchos-a-muchos
    //     return $this->belongsToMany(Role::class);
    // }

    public function redirectToDashboard()
    {
        switch ($this->role) {
            case 'ADMIN':
                $reservasPendientes = Reserva::apartamentosPendiente();
                $reservasSalida = Reserva::apartamentosSalida();
                return view('admin.dashboard', compact('reservasPendientes', 'reservasSalida'));
            case 'USER':
                $reservasPendientes = Reserva::apartamentosPendiente();
                $reservasSalida = Reserva::apartamentosSalida();
                return view('user.dashboard', compact('reservasPendientes', 'reservasSalida'));
            default:
                abort(403, 'No tienes permiso para acceder a esta página.');
        }
    }

    /**
     * Obtener reservas del usuario a través del email del cliente
     */
    public function reservas()
    {
        return $this->hasManyThrough(
            Reserva::class,
            \App\Models\Cliente::class,
            'email', // Foreign key en clientes
            'cliente_id', // Foreign key en reservas
            'email', // Local key en users
            'id' // Local key en clientes
        );
    }

}
