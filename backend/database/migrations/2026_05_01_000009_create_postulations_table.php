<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('projet_id')->constrained('projets')->cascadeOnDelete();
            $table->string('statut')->default('en_attente');
            $table->timestamp('date_candidature')->useCurrent();
            $table->timestamps();

            $table->unique(['etudiant_id', 'projet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulations');
    }
};