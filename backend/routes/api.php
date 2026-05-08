<?php

use App\Http\Controllers\Api\AnneeUniversitaireController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoordinateurController;
use App\Http\Controllers\Api\CoucheCarteController;
use App\Http\Controllers\Api\DepartementController;
use App\Http\Controllers\Api\DepotController;
use App\Http\Controllers\Api\DonneeSpatialeController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\PostulationController;
use App\Http\Controllers\Api\ProfesseurController;
use App\Http\Controllers\Api\ProjetController;
use App\Http\Controllers\Api\SoutenanceController;
use App\Http\Controllers\Api\StatistiqueController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC
// ─────────────────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─────────────────────────────────────────────────────────────────────────────
// AUTHENTIFIÉ
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [UserController::class, 'me']);

    // ── Superadmin uniquement ─────────────────────────────────────────────
    Route::middleware('role:superadmin')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::apiResource('users',        UserController::class);
        Route::apiResource('departements', DepartementController::class);
    });

    // ── Superadmin + Coordinateur ─────────────────────────────────────────
    Route::middleware('role:superadmin,coordinateur')->group(function () {
        Route::apiResource('soutenances',           SoutenanceController::class);
        Route::apiResource('coordinateurs',         CoordinateurController::class);

        Route::get( 'statistiques',                  [StatistiqueController::class, 'index']);
        Route::get( 'statistiques/{statistique}',    [StatistiqueController::class, 'show']);
        Route::post('statistiques/calculer/{annee}', [StatistiqueController::class, 'calculer']);
    });

    // ── Lecture des années universitaires pour les professeurs ───────────
    Route::middleware('role:superadmin,coordinateur,professeur')->group(function () {
        Route::get('annees-universitaires', [AnneeUniversitaireController::class, 'index']);
        Route::get('annees-universitaires/{anneeUniversitaire}', [AnneeUniversitaireController::class, 'show']);
    });

    // ── Écriture des années universitaires réservée à l'administration ────
    Route::middleware('role:superadmin,coordinateur')->group(function () {
        Route::post('annees-universitaires', [AnneeUniversitaireController::class, 'store']);
        Route::put('annees-universitaires/{anneeUniversitaire}', [AnneeUniversitaireController::class, 'update']);
        Route::patch('annees-universitaires/{anneeUniversitaire}', [AnneeUniversitaireController::class, 'update']);
        Route::delete('annees-universitaires/{anneeUniversitaire}', [AnneeUniversitaireController::class, 'destroy']);
    });

    // ── Superadmin + Coordinateur + Professeur ────────────────────────────
    Route::middleware('role:superadmin,coordinateur,professeur')->group(function () {
        Route::apiResource('professeurs', ProfesseurController::class);
        Route::apiResource('etudiants',   EtudiantController::class);

        Route::post('depots/{depot}/valider', [DepotController::class, 'valider']);
        Route::post('depots/{depot}/rejeter', [DepotController::class, 'rejeterDepot']);
    });

    // ── Projets — lecture ouverte, écriture contrôlée dans le controller ─
    Route::apiResource('projets', ProjetController::class);

    // ── Postulations ──────────────────────────────────────────────────────
    Route::apiResource('postulations', PostulationController::class);
    Route::post('postulations/{postulation}/accepter', [PostulationController::class, 'accepter'])
         ->middleware('role:superadmin,coordinateur');
    Route::post('postulations/{postulation}/rejeter',  [PostulationController::class, 'rejeter'])
         ->middleware('role:superadmin,coordinateur');

    // ── Dépôts ────────────────────────────────────────────────────────────
    Route::apiResource('depots', DepotController::class);

    // ── SIG ───────────────────────────────────────────────────────────────
    Route::apiResource('donnees-spatiales', DonneeSpatialeController::class);
    Route::apiResource('couches-cartes',    CoucheCarteController::class);
});
