<?php

namespace App\Http\Controllers\Api;

use App\Models\Depot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepotController extends CrudController
{
    protected function model(): string
    {
        return Depot::class;
    }

    protected function relations(): array
    {
        return ['projet', 'etudiant.utilisateur'];
    }

    protected function rules(): array
    {
        return [
            'projet_id'        => ['required', 'integer', 'exists:projets,id'],
            'etudiant_id'      => ['required', 'integer', 'exists:etudiants,id'],
            'chemin_fichier'   => ['required', 'string', 'max:500'],
            'type_depot'       => ['required', 'string', 'in:rapport,donnees,presentation,autre'],
            'statut_validation' => ['sometimes', 'string', 'in:en_attente,valide,rejete'],
            'commentaire'      => ['nullable', 'string'],
        ];
    }

    public function valider(Request $request, int $id): JsonResponse
    {
        $depot = Depot::with('projet')->findOrFail($id);
        $prof  = $request->user()->professeur;

        if ($prof && $depot->projet?->professeur_id !== $prof->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $depot->update(['statut_validation' => 'valide', 'commentaire' => null]);

        return response()->json(['data' => $depot->fresh($this->relations())]);
    }

    public function rejeterDepot(Request $request, int $id): JsonResponse
    {
        $data  = $request->validate(['commentaire' => ['nullable', 'string']]);
        $depot = Depot::with('projet')->findOrFail($id);
        $prof  = $request->user()->professeur;

        if ($prof && $depot->projet?->professeur_id !== $prof->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $depot->update(['statut_validation' => 'rejete', 'commentaire' => $data['commentaire'] ?? null]);

        return response()->json(['data' => $depot->fresh($this->relations())]);
    }
}
