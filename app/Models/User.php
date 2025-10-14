<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'company_id',
        'institution',
        'career',
        'start_time',
        'break_start',
        'longitude',
        'latitude',
    ];

    /**
     * Los atributos ocultos para arrays/JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos convertidos automáticamente a tipos nativos
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'start_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
    ];

    /**
     * Mutator para encriptar contraseña automáticamente
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'worker_area');
    }
}
