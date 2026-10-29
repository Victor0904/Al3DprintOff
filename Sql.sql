-- 1️⃣ Création de la base de données
CREATE DATABASE IF NOT EXISTS Al3DPrint
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE Al3DPrint;

-- 2️⃣ Table : categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- 3️⃣ Table : produits
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    categorie_id INT,
    image_principale VARCHAR(255),
    fichier_3d VARCHAR(255),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 4️⃣ Table : options_personnalisation
CREATE TABLE options_personnalisation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    type ENUM('texte','choix','nombre','fichier') NOT NULL,
    valeurs_possibles JSON,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- 5️⃣ Table : clients
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    adresse TEXT,
    telephone VARCHAR(30),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6️⃣ Table : commandes
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en attente','en fabrication','expédiée','livrée','annulée','payée') DEFAULT 'en attente',
    total DECIMAL(10,2) NOT NULL,
    adresse_livraison TEXT,
    mode_paiement VARCHAR(50),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- 7️⃣ Table : details_commande
CREATE TABLE details_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    personnalisation JSON,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- 8️⃣ Table : paiements (Stripe, PayPal, etc.)
CREATE TABLE paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    methode VARCHAR(50),
    statut ENUM('en attente','réussi','échoué','remboursé') DEFAULT 'en attente',
    stripe_payment_id VARCHAR(255),
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

-- 9️⃣ Table : images_produit
CREATE TABLE images_produit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    ordre INT DEFAULT 1,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- 🔟 Table : avis_clients
CREATE TABLE avis_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    client_id INT NOT NULL,
    note INT CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- 11️⃣ Table : impressions (suivi de fabrication 3D interne)
CREATE TABLE impressions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    statut ENUM('à faire','en cours','terminée','échouée') DEFAULT 'à faire',
    materiau VARCHAR(100),
    temps_estime INT,
    date_debut DATETIME,
    date_fin DATETIME,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- ✅ Index pour optimiser les performances
CREATE INDEX idx_produits_categorie ON produits(categorie_id);
CREATE INDEX idx_commandes_client ON commandes(client_id);
CREATE INDEX idx_paiements_commande ON paiements(commande_id);
CREATE INDEX idx_details_commande ON details_commande(commande_id);
CREATE INDEX idx_avis_produit ON avis_clients(produit_id);
CREATE INDEX idx_impressions_commande ON impressions(commande_id);
