<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les coordonnées sont chiffrées (base64 ~56+ caractères) : les colonnes de 10/30/100
 * caractères tronquaient les valeurs et les rendaient indéchiffrables.
 */
final class Version20261029181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrandit les colonnes chiffrées de clients (téléphone, codes postaux, villes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients
            MODIFY telephone VARCHAR(255) DEFAULT NULL,
            MODIFY code_postal VARCHAR(255) DEFAULT NULL,
            MODIFY code_postal2 VARCHAR(255) DEFAULT NULL,
            MODIFY code_postal3 VARCHAR(255) DEFAULT NULL,
            MODIFY ville VARCHAR(255) DEFAULT NULL,
            MODIFY ville2 VARCHAR(255) DEFAULT NULL,
            MODIFY ville3 VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
