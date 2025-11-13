<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            //Un proyecto pertenece a un equipo de trabajo
            $table->foreignId('work_team_id')
                  ->nullable() // opcional si quieres permitir proyectos sin equipo al inicio
                  ->constrained('work_teams')
                  ->onDelete('set null'); // Si se elimina el equipo, no borra el proyecto

            $table->string('name');
            $table->text('description')->nullable();
            $table->text('recursos')->nullable();
            $table->enum('status', ['pendiente', 'en_progreso', 'finalizado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
