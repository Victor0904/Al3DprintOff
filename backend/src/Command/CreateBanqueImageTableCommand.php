<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-banque-image-table',
    description: 'Crée la table banque_image pour la gestion centralisée des images'
)]
class CreateBanqueImageTableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Création de la table banque_image');

            // Vérifier si la table existe déjà
            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            if (in_array('banque_image', $tables)) {
                $io->warning('La table banque_image existe déjà.');
                if (!$io->confirm('Voulez-vous la supprimer et la recréer ?', false)) {
                    $io->info('Opération annulée.');
                    return Command::SUCCESS;
                }
                $this->connection->executeStatement('DROP TABLE IF EXISTS banque_image');
            }

            // Détecter le type de base de données
            $platform = $this->connection->getDatabasePlatform();
            $isMySQL = strpos($platform->getName(), 'mysql') !== false;

            // Créer la table selon le type de base de données
            if ($isMySQL) {
                $sql = "
                    CREATE TABLE banque_image (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nom VARCHAR(255) NOT NULL,
                        chemin VARCHAR(500) NOT NULL,
                        type VARCHAR(50) NULL,
                        categorie VARCHAR(100) NULL,
                        taille INT NULL,
                        format VARCHAR(20) NULL,
                        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        description TEXT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
            } else {
                // SQLite
                $sql = "
                    CREATE TABLE banque_image (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nom VARCHAR(255) NOT NULL,
                        chemin VARCHAR(500) NOT NULL,
                        type VARCHAR(50) NULL,
                        categorie VARCHAR(100) NULL,
                        taille INTEGER NULL,
                        format VARCHAR(20) NULL,
                        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        description TEXT NULL
                    );
                ";
            }

            $this->connection->executeStatement($sql);

            // Créer les index
            if ($isMySQL) {
                $indexes = $schemaManager->listTableIndexes('banque_image');
                $indexNames = array_keys($indexes);
                
                if (!in_array('idx_banque_image_type', $indexNames)) {
                    $this->connection->executeStatement('CREATE INDEX idx_banque_image_type ON banque_image(type)');
                }
                if (!in_array('idx_banque_image_categorie', $indexNames)) {
                    $this->connection->executeStatement('CREATE INDEX idx_banque_image_categorie ON banque_image(categorie)');
                }
                if (!in_array('idx_banque_image_date', $indexNames)) {
                    $this->connection->executeStatement('CREATE INDEX idx_banque_image_date ON banque_image(date_ajout)');
                }
            } else {
                // SQLite
                $this->connection->executeStatement('CREATE INDEX IF NOT EXISTS idx_banque_image_type ON banque_image(type)');
                $this->connection->executeStatement('CREATE INDEX IF NOT EXISTS idx_banque_image_categorie ON banque_image(categorie)');
                $this->connection->executeStatement('CREATE INDEX IF NOT EXISTS idx_banque_image_date ON banque_image(date_ajout)');
            }

            $io->success('La table banque_image a été créée avec succès !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de la table : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

