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
        Schema::create('publication_requests', function (Blueprint $table) {
            $table->id();
            
            // Informations personnelles
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('institution')->nullable();
            $table->string('position')->nullable();
            
            // Informations sur le travail de recherche
            $table->string('title');
            $table->text('abstract');
            $table->enum('type', ['article', 'research-paper', 'book', 'report', 'other'])->default('article');
            $table->json('domains')->nullable(); // Domaines de recherche
            
            // Informations sur les auteurs
            $table->string('authors'); // Liste des auteurs séparés par des virgules
            $table->text('co_authors')->nullable();
            
            // Informations supplémentaires
            $table->text('keywords')->nullable();
            $table->text('message')->nullable();
            
            // Statut de la demande
            $table->enum('status', ['pending', 'under-review', 'accepted', 'rejected', 'published'])->default('pending');
            
            // Dates
            $table->timestamp('submission_date')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('status');
            $table->index('email');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_requests');
    }
};
