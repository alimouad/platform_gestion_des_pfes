<?php

namespace App\Http\Controllers\Api;

use App\Models\Postulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostulationController extends CrudController
{
    protected function model(): string
    {
        return Postulation::class;
    }

    protected function relations(): array
    {
        return ['etudiant.utilisateur', 'projet.professeur.utilisateur'];
    }

    protected function rules(): array
    {
        return [
            'etudiant_id' => ['required', 'integer', 'exists:etudiants,id'],
            'projet_id'   => ['required', 'integer', 'exists:projets,id'],
            'statut'      => ['sometimes', 'string', 'in:en_attente,accepte,rejete'],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $etudiant = $request->user()->etudiant;

        if (! $etudiant) {
            return response()->json(['message' => 'Profil étudiant introuvable. Contactez l\'administration.'], 422);
        }

        $data = $request->validate([
            'projet_id' => ['required', 'integer', 'exists:projets,id'],
        ]);

        // Student already has an accepted postulation
        $alreadyAccepted = Postulation::where('etudiant_id', $etudiant->id)
            ->where('statut', 'accepte')
            ->exists();
        if ($alreadyAccepted) {
            return response()->json(['message' => 'Vous êtes déjà assigné à un projet.'], 422);
        }

        // Student already postulated to this project
        $duplicate = Postulation::where('etudiant_id', $etudiant->id)
            ->where('projet_id', $data['projet_id'])
            ->whereIn('statut', ['en_attente', 'accepte'])
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'Vous avez déjà postulé à ce projet.'], 422);
        }

        // Project already has an accepted student
        $projetPris = Postulation::where('projet_id', $data['projet_id'])
            ->where('statut', 'accepte')
            ->exists();
        if ($projetPris) {
            return response()->json(['message' => 'Ce projet est déjà assigné à un autre étudiant.'], 422);
        }

        $postulation = Postulation::create([
            'etudiant_id' => $etudiant->id,
            'projet_id'   => $data['projet_id'],
            'statut'      => 'en_attente',
        ]);

        return response()->json(['data' => $postulation->fresh($this->relations())], 201);
    }

    public function accepter(int $id): JsonResponse
    {
        $postulation = Postulation::with($this->relations())->findOrFail($id);

        // Block if project already taken by someone else
        $alreadyTaken = Postulation::where('projet_id', $postulation->projet_id)
            ->where('id', '!=', $id)
            ->where('statut', 'accepte')
            ->exists();
        if ($alreadyTaken) {
            return response()->json(['message' => 'Ce projet est déjà assigné à un autre étudiant.'], 422);
        }

        $postulation->update(['statut' => 'accepte']);

        // Rejeter automatiquement les autres candidatures sur le même projet
        Postulation::where('projet_id', $postulation->projet_id)
            ->where('id', '!=', $id)
            ->where('statut', 'en_attente')
            ->update(['statut' => 'rejete']);

        return response()->json(['data' => $postulation->fresh($this->relations())]);
    }

    public function rejeter(int $id): JsonResponse
    {
        $postulation = Postulation::findOrFail($id);
        $postulation->update(['statut' => 'rejete']);

        return response()->json(['data' => $postulation->fresh($this->relations())]);
    }
}
