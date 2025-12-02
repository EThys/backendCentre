<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'content',
        'image',
        'type',
        'status',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'address',
        'price',
        'currency',
        'max_attendees',
        'current_attendees',
        'registration_required',
        'registration_deadline',
        'speakers',
        'agenda',
        'tags',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
            'speakers' => 'array',
            'agenda' => 'array',
            'tags' => 'array',
            'registration_required' => 'boolean',
            'registration_deadline' => 'datetime',
            'max_attendees' => 'integer',
            'current_attendees' => 'integer',
        ];
    }

    /**
     * Relation avec les inscriptions
     */
    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}
