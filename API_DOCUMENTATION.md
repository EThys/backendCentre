# Documentation des APIs

Ce document décrit les APIs disponibles pour gérer les actualités, événements, publications et galeries depuis le dashboard Vue.js.

## Base URL

Toutes les APIs sont accessibles via le préfixe `/api` :
```
http://localhost:8000/api
```

## Endpoints disponibles

### 1. Actualités (Actualities)

#### Liste des actualités
```
GET /api/actualities
```

**Paramètres de requête (optionnels) :**
- `per_page` : Nombre d'éléments par page (défaut: 15)
- `status` : Filtrer par statut (`draft`, `published`, `archived`)
- `category` : Filtrer par catégorie
- `featured` : Filtrer les actualités mises en avant (`true`/`false`)

**Exemple de réponse :**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 15,
    "total": 50,
    "totalPages": 4
  }
}
```

#### Créer une actualité
```
POST /api/actualities
Content-Type: multipart/form-data
```

**Corps de la requête :**
- `title` (requis) : Titre de l'actualité
- `summary` (requis) : Résumé
- `content` (requis) : Contenu complet
- `image` (optionnel) : Image (fichier)
- `category` (optionnel) : Catégorie
- `author` (requis) : Auteur
- `author_photo` (optionnel) : Photo de l'auteur (fichier)
- `publish_date` (requis) : Date de publication (format: YYYY-MM-DD)
- `read_time` (optionnel) : Temps de lecture en minutes
- `tags` (optionnel) : Tableau de tags
- `featured` (optionnel) : Mise en avant (boolean)
- `status` (optionnel) : Statut (`draft`, `published`, `archived`)
- `related_articles` (optionnel) : Tableau d'IDs d'articles liés

#### Obtenir une actualité
```
GET /api/actualities/{id}
```

#### Mettre à jour une actualité
```
PUT /api/actualities/{id}
PATCH /api/actualities/{id}
```

#### Supprimer une actualité
```
DELETE /api/actualities/{id}
```

---

### 2. Événements (Events)

#### Liste des événements
```
GET /api/events
```

**Paramètres de requête (optionnels) :**
- `per_page` : Nombre d'éléments par page
- `status` : Filtrer par statut (`upcoming`, `ongoing`, `completed`, `cancelled`)
- `type` : Filtrer par type (`conference`, `workshop`, `seminar`, `webinar`, `other`)
- `category` : Filtrer par catégorie

#### Créer un événement
```
POST /api/events
```

**Corps de la requête :**
- `title` (requis) : Titre de l'événement
- `description` (requis) : Description
- `content` (optionnel) : Contenu détaillé
- `image` (optionnel) : Image (fichier)
- `type` (requis) : Type d'événement
- `status` (optionnel) : Statut
- `start_date` (requis) : Date de début (YYYY-MM-DD)
- `end_date` (optionnel) : Date de fin
- `start_time` (requis) : Heure de début (HH:MM)
- `end_time` (optionnel) : Heure de fin
- `location` (requis) : Lieu
- `address` (optionnel) : Adresse complète
- `price` (optionnel) : Prix
- `currency` (optionnel) : Devise (3 caractères)
- `max_attendees` (optionnel) : Nombre maximum de participants
- `registration_required` (optionnel) : Inscription requise (boolean)
- `registration_deadline` (optionnel) : Date limite d'inscription
- `speakers` (optionnel) : Tableau d'objets speakers
- `agenda` (optionnel) : Tableau d'objets agenda
- `tags` (optionnel) : Tableau de tags
- `category` (optionnel) : Catégorie

**Exemple de speaker :**
```json
{
  "name": "John Doe",
  "position": "Directeur",
  "bio": "Biographie...",
  "photo": "url",
  "organization": "Organisation"
}
```

**Exemple d'agenda :**
```json
{
  "time": "09:00",
  "title": "Accueil",
  "description": "Description",
  "speaker": "Nom du speaker"
}
```

#### Obtenir un événement
```
GET /api/events/{id}
```

#### Mettre à jour un événement
```
PUT /api/events/{id}
PATCH /api/events/{id}
```

#### Supprimer un événement
```
DELETE /api/events/{id}
```

---

### 3. Publications

#### Liste des publications
```
GET /api/publications
```

**Paramètres de requête (optionnels) :**
- `per_page` : Nombre d'éléments par page
- `status` : Filtrer par statut (`draft`, `published`, `archived`)
- `type` : Filtrer par type (`article`, `research-paper`, `book`, `report`, `other`)
- `featured` : Filtrer les publications mises en avant

#### Créer une publication
```
POST /api/publications
```

**Corps de la requête :**
- `title` (requis) : Titre
- `abstract` (requis) : Résumé
- `content` (requis) : Contenu
- `image` (optionnel) : Image (fichier)
- `type` (requis) : Type de publication
- `authors` (requis) : Tableau d'auteurs (minimum 1)
  - `name` (requis) : Nom
  - `affiliation` (optionnel) : Affiliation
  - `email` (optionnel) : Email
  - `orcid` (optionnel) : ORCID
- `journal` (optionnel) : Journal
- `publisher` (optionnel) : Éditeur
- `publication_date` (requis) : Date de publication
- `doi` (optionnel) : DOI
- `isbn` (optionnel) : ISBN
- `pdf_url` (optionnel) : URL du PDF (ou utiliser le champ `pdf` pour upload)
- `pdf` (optionnel) : Fichier PDF
- `domains` (requis) : Tableau de domaines (minimum 1)
- `keywords` (optionnel) : Tableau de mots-clés
- `references` (optionnel) : Tableau de références
- `status` (optionnel) : Statut
- `featured` (optionnel) : Mise en avant

#### Obtenir une publication
```
GET /api/publications/{id}
```

#### Mettre à jour une publication
```
PUT /api/publications/{id}
PATCH /api/publications/{id}
```

#### Supprimer une publication
```
DELETE /api/publications/{id}
```

---

### 4. Galerie (Gallery)

#### Liste des photos
```
GET /api/gallery
```

**Paramètres de requête (optionnels) :**
- `per_page` : Nombre d'éléments par page
- `category` : Filtrer par catégorie
- `featured` : Filtrer les photos mises en avant

#### Obtenir les catégories
```
GET /api/gallery/categories
```

#### Créer une photo
```
POST /api/gallery
```

**Corps de la requête :**
- `title` (requis) : Titre
- `description` (optionnel) : Description
- `image` (requis) : Image (fichier)
- `category` (requis) : Catégorie
- `date` (requis) : Date (YYYY-MM-DD)
- `author` (requis) : Auteur
- `tags` (optionnel) : Tableau de tags
- `featured` (optionnel) : Mise en avant
- `order` (optionnel) : Ordre d'affichage

#### Obtenir une photo
```
GET /api/gallery/{id}
```

#### Mettre à jour une photo
```
PUT /api/gallery/{id}
PATCH /api/gallery/{id}
```

#### Supprimer une photo
```
DELETE /api/gallery/{id}
```

---

## Format des réponses

### Succès
```json
{
  "success": true,
  "data": {...},
  "message": "Message optionnel"
}
```

### Erreur de validation
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "field": ["Message d'erreur"]
  }
}
```

