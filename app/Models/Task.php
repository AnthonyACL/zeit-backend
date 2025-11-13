<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'assignee_id',
        'task_image',
        'priority',
        'status',
        'order',
        'project_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Una tarea puede estar asignada a un usuario
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
    
}
