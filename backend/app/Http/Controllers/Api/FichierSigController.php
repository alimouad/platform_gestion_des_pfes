<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonneeSpatiale;
use App\Models\Projet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Shapefile\Shapefile;
use Shapefile\ShapefileReader;

class FichierSigController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'projet_id' => ['required', 'integer', 'exists:projets,id'],
            'fichier'   => ['required', 'file', 'max:20480', 'mimetypes:application/json,application/geo+json,application/zip,application/x-zip-compressed,application/octet-stream'],
        ]);

        $projet   = Projet::findOrFail($request->projet_id);
        $etudiant = $request->user()->etudiant;

        if ($etudiant) {
            $accepted = $projet->postulations()
                ->where('etudiant_id', $etudiant->id)
                ->where('statut', 'accepte')
                ->exists();
            if (!$accepted) {
                return response()->json(['message' => 'Vous n\'êtes pas assigné à ce projet.'], 403);
            }
        }

        $file      = $request->file('fichier');
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['json', 'geojson'])) {
            $geojson = json_decode(file_get_contents($file->getRealPath()), true);
            if (!isset($geojson['type'])) {
                return response()->json(['message' => 'Fichier GeoJSON invalide.'], 422);
            }
        } elseif ($extension === 'zip') {
            $geojson = $this->shapefileZipToGeojson($file);
            if (!$geojson) {
                return response()->json(['message' => 'ZIP invalide. Incluez .shp, .dbf et .shx.'], 422);
            }
        } else {
            return response()->json(['message' => 'Format non supporté. Utilisez GeoJSON ou ZIP (shapefile).'], 422);
        }

        // Save original file to disk
        $file->store("sig/projet_{$projet->id}", 'public');

        // Save parsed GeoJSON as a file
        $geojsonPath = "sig/projet_{$projet->id}/" . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.geojson';
        Storage::disk('public')->put($geojsonPath, json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Each upload is its own row
        $donnee = DonneeSpatiale::create([
            'projet_id'        => $projet->id,
            'type_geometrie'   => $geojson['type'] ?? 'FeatureCollection',
            'nom_fichier'      => $file->getClientOriginalName(),
            'geojson'          => $geojson,
            'description_zone' => null,
        ]);

        return response()->json(['data' => $donnee], 201);
    }

    public function show(int $projetId): JsonResponse
    {
        $donnees = DonneeSpatiale::where('projet_id', $projetId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $donnees]);
    }

    private function shapefileZipToGeojson(UploadedFile $file): ?array
    {
        $tmpDir = sys_get_temp_dir() . '/shp_' . uniqid();
        mkdir($tmpDir);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) return null;
            $zip->extractTo($tmpDir);
            $zip->close();

            $shpFiles = glob($tmpDir . '/*.shp');
            if (empty($shpFiles)) {
                $shpFiles = glob($tmpDir . '/**/*.shp') ?: [];
            }
            if (empty($shpFiles)) return null;

            $shapefile = new ShapefileReader($shpFiles[0]);
            $features  = [];

            while ($geometry = $shapefile->fetchRecord()) {
                if ($geometry === Shapefile::EOF) break;
                if ($geometry->isDeleted()) continue;
                $features[] = [
                    'type'       => 'Feature',
                    'geometry'   => json_decode($geometry->getGeoJSON(), true),
                    'properties' => $geometry->getDataArray(),
                ];
            }

            return ['type' => 'FeatureCollection', 'features' => $features];
        } finally {
            array_map('unlink', glob($tmpDir . '/*') ?: []);
            @rmdir($tmpDir);
        }
    }
}
