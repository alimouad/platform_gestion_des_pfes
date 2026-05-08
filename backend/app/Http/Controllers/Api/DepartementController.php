<?php

namespace App\Http\Controllers\Api;

use App\Models\Departement;

class DepartementController extends CrudController
{
    protected function model(): string
    {
        return Departement::class;
    }

    protected function relations(): array
    {
        return ['utilisateurs'];
    }

    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}