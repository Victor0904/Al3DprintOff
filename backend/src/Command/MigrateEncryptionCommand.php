<?php

namespace App\Command;

use App\Entity\Clients;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-encryption',
    description: 'Migre les données de chiffrement existantes vers le nouveau format',
)]
class MigrateEncryptionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EncryptionService $encryptionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Migration des données de chiffrement');

        $clients = $this->entityManager->getRepository(Clients::class)->findAll();
        $migrated = 0;
        $errors = 0;

        foreach ($clients as $client) {
            try {
                $needsUpdate = false;

                // Vérifier et migrer les champs d'adresse
                $fields = ['adresse', 'codePostal', 'ville', 'telephone', 'adresse2', 'codePostal2', 'ville2', 'adresse3', 'codePostal3', 'ville3'];
                
                foreach ($fields as $field) {
                    $getter = 'get' . ucfirst($field);
                    $setter = 'set' . ucfirst($field);
                    
                    if (method_exists($client, $getter) && method_exists($client, $setter)) {
                        $value = $client->$getter();
                        
                        if (!empty($value)) {
                            // Essayer de déchiffrer avec l'ancien format
                            try {
                                $this->encryptionService->decrypt($value);
                                // Si ça marche, c'est déjà au bon format
                            } catch (\Exception $e) {
                                // Si ça échoue, c'est probablement en texte clair ou mal formaté
                                // Rechiffrer avec le nouveau format
                                $client->$setter($this->encryptionService->encrypt($value));
                                $needsUpdate = true;
                            }
                        }
                    }
                }

                if ($needsUpdate) {
                    $this->entityManager->flush();
                    $migrated++;
                    $io->writeln("✓ Client {$client->getId()} migré");
                }

            } catch (\Exception $e) {
                $errors++;
                $io->error("Erreur pour le client {$client->getId()}: " . $e->getMessage());
            }
        }

        $io->success("Migration terminée: {$migrated} clients migrés, {$errors} erreurs");

        return Command::SUCCESS;
    }
}
