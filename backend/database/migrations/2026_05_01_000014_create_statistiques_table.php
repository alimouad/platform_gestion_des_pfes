<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_universitaire_id')->unique()->constrained('annees_universitaires')->cascadeOnDelete();
            $table->integer('total_projets')->default(0);
            $table->integer('projets_valides')->default(0);
            $table->integer('projets_en_cours')->default(0);
            $table->integer('projets_soutenus')->default(0);
            $table->json('projets_par_domaine')->nullable();
            $table->json('projets_par_region')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistiques');
    }
};