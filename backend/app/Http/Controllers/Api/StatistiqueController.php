<?php

namespace App\Http\Controllers\Api;

use App\Models\AnneeUniversitaire;
use App\Models\Projet;
use App\Models\Statistique;
use Illuminate\Http\JsonResponse;

class StatistiqueController extends CrudController
{
    protected function model(): string
    {
        return Statistique::class;
    }

    protected function relations(): array
    {
        return ['anneeUniversitaire'];
    }

    protected function rules(): array
    {
        return [
            'annees_universitaire_id' => ['required', 'integer', 'exists:annees_universitaires,id'],
        ];
    }

    public function calculer(int $anneeId): JsonResponse
    {
        $annee = AnneeUniversitaire::findOrFail($anneeId);

        $projets = Projet::where('annees_universitaire_id', $anneeId)->get();

        $parDomaine = $projets->groupBy('domaine')
            ->map(fn ($groupe) => $groupe->count())
            ->toArray();

        $stats = Statistique::updateOrCreate(
            ['annees_universitaire_id' => $anneeId],
            [
                'total_projets'    => $projets->count(),
                'projets_valides'  => $projets->where('statut', 'valide')->count(),
                'projets_en_cours' => $projets->whereIn('statut', ['en_cours', 'soumis'])->count(),
                'projets_soutenus' => $projets->where('statut', 'soutenu')->count(),
                'projets_par_domaine' => $parDomaine,
            ]
        );

        return response()->json(['data' => $stats->load($this->relations())]);
    }
}
