<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GalleryPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'thumbnail',
        'category',
        'date',
        'author',
        'tags',
        'featured',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'tags' => 'array',
            'featured' => 'boolean',
            'order' => 'integer',
        ];
    }
}
