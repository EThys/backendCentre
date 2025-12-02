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
        Schema::table('financing_requests', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('contact_phone')->nullable()->change();
            $table->decimal('requested_amount', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_requests', function (Blueprint $table) {
            $table->text('address')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('contact_phone')->nullable(false)->change();
            $table->decimal('requested_amount', 15, 2)->default(null)->change();
        });
    }
};
