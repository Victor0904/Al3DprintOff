<?php

namespace App\Command;

use App\Entity\Clients;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur dans la base de données',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, "Email de l'administrateur");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Mot de passe saisi de façon masquée, jamais stocké dans le code
        $password = $io->askHidden('Mot de passe (12 caractères minimum)', function (?string $value) {
            if (strlen((string) $value) < 12) {
                throw new \RuntimeException('Mot de passe trop court');
            }
            return $value;
        });

        // Vérifier si l'admin existe déjà
        $existingAdmin = $this->entityManager->getRepository(Clients::class)
            ->findOneBy(['email' => $email]);

        if ($existingAdmin) {
            $io->warning('L\'administrateur existe déjà dans la base de données.');
            return Command::SUCCESS;
        }

        // Créer l'administrateur
        $admin = new Clients();
        $admin->setNom('Administrateur');
        $admin->setEmail($email);
        $admin->setMotDePasse(password_hash($password, PASSWORD_DEFAULT));
        $admin->setDateInscription(new \DateTime());
        // Définir le rôle admin en base
        $admin->setRole('admin');

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success("Administrateur $email créé.");

        return Command::SUCCESS;
    }
}
