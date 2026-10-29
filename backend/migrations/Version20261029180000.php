<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Statuts de commande unifiés : En attente, Payée, En cours, Expédiée, Livrée, Annulée.
 */
final class Version20261029180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unifie les libellés de statut des commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE commandes SET statut = 'Expédiée' WHERE statut = 'Expédié'");
        $this->addSql("UPDATE commandes SET statut = 'Livrée' WHERE statut IN ('Terminé', 'Livré')");
        $this->addSql("UPDATE commandes SET statut = 'Annulée' WHERE statut = 'Annulé'");
    }

    public function down(Schema $schema): void
    {
    }
}
