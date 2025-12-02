<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('actualities', function (Blueprint $table) {
            $table->json('learning_points')->nullable()->after('content');
            $table->json('key_points')->nullable()->after('learning_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actualities', function (Blueprint $table) {
            $table->dropColumn(['learning_points', 'key_points']);
        });
    }
};
