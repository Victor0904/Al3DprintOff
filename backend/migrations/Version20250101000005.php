<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add numero_suivi column to commandes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD COLUMN numero_suivi VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes DROP COLUMN numero_suivi');
    }
}

