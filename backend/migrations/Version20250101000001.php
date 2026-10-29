<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create adresses_livraison table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE adresses_livraison (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            adresse LONGTEXT NOT NULL,
            code_postal VARCHAR(10) NOT NULL,
            ville VARCHAR(100) NOT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            date_creation DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_client_id (client_id)
        )');
        
        $this->addSql('ALTER TABLE adresses_livraison ADD CONSTRAINT FK_adresses_livraison_client_id FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE adresses_livraison');
    }
}
