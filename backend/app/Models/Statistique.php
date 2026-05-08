<?php

namespace App\Models;

use App\Models\AnneeUniversitaire;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Statistique extends Model
{
    use HasFactory;

    protected $table = 'statistiques';

    protected $fillable = [
        'annee_universitaire_id',
        'total_projets',
        'projets_valides',
        'projets_en_cours',
        'projets_soutenus',
        'projets_par_domaine',
        'projets_par_region',
    ];

    protected function casts(): array
    {
        return [
            'projets_par_domaine' => 'array',
            'projets_par_region' => 'array',
        ];
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(AnneeUniversitaire::class);
    }
}