# GAGE — Plateforme de Gestion des PFE

> **Master Géomatique Appliquée aux Géosciences et Environnement**  
> Faculté des Sciences Ben M'Sick · Université Hassan II de Casablanca · 2024–2026

Application web full-stack de gestion complète du cycle Projet de Fin d'Études (PFE), intégrant cartographie SIG interactive, messagerie, et suivi des soutenances.

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 11 + Laravel Sanctum (Bearer token) |
| Frontend | Vue.js 3 (Composition API `<script setup>`) + Vite |
| Base de données | PostgreSQL 17 + PostGIS 3.5 |
| Styles | Tailwind CSS v4 |
| Cartographie | Leaflet.js + leaflet.markercluster |
| Conteneurisation | Docker + Docker Compose |
| Admin BDD | pgAdmin 4 |

---

## Architecture du projet

```
pfe_master/
├── backend/          # API Laravel 11
│   ├── app/
│   │   ├── Http/Controllers/Api/   # Contrôleurs REST
│   │   └── Models/                 # Modèles Eloquent
│   ├── database/migrations/        # Migrations SQL
│   └── routes/api.php              # Routes API
├── frontend/         # SPA Vue.js 3
│   └── src/
│       ├── layouts/                # Layouts par rôle
│       ├── views/                  # Pages par rôle
│       │   ├── admin/
│       │   ├── coordinateur/
│       │   ├── professeur/
│       │   └── etudiant/
│       ├── composables/            # Logique réutilisable
│       └── services/               # Client API Axios
├── conception/       # Diagrammes UML (PlantUML)
│   ├── class_diagramme.puml
│   └── use_case.puml
└── docker-compose.yml
```

---

## Démarrage rapide (Docker)

### Prérequis
- Docker & Docker Compose installés

### Lancer tous les services

```bash
docker compose up -d
```

| Service | URL |
|---------|-----|
| API Laravel | http://localhost:8000 |
| Frontend Vue | http://localhost:5173 |
| pgAdmin | http://localhost:5050 |

### Identifiants pgAdmin
- Email : `mouad@gmail.com`
- Mot de passe : `mouad`
- Connexion BDD : host `postgres`, port `5432`, user/pass `mouad`

---

## Installation manuelle (sans Docker)

### Backend

```bash
cd backend
composer install
cp .env.example .env
# Configurer DB_* dans .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

---

## Modèles de données

| Modèle | Description |
|--------|-------------|
| `User` | Utilisateur de base (tous rôles) |
| `Etudiant` | Profil étudiant, lié à une filière |
| `Professeur` | Encadrant de projets |
| `Coordinateur` | Gestionnaire par département |
| `Projet` | Sujet PFE avec zone géographique (GeoJSON) |
| `Postulation` | Candidature d'un étudiant à un projet |
| `Depot` | Livrable soumis (rapport, etc.) |
| `Soutenance` | Date, salle, jury, note finale |
| `Message` | Messagerie étudiant ↔ encadrant |
| `DonneeSpatiale` | Données SIG associées à un projet |
| `Departement` | Département académique |
| `Filiere` | Filière rattachée à un département |
| `AnneeUniversitaire` | Année académique |
| `Statistique` | Agrégats calculés par année |

---

## Rôles et accès

### Super Administrateur
- Gestion des utilisateurs, départements, filières, années universitaires
- Vue globale sur tous les projets, étudiants, professeurs
- Statistiques globales

### Coordinateur
- Gestion restreinte à son **département uniquement**
- Affectation des étudiants aux projets
- Organisation et validation des soutenances (avec saisie de la note finale)
- Statistiques du département

### Professeur
- Proposer des sujets PFE avec zone d'étude sur carte SIG
- Valider/rejeter les livrables des étudiants
- Suivre l'avancement des projets encadrés
- Messagerie avec les étudiants encadrés

### Étudiant
- Consulter les sujets PFE de son département
- Postuler à un projet
- Déposer des livrables
- Consulter sa soutenance et sa note
- Messagerie avec l'encadrant
- Visualiser et importer des données SIG

---

## Fonctionnalités SIG

- **Carte Leaflet interactive** — affichage des projets géolocalisés
- **Clustering** — regroupement des marqueurs (leaflet.markercluster)
- **Géocodage Nominatim** — recherche d'adresse OpenStreetMap (gratuit)
- **Dessin de zones** — définition de la zone d'étude par polygone
- **Export GeoJSON** — téléchargement des données spatiales

---

## API principale

Toutes les routes sont préfixées `/api` et requièrent un Bearer token Sanctum (sauf `/login`).

```
POST   /api/login
GET    /api/me

GET    /api/projets
POST   /api/projets
PUT    /api/projets/{id}

GET    /api/postulations
POST   /api/postulations/{id}/accepter
POST   /api/postulations/{id}/rejeter

GET    /api/depots
POST   /api/depots/{id}/valider
POST   /api/depots/{id}/rejeter

GET    /api/soutenances
PUT    /api/soutenances/{id}          # inclut statut + note_finale

GET    /api/messages/contacts
GET    /api/messages/{userId}
POST   /api/messages

GET    /api/etudiants
GET    /api/professeurs
GET    /api/departements
GET    /api/filieres
GET    /api/annees-universitaires
GET    /api/statistiques
POST   /api/statistiques/calculer/{annee}
```

---

## Conception

Les diagrammes UML sont disponibles dans [`conception/`](conception/) :

- [`class_diagramme.puml`](conception/class_diagramme.puml) — Diagramme de classes
- [`use_case.puml`](conception/use_case.puml) — Diagramme des cas d'utilisation

Rendu avec [PlantUML](https://plantuml.com/).

---

## Auteur

**Mouad** — Master GAGE · FSBM · Université Hassan II de Casablanca
