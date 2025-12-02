<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        // Informations sur l'entreprise
        'company_name',
        'legal_form',
        'registration_number',
        'tax_id',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'website',
        
        // Informations sur le demandeur
        'contact_first_name',
        'contact_last_name',
        'contact_position',
        'contact_phone',
        'contact_email',
        
        // Informations sur le projet
        'project_title',
        'project_description',
        'project_type',
        'sector',
        'requested_amount',
        'currency',
        'project_duration',
        'expected_start_date',
        
        // Documents
        'business_plan',
        'financial_statements',
        'other_documents',
        
        // Statut
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'project_duration' => 'integer',
        'financial_statements' => 'array',
        'other_documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'submitted',
    ];
}

