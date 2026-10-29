-- Création de la table images_variante_couleur
-- Galerie d'images pour les variantes de couleur des produits

CREATE TABLE IF NOT EXISTS images_variante_couleur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    ordre INT NOT NULL DEFAULT 1,
    FOREIGN KEY (variante_id) REFERENCES variantes_couleur(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE INDEX idx_images_vc_variante ON images_variante_couleur(variante_id);
CREATE INDEX idx_images_vc_ordre ON images_variante_couleur(ordre);
