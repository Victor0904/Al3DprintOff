<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706202124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis_clients (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, client_id INT NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date DATETIME NOT NULL, INDEX IDX_2BCBBE72F347EFB (produit_id), INDEX IDX_2BCBBE7219EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE code_promo (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, remise NUMERIC(5, 2) NOT NULL, actif TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_5C4683B777153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_personnalisation (id INT AUTO_INCREMENT NOT NULL, client_email VARCHAR(255) NOT NULL, client_nom VARCHAR(255) NOT NULL, product_name VARCHAR(255) NOT NULL, product_id INT DEFAULT NULL, statut VARCHAR(50) NOT NULL, fichiers JSON NOT NULL COMMENT \'(DC2Type:json)\', notes LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE images_produit (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, url VARCHAR(255) NOT NULL, ordre INT NOT NULL, INDEX IDX_B6D8F245F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE images_produit_personnalise (id INT AUTO_INCREMENT NOT NULL, produit_personnalise_id INT NOT NULL, url VARCHAR(255) NOT NULL, ordre INT NOT NULL, INDEX IDX_6A67A7C0BC0637F7 (produit_personnalise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE images_variante_couleur (id INT AUTO_INCREMENT NOT NULL, variante_id INT NOT NULL, url VARCHAR(255) NOT NULL, ordre INT NOT NULL, INDEX IDX_16A1F21AD45162B6 (variante_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE impressions (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, statut VARCHAR(20) NOT NULL, materiau VARCHAR(100) DEFAULT NULL, temps_estime INT DEFAULT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, INDEX IDX_A74C17A082EA2E54 (commande_id), INDEX IDX_A74C17A0F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE options_personnalisation (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, nom VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, valeurs_possibles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_D61CF715F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE avis_clients ADD CONSTRAINT FK_2BCBBE72F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_clients ADD CONSTRAINT FK_2BCBBE7219EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE images_produit ADD CONSTRAINT FK_B6D8F245F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE images_produit_personnalise ADD CONSTRAINT FK_6A67A7C0BC0637F7 FOREIGN KEY (produit_personnalise_id) REFERENCES produit_personnalise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE images_variante_couleur ADD CONSTRAINT FK_16A1F21AD45162B6 FOREIGN KEY (variante_id) REFERENCES variantes_couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE impressions ADD CONSTRAINT FK_A74C17A082EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE impressions ADD CONSTRAINT FK_A74C17A0F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE options_personnalisation ADD CONSTRAINT FK_D61CF715F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_banque_image_date ON banque_image');
        $this->addSql('DROP INDEX idx_banque_image_type ON banque_image');
        $this->addSql('DROP INDEX idx_banque_image_categorie ON banque_image');
        $this->addSql('ALTER TABLE banque_image CHANGE date_ajout date_ajout DATETIME NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE categories ADD parent_id INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3AF34668727ACA70 ON categories (parent_id)');
        $this->addSql('DROP INDEX idx_reset_token ON clients');
        $this->addSql('ALTER TABLE clients CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON clients');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74E7927C74 ON clients (email)');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY commandes_ibfk_1');
        $this->addSql('ALTER TABLE commandes ADD frais_livraison NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, ADD numero_commande VARCHAR(20) DEFAULT NULL, ADD code_promo_utilise VARCHAR(50) DEFAULT NULL, CHANGE date_commande date_commande DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE adresse_livraison adresse_livraison LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_35D4282CCFFD611D ON commandes (numero_commande)');
        $this->addSql('DROP INDEX idx_commandes_client ON commandes');
        $this->addSql('CREATE INDEX IDX_35D4282C19EB6921 ON commandes (client_id)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT commandes_ibfk_1 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commande DROP FOREIGN KEY details_commande_ibfk_2');
        $this->addSql('ALTER TABLE details_commande DROP FOREIGN KEY details_commande_ibfk_1');
        $this->addSql('DROP INDEX idx_details_commande ON details_commande');
        $this->addSql('CREATE INDEX IDX_4BCD5F682EA2E54 ON details_commande (commande_id)');
        $this->addSql('DROP INDEX produit_id ON details_commande');
        $this->addSql('CREATE INDEX IDX_4BCD5F6F347EFB ON details_commande (produit_id)');
        $this->addSql('ALTER TABLE details_commande ADD CONSTRAINT details_commande_ibfk_2 FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commande ADD CONSTRAINT details_commande_ibfk_1 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiements DROP FOREIGN KEY paiements_ibfk_1');
        $this->addSql('ALTER TABLE paiements CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE date_paiement date_paiement DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_paiements_commande ON paiements');
        $this->addSql('CREATE INDEX IDX_E1B02E1282EA2E54 ON paiements (commande_id)');
        $this->addSql('ALTER TABLE paiements ADD CONSTRAINT paiements_ibfk_1 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_panier_item_produit_id');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_panier_item_client_id');
        $this->addSql('ALTER TABLE panier_item CHANGE quantite quantite INT NOT NULL');
        $this->addSql('DROP INDEX idx_panier_item_client_id ON panier_item');
        $this->addSql('CREATE INDEX IDX_EBFD006719EB6921 ON panier_item (client_id)');
        $this->addSql('DROP INDEX idx_panier_item_produit_id ON panier_item');
        $this->addSql('CREATE INDEX IDX_EBFD0067F347EFB ON panier_item (produit_id)');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_panier_item_produit_id FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_panier_item_client_id FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_produit_personnalise_actif ON produit_personnalise');
        $this->addSql('DROP INDEX idx_produit_personnalise_date ON produit_personnalise');
        $this->addSql('ALTER TABLE produit_personnalise DROP FOREIGN KEY produit_personnalise_ibfk_1');
        $this->addSql('ALTER TABLE produit_personnalise CHANGE description description LONGTEXT DEFAULT NULL, CHANGE stock stock INT NOT NULL, CHANGE date_ajout date_ajout DATETIME NOT NULL, CHANGE actif actif TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX idx_produit_personnalise_categorie ON produit_personnalise');
        $this->addSql('CREATE INDEX IDX_E4F73AA7BCF5E72D ON produit_personnalise (categorie_id)');
        $this->addSql('ALTER TABLE produit_personnalise ADD CONSTRAINT produit_personnalise_ibfk_1 FOREIGN KEY (categorie_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY produits_ibfk_1');
        $this->addSql('ALTER TABLE produits DROP fichier_3d, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE stock stock INT NOT NULL, CHANGE date_ajout date_ajout DATETIME NOT NULL, CHANGE actif actif TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX idx_produits_categorie ON produits');
        $this->addSql('CREATE INDEX IDX_BE2DDF8CBCF5E72D ON produits (categorie_id)');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT produits_ibfk_1 FOREIGN KEY (categorie_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_variantes_actif ON variantes_couleur');
        $this->addSql('ALTER TABLE variantes_couleur DROP FOREIGN KEY variantes_couleur_ibfk_1');
        $this->addSql('ALTER TABLE variantes_couleur CHANGE stock stock INT NOT NULL, CHANGE actif actif TINYINT(1) NOT NULL, CHANGE ordre ordre INT NOT NULL');
        $this->addSql('DROP INDEX idx_variantes_produit ON variantes_couleur');
        $this->addSql('CREATE INDEX IDX_9E6D9BEDF347EFB ON variantes_couleur (produit_id)');
        $this->addSql('ALTER TABLE variantes_couleur ADD CONSTRAINT variantes_couleur_ibfk_1 FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis_clients DROP FOREIGN KEY FK_2BCBBE72F347EFB');
        $this->addSql('ALTER TABLE avis_clients DROP FOREIGN KEY FK_2BCBBE7219EB6921');
        $this->addSql('ALTER TABLE images_produit DROP FOREIGN KEY FK_B6D8F245F347EFB');
        $this->addSql('ALTER TABLE images_produit_personnalise DROP FOREIGN KEY FK_6A67A7C0BC0637F7');
        $this->addSql('ALTER TABLE images_variante_couleur DROP FOREIGN KEY FK_16A1F21AD45162B6');
        $this->addSql('ALTER TABLE impressions DROP FOREIGN KEY FK_A74C17A082EA2E54');
        $this->addSql('ALTER TABLE impressions DROP FOREIGN KEY FK_A74C17A0F347EFB');
        $this->addSql('ALTER TABLE options_personnalisation DROP FOREIGN KEY FK_D61CF715F347EFB');
        $this->addSql('DROP TABLE avis_clients');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE demande_personnalisation');
        $this->addSql('DROP TABLE images_produit');
        $this->addSql('DROP TABLE images_produit_personnalise');
        $this->addSql('DROP TABLE images_variante_couleur');
        $this->addSql('DROP TABLE impressions');
        $this->addSql('DROP TABLE options_personnalisation');
        $this->addSql('ALTER TABLE banque_image CHANGE date_ajout date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_banque_image_date ON banque_image (date_ajout)');
        $this->addSql('CREATE INDEX idx_banque_image_type ON banque_image (type)');
        $this->addSql('CREATE INDEX idx_banque_image_categorie ON banque_image (categorie)');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668727ACA70');
        $this->addSql('DROP INDEX IDX_3AF34668727ACA70 ON categories');
        $this->addSql('ALTER TABLE categories DROP parent_id, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_reset_token ON clients (reset_token)');
        $this->addSql('DROP INDEX uniq_c82e74e7927c74 ON clients');
        $this->addSql('CREATE UNIQUE INDEX email ON clients (email)');
        $this->addSql('DROP INDEX UNIQ_35D4282CCFFD611D ON commandes');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282C19EB6921');
        $this->addSql('ALTER TABLE commandes DROP frais_livraison, DROP numero_commande, DROP code_promo_utilise, CHANGE date_commande date_commande DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE statut statut VARCHAR(20) DEFAULT \'En attente\', CHANGE adresse_livraison adresse_livraison TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_35d4282c19eb6921 ON commandes');
        $this->addSql('CREATE INDEX idx_commandes_client ON commandes (client_id)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282C19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commande DROP FOREIGN KEY FK_4BCD5F682EA2E54');
        $this->addSql('ALTER TABLE details_commande DROP FOREIGN KEY FK_4BCD5F6F347EFB');
        $this->addSql('DROP INDEX idx_4bcd5f682ea2e54 ON details_commande');
        $this->addSql('CREATE INDEX idx_details_commande ON details_commande (commande_id)');
        $this->addSql('DROP INDEX idx_4bcd5f6f347efb ON details_commande');
        $this->addSql('CREATE INDEX produit_id ON details_commande (produit_id)');
        $this->addSql('ALTER TABLE details_commande ADD CONSTRAINT FK_4BCD5F682EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commande ADD CONSTRAINT FK_4BCD5F6F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiements DROP FOREIGN KEY FK_E1B02E1282EA2E54');
        $this->addSql('ALTER TABLE paiements CHANGE statut statut VARCHAR(255) DEFAULT \'en attente\', CHANGE date_paiement date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX idx_e1b02e1282ea2e54 ON paiements');
        $this->addSql('CREATE INDEX idx_paiements_commande ON paiements (commande_id)');
        $this->addSql('ALTER TABLE paiements ADD CONSTRAINT FK_E1B02E1282EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD006719EB6921');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F347EFB');
        $this->addSql('ALTER TABLE panier_item CHANGE quantite quantite INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX idx_ebfd006719eb6921 ON panier_item');
        $this->addSql('CREATE INDEX IDX_panier_item_client_id ON panier_item (client_id)');
        $this->addSql('DROP INDEX idx_ebfd0067f347efb ON panier_item');
        $this->addSql('CREATE INDEX IDX_panier_item_produit_id ON panier_item (produit_id)');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD006719EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY FK_BE2DDF8CBCF5E72D');
        $this->addSql('ALTER TABLE produits ADD fichier_3d VARCHAR(255) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE stock stock INT DEFAULT 0, CHANGE date_ajout date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE actif actif TINYINT(1) DEFAULT 1');
        $this->addSql('DROP INDEX idx_be2ddf8cbcf5e72d ON produits');
        $this->addSql('CREATE INDEX idx_produits_categorie ON produits (categorie_id)');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT FK_BE2DDF8CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit_personnalise DROP FOREIGN KEY FK_E4F73AA7BCF5E72D');
        $this->addSql('ALTER TABLE produit_personnalise CHANGE description description TEXT DEFAULT NULL, CHANGE stock stock INT DEFAULT 0, CHANGE date_ajout date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE actif actif TINYINT(1) DEFAULT 1');
        $this->addSql('CREATE INDEX idx_produit_personnalise_actif ON produit_personnalise (actif)');
        $this->addSql('CREATE INDEX idx_produit_personnalise_date ON produit_personnalise (date_ajout)');
        $this->addSql('DROP INDEX idx_e4f73aa7bcf5e72d ON produit_personnalise');
        $this->addSql('CREATE INDEX idx_produit_personnalise_categorie ON produit_personnalise (categorie_id)');
        $this->addSql('ALTER TABLE produit_personnalise ADD CONSTRAINT FK_E4F73AA7BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE variantes_couleur DROP FOREIGN KEY FK_9E6D9BEDF347EFB');
        $this->addSql('ALTER TABLE variantes_couleur CHANGE stock stock INT DEFAULT 0 NOT NULL, CHANGE actif actif TINYINT(1) DEFAULT 1 NOT NULL, CHANGE ordre ordre INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_variantes_actif ON variantes_couleur (actif)');
        $this->addSql('DROP INDEX idx_9e6d9bedf347efb ON variantes_couleur');
        $this->addSql('CREATE INDEX idx_variantes_produit ON variantes_couleur (produit_id)');
        $this->addSql('ALTER TABLE variantes_couleur ADD CONSTRAINT FK_9E6D9BEDF347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
    }
}
