<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'courriel' => ['required', 'email', 'max:255', 'unique:users,courriel'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:superadmin,professeur,etudiant,coordinateur'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
        ]);

        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'courriel' => $data['courriel'],
            'mot_de_passe' => Hash::make($data['mot_de_passe']),
            'role' => $data['role'],
            'departement_id' => $data['departement_id'] ?? null,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'courriel' => ['required', 'email'],
            'mot_de_passe' => ['required', 'string'],
        ]);

        $user = User::where('courriel', $data['courriel'])->first();

        if (! $user || ! Hash::check($data['mot_de_passe'], $user->getAuthPassword())) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
