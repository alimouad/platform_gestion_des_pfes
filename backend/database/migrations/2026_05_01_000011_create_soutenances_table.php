<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soutenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->unique()->constrained('projets')->cascadeOnDelete();
            $table->date('date');
            $table->time('heure');
            $table->string('salle');
            $table->string('statut')->default('planifiee');
            $table->string('jury')->nullable();
            $table->decimal('note_finale', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soutenances');
    }
};