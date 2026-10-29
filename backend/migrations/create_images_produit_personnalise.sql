-- Création de la table images_produit_personnalise
-- Galerie d'images pour les produits personnalisés (relation OneToMany)

CREATE TABLE IF NOT EXISTS images_produit_personnalise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_personnalise_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    ordre INT NOT NULL DEFAULT 1,
    FOREIGN KEY (produit_personnalise_id) REFERENCES produit_personnalise(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Index pour optimiser les performances
CREATE INDEX idx_images_pp_produit ON images_produit_personnalise(produit_personnalise_id);
CREATE INDEX idx_images_pp_ordre ON images_produit_personnalise(ordre);
