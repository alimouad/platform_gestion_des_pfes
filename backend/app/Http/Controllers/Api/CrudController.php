<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class CrudController extends Controller
{
    abstract protected function model(): string;

    protected function relations(): array
    {
        return [];
    }

    protected function rules(): array
    {
        return [];
    }

    public function index(): JsonResponse
    {
        $model = $this->model();

        return response()->json([
            'data' => $model::query()->with($this->relations())->latest('id')->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->query()->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $model = $this->model();

        /** @var Model $created */
        $created = $model::query()->create($validated);

        return response()->json([
            'data' => $created->fresh($this->relations()),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate($this->updateRules());
        $record = $this->query()->findOrFail($id);

        $record->fill($validated);
        $record->save();

        return response()->json([
            'data' => $record->fresh($this->relations()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->query()->findOrFail($id)->delete();

        return new \Illuminate\Http\JsonResponse(null, 204);
    }

    protected function query()
    {
        $model = $this->model();

        return $model::query()->with($this->relations());
    }

    protected function updateRules(): array
    {
        return collect($this->rules())
            ->map(function (array $rules): array {
                return array_values(array_unique(array_merge(['sometimes'], $rules)));
            })
            ->all();
    }
}