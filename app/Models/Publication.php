<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Publication extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'abstract',
        'content',
        'image',
        'type',
        'authors',
        'journal',
        'publisher',
        'publication_date',
        'doi',
        'isbn',
        'citations',
        'downloads',
        'views',
        'pdf_url',
        'domains',
        'keywords',
        'references',
        'status',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'domains' => 'array',
            'keywords' => 'array',
            'references' => 'array',
            'publication_date' => 'date',
            'citations' => 'integer',
            'downloads' => 'integer',
            'views' => 'integer',
            'featured' => 'boolean',
        ];
    }
}
