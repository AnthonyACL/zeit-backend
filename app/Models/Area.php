<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        'name',       // nombre del área
        'description' // descripción opcional
    ];

    /**
     * Relación muchos a muchos con usuarios
     * Solo los usuarios de nivel bajo estarán relacionados
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'worker_area');
    }
}
