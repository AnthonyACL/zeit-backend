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

    protected $fillable = [
        'name',
        'last_name',
        'dni',
        'email',
        'password',
        'phone',
        'profile_image',
        'company_id',
        'institution',
        'career',
        'start_time',
        'break_start',
        'longitude',
        'latitude',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'start_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function workTeams()
    {
        return $this->belongsToMany(WorkTeam::class, 'team_user')
            ->withTimestamps();
    }

    public function workSchedules()
    {
        return $this->belongsToMany(WorkSchedule::class, 'user_work_days', 'user_id', 'work_schedule_id')
            ->withPivot(['days', 'assigned_by'])
            ->withTimestamps();
    }
}
