<?php

namespace App\Http\Controllers\Api;

use App\Models\Soutenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SoutenanceController extends CrudController
{
    protected function model(): string
    {
        return Soutenance::class;
    }

    protected function relations(): array
    {
        return ['projet.professeur.utilisateur', 'projet.postulations.etudiant'];
    }

    protected function rules(): array
    {
        return [
            'projet_id'   => ['required', 'integer', 'exists:projets,id', 'unique:soutenances,projet_id'],
            'date'        => ['required', 'date'],
            'heure'       => ['required', 'date_format:H:i'],
            'salle'       => ['required', 'string', 'max:255'],
            'statut'      => ['sometimes', 'string', 'in:planifiee,en_cours,terminee,annulee'],
            'note_finale' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'projet_id'   => ['sometimes', 'integer', 'exists:projets,id', Rule::unique('soutenances', 'projet_id')->ignore($id)],
            'date'        => ['sometimes', 'date'],
            'heure'       => ['sometimes', 'date_format:H:i'],
            'salle'       => ['sometimes', 'string', 'max:255'],
            'statut'      => ['sometimes', 'string', 'in:planifiee,en_cours,terminee,annulee'],
            'note_finale' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'jury'        => ['nullable', 'string'],
        ]);

        $record = $this->query()->findOrFail($id);
        $record->fill($validated)->save();

        return response()->json(['data' => $record->fresh($this->relations())]);
    }
}
