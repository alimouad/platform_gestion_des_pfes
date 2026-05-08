<?php

namespace App\Models;

use App\Models\Projet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coordinateur extends Model
{
    use HasFactory;

    protected $table = 'coordinateurs';

    protected $fillable = [
        'user_id',
        'bureau',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class, 'coordinateur_id');
    }

}