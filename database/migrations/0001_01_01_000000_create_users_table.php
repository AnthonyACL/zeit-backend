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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // id
            $table->string('name'); // Nombre
            $table->string('last_name'); // Apellido
            $table->string('email')->unique(); // Email único
            $table->string('password'); // Contraseña
            $table->string('phone')->nullable(); // Teléfono opcional
            $table->unsignedBigInteger('company_id')->nullable(); // Relación a empresa (puede ser null)
            $table->string('institution')->nullable(); // Institución
            $table->string('career')->nullable(); // Carrera
            $table->time('start_time')->nullable(); // Hora de inicio de trabajo
            $table->time('break_start')->nullable(); // Hora de inicio de descanso
            $table->decimal('longitude', 10, 7)->nullable(); // Longitud geográfica
            $table->decimal('latitude', 10, 7)->nullable(); // Latitud geográfica
            $table->rememberToken(); // Token de "remember me"
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
