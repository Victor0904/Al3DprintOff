# 🔧 Guide : Corriger le problème des statuts de commandes

## ❌ Problème

Les statuts "En cours", "Expédié" et "Terminé" ne se sauvegardent pas dans la base de données. Seul "En attente" fonctionne.

**Cause :** La table `commandes` utilise un ENUM qui n'accepte que certaines valeurs :
- `'en attente'`, `'en fabrication'`, `'expédiée'`, `'livrée'`, `'annulée'`, `'payée'`

Mais l'interface utilise :
- `'En attente'`, `'En cours'`, `'Expédié'`, `'Terminé'`

## ✅ Solution : Exécuter la migration

### Option 1 : Commande Symfony (RECOMMANDÉ) ⭐

```bash
cd backend
php bin/console app:fix-statut-enum
```

Cette commande va :
1. ✅ Vérifier la structure actuelle
2. ✅ Mettre à jour les valeurs existantes
3. ✅ Convertir l'ENUM en VARCHAR
4. ✅ Vérifier que tout fonctionne

### Option 2 : SQL manuel

Ouvrez votre client MySQL (phpMyAdmin, MySQL Workbench, etc.) et exécutez :

```sql
-- 1. Mettre à jour les valeurs existantes
UPDATE commandes SET statut = 'En attente' WHERE statut IN ('en attente', 'En attente');
UPDATE commandes SET statut = 'En cours' WHERE statut IN ('en fabrication', 'En fabrication');
UPDATE commandes SET statut = 'Expédié' WHERE statut IN ('expédiée', 'Expédiée', 'expédié', 'Expédié');
UPDATE commandes SET statut = 'Terminé' WHERE statut IN ('livrée', 'Livrée', 'terminé', 'Terminé');

-- 2. ⚠️ IMPORTANT : Convertir ENUM en VARCHAR
ALTER TABLE commandes MODIFY COLUMN statut VARCHAR(20) DEFAULT 'En attente';
```

### Option 3 : Via le fichier SQL

```bash
mysql -u votre_utilisateur -p Al3DPrint < backend/migrations/fix_statut_enum.sql
```

## 🔍 Vérification

Après la migration, vérifiez que ça fonctionne :

```sql
DESCRIBE commandes;
```

La colonne `statut` devrait maintenant être de type `varchar(20)` au lieu de `enum(...)`.

## ✅ Test

1. Allez dans l'interface admin → Commandes
2. Changez un statut à "En cours"
3. Vérifiez dans la base de données que la valeur est bien "En cours"
4. Répétez avec "Expédié" et "Terminé"

## 🆘 Si ça ne fonctionne toujours pas

1. Vérifiez les logs PHP : `var/log/dev.log` ou les logs de votre serveur
2. Vérifiez la console du navigateur (F12) pour voir les erreurs
3. Vérifiez que la migration a bien été exécutée avec : `DESCRIBE commandes;`

