<?php

namespace App\Http\Controllers\Api;

use App\Models\Filiere;

class FiliereController extends CrudController
{
    protected function model(): string
    {
        return Filiere::class;
    }

    protected function relations(): array
    {
        return ['departement'];
    }

    protected function rules(): array
    {
        return [
            'nom'           => ['required', 'string', 'max:255', 'unique:filieres,nom'],
            'description'   => ['nullable', 'string'],
            'departement_id'=> ['nullable', 'integer', 'exists:departements,id'],
        ];
    }
}
