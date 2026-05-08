<?php

namespace App\Http\Controllers\Api;

use App\Models\Professeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfesseurController extends CrudController
{
    protected function model(): string
    {
        return Professeur::class;
    }

    protected function relations(): array
    {
        return ['utilisateur.departement', 'projets'];
    }

    protected function rules(): array
    {
        return [
            'user_id'    => ['required', 'integer', 'exists:users,id', 'unique:professeurs,user_id'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'grade'      => ['nullable', 'string', 'max:255'],
            'bureau'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id'    => ['sometimes', 'integer', 'exists:users,id', Rule::unique('professeurs', 'user_id')->ignore($id)],
            'specialite' => ['sometimes', 'nullable', 'string', 'max:255'],
            'grade'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'bureau'     => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $record = $this->query()->findOrFail($id);
        $record->fill($validated);
        $record->save();

        return response()->json([
            'data' => $record->fresh($this->relations()),
        ]);
    }
}
