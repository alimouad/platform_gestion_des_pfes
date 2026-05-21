<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends CrudController
{
    protected function model(): string
    {
        return Etudiant::class;
    }

    protected function relations(): array
    {
        return ['utilisateur', 'postulations.projet', 'depots'];
    }

    public function index(): JsonResponse
    {
        $query = Etudiant::with($this->relations())->latest('id');

        $user = Auth::user();
        if ($user && $user->role === 'coordinateur' && $user->departement_id) {
            $query->whereHas('utilisateur', fn($q) =>
                $q->where('departement_id', $user->departement_id)
            );
        }

        return response()->json(['data' => $query->get()]);
    }

    protected function rules(): array
    {
        return [
            'user_id'       => ['required', 'integer', 'exists:users,id', 'unique:etudiants,user_id'],
            'code_etudiant' => ['required', 'string', 'max:255', 'unique:etudiants,code_etudiant'],
            'niveau'        => ['required', 'string', 'max:255'],
            'groupe'        => ['nullable', 'string', 'max:255'],
        ];
    }
}
