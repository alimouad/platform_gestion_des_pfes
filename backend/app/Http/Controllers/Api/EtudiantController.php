<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;

class EtudiantController extends CrudController
{
    protected function model(): string
    {
        return Etudiant::class;
    }

    protected function relations(): array
    {
        return ['utilisateur', 'postulations.projet', 'depots'];
    }

    protected function rules(): array
    {
        return [
            'user_id'       => ['required', 'integer', 'exists:users,id', 'unique:etudiants,user_id'],
            'code_etudiant' => ['required', 'string', 'max:255', 'unique:etudiants,code_etudiant'],
            'niveau'        => ['required', 'string', 'max:255'],
            'groupe'        => ['nullable', 'string', 'max:255'],
        ];
    }
}