### Erreur 404
```json
{
  "success": false,
  "message": "Ressource non trouvée"
}
```

## Configuration CORS

Les APIs sont configurées pour accepter les requêtes depuis :
- `http://localhost:5173` (Vite dev server)
- `http://localhost:3000` (Autre serveur de développement)
- URL définie dans `FRONTEND_URL` (variable d'environnement)

Pour modifier les origines autorisées, éditez le fichier `config/cors.php`.

## Upload de fichiers

Les fichiers (images, PDFs) doivent être envoyés en utilisant `multipart/form-data`.

Les fichiers sont stockés dans :
- `storage/app/public/actualities/` pour les images d'actualités
- `storage/app/public/events/` pour les images d'événements
- `storage/app/public/publications/` pour les images de publications
- `storage/app/public/publications/pdf/` pour les PDFs de publications
- `storage/app/public/gallery/` pour les photos de galerie
- `storage/app/public/authors/` pour les photos d'auteurs

Assurez-vous que le lien symbolique est créé :
```bash
php artisan storage:link
```

## Exemple d'utilisation avec Axios (Vue.js)

```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  }
})

// Pour les uploads de fichiers
const formData = new FormData()
formData.append('title', 'Mon titre')
formData.append('content', 'Mon contenu')
formData.append('image', fileInput.files[0])

// Créer une actualité
const createActuality = async (data) => {
  try {
    const response = await api.post('/actualities', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  } catch (error) {
    console.error('Erreur:', error.response.data)
    throw error
  }
}
```

## Migration de la base de données

Pour créer les tables dans la base de données, exécutez :

```bash
php artisan migrate
```


