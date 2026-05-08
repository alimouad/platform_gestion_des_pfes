<?php

namespace App\Http\Controllers\Api;

use App\Models\Soutenance;

class SoutenanceController extends CrudController
{
    protected function model(): string
    {
        return Soutenance::class;
    }

    protected function relations(): array
    {
        return ['projet.professeur.utilisateur', 'projet.postulations.etudiant'];
    }

    protected function rules(): array
    {
        return [
            'projet_id'   => ['required', 'integer', 'exists:projets,id', 'unique:soutenances,projet_id'],
            'date'        => ['required', 'date'],
            'heure'       => ['required', 'date_format:H:i'],
            'salle'       => ['required', 'string', 'max:255'],
            'statut'      => ['sometimes', 'string', 'in:planifiee,en_cours,terminee,annulee'],
            'note_finale' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
