<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etudiant extends Model
{
    use HasFactory;

    protected $table = 'etudiants';

    protected $fillable = [
        'user_id',
        'code_etudiant',
        'filiere',
        'niveau',
        'groupe',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function postulations(): HasMany
    {
        return $this->hasMany(Postulation::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }
}