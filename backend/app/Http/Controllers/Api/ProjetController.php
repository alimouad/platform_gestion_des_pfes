<?php

namespace App\Http\Controllers\Api;

use App\Models\Projet;

class ProjetController extends CrudController
{
    protected function model(): string
    {
        return Projet::class;
    }

    protected function relations(): array
    {
        return ['professeur.utilisateur', 'coordinateur.utilisateur', 'anneeUniversitaire', 'postulations.etudiant', 'depots', 'soutenance', 'donneeSpatiale'];
    }

    protected function rules(): array
    {
        return [
            'titre'                  => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'domaine'                => ['required', 'string', 'max:255'],
            'statut'                 => ['sometimes', 'string', 'in:brouillon,soumis,en_cours,valide,soutenu,rejete'],
            'date_debut'             => ['nullable', 'date'],
            'date_fin'               => ['nullable', 'date', 'after_or_equal:date_debut'],
            'professeur_id'          => ['required', 'integer', 'exists:professeurs,id'],
            'coordinateur_id'        => ['nullable', 'integer', 'exists:coordinateurs,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:annees_universitaires,id'],
            'ville'                  => ['nullable', 'string', 'max:255'],
            'latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'zone_etude'             => ['nullable', 'array'],
        ];
    }
}
