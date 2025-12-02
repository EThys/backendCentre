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
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('status', ['active', 'unsubscribed', 'pending'])->default('active');
            
            // Préférences d'abonnement (JSON)
            $table->json('preferences')->nullable();
            
            // Dates
            $table->dateTime('subscribed_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('email');
            $table->index('status');
            $table->index('subscribed_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
