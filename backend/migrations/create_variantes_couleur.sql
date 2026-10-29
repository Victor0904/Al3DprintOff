-- Migration pour créer la table variantes_couleur
CREATE TABLE IF NOT EXISTS variantes_couleur (
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

-- Créer un index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_variantes_produit ON variantes_couleur(produit_id);
CREATE INDEX IF NOT EXISTS idx_variantes_actif ON variantes_couleur(actif);

