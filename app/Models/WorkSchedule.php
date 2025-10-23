<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'work_team_id', 'start_time', 'end_time']; 

    public function workTeam()
    {
        return $this->belongsTo(WorkTeam::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_work_days', 'work_schedule_id', 'user_id')
            ->withPivot(['work_team_id', 'day_of_week', 'is_working'])
            ->withTimestamps();
    }
}
