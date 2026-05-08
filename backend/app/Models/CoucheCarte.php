<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoucheCarte extends Model
{
    use HasFactory;

    protected $table = 'couches_cartes';

    protected $fillable = [
        'donnee_spatiale_id',
        'nom',
        'type_couche',
        'geojson',
        'url',
        'couleur',
        'opacite',
        'visible',
    ];

    protected function casts(): array
    {
        return [
            'geojson' => 'array',
            'visible' => 'boolean',
            'opacite' => 'decimal:2',
        ];
    }

    public function donneeSpatiale(): BelongsTo
    {
        return $this->belongsTo(DonneeSpatiale::class);
    }
}