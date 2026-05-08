<?php

namespace App\Http\Controllers\Api;

use App\Models\CoucheCarte;

class CoucheCarteController extends CrudController
{
    protected function model(): string
    {
        return CoucheCarte::class;
    }

    protected function relations(): array
    {
        return ['donneeSpatiale'];
    }

    protected function rules(): array
    {
        return [
            'donnee_spatiale_id' => ['required', 'integer', 'exists:donnees_spatiales,id'],
            'nom'                => ['required', 'string', 'max:255'],
            'type_couche'        => ['required', 'string', 'in:geojson,wms,tile,shapefile'],
            'geojson'            => ['nullable', 'array'],
            'url'                => ['nullable', 'string', 'max:500'],
            'couleur'            => ['nullable', 'string', 'max:20'],
            'opacite'            => ['nullable', 'numeric', 'min:0', 'max:1'],
            'visible'            => ['nullable', 'boolean'],
        ];
    }
}
