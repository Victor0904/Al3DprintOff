<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-variantes-couleur-table',
    description: 'Crée la table variantes_couleur pour les variantes de couleur des produits'
)]
class CreateVariantesCouleurTableCommand extends Command
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
            $io->title('Création de la table variantes_couleur');

            // Vérifier si la table existe déjà
            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            if (in_array('variantes_couleur', $tables)) {
                $io->warning('La table variantes_couleur existe déjà.');
                if (!$io->confirm('Voulez-vous la supprimer et la recréer ?', false)) {
                    $io->info('Opération annulée.');
                    return Command::SUCCESS;
                }
                $this->connection->executeStatement('DROP TABLE IF EXISTS variantes_couleur');
            }

            // Détecter le type de base de données
            $platform = $this->connection->getDatabasePlatform();
            $isMySQL = strpos($platform->getName(), 'mysql') !== false;
            $isSQLite = strpos($platform->getName(), 'sqlite') !== false;

            // Créer la table selon le type de base de données
            if ($isMySQL) {
                $sql = "
                    CREATE TABLE variantes_couleur (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        produit_id INT NOT NULL,
                        nom_couleur VARCHAR(50) NOT NULL,
                        code_couleur VARCHAR(7) NULL,
                        image_url VARCHAR(255) NULL,
                        reference VARCHAR(100) NULL,
                        stock INT NOT NULL DEFAULT 0,
                        actif BOOLEAN NOT NULL DEFAULT 1,
                        ordre INT NOT NULL DEFAULT 0,
                        FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
            } else {
                // SQLite
                $sql = "
                    CREATE TABLE variantes_couleur (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        produit_id INTEGER NOT NULL,
                        nom_couleur VARCHAR(50) NOT NULL,
                        code_couleur VARCHAR(7) NULL,
                        image_url VARCHAR(255) NULL,
                        reference VARCHAR(100) NULL,
                        stock INTEGER NOT NULL DEFAULT 0,
                        actif BOOLEAN NOT NULL DEFAULT 1,
                        ordre INTEGER NOT NULL DEFAULT 0,
                        FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
                    );
                ";
            }

            $this->connection->executeStatement($sql);

            // Créer les index (syntaxe différente selon la base)
            if ($isMySQL) {
                // Vérifier si les index existent déjà
                $indexes = $schemaManager->listTableIndexes('variantes_couleur');
                $indexNames = array_keys($indexes);
                
                if (!in_array('idx_variantes_produit', $indexNames)) {
                    $this->connection->executeStatement('CREATE INDEX idx_variantes_produit ON variantes_couleur(produit_id)');
                }
                if (!in_array('idx_variantes_actif', $indexNames)) {
                    $this->connection->executeStatement('CREATE INDEX idx_variantes_actif ON variantes_couleur(actif)');
                }
            } else {
                // SQLite
                $this->connection->executeStatement('CREATE INDEX IF NOT EXISTS idx_variantes_produit ON variantes_couleur(produit_id)');
                $this->connection->executeStatement('CREATE INDEX IF NOT EXISTS idx_variantes_actif ON variantes_couleur(actif)');
            }

            $io->success('La table variantes_couleur a été créée avec succès !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de la table : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

