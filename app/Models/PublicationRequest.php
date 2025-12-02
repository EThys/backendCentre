<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        // Informations personnelles
        'name',
        'email',
        'phone',
        'institution',
        'position',
        
        // Informations sur le travail de recherche
        'title',
        'abstract',
        'type',
        'domains',
        
        // Informations sur les auteurs
        'authors',
        'co_authors',
        
        // Informations supplémentaires
        'keywords',
        'message',
        
        // Fichiers
        'document_file',
        'document_image',
        
        // Statut de la demande
        'status',
        
        // Dates
        'submission_date',
        'reviewed_at',
        'published_at',
    ];

    protected $casts = [
        'domains' => 'array',
        'submission_date' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];
}

