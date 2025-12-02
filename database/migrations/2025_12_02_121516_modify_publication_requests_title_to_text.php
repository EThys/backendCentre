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
        Schema::table('publication_requests', function (Blueprint $table) {
            // Changer title de string(255) à text pour permettre des titres plus longs
            $table->text('title')->change();
            // Changer name de string(255) à text au cas où le nom serait très long
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_requests', function (Blueprint $table) {
            // Revenir à string(255) si nécessaire
            $table->string('title', 255)->change();
            $table->string('name', 255)->change();
        });
    }
};
