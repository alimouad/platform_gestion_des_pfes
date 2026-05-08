<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'departement_id')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('departement_id')->nullable()->after('role')->constrained('departements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
        });
    }
};
