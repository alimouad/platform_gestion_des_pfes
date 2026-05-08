<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('donnees_spatiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->unique()->constrained('projets')->cascadeOnDelete();
            $table->string('type_geometrie')->nullable();
            $table->json('geojson')->nullable();
            $table->decimal('surface', 15, 4)->nullable();
            $table->text('description_zone')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE donnees_spatiales ADD COLUMN geometrie geometry(Geometry,4326)');
        DB::statement('CREATE INDEX donnees_spatiales_geometrie_idx ON donnees_spatiales USING GIST (geometrie)');
    }

    public function down(): void
    {
        Schema::dropIfExists('donnees_spatiales');
    }
};