<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Postulation;
use App\Models\Depot;
use App\Models\Soutenance;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    public static function send(int $userId, string $type, string $titre, string $corps = '', string $lien = ''): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'titre'   => $titre,
            'corps'   => $corps,
            'lien'    => $lien,
        ]);
    }

    // ── Postulations ──────────────────────────────────────────
    public static function postulationAcceptee(Postulation $postulation): void
    {
        $etudiantUserId = $postulation->etudiant?->user_id;
        if (!$etudiantUserId) return;
        self::send(
            $etudiantUserId,
            'postulation_acceptee',
            '🎉 Postulation acceptée',
            "Votre candidature au projet « {$postulation->projet?->titre} » a été acceptée.",
            '/etudiant/mon-projet'
        );
    }

    public static function postulationRejetee(Postulation $postulation): void
    {
        $etudiantUserId = $postulation->etudiant?->user_id;
        if (!$etudiantUserId) return;
        self::send(
            $etudiantUserId,
            'postulation_rejetee',
            '❌ Postulation rejetée',
            "Votre candidature au projet « {$postulation->projet?->titre} » a été rejetée.",
            '/etudiant/postulations'
        );
    }

    // ── Dépôts ────────────────────────────────────────────────
    public static function depotValide(Depot $depot): void
    {
        $etudiantUserId = $depot->etudiant?->user_id;
        if (!$etudiantUserId) return;
        self::send(
            $etudiantUserId,
            'depot_valide',
            '✅ Dépôt validé',
            "Votre dépôt « {$depot->type_depot} » a été validé par votre encadrant.",
            '/etudiant/depots'
        );
    }

    public static function depotRejete(Depot $depot): void
    {
        $etudiantUserId = $depot->etudiant?->user_id;
        if (!$etudiantUserId) return;
        self::send(
            $etudiantUserId,
            'depot_rejete',
            '⚠️ Dépôt rejeté',
            "Votre dépôt « {$depot->type_depot} » a été rejeté. " . ($depot->commentaire ? "Commentaire : {$depot->commentaire}" : ''),
            '/etudiant/depots'
        );
    }

    // ── Soutenances ───────────────────────────────────────────
    public static function soutenancePlanifiee(Soutenance $soutenance): void
    {
        $projet = $soutenance->projet;
        if (!$projet) return;

        $dateStr = Carbon::parse($soutenance->date)->format('d/m/Y');

        $acceptedPost = $projet->postulations?->firstWhere('statut', 'accepte');
        if ($acceptedPost) {
            $etudiantUserId = $acceptedPost->etudiant?->user_id;
            if ($etudiantUserId) {
                self::send(
                    $etudiantUserId,
                    'soutenance_planifiee',
                    '📅 Soutenance planifiée',
                    "Votre soutenance est fixée le {$dateStr} à {$soutenance->heure} — Salle {$soutenance->salle}.",
                    '/etudiant/soutenance'
                );
            }
        }

        $profUserId = $projet->professeur?->user_id;
        if ($profUserId) {
            self::send(
                $profUserId,
                'soutenance_planifiee',
                '📅 Soutenance planifiée',
                "La soutenance du projet « {$projet->titre} » est fixée le {$dateStr}.",
                '/professeur/avancement'
            );
        }
    }

    // ── Messages ──────────────────────────────────────────────
    public static function messageRecu(int $receiverUserId, string $senderName): void
    {
        $role = User::find($receiverUserId)?->role;
        $lien = match($role) {
            'etudiant'     => '/etudiant/messages',
            'professeur'   => '/professeur/messages',
            'coordinateur' => '/coordinateur/profil',
            default        => '/',
        };
        self::send(
            $receiverUserId,
            'message_recu',
            '💬 Nouveau message',
            "Vous avez reçu un message de {$senderName}.",
            $lien
        );
    }
}
