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

        // Save the original file to disk
        $file->store("sig/projet_{$projet->id}", 'public');

        // Also save the parsed GeoJSON as a .geojson file
        $geojsonPath = "sig/projet_{$projet->id}/" . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.geojson';
        Storage::disk('public')->put($geojsonPath, json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Merge new features into existing FeatureCollection
        $existing = DonneeSpatiale::where('projet_id', $projet->id)->first();
        $existingFeatures = [];
        if ($existing && isset($existing->geojson['features'])) {
            $existingFeatures = $existing->geojson['features'];
        }

        $newFeatures = $geojson['features'] ?? [$geojson];
        $merged = [
            'type'     => 'FeatureCollection',
            'features' => array_merge($existingFeatures, $newFeatures),
        ];

        $files = $existing ? ($existing->nom_fichier ? $existing->nom_fichier . ', ' : '') : '';
        $files .= $file->getClientOriginalName();

        $donnee = DonneeSpatiale::updateOrCreate(
            ['projet_id' => $projet->id],
            [
                'type_geometrie'   => 'FeatureCollection',
                'nom_fichier'      => $files,
                'geojson'          => $merged,
                'description_zone' => null,
            ]
        );

        return response()->json(['data' => $donnee], 201);
    }

    public function show(int $projetId): JsonResponse
    {
        $donnee = DonneeSpatiale::where('projet_id', $projetId)->first();
        return response()->json(['data' => $donnee]);
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
                // Try recursive search (files may be in a subdirectory)
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
