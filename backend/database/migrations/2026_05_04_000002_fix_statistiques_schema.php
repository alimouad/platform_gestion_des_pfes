<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistiques', function (Blueprint $table) {
            // Drop old column
            if (Schema::hasColumn('statistiques', 'coordinateur_id')) {
                $table->dropColumn('coordinateur_id');
            }

            // Add new columns if missing
            if (!Schema::hasColumn('statistiques', 'annee_universitaire_id')) {
                $table->foreignId('annee_universitaire_id')
                      ->unique()
                      ->constrained('annees_universitaires')
                      ->cascadeOnDelete()
                      ->after('id');
            }

            if (!Schema::hasColumn('statistiques', 'projets_en_cours')) {
                $table->integer('projets_en_cours')->default(0)->after('projets_valides');
            }

            if (!Schema::hasColumn('statistiques', 'projets_soutenus')) {
                $table->integer('projets_soutenus')->default(0)->after('projets_en_cours');
            }

            if (!Schema::hasColumn('statistiques', 'projets_par_region')) {
                $table->json('projets_par_region')->nullable()->after('projets_par_domaine');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statistiques', function (Blueprint $table) {
            $table->dropForeign(['annee_universitaire_id']);
            $table->dropColumn(['annee_universitaire_id', 'projets_en_cours', 'projets_soutenus', 'projets_par_region']);
            $table->foreignId('coordinateur_id')->nullable()->constrained('coordinateurs')->nullOnDelete();
        });
    }
};
