-- Migration pour ajouter les champs de réinitialisation de mot de passe
-- Exécutez cette migration si vous n'utilisez pas Doctrine Migrations

ALTER TABLE clients 
ADD COLUMN reset_token VARCHAR(255) NULL,
ADD COLUMN reset_token_expires_at DATETIME NULL;

-- Créer un index pour améliorer les performances de recherche par token
CREATE INDEX idx_reset_token ON clients(reset_token);

