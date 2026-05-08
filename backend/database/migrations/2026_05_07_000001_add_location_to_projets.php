<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            if (!Schema::hasColumn('projets', 'ville')) {
                $table->string('ville')->nullable()->after('domaine');
            }
            if (!Schema::hasColumn('projets', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('ville');
            }
            if (!Schema::hasColumn('projets', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            $table->dropColumn(['ville', 'latitude', 'longitude']);
        });
    }
};
