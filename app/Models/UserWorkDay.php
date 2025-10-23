<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserWorkDay extends Model
{
use HasFactory;

    protected $fillable = ['user_id', 'work_team_id', 'work_schedule_id', 'day_of_week', 'is_working'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function workTeam()
    {
        return $this->belongsTo(WorkTeam::class);
    }
}