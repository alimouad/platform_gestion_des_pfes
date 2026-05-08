<?php

namespace App\Http\Controllers\Api;

use App\Models\DonneeSpatiale;

class DonneeSpatialeController extends CrudController
{
    protected function model(): string
    {
        return DonneeSpatiale::class;
    }

    protected function relations(): array
    {
        return ['projet', 'couches'];
    }

    protected function rules(): array
    {
        return [
            'projet_id'       => ['required', 'integer', 'exists:projets,id'],
            'type_geometrie'  => ['nullable', 'string', 'in:Point,LineString,Polygon,MultiPolygon,MultiPoint,GeometryCollection'],
            'geojson'         => ['nullable', 'array'],
            'surface'         => ['nullable', 'numeric', 'min:0'],
            'description_zone' => ['nullable', 'string'],
        ];
    }
}
