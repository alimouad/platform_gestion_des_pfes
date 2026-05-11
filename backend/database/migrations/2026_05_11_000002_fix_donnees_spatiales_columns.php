<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donnees_spatiales', function (Blueprint $table) {
            if (!Schema::hasColumn('donnees_spatiales', 'type_geometrie')) {
                $table->string('type_geometrie')->nullable()->after('projet_id');
            }
            if (!Schema::hasColumn('donnees_spatiales', 'description_zone')) {
                $table->text('description_zone')->nullable()->after('surface');
            }
            if (!Schema::hasColumn('donnees_spatiales', 'nom_fichier')) {
                $table->string('nom_fichier')->nullable()->after('type_geometrie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donnees_spatiales', function (Blueprint $table) {
            $table->dropColumn(['type_geometrie', 'description_zone', 'nom_fichier']);
        });
    }
};
