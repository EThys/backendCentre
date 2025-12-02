<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Actuality extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'content',
        'learning_points',
        'key_points',
        'image',
        'category',
        'author',
        'author_photo',
        'publish_date',
        'read_time',
        'views',
        'tags',
        'featured',
        'status',
        'related_articles',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'tags' => 'array',
            'learning_points' => 'array',
            'key_points' => 'array',
            'related_articles' => 'array',
            'featured' => 'boolean',
            'views' => 'integer',
            'read_time' => 'integer',
        ];
    }
}
