<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. users — add prenom
        if (!Schema::hasColumn('users', 'prenom')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('prenom')->default('')->after('nom');
            });
        }

        // 2. professeurs — rename office → bureau
        if (Schema::hasColumn('professeurs', 'office') && !Schema::hasColumn('professeurs', 'bureau')) {
            Schema::table('professeurs', function (Blueprint $table) {
                $table->renameColumn('office', 'bureau');
            });
        }

        // 3. etudiants — add filiere
        if (!Schema::hasColumn('etudiants', 'filiere')) {
            Schema::table('etudiants', function (Blueprint $table) {
                $table->string('filiere')->nullable()->after('code_etudiant');
            });
        }

        // 4. annees_universitaires — add date_debut, date_fin
        if (!Schema::hasColumn('annees_universitaires', 'date_debut')) {
            Schema::table('annees_universitaires', function (Blueprint $table) {
                $table->date('date_debut')->nullable()->after('statut');
                $table->date('date_fin')->nullable()->after('date_debut');
            });
        }

        // 5. soutenances — add jury
        if (!Schema::hasColumn('soutenances', 'jury')) {
            Schema::table('soutenances', function (Blueprint $table) {
                $table->string('jury')->nullable()->after('statut');
            });
        }

        // 6. projets — fix statut default to brouillon
        DB::statement("ALTER TABLE projets ALTER COLUMN statut SET DEFAULT 'brouillon'");
        DB::statement("UPDATE projets SET statut = 'brouillon' WHERE statut = 'draft'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('prenom');
        });

        if (Schema::hasColumn('professeurs', 'bureau')) {
            Schema::table('professeurs', function (Blueprint $table) {
                $table->renameColumn('bureau', 'office');
            });
        }

        Schema::table('etudiants', function (Blueprint $table) {
            $table->dropColumn('filiere');
        });

        Schema::table('annees_universitaires', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
        });

        Schema::table('soutenances', function (Blueprint $table) {
            $table->dropColumn('jury');
        });
    }
};
