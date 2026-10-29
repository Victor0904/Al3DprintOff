<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add additional address fields (adresse2, ville2, code_postal2, adresse3, ville3, code_postal3) to clients table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients ADD adresse2 LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD ville2 VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD code_postal2 VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD adresse3 LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD ville3 VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD code_postal3 VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP adresse2');
        $this->addSql('ALTER TABLE clients DROP ville2');
        $this->addSql('ALTER TABLE clients DROP code_postal2');
        $this->addSql('ALTER TABLE clients DROP adresse3');
        $this->addSql('ALTER TABLE clients DROP ville3');
        $this->addSql('ALTER TABLE clients DROP code_postal3');
    }
}
