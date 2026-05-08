<?php

namespace App\Http\Controllers\Api;

use App\Models\Coordinateur;

class CoordinateurController extends CrudController
{
    protected function model(): string
    {
        return Coordinateur::class;
    }

    protected function relations(): array
    {
        return ['utilisateur', 'projets'];
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'bureau' => ['nullable', 'string', 'max:255'],
        ];
    }
}