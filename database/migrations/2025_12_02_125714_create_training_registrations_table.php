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
        Schema::create('training_registrations', function (Blueprint $table) {
            $table->id();
            
            // Informations personnelles
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            
            // Informations sur la formation
            $table->string('program'); // training1, training2, training3, training4
            $table->string('program_name')->nullable(); // Nom lisible du programme
            
            // Message optionnel
            $table->text('message')->nullable();
            
            // Informations supplémentaires
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            
            // Statut de l'inscription
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            
            // Dates
            $table->dateTime('registration_date')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('email');
            $table->index('status');
            $table->index('program');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_registrations');
    }
};
