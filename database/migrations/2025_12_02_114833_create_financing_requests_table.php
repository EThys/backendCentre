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
        Schema::create('financing_requests', function (Blueprint $table) {
            $table->id();
            
            // Informations sur l'entreprise
            $table->string('company_name');
            $table->string('legal_form')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('RDC');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            
            // Informations sur le demandeur
            $table->string('contact_first_name');
            $table->string('contact_last_name');
            $table->string('contact_position')->nullable();
            $table->string('contact_phone');
            $table->string('contact_email');
            
            // Informations sur le projet
            $table->string('project_title');
            $table->text('project_description');
            $table->enum('project_type', ['startup', 'expansion', 'equipment', 'working-capital', 'other'])->default('other');
            $table->string('sector')->nullable();
            $table->decimal('requested_amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->integer('project_duration')->nullable(); // en mois
            $table->date('expected_start_date')->nullable();
            
            // Documents (chemins vers les fichiers)
            $table->string('business_plan')->nullable();
            $table->json('financial_statements')->nullable();
            $table->json('other_documents')->nullable();
            
            // Statut
            $table->enum('status', ['draft', 'submitted', 'under-review', 'approved', 'rejected', 'on-hold'])->default('submitted');
            $table->text('review_notes')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('status');
            $table->index('contact_email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_requests');
    }
};
