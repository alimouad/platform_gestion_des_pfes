<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depot extends Model
{
    use HasFactory;

    protected $table = 'depots';

    protected $fillable = [
        'projet_id',
        'etudiant_id',
        'chemin_fichier',
        'type_depot',
        'statut_validation',
        'commentaire',
        'depose_le',
    ];

    protected function casts(): array
    {
        return [
            'depose_le' => 'datetime',
        ];
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }
}