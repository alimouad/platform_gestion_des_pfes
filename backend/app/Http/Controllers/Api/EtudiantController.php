<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EtudiantController extends CrudController
{
    protected function model(): string
    {
        return Etudiant::class;
    }

    protected function relations(): array
    {
        return ['utilisateur', 'filiere', 'postulations.projet', 'depots'];
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

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id'       => ['sometimes', 'integer', 'exists:users,id', Rule::unique('etudiants', 'user_id')->ignore($id)],
            'code_etudiant' => ['sometimes', 'string', 'max:255', Rule::unique('etudiants', 'code_etudiant')->ignore($id)],
            'filiere_id'    => ['nullable', 'integer', 'exists:filieres,id'],
            'niveau'        => ['sometimes', 'string', 'max:255'],
            'groupe'        => ['nullable', 'string', 'max:255'],
        ]);

        $record = $this->query()->findOrFail($id);
        $record->fill($validated)->save();

        return response()->json(['data' => $record->fresh($this->relations())]);
    }

    protected function rules(): array
    {
        return [
            'user_id'       => ['required', 'integer', 'exists:users,id', 'unique:etudiants,user_id'],
            'code_etudiant' => ['required', 'string', 'max:255', 'unique:etudiants,code_etudiant'],
            'filiere_id'    => ['nullable', 'integer', 'exists:filieres,id'],
            'niveau'        => ['required', 'string', 'max:255'],
            'groupe'        => ['nullable', 'string', 'max:255'],
        ];
    }
}
