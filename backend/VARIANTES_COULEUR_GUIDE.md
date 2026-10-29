# 🎨 Guide : Variantes de Couleur pour les Produits

## 📋 Description

Ce système permet d'ajouter des variantes de couleurs aux produits. Chaque variante peut avoir :
- Un nom de couleur (ex: "Rouge", "Bleu", "Noir")
- Un code couleur hexadécimal (ex: #FF5733)
- Une image spécifique
- Une référence unique
- Un stock indépendant
- Un ordre d'affichage

## 🚀 Installation

### 1. Exécuter la migration SQL

Exécutez le fichier de migration pour créer la table `variantes_couleur` :

```bash
# Option 1 : Via SQLite directement
sqlite3 backend/var/data.db < backend/migrations/create_variantes_couleur.sql

# Option 2 : Via MySQL/MariaDB
mysql -u votre_utilisateur -p Al3DPrint < backend/migrations/create_variantes_couleur.sql
```

### 2. Vérifier la migration

Vérifiez que la table a été créée :

```sql
-- SQLite
.schema variantes_couleur

-- MySQL
DESCRIBE variantes_couleur;
```

## 📖 Utilisation

### Dans l'interface d'administration

1. Allez dans **Admin → Produits**
2. Cliquez sur **Ajouter un produit** ou **Modifier** un produit existant
3. Dans le formulaire, vous trouverez la section **🎨 Variantes de Couleur**
4. Cliquez sur **+ Ajouter une variante** pour créer une nouvelle variante
5. Remplissez les champs :
   - **Nom de la couleur** : Le nom affiché (ex: "Rouge")
   - **Code couleur** : Sélectionnez la couleur avec le sélecteur de couleur
   - **URL de l'image** : L'URL de l'image spécifique à cette couleur (optionnel)
   - **Référence** : Une référence unique pour cette variante (optionnel)
   - **Stock** : Le stock disponible pour cette variante
   - **Ordre d'affichage** : L'ordre d'affichage (0 = premier)
   - **Actif** : Cochez pour activer la variante
6. Cliquez sur **×** pour supprimer une variante
7. Sauvegardez le produit

### Sur la page produit

1. Les variantes de couleur s'affichent automatiquement sous l'image principale
2. Cliquez sur une couleur pour la sélectionner
3. L'image du produit change si la variante a une image spécifique
4. La référence du produit s'affiche si la variante en a une
5. Le stock disponible se met à jour selon la variante sélectionnée
6. Les variantes en rupture de stock sont désactivées

## 🔧 Structure de la base de données

```sql
CREATE TABLE variantes_couleur (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    produit_id INTEGER NOT NULL,
    nom_couleur VARCHAR(50) NOT NULL,
    code_couleur VARCHAR(7) NULL,
    image_url VARCHAR(255) NULL,
    reference VARCHAR(100) NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    actif BOOLEAN NOT NULL DEFAULT 1,
    ordre INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);
```

## 📡 API

### Récupérer un produit avec ses variantes

```http
GET /api/produits/{id}
```

Réponse :
```json
{
  "id": 1,
  "nom": "Produit exemple",
  "variantes_couleur": [
    {
      "id": 1,
      "nom_couleur": "Rouge",
      "code_couleur": "#FF0000",
      "image_url": "https://example.com/rouge.jpg",
      "reference": "PROD-001-RED",
      "stock": 10,
      "actif": true,
      "ordre": 0
    }
  ]
}
```

### Créer un produit avec variantes

```http
POST /api/produits
Content-Type: application/json

{
  "nom": "Produit exemple",
  "prix": 29.99,
  "variantes_couleur": [
    {
      "nom_couleur": "Rouge",
      "code_couleur": "#FF0000",
      "image_url": "https://example.com/rouge.jpg",
      "reference": "PROD-001-RED",
      "stock": 10,
      "actif": true,
      "ordre": 0
    }
  ]
}
```

## ✅ Fonctionnalités

- ✅ Ajout/Modification/Suppression de variantes dans l'admin
- ✅ Affichage des variantes sur la page produit
- ✅ Changement d'image selon la variante sélectionnée
- ✅ Affichage de la référence selon la variante
- ✅ Gestion du stock par variante
- ✅ Désactivation des variantes en rupture de stock
- ✅ Ordre d'affichage personnalisable

## 🎨 Exemple d'utilisation

1. Créez un produit "Tasse personnalisée"
2. Ajoutez 3 variantes :
   - Rouge (#FF0000) - Stock: 15 - Image: tasse-rouge.jpg
   - Bleu (#0000FF) - Stock: 8 - Image: tasse-bleue.jpg
   - Noir (#000000) - Stock: 0 - Image: tasse-noire.jpg
3. Sur la page produit, les clients peuvent :
   - Voir les 3 couleurs disponibles
   - Cliquer sur une couleur pour voir l'image correspondante
   - Voir la référence du produit selon la couleur
   - Le stock se met à jour automatiquement

## 🐛 Dépannage

### Les variantes ne s'affichent pas

1. Vérifiez que la migration a été exécutée
2. Vérifiez que les variantes sont actives (`actif = true`)
3. Vérifiez la console du navigateur pour les erreurs

### L'image ne change pas

1. Vérifiez que la variante a une `image_url` définie
2. Vérifiez que l'URL de l'image est accessible
3. Si aucune image n'est définie pour la variante, l'image principale du produit est utilisée

