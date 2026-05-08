<?php

namespace App\Http\Controllers\Api;

use App\Models\AnneeUniversitaire;
use Illuminate\Http\Request;

class AnneeUniversitaireController extends CrudController
{
    protected function model(): string
    {
        return AnneeUniversitaire::class;
    }

    protected function relations(): array
    {
        return ['projets', 'statistique'];
    }

    protected function rules(): array
    {
        return [
            'annee'       => ['required', 'string', 'max:20', 'unique:annees_universitaires,annee'],
            'statut'      => ['sometimes', 'string', 'in:active,archivee,future,inactive'],
            'date_debut'  => ['sometimes', 'nullable', 'date'],
            'date_fin'    => ['sometimes', 'nullable', 'date', 'after_or_equal:date_debut'],
        ];
    }

    protected function updateRules(int $id = null): array
    {
        $rules = collect($this->rules())
            ->map(function (array $rules, string $field) use ($id): array {
                $updated = array_values(array_unique(array_merge(['sometimes'], $rules)));
                // Handle unique constraint for updates
                if ($field === 'annee' && $id) {
                    $updated = array_map(function ($rule) use ($id) {
                        if (strpos($rule, 'unique:annees_universitaires,annee') === 0) {
                            return "unique:annees_universitaires,annee,{$id}";
                        }
                        return $rule;
                    }, $updated);
                }
                return $updated;
            })
            ->all();

        return $rules;
    }

    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate($this->updateRules($id));
        $record = $this->query()->findOrFail($id);

        $record->fill($validated);
        $record->save();

        return response()->json([
            'data' => $record->fresh($this->relations()),
        ]);
    }
}
