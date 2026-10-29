<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-produit-personnalise-table',
    description: 'Crée la table produit_personnalise dans la base de données',
)]
class CreateProduitPersonnaliseTableCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔧 Création de la table produit_personnalise');

        try {
            // Vérifier si la table existe déjà
            $sql = "SHOW TABLES LIKE 'produit_personnalise'";
            $result = $this->connection->fetchOne($sql);
            
            if ($result) {
                $io->warning('La table produit_personnalise existe déjà.');
                if (!$io->confirm('Voulez-vous la supprimer et la recréer ?', false)) {
                    return Command::SUCCESS;
                }
                $this->connection->executeStatement('DROP TABLE IF EXISTS produit_personnalise');
                $io->text('✓ Ancienne table supprimée');
            }

            // Créer la table
            $io->section('Création de la table...');
            
            $createTableSql = "
                CREATE TABLE produit_personnalise (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nom VARCHAR(150) NOT NULL,
                    description TEXT,
                    prix DECIMAL(10,2) NOT NULL,
                    stock INT DEFAULT 0,
                    categorie_id INT,
                    image_principale VARCHAR(255),
                    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
                    actif BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";

            $this->connection->executeStatement($createTableSql);
            $io->success('✅ Table produit_personnalise créée avec succès !');

            // Créer les index
            $io->section('Création des index...');
            
            try {
                $this->connection->executeStatement('CREATE INDEX idx_produit_personnalise_categorie ON produit_personnalise(categorie_id)');
                $io->text('  ✓ Index sur categorie_id créé');
            } catch (\Exception $e) {
                $io->text('  ⚠ Index categorie_id déjà existant ou erreur');
            }

            try {
                $this->connection->executeStatement('CREATE INDEX idx_produit_personnalise_actif ON produit_personnalise(actif)');
                $io->text('  ✓ Index sur actif créé');
            } catch (\Exception $e) {
                $io->text('  ⚠ Index actif déjà existant ou erreur');
            }

            try {
                $this->connection->executeStatement('CREATE INDEX idx_produit_personnalise_date ON produit_personnalise(date_ajout)');
                $io->text('  ✓ Index sur date_ajout créé');
            } catch (\Exception $e) {
                $io->text('  ⚠ Index date_ajout déjà existant ou erreur');
            }

            // Vérification
            $io->section('Vérification...');
            $columns = $this->connection->fetchAllAssociative('DESCRIBE produit_personnalise');
            $io->table(
                ['Colonne', 'Type', 'Null', 'Clé', 'Défaut'],
                array_map(fn($col) => [
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key'],
                    $col['Default'] ?? 'NULL'
                ], $columns)
            );

            $io->success([
                '✅ Migration terminée avec succès !',
                '',
                'La table produit_personnalise est maintenant disponible.',
                'Vous pouvez utiliser l\'API : /api/produits-personnalises'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

