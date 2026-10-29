<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create panier_item table for cart management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE panier_item (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            produit_id INT NOT NULL,
            quantite INT NOT NULL DEFAULT 1,
            date_ajout DATETIME NOT NULL,
            personnalisation JSON DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX IDX_panier_item_client_id (client_id),
            INDEX IDX_panier_item_produit_id (produit_id)
        )');
        
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_panier_item_client_id FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_panier_item_produit_id FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE panier_item');
    }
}

