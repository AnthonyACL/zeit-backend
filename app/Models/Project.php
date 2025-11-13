<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'recursos',
        'status',
        'work_team_id',
    ];

    public function workTeam()
    {
        return $this->belongsTo(WorkTeam::class);
    }

    // Un proyecto tiene muchas tareas
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

}
