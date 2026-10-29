# API Al3DPrint - Documentation

## 🚀 Installation et Configuration

### 1. Configuration de la base de données

Modifiez le fichier `.env` pour configurer votre base de données MySQL :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/Al3DPrint?serverVersion=8.0&charset=utf8mb4"
```

### 2. Création de la base de données

Exécutez les migrations Doctrine :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

### 3. Initialisation avec des données de test

```bash
php bin/console app:init-database
```

### 4. Démarrage du serveur

```bash
php -S localhost:8000 -t public
```

## 📚 Endpoints API

### Base URL
```
http://localhost:8000/api
```

### Interface de Test
```
http://localhost:8000/test-api.html
```

## 🗂️ Entités et Endpoints

### 1. Catégories (`/categories`)
- **GET** `/categories` - Liste toutes les catégories
- **GET** `/categories/{id}` - Récupère une catégorie par ID
- **POST** `/categories` - Crée une nouvelle catégorie
- **PUT** `/categories/{id}` - Met à jour une catégorie
- **DELETE** `/categories/{id}` - Supprime une catégorie

### 2. Produits (`/produits`)
- **GET** `/produits` - Liste tous les produits
  - Paramètres : `categorie_id`, `actif`
- **GET** `/produits/{id}` - Récupère un produit par ID
- **POST** `/produits` - Crée un nouveau produit
- **PUT** `/produits/{id}` - Met à jour un produit
- **DELETE** `/produits/{id}` - Supprime un produit

### 3. Clients (`/clients`)
- **GET** `/clients` - Liste tous les clients
- **GET** `/clients/{id}` - Récupère un client par ID
- **POST** `/clients` - Crée un nouveau client
- **PUT** `/clients/{id}` - Met à jour un client
- **DELETE** `/clients/{id}` - Supprime un client

### 4. Commandes (`/commandes`)
- **GET** `/commandes` - Liste toutes les commandes
  - Paramètres : `client_id`, `statut`
- **GET** `/commandes/{id}` - Récupère une commande par ID
- **POST** `/commandes` - Crée une nouvelle commande
- **PUT** `/commandes/{id}` - Met à jour une commande
- **DELETE** `/commandes/{id}` - Supprime une commande

### 5. Options de Personnalisation (`/options-personnalisation`)
- **GET** `/options-personnalisation` - Liste toutes les options
  - Paramètres : `produit_id`
- **GET** `/options-personnalisation/{id}` - Récupère une option par ID
- **POST** `/options-personnalisation` - Crée une nouvelle option
- **PUT** `/options-personnalisation/{id}` - Met à jour une option
- **DELETE** `/options-personnalisation/{id}` - Supprime une option

### 6. Paiements (`/paiements`)
- **GET** `/paiements` - Liste tous les paiements
  - Paramètres : `commande_id`, `statut`
- **GET** `/paiements/{id}` - Récupère un paiement par ID
- **POST** `/paiements` - Crée un nouveau paiement
- **PUT** `/paiements/{id}` - Met à jour un paiement
- **DELETE** `/paiements/{id}` - Supprime un paiement

### 7. Avis Clients (`/avis-clients`)
- **GET** `/avis-clients` - Liste tous les avis
  - Paramètres : `produit_id`, `client_id`
- **GET** `/avis-clients/{id}` - Récupère un avis par ID
- **POST** `/avis-clients` - Crée un nouvel avis
- **PUT** `/avis-clients/{id}` - Met à jour un avis
- **DELETE** `/avis-clients/{id}` - Supprime un avis

### 8. Impressions (`/impressions`)
- **GET** `/impressions` - Liste toutes les impressions
  - Paramètres : `commande_id`, `produit_id`, `statut`
- **GET** `/impressions/{id}` - Récupère une impression par ID
- **POST** `/impressions` - Crée une nouvelle impression
- **PUT** `/impressions/{id}` - Met à jour une impression
- **DELETE** `/impressions/{id}` - Supprime une impression

### 9. Images Produit (`/images-produit`)
- **GET** `/images-produit` - Liste toutes les images
  - Paramètres : `produit_id`
- **GET** `/images-produit/{id}` - Récupère une image par ID
- **POST** `/images-produit` - Crée une nouvelle image
- **PUT** `/images-produit/{id}` - Met à jour une image
- **DELETE** `/images-produit/{id}` - Supprime une image

## 📝 Exemples d'utilisation

### Créer une catégorie
```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Content-Type: application/json" \
  -d '{"nom": "Nouvelle Catégorie", "description": "Description de la catégorie"}'
```

### Créer un produit
```bash
curl -X POST http://localhost:8000/api/produits \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Nouveau Produit",
    "description": "Description du produit",
    "prix": "99.99",
    "stock": 10,
    "categorie_id": 1
  }'
```

### Créer une commande
```bash
curl -X POST http://localhost:8000/api/commandes \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "details": [
      {
        "produit_id": 1,
        "quantite": 2,
        "personnalisation": {"couleur": "rouge"}
      }
    ],
    "adresse_livraison": "123 Rue de la Paix, 75001 Paris",
    "mode_paiement": "carte"
  }'
```

## 🔧 Structure de la Base de Données

### Tables principales :
- `categories` - Catégories de produits
- `produits` - Produits disponibles
- `clients` - Clients du système
- `commandes` - Commandes passées
- `details_commande` - Détails des commandes
- `options_personnalisation` - Options de personnalisation des produits
- `paiements` - Paiements des commandes
- `avis_clients` - Avis des clients sur les produits
- `impressions` - Suivi des impressions 3D
- `images_produit` - Images des produits

## 🚨 Codes de Réponse

- `200` - Succès
- `201` - Créé avec succès
- `400` - Erreur de validation
- `404` - Ressource non trouvée
- `500` - Erreur serveur

## 🧪 Test de l'API

Utilisez l'interface de test disponible à l'adresse :
```
http://localhost:8000/test-api.html
```

Cette interface vous permet de tester tous les endpoints de manière interactive.

## 📋 Notes importantes

1. Tous les endpoints retournent du JSON
2. Les mots de passe sont hashés avec `password_hash()`
3. Les relations entre entités sont gérées automatiquement
4. Les suppressions en cascade sont configurées selon les besoins métier
5. L'API supporte les requêtes CORS pour les applications frontend
