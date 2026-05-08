<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulation extends Model
{
    use HasFactory;

    protected $table = 'postulations';

    protected $fillable = [
        'etudiant_id',
        'projet_id',
        'statut',
        'date_candidature',
    ];

    protected function casts(): array
    {
        return [
            'date_candidature' => 'datetime',
        ];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }
}