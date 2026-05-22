<?php

namespace App\Http\Controllers\Api;

use App\Models\Projet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjetController extends CrudController
{
    protected function model(): string
    {
        return Projet::class;
    }

    protected function relations(): array
    {
        return ['professeur.utilisateur', 'coordinateur.utilisateur', 'anneeUniversitaire', 'filiere', 'postulations.etudiant.filiere', 'depots', 'soutenance', 'donneeSpatiale'];
    }

    public function index(): JsonResponse
    {
        $query = Projet::with($this->relations())->latest('id');

        $user = Auth::user();
        if ($user && $user->departement_id && in_array($user->role, ['coordinateur', 'etudiant'])) {
            $query->whereHas('professeur.utilisateur', fn($q) =>
                $q->where('departement_id', $user->departement_id)
            );
        }

        return response()->json(['data' => $query->get()]);
    }

    public function archive(): JsonResponse
    {
        $projets = Projet::with([
            'professeur.utilisateur',
            'anneeUniversitaire',
            'postulations.etudiant.utilisateur',
            'depots',
            'donneeSpatiale',
            'soutenance',
        ])
        ->where(function ($q) {
            $q->where('statut', 'soutenu')
              ->orWhereHas('soutenance', fn($s) => $s->where('statut', 'terminee'));
        })
        ->latest('id')
        ->get();

        return response()->json(['data' => $projets]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->all();
        $prof = \App\Models\Professeur::where('user_id', $user->id)->first();
        if ($user->role === 'professeur' && $prof) {
            $data['professeur_id'] = $prof->id;
        }

        $validated = validator($data, $this->rules())->validate();
        $projet = Projet::create($validated);

        return response()->json(['data' => $projet->fresh($this->relations())], 201);
    }

    protected function rules(): array
    {
        return [
            'titre'                  => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'domaine'                => ['required', 'string', 'max:255'],
            'statut'                 => ['sometimes', 'string', 'in:brouillon,soumis,en_cours,valide,soutenu,rejete'],
            'date_debut'             => ['nullable', 'date'],
            'date_fin'               => ['nullable', 'date', 'after_or_equal:date_debut'],
            'professeur_id'          => ['required', 'integer', 'exists:professeurs,id'],
            'coordinateur_id'        => ['nullable', 'integer', 'exists:coordinateurs,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:annees_universitaires,id'],
            'filiere_id'             => ['nullable', 'integer', 'exists:filieres,id'],
            'ville'                  => ['nullable', 'string', 'max:255'],
            'latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'zone_etude'             => ['nullable', 'array'],
        ];
    }
}
