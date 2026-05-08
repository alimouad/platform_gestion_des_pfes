<?php

namespace App\Models;

use App\Models\CoucheCarte;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonneeSpatiale extends Model
{
    use HasFactory;

    protected $table = 'donnees_spatiales';

    protected $fillable = [
        'projet_id',
        'type_geometrie',
        'geojson',
        'surface',
        'description_zone',
    ];

    protected function casts(): array
    {
        return [
            'geojson' => 'array',
            'surface' => 'decimal:4',
        ];
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function couches(): HasMany
    {
        return $this->hasMany(CoucheCarte::class, 'donnee_spatiale_id');
    }
}