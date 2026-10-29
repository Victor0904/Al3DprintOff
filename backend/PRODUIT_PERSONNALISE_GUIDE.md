# 🎨 Guide : Produits Personnalisés

## 📋 Description

Une nouvelle table `produit_personnalise` a été créée pour gérer les produits personnalisés (fait maison) séparément des produits normaux.

## 🗄️ Structure de la table

La table `produit_personnalise` contient les mêmes champs que la table `produits` :
- `id` : Identifiant unique
- `nom` : Nom du produit
- `description` : Description du produit
- `prix` : Prix (DECIMAL 10,2)
- `stock` : Quantité en stock
- `categorie_id` : Référence à la catégorie (optionnel)
- `image_principale` : URL de l'image principale
- `date_ajout` : Date d'ajout (auto)
- `actif` : Produit actif ou non (BOOLEAN)

## 🚀 Installation

### Option 1 : Commande Symfony (RECOMMANDÉ) ⭐

```bash
cd backend
php bin/console app:create-produit-personnalise-table
```

Cette commande va :
1. ✅ Vérifier si la table existe
2. ✅ Créer la table avec tous les champs
3. ✅ Créer les index pour optimiser les performances
4. ✅ Vérifier que tout est correct

### Option 2 : SQL manuel

Exécutez le fichier SQL :

```bash
mysql -u votre_utilisateur -p Al3DPrint < backend/migrations/create_produit_personnalise.sql
```

Ou via phpMyAdmin :
1. Ouvrez votre outil MySQL
2. Sélectionnez la base `Al3DPrint`
3. Exécutez le contenu de `backend/migrations/create_produit_personnalise.sql`

## 📡 API Endpoints

### Base URL
```
http://localhost:8000/api/produits-personnalises
```

### Endpoints disponibles

- **GET** `/api/produits-personnalises` - Liste tous les produits personnalisés
  - Paramètres : `categorie_id`, `actif`
  - Exemple : `/api/produits-personnalises?actif=true`

- **GET** `/api/produits-personnalises/{id}` - Récupère un produit personnalisé par ID

- **POST** `/api/produits-personnalises` - Crée un nouveau produit personnalisé
  ```json
  {
    "nom": "Support téléphone personnalisé",
    "description": "Support unique fait main",
    "prix": "29.99",
    "stock": 10,
    "categorie_id": 1,
    "image_principale": "https://example.com/image.jpg",
    "actif": true
  }
  ```

- **PUT** `/api/produits-personnalises/{id}` - Met à jour un produit personnalisé

- **DELETE** `/api/produits-personnalises/{id}` - Supprime un produit personnalisé

## 🎯 Utilisation dans le frontend

La page `/custom-products` charge automatiquement les produits depuis `/api/produits-personnalises`.

## ✅ Vérification

Après la création, vérifiez que la table existe :

```sql
DESCRIBE produit_personnalise;
SHOW TABLES LIKE 'produit_personnalise';
```

