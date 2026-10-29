<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-statut-enum',
    description: 'Corrige la colonne statut de la table commandes (ENUM → VARCHAR)',
)]
class FixStatutEnumCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔧 Correction de la colonne statut de la table commandes');

        try {
            // Étape 1: Vérifier la structure actuelle
            $io->section('Vérification de la structure actuelle...');
            
            $sql = "SHOW COLUMNS FROM commandes WHERE Field = 'statut'";
            $result = $this->connection->fetchAssociative($sql);
            
            if (!$result) {
                $io->error('La colonne statut n\'existe pas dans la table commandes !');
                return Command::FAILURE;
            }

            $currentType = $result['Type'];
            $io->info("Type actuel de la colonne statut : {$currentType}");

            // Vérifier si c'est un ENUM
            if (strpos(strtolower($currentType), 'enum') === false) {
                $io->success('✅ La colonne statut n\'est pas un ENUM. Aucune modification nécessaire.');
                return Command::SUCCESS;
            }

            $io->warning('⚠️  La colonne statut est un ENUM. Conversion en VARCHAR nécessaire...');

            // Étape 2: Mettre à jour les valeurs existantes
            $io->section('Mise à jour des valeurs existantes...');
            
            $updates = [
                "UPDATE commandes SET statut = 'En attente' WHERE statut IN ('en attente', 'En attente')" => 'En attente',
                "UPDATE commandes SET statut = 'En cours' WHERE statut IN ('en fabrication', 'En fabrication')" => 'En cours',
                "UPDATE commandes SET statut = 'Expédié' WHERE statut IN ('expédiée', 'Expédiée', 'expédié', 'Expédié')" => 'Expédié',
                "UPDATE commandes SET statut = 'Terminé' WHERE statut IN ('livrée', 'Livrée', 'terminé', 'Terminé')" => 'Terminé',
            ];

            foreach ($updates as $sql => $newValue) {
                try {
                    $this->connection->executeStatement($sql);
                    $io->text("  ✓ Valeurs mises à jour vers '{$newValue}'");
                } catch (\Exception $e) {
                    $io->text("  ⚠ Erreur pour '{$newValue}': " . $e->getMessage());
                }
            }

            // Étape 3: Modifier la structure de la colonne
            $io->section('Modification de la structure (ENUM → VARCHAR)...');
            
            try {
                $this->connection->executeStatement(
                    "ALTER TABLE commandes MODIFY COLUMN statut VARCHAR(20) DEFAULT 'En attente'"
                );
                $io->success('✅ Colonne statut modifiée avec succès !');
            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de la modification : ' . $e->getMessage());
                return Command::FAILURE;
            }

            // Étape 4: Vérification finale
            $io->section('Vérification finale...');
            $result = $this->connection->fetchAssociative($sql);
            $newType = $result['Type'];
            $io->info("Nouveau type de la colonne statut : {$newType}");

            if (strpos(strtolower($newType), 'varchar') !== false) {
                $io->success([
                    '✅ Migration terminée avec succès !',
                    '',
                    'Vous pouvez maintenant utiliser les statuts suivants :',
                    '  • En attente',
                    '  • En cours',
                    '  • Expédié',
                    '  • Terminé',
                ]);
                return Command::SUCCESS;
            } else {
                $io->error('❌ La colonne n\'a pas été correctement modifiée.');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

