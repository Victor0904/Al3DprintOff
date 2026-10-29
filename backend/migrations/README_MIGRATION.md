# Migration de la colonne statut de la table commandes

## Problème
La table `commandes` utilise un ENUM qui n'accepte que certaines valeurs :
- 'en attente', 'en fabrication', 'expédiée', 'livrée', 'annulée', 'payée'

Mais l'interface admin utilise :
- 'En attente', 'En cours', 'Expédié', 'Terminé'

## Solution
Convertir la colonne `statut` de ENUM à VARCHAR pour plus de flexibilité.

## Instructions

### Option 1 : Via MySQL directement
```sql
-- Connectez-vous à votre base de données MySQL
mysql -u votre_utilisateur -p Al3DPrint

-- Puis exécutez le contenu du fichier update_commandes_statut.sql
source backend/migrations/update_commandes_statut.sql;
```

### Option 2 : Via phpMyAdmin ou autre outil
1. Ouvrez votre outil de gestion MySQL
2. Sélectionnez la base de données `Al3DPrint`
3. Exécutez les commandes SQL du fichier `update_commandes_statut.sql`

### Option 3 : Via la ligne de commande
```bash
mysql -u votre_utilisateur -p Al3DPrint < backend/migrations/update_commandes_statut.sql
```

## Vérification
Après la migration, vérifiez que la structure est correcte :
```sql
DESCRIBE commandes;
```

La colonne `statut` devrait maintenant être de type `varchar(20)` au lieu de `enum(...)`.

