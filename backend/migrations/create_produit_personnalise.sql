-- Création de la table produit_personnalise
-- Cette table contient les produits personnalisés (fait maison) du site

CREATE TABLE IF NOT EXISTS produit_personnalise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    categorie_id INT,
    image_principale VARCHAR(255),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Index pour optimiser les performances
CREATE INDEX idx_produit_personnalise_categorie ON produit_personnalise(categorie_id);
CREATE INDEX idx_produit_personnalise_actif ON produit_personnalise(actif);
CREATE INDEX idx_produit_personnalise_date ON produit_personnalise(date_ajout);

