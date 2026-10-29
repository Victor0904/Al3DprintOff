# Guide d'utilisation de la Banque d'Images

## 📋 Vue d'ensemble

La banque d'images est un système centralisé pour gérer toutes les images utilisées dans l'application. Au lieu de saisir manuellement des URLs, vous pouvez maintenant uploader des images et les sélectionner depuis une liste déroulante.

## 🚀 Fonctionnalités

### 1. Upload d'images
- **Drag & Drop** : Glissez-déposez vos images directement dans l'interface
- **Sélection de fichier** : Cliquez pour sélectionner des images depuis votre ordinateur
- **Formats supportés** : JPG, PNG, GIF, WEBP
- **Taille maximale** : 10MB par fichier

### 2. Organisation
- **Types** : Produit, Variante, Catégorie, Autre
- **Catégories** : Organisez vos images par catégories personnalisées
- **Description** : Ajoutez des descriptions pour mieux identifier vos images

### 3. Recherche et filtres
- Recherche par nom, chemin ou description
- Filtrage par type
- Filtrage par catégorie

### 4. Intégration dans les formulaires
- Les champs "URL de l'image" ont été remplacés par des listes déroulantes
- Sélection directe depuis la banque d'images
- Aperçu de l'image sélectionnée

## 📁 Structure des fichiers

### Backend
- **Entité** : `backend/src/Entity/BanqueImage.php`
- **Contrôleur API** : `backend/src/Controller/BanqueImageController.php`
- **Migration** : `backend/migrations/create_banque_image.sql`
- **Commande** : `backend/src/Command/CreateBanqueImageTableCommand.php`

### Frontend
- **Composant principal** : `frontend/src/components/admin/ImageBankManagement.vue`
- **Intégration** : `frontend/src/components/admin/ProductsManagement.vue`
- **Vue Admin** : `frontend/src/views/AdminView.vue`

## 🔧 Installation

### 1. Migration de la base de données

La table `banque_image` a été créée automatiquement. Si vous devez la recréer :

```bash
cd backend
php bin/console app:create-banque-image-table
```

### 2. Dossier d'upload

Le dossier `backend/public/uploads/` est créé automatiquement lors du premier upload. Assurez-vous que les permissions d'écriture sont correctes.

## 📡 API Endpoints

### Liste des images
```
GET /api/banque-image
Query params:
  - type: Filtrer par type (optionnel)
  - categorie: Filtrer par catégorie (optionnel)
```

### Upload d'image
```
POST /api/banque-image
Content-Type: multipart/form-data

Body:
  - file: Fichier image (obligatoire)
  - nom: Nom de l'image (obligatoire)
  - type: Type (optionnel)
  - categorie: Catégorie (optionnel)
  - description: Description (optionnel)
```

### Création manuelle (URL externe)
```
POST /api/banque-image
Content-Type: application/json

Body:
{
  "nom": "Image externe",
  "chemin": "https://example.com/image.jpg",
  "type": "produit",
  "categorie": "couleurs",
  "description": "Description optionnelle"
}
```

### Suppression
```
DELETE /api/banque-image/{id}
```

### Mise à jour
```
PUT /api/banque-image/{id}
Content-Type: application/json

Body:
{
  "nom": "Nouveau nom",
  "type": "variante",
  "categorie": "nouvelle categorie",
  "description": "Nouvelle description"
}
```

## 🎨 Utilisation dans l'interface admin

### Accès à la banque d'images

1. Dans le panneau admin, cliquez sur l'onglet **"🖼️ Banque d'Images"**
2. Vous verrez toutes vos images organisées en grille
3. Utilisez les filtres pour trouver rapidement une image

### Upload d'une nouvelle image

1. Cliquez sur **"📤 Ajouter une image"**
2. Glissez-déposez votre image ou cliquez pour sélectionner
3. Remplissez les informations :
   - **Nom** : Nom descriptif de l'image
   - **Type** : Produit, Variante, Catégorie, ou Autre
   - **Catégorie** : Catégorie personnalisée (ex: "Couleurs", "Produits")
   - **Description** : Description optionnelle
4. Cliquez sur **"📤 Uploader"**

### Utilisation dans les formulaires

#### Pour un produit

1. Ouvrez le formulaire de création/édition d'un produit
2. Dans le champ **"Image principale"**, vous verrez une liste déroulante
3. Sélectionnez une image depuis la banque
4. Un aperçu de l'image s'affiche automatiquement
5. Cliquez sur **"🖼️ Banque d'images"** pour ouvrir la banque dans une modal

#### Pour une variante de couleur

1. Dans la section **"🎨 Variantes de Couleur"**
2. Pour chaque variante, utilisez le champ **"Image de la variante"**
3. Sélectionnez une image depuis la liste déroulante
4. L'aperçu s'affiche automatiquement

## 🔄 Migration depuis les URLs

Si vous avez déjà des produits avec des URLs d'images :

1. **Option 1** : Uploader les images dans la banque et mettre à jour les produits
2. **Option 2** : Créer des entrées manuelles dans la banque avec les URLs existantes

Pour créer une entrée manuelle avec une URL externe, utilisez l'API :

```bash
curl -X POST http://127.0.0.1:8000/api/banque-image \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Image externe",
    "chemin": "https://example.com/image.jpg",
    "type": "produit"
  }'
```

## 🛠️ Configuration

### Chemin des uploads

Par défaut, les images sont stockées dans `backend/public/uploads/`. Pour changer ce chemin, modifiez la propriété `$uploadDirectory` dans `BanqueImageController.php`.

### URL de base

Les images uploadées sont accessibles via :
```
http://127.0.0.1:8000/uploads/{nom_fichier}
```

Assurez-vous que le serveur web peut servir les fichiers depuis le dossier `public/uploads/`.

## 📝 Notes importantes

1. **Suppression d'images** : Lors de la suppression d'une image, le fichier physique est également supprimé (si c'est un fichier local)

2. **Images externes** : Vous pouvez toujours utiliser des URLs externes en créant une entrée manuelle dans la banque

3. **Performance** : Les images sont indexées par type et catégorie pour des recherches rapides

4. **Sécurité** : Seuls les formats d'image autorisés peuvent être uploadés (JPG, PNG, GIF, WEBP)

## 🐛 Dépannage

### Les images ne s'affichent pas
- Vérifiez que le dossier `public/uploads/` existe et a les bonnes permissions
- Vérifiez l'URL de base dans la configuration

### Erreur lors de l'upload
- Vérifiez la taille du fichier (max 10MB)
- Vérifiez le format du fichier (JPG, PNG, GIF, WEBP uniquement)
- Vérifiez les permissions d'écriture du dossier uploads

### La liste déroulante est vide
- Assurez-vous d'avoir uploadé au moins une image dans la banque
- Vérifiez que l'API `/api/banque-image` fonctionne correctement

## 🎯 Prochaines améliorations possibles

- Compression automatique des images
- Redimensionnement automatique
- Prévisualisation avant upload
- Tags multiples pour une meilleure organisation
- Recherche par similarité d'images
- Import en masse

