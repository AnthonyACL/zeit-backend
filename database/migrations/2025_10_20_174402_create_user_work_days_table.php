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
        Schema::create('user_work_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_team_id')->constrained('work_teams')->onDelete('cascade');
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->onDelete('cascade');
            $table->enum('day_of_week', ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']);
            $table->boolean('is_working')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_work_days');
    }
};
