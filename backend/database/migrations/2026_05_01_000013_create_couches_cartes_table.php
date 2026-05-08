<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couches_cartes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donnee_spatiale_id')->constrained('donnees_spatiales')->cascadeOnDelete();
            $table->string('nom');
            $table->string('type_couche'); // 'geojson', 'wms', 'tile', 'shapefile'
            $table->json('geojson')->nullable();
            $table->string('url')->nullable();
            $table->string('couleur', 20)->default('#3388ff');
            $table->decimal('opacite', 3, 2)->default(1.0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couches_cartes');
    }
};