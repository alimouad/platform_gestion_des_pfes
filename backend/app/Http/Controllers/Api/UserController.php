<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends CrudController
{
    protected function model(): string
    {
        return User::class;
    }

    protected function relations(): array
    {
        return ['departement', 'etudiant', 'professeur', 'coordinateur'];
    }

    protected function rules(): array
    {
        return [
            'nom'            => ['required', 'string', 'max:255'],
            'prenom'         => ['required', 'string', 'max:255'],
            'courriel'       => ['required', 'email', 'max:255', 'unique:users,courriel'],
            'mot_de_passe'   => ['required', 'string', 'min:8'],
            'role'           => ['required', 'string', 'in:superadmin,professeur,etudiant,coordinateur'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
        ];
    }

    protected function updateRules(): array
    {
        $id = request()->route('user');

        return [
            'nom'            => ['sometimes', 'string', 'max:255'],
            'prenom'         => ['sometimes', 'string', 'max:255'],
            'courriel'       => ['sometimes', 'email', 'max:255', Rule::unique('users', 'courriel')->ignore($id)],
            'mot_de_passe'   => ['sometimes', 'string', 'min:8'],
            'role'           => ['sometimes', 'string', 'in:superadmin,professeur,etudiant,coordinateur'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);

        $user = User::create($data);

        return response()->json(['data' => $user->fresh($this->relations())], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->updateRules());

        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
        }

        $user = $this->query()->findOrFail($id);
        $user->fill($data)->save();

        return response()->json(['data' => $user->fresh($this->relations())]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load($this->relations()),
        ]);
    }
}
