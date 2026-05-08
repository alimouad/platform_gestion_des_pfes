<?php

namespace App\Models;

use App\Models\AnneeUniversitaire;
use App\Models\Coordinateur;
use App\Models\Depot;
use App\Models\DonneeSpatiale;
use App\Models\Postulation;
use App\Models\Soutenance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Projet extends Model
{
    use HasFactory;

    protected $table = 'projets';

    protected $fillable = [
        'titre',
        'description',
        'domaine',
        'statut',
        'date_debut',
        'date_fin',
        'professeur_id',
        'coordinateur_id',
        'annee_universitaire_id',
        'ville',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function professeur(): BelongsTo
    {
        return $this->belongsTo(Professeur::class);
    }

    public function coordinateur(): BelongsTo
    {
        return $this->belongsTo(Coordinateur::class);
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(AnneeUniversitaire::class);
    }

    public function postulations(): HasMany
    {
        return $this->hasMany(Postulation::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    public function soutenance(): HasOne
    {
        return $this->hasOne(Soutenance::class);
    }

    public function donneeSpatiale(): HasOne
    {
        return $this->hasOne(DonneeSpatiale::class);
    }
}