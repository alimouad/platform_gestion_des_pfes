<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            if (!Schema::hasColumn('depots', 'commentaire')) {
                $table->text('commentaire')->nullable()->after('statut_validation');
            }
            if (!Schema::hasColumn('depots', 'depose_le')) {
                $table->timestamp('depose_le')->useCurrent()->after('commentaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            $table->dropColumn(['commentaire', 'depose_le']);
        });
    }
};
