<?php

namespace App\Models;

use App\Models\Coordinateur;
use App\Models\Departement;
use App\Models\Etudiant;
use App\Models\Professeur;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'nom',
        'prenom',
        'courriel',
        'mot_de_passe',
        'role',
        'departement_id',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'courriel_verifie_le' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->courriel;
    }

    public function getEmailForVerification(): string
    {
        return $this->courriel;
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->courriel,
            set: fn (string $value): array => ['courriel' => $value],
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->nom . ' ' . $this->prenom,
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->mot_de_passe,
            set: fn (string $value): array => ['mot_de_passe' => $value],
        );
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function etudiant(): HasOne
    {
        return $this->hasOne(Etudiant::class);
    }

    public function professeur(): HasOne
    {
        return $this->hasOne(Professeur::class);
    }

    public function coordinateur(): HasOne
    {
        return $this->hasOne(Coordinateur::class);
    }
}
