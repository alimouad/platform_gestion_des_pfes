<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('projets', function (Blueprint $table) {
            if (!Schema::hasColumn('projets', 'zone_etude')) {
                $table->json('zone_etude')->nullable()->after('longitude');
            }
        });
    }
    public function down(): void {
        Schema::table('projets', function (Blueprint $table) {
            $table->dropColumn('zone_etude');
        });
    }
};
