<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkTeam extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withTimestamps();
    }

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
    
}
