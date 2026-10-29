-- ============================================
-- FIX URGENT : Modifier la colonne statut de ENUM à VARCHAR
-- ============================================
-- 
-- PROBLÈME : La table commandes utilise un ENUM qui n'accepte que :
--   'en attente', 'en fabrication', 'expédiée', 'livrée', 'annulée', 'payée'
-- 
-- Mais l'interface utilise : 'En attente', 'En cours', 'Expédié', 'Terminé'
-- 
-- SOLUTION : Convertir l'ENUM en VARCHAR pour accepter n'importe quelle valeur
-- ============================================

-- Étape 1 : Mettre à jour les valeurs existantes pour correspondre aux nouvelles
UPDATE commandes SET statut = 'En attente' WHERE statut IN ('en attente', 'En attente');
UPDATE commandes SET statut = 'En cours' WHERE statut IN ('en fabrication', 'En fabrication');
UPDATE commandes SET statut = 'Expédié' WHERE statut IN ('expédiée', 'Expédiée', 'expédié', 'Expédié');
UPDATE commandes SET statut = 'Terminé' WHERE statut IN ('livrée', 'Livrée', 'terminé', 'Terminé');

-- Étape 2 : Modifier la structure de la colonne de ENUM à VARCHAR
-- C'est cette commande qui résout le problème !
ALTER TABLE commandes 
MODIFY COLUMN statut VARCHAR(20) DEFAULT 'En attente';

-- Étape 3 : Vérification
-- Exécutez cette commande pour vérifier que ça a fonctionné :
-- DESCRIBE commandes;
-- 
-- Vous devriez voir : statut | varchar(20) | YES | | En attente

