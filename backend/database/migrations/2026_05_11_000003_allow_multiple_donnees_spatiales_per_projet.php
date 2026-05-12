<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donnees_spatiales', function (Blueprint $table) {
            $table->dropUnique(['projet_id']);
        });
    }

    public function down(): void
    {
        Schema::table('donnees_spatiales', function (Blueprint $table) {
            $table->unique('projet_id');
        });
    }
};
