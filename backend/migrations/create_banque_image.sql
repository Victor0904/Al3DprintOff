-- Migration pour créer la table banque_image
CREATE TABLE IF NOT EXISTS banque_image (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    chemin VARCHAR(500) NOT NULL,
    type VARCHAR(50) NULL,
    categorie VARCHAR(100) NULL,
    taille INT NULL,
    format VARCHAR(20) NULL,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer des index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_banque_image_type ON banque_image(type);
CREATE INDEX IF NOT EXISTS idx_banque_image_categorie ON banque_image(categorie);
CREATE INDEX IF NOT EXISTS idx_banque_image_date ON banque_image(date_ajout);

