<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soutenance extends Model
{
    use HasFactory;

    protected $table = 'soutenances';

    protected $fillable = [
        'projet_id',
        'date',
        'heure',
        'salle',
        'statut',
        'note_finale',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'note_finale' => 'decimal:2',
        ];
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }
}