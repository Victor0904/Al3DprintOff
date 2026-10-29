<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vidéo 360° sur les variantes de couleur et les produits personnalisés';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes_couleur ADD video_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_personnalise ADD video_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes_couleur DROP video_url');
        $this->addSql('ALTER TABLE produit_personnalise DROP video_url');
    }
}
