<?php

namespace App\Command;

use App\Entity\Categories;
use App\Entity\Produits;
use App\Entity\Clients;
use App\Entity\Commandes;
use App\Entity\DetailsCommande;
use App\Entity\OptionsPersonnalisation;
use App\Entity\ImagesProduit;
use App\Entity\AvisClients;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-database',
    description: 'Initialise la base de données avec des données de test',
)]
class InitDatabaseCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer des catégories
        $categories = [
            ['nom' => 'Figurines', 'description' => 'Figurines et personnages 3D'],
            ['nom' => 'Décoration', 'description' => 'Objets de décoration pour la maison'],
            ['nom' => 'Outils', 'description' => 'Outils et accessoires imprimés'],
            ['nom' => 'Jouets', 'description' => 'Jouets et jeux pour enfants']
        ];

        $categoryEntities = [];
        foreach ($categories as $catData) {
            $category = new Categories();
            $category->setNom($catData['nom']);
            $category->setDescription($catData['description']);
            $this->entityManager->persist($category);
            $categoryEntities[] = $category;
        }

        // Créer des produits
        $produits = [
            [
                'nom' => 'Dragon Fantasy',
                'description' => 'Magnifique dragon en résine pour décoration',
                'prix' => '89.99',
                'stock' => 15,
                'categorie' => 0,
                'image_principale' => 'dragon.jpg',
                'fichier_3d' => 'dragon.stl'
            ],
            [
                'nom' => 'Vase Moderne',
                'description' => 'Vase design pour fleurs séchées',
                'prix' => '45.50',
                'stock' => 8,
                'categorie' => 1,
                'image_principale' => 'vase.jpg',
                'fichier_3d' => 'vase.stl'
            ],
            [
                'nom' => 'Porte-clés Personnalisé',
                'description' => 'Porte-clés avec votre nom gravé',
                'prix' => '12.99',
                'stock' => 50,
                'categorie' => 2,
                'image_principale' => 'porte_cles.jpg',
                'fichier_3d' => 'porte_cles.stl'
            ],
            [
                'nom' => 'Puzzle 3D',
                'description' => 'Puzzle éducatif pour enfants',
                'prix' => '25.00',
                'stock' => 20,
                'categorie' => 3,
                'image_principale' => 'puzzle.jpg',
                'fichier_3d' => 'puzzle.stl'
            ]
        ];

        $produitEntities = [];
        foreach ($produits as $prodData) {
            $produit = new Produits();
            $produit->setNom($prodData['nom']);
            $produit->setDescription($prodData['description']);
            $produit->setPrix($prodData['prix']);
            $produit->setStock($prodData['stock']);
            $produit->setCategorie($categoryEntities[$prodData['categorie']]);
            $produit->setImagePrincipale($prodData['image_principale']);
            $produit->setFichier3d($prodData['fichier_3d']);
            $this->entityManager->persist($produit);
            $produitEntities[] = $produit;
        }

        // Créer des options de personnalisation
        $options = [
            [
                'produit' => 0, // Dragon
                'nom' => 'Couleur',
                'type' => 'choix',
                'valeurs' => ['Rouge', 'Bleu', 'Vert', 'Noir']
            ],
            [
                'produit' => 0, // Dragon
                'nom' => 'Taille',
                'type' => 'choix',
                'valeurs' => ['Petit (10cm)', 'Moyen (20cm)', 'Grand (30cm)']
            ],
            [
                'produit' => 2, // Porte-clés
                'nom' => 'Texte personnalisé',
                'type' => 'texte',
                'valeurs' => null
            ]
        ];

        foreach ($options as $optData) {
            $option = new OptionsPersonnalisation();
            $option->setProduit($produitEntities[$optData['produit']]);
            $option->setNom($optData['nom']);
            $option->setType($optData['type']);
            $option->setValeursPossibles($optData['valeurs']);
            $this->entityManager->persist($option);
        }

        // Créer des clients
        $clients = [
            [
                'nom' => 'Marie Dubois',
                'email' => 'marie.dubois@email.com',
                'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT),
                'adresse' => '15 Rue de la Paix, 75001 Paris',
                'telephone' => '0123456789'
            ],
            [
                'nom' => 'Pierre Martin',
                'email' => 'pierre.martin@email.com',
                'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT),
                'adresse' => '42 Avenue des Champs, 69000 Lyon',
                'telephone' => '0987654321'
            ]
        ];

        $clientEntities = [];
        foreach ($clients as $clientData) {
            $client = new Clients();
            $client->setNom($clientData['nom']);
            $client->setEmail($clientData['email']);
            $client->setMotDePasse($clientData['mot_de_passe']);
            $client->setAdresse($clientData['adresse']);
            $client->setTelephone($clientData['telephone']);
            $this->entityManager->persist($client);
            $clientEntities[] = $client;
        }

        // Créer des commandes
        $commande = new Commandes();
        $commande->setClient($clientEntities[0]);
        $commande->setStatut('En attente');
        $commande->setTotal('102.98');
        $commande->setAdresseLivraison('15 Rue de la Paix, 75001 Paris');
        $commande->setModePaiement('carte');
        $this->entityManager->persist($commande);

        // Créer des détails de commande
        $detail1 = new DetailsCommande();
        $detail1->setCommande($commande);
        $detail1->setProduit($produitEntities[0]); // Dragon
        $detail1->setQuantite(1);
        $detail1->setPrixUnitaire('89.99');
        $detail1->setPersonnalisation(['couleur' => 'Rouge', 'taille' => 'Moyen (20cm)']);
        $this->entityManager->persist($detail1);

        $detail2 = new DetailsCommande();
        $detail2->setCommande($commande);
        $detail2->setProduit($produitEntities[2]); // Porte-clés
        $detail2->setQuantite(1);
        $detail2->setPrixUnitaire('12.99');
        $detail2->setPersonnalisation(['texte' => 'Marie']);
        $this->entityManager->persist($detail2);

        // Créer des avis clients
        $avis = new AvisClients();
        $avis->setProduit($produitEntities[0]);
        $avis->setClient($clientEntities[0]);
        $avis->setNote(5);
        $avis->setCommentaire('Très belle qualité, je recommande !');
        $this->entityManager->persist($avis);

        $this->entityManager->flush();

        $io->success('Base de données initialisée avec succès !');
        $io->note('Données créées :');
        $io->listing([
            count($categories) . ' catégories',
            count($produits) . ' produits',
            count($options) . ' options de personnalisation',
            count($clients) . ' clients',
            '1 commande avec 2 détails',
            '1 avis client'
        ]);

        return Command::SUCCESS;
    }
}
