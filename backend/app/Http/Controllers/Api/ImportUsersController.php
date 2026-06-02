<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Professeur;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportUsersController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path('app/' . $path);

        try {
            $spreadsheet = IOFactory::load($fullPath);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Fichier invalide : ' . $e->getMessage()], 422);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $created = [];
        $errors  = [];
        $skipped = 0;

        // Skip header row
        array_shift($rows);

        foreach ($rows as $i => $row) {
            $line = $i + 2;

            $nom       = trim($row['A'] ?? '');
            $prenom    = trim($row['B'] ?? '');
            $courriel  = trim($row['C'] ?? '');
            $role      = strtolower(trim($row['D'] ?? 'etudiant'));
            $filiere   = trim($row['E'] ?? '');
            $dept      = trim($row['F'] ?? '');

            if (!$nom && !$prenom && !$courriel) { $skipped++; continue; }

            if (!$courriel || !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne {$line} : email invalide ({$courriel})";
                continue;
            }

            if (!in_array($role, ['etudiant', 'professeur', 'coordinateur', 'superadmin'])) {
                $errors[] = "Ligne {$line} : rôle inconnu ({$role})";
                continue;
            }

            if (User::where('courriel', $courriel)->exists()) {
                $errors[] = "Ligne {$line} : email déjà utilisé ({$courriel})";
                continue;
            }

            $password = Str::random(10);

            $departementId = null;
            if ($dept) {
                $departementId = Departement::whereRaw('LOWER(nom) = ?', [strtolower($dept)])->value('id');
            }

            $user = User::create([
                'nom'            => $nom,
                'prenom'         => $prenom,
                'courriel'       => $courriel,
                'mot_de_passe'   => Hash::make($password),
                'role'           => $role,
                'departement_id' => $departementId,
            ]);

            // Create role profile
            if ($role === 'etudiant') {
                $filiereId = null;
                if ($filiere) {
                    $filiereId = Filiere::whereRaw('LOWER(nom) = ?', [strtolower($filiere)])->value('id');
                }
                Etudiant::create([
                    'user_id'    => $user->id,
                    'filiere_id' => $filiereId,
                    'niveau'     => 'Master',
                ]);
            } elseif ($role === 'professeur') {
                Professeur::create(['user_id' => $user->id]);
            }

            $created[] = [
                'nom'      => $nom,
                'prenom'   => $prenom,
                'courriel' => $courriel,
                'role'     => $role,
                'password' => $password,
            ];
        }

        @unlink($fullPath);

        return response()->json([
            'created' => count($created),
            'errors'  => $errors,
            'skipped' => $skipped,
            'users'   => $created,
        ]);
    }

    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Utilisateurs');

        // Headers
        $headers = ['Nom', 'Prénom', 'Email', 'Rôle', 'Filière', 'Département'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getStyle("{$col}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e4a49');
            $sheet->getStyle("{$col}1")->getFont()->getColor()->setRGB('d6e87a');
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Example rows
        $examples = [
            ['Alami',    'Sara',    'sara.alami@fsbm.ac.ma',    'etudiant',   'GAGE', 'Sciences de la Terre'],
            ['Benali',   'Youssef', 'y.benali@fsbm.ac.ma',     'professeur', '',     'Sciences de la Terre'],
            ['Cherkaoui','Fatima',  'f.cherkaoui@fsbm.ac.ma',  'etudiant',   'GAGE', 'Sciences de la Terre'],
        ];
        foreach ($examples as $r => $row) {
            foreach ($row as $c => $val) {
                $sheet->setCellValue(chr(65 + $c) . ($r + 2), $val);
            }
        }

        // Validation: role dropdown
        $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
            ->setFormula1('"etudiant,professeur,coordinateur,superadmin"')
            ->setShowDropDown(false);
        for ($r = 2; $r <= 100; $r++) {
            $sheet->getCell("D{$r}")->setDataValidation(clone $validation);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tmp = tempnam(sys_get_temp_dir(), 'tpl_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'template_utilisateurs.xlsx')->deleteFileAfterSend();
    }
}
