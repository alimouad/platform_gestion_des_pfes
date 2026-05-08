<?php

namespace App\Models;

use App\Models\Projet;
use App\Models\Statistique;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnneeUniversitaire extends Model
{
    use HasFactory;
    protected $table = 'annees_universitaires';

    protected $fillable = [
        'annee',
        'statut',
        'date_debut',
        'date_fin',
    ];

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    public function statistique(): HasOne
    {
        return $this->hasOne(Statistique::class);
    }
}