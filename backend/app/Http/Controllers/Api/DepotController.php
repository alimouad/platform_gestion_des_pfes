<?php

namespace App\Http\Controllers\Api;

use App\Models\Depot;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepotController extends CrudController
{
    protected function model(): string
    {
        return Depot::class;
    }

    protected function relations(): array
    {
        return ['projet', 'etudiant.utilisateur', 'etudiant.filiere'];
    }

    public function index(): JsonResponse
    {
        $query = Depot::with($this->relations())->latest('id');

        $user = Auth::user();
        if ($user && $user->role === 'coordinateur' && $user->departement_id) {
            $query->whereHas('projet.professeur.utilisateur', fn($q) =>
                $q->where('departement_id', $user->departement_id)
            );
        }

        return response()->json(['data' => $query->get()]);
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
        $depot->load($this->relations());
        NotificationService::depotValide($depot);

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
        $depot->load($this->relations());
        NotificationService::depotRejete($depot);

        return response()->json(['data' => $depot->fresh($this->relations())]);
    }
}
