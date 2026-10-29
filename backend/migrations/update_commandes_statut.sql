-- Migration pour modifier la colonne statut de la table commandes
-- Permet d'utiliser VARCHAR au lieu d'ENUM pour plus de flexibilité

-- Étape 1: Mettre à jour les valeurs existantes AVANT de modifier la structure
-- (pour éviter les erreurs de contrainte)
UPDATE commandes SET statut = 'En attente' WHERE statut IN ('en attente', 'En attente');
UPDATE commandes SET statut = 'En cours' WHERE statut IN ('en fabrication', 'En fabrication');
UPDATE commandes SET statut = 'Expédié' WHERE statut IN ('expédiée', 'Expédiée', 'expédié', 'Expédié');
UPDATE commandes SET statut = 'Terminé' WHERE statut IN ('livrée', 'Livrée', 'terminé', 'Terminé');

-- Étape 2: Modifier la colonne statut de ENUM à VARCHAR
-- Cela permet d'accepter n'importe quelle valeur de statut
ALTER TABLE commandes 
MODIFY COLUMN statut VARCHAR(20) DEFAULT 'En attente';

-- Vérification : Afficher la structure de la colonne pour confirmer
-- DESCRIBE commandes;

