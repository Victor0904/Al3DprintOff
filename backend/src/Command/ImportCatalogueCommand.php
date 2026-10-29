<?php

namespace App\Command;

use App\Entity\Categories;
use App\Entity\ImagesProduitPersonnalise;
use App\Entity\ImagesVarianteCouleur;
use App\Entity\ProduitPersonnalise;
use App\Entity\Produits;
use App\Entity\VarianteCouleur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Crée ou met à jour le catalogue à partir des médias de public/catalogue (manifest.json).
 * Relançable sans doublon : les produits sont retrouvés par leur nom.
 */
#[AsCommand(name: 'app:import-catalogue', description: 'Importe le catalogue (produits, variantes, images, vidéos 360°)')]
class ImportCatalogueCommand extends Command
{
    private const CATEGORIE_SUPPORTS = 'Supports de téléphone';
    private const CATEGORIE_PERSO = 'Personnalisation 3D';

    private const MATIERE = "Imprimé en 3D en filament effet soie multicolore : les couleurs changent selon l'angle et la lumière, chaque pièce est donc unique.";

    // Variantes : clé du manifest => [nom affiché, couleur dominante]
    private const COULEURS = [
        'arc-en-ciel'      => ['Arc-en-ciel', '#e0559a'],
        'orange-bleu-vert' => ['Orange · Bleu · Vert', '#e8742a'],
        'rose-rouge-bleu'  => ['Rose · Rouge · Bleu', '#c2307a'],
        'mauve-bleu-vert'  => ['Mauve · Bleu · Vert', '#7b3fa0'],
        'mauve-bleu'       => ['Mauve · Bleu', '#6a3fb5'],
        'mauve-vert'       => ['Mauve · Vert', '#6b4a8a'],
        'twist-vert-bleu'  => ['Twist Vert · Bleu', '#2bb39a'],
        'bleu-vert-orange' => ['Bleu · Vert · Orange', '#2f8f8a'],
        'twist'            => ['Twist Vert · Bleu · Jaune', '#3fb0c9'],
    ];

    private const PRODUITS = [
        'cheval' => [
            'nom' => 'Support de téléphone Cheval',
            'prix' => '29.90',
            'description' => "Une tête de cheval à la crinière sculptée qui veille sur votre smartphone. Le support maintient le téléphone incliné, idéal pour regarder une vidéo, suivre une recette ou passer un appel en visio. Un objet déco qui a du caractère, sur un bureau comme sur une table de chevet.",
        ],
        'lapin' => [
            'nom' => 'Support de téléphone Lapin',
            'prix' => '24.90',
            'description' => "Un lapin tout mignon, assis sur son socle, qui tient votre smartphone bien calé. Parfait sur un bureau, une table de nuit ou dans une chambre d'enfant, il transforme un accessoire du quotidien en petite figurine pleine de douceur.",
        ],
        'ours' => [
            'nom' => 'Support de téléphone Ours',
            'prix' => '24.90',
            'description' => "Un ourson paisible, installé sur un socle rainuré qui accueille votre smartphone en position inclinée. Stable et pratique pour les vidéos et les appels, il apporte une touche tendre et colorée à votre intérieur.",
        ],
        'chien' => [
            'nom' => 'Chiot dans son panier',
            'prix' => '24.90',
            'description' => "Un adorable chiot assis dans son panier. Il trouve sa place sur une étagère, un bureau ou une table de chevet, et son panier peut servir de vide-poche pour les petits objets du quotidien.",
        ],
        'siege-baquet' => [
            'nom' => 'Support de téléphone Siège baquet',
            'prix' => '27.90',
            'description' => "Un siège baquet de course en miniature, monté sur son pied, pour poser votre smartphone comme dans un cockpit. Le cadeau idéal pour les passionnés d'automobile et de sport mécanique.",
        ],
    ];

    private const PERSONNALISES = [
        'perso-buste' => [
            'nom' => 'Buste personnalisé',
            'prix' => '79.00',
            'description' => "Votre portrait, ou celui d'un proche, transformé en buste sculpté en 3D à partir de vos photos. Envoyez-nous quelques photos de face et de profil : nous modélisons le visage, puis l'imprimons. Un cadeau unique pour un anniversaire, une fête ou un départ. Nous respectons le droit à l'image : vos photos ne servent qu'à votre commande.",
        ],
        'perso-halterophile' => [
            'nom' => 'Statuette personnalisée',
            'prix' => '119.00',
            'description' => "Une statuette en pied à votre effigie, dans la pose de votre choix : sportif, musicien, métier, passion… L'exemple présenté est un haltérophile soulevant sa barre. Envoyez-nous vos photos et décrivez la mise en scène souhaitée, nous vous proposons un rendu avant impression. Vos photos ne servent qu'à votre commande.",
        ],
        'perso-echecs' => [
            'nom' => "Jeu d'échecs personnalisé",
            'prix' => '349.00',
            'description' => "Un jeu d'échecs complet dont les pièces portent les visages de votre famille ou de vos amis : le roi, la reine, les fous, les cavaliers… chaque pièce peut devenir un proche. Livré avec son plateau, en noir et blanc. Envoyez-nous les photos des personnes à représenter et indiquez qui sera quelle pièce. Nous respectons le droit à l'image : vos photos ne servent qu'à votre commande.",
        ],
    ];

    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('desactiver-autres', null, InputOption::VALUE_NONE, 'Désactive les produits qui ne font pas partie du catalogue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestPath = $this->projectDir . '/public/catalogue/manifest.json';
        if (!is_file($manifestPath)) {
            $io->error('Manifest introuvable : ' . $manifestPath);
            return Command::FAILURE;
        }
        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $catSupports = $this->categorie(self::CATEGORIE_SUPPORTS, 'Supports de smartphone décoratifs imprimés en 3D, en plusieurs coloris.');
        $catPerso = $this->categorie(self::CATEGORIE_PERSO, 'Créations sur mesure à partir de vos photos : bustes, statuettes, jeux d\'échecs.');

        $noms = [];
        foreach (self::PRODUITS as $cle => $def) {
            if (empty($manifest[$cle])) {
                $io->warning("Aucun média pour « $cle », produit ignoré.");
                continue;
            }
            $this->importProduit($def, $manifest[$cle], $catSupports, $cle);
            $noms[] = $def['nom'];
            $io->writeln(sprintf('  %s : %d coloris', $def['nom'], count($manifest[$cle])));
        }

        foreach (self::PERSONNALISES as $cle => $def) {
            $medias = $manifest[$cle]['defaut'] ?? null;
            if (!$medias) {
                $io->warning("Aucun média pour « $cle », produit ignoré.");
                continue;
            }
            $this->importPersonnalise($def, $medias, $catPerso);
            $io->writeln(sprintf('  %s : %d images', $def['nom'], count($medias['images'])));
        }

        if ($input->getOption('desactiver-autres')) {
            $n = $this->em->createQuery('UPDATE App\Entity\Produits p SET p.actif = false WHERE p.nom NOT IN (:noms)')
                ->setParameter('noms', $noms)
                ->execute();
            $io->writeln("  $n autre(s) produit(s) désactivé(s)");
        }

        $this->em->flush();
        $io->success('Catalogue importé.');

        return Command::SUCCESS;
    }

    private function categorie(string $nom, string $description): Categories
    {
        $categorie = $this->em->getRepository(Categories::class)->findOneBy(['nom' => $nom]) ?? new Categories();
        $categorie->setNom($nom);
        $categorie->setDescription($description);
        $this->em->persist($categorie);

        return $categorie;
    }

    private function importProduit(array $def, array $variantes, Categories $categorie, string $cle): void
    {
        $produit = $this->em->getRepository(Produits::class)->findOneBy(['nom' => $def['nom']]) ?? new Produits();
        $produit->setNom($def['nom']);
        $produit->setDescription($def['description'] . "\n\n" . self::MATIERE);
        $produit->setPrix($def['prix']);
        $produit->setStock(100);
        $produit->setCategorie($categorie);
        $produit->setActif(true);
        if (!$produit->getId()) {
            $produit->setDateAjout(new \DateTime());
        }

        // On repart de zéro pour les variantes : le manifest fait foi
        foreach ($produit->getVariantesCouleur() as $ancienne) {
            $produit->getVariantesCouleur()->removeElement($ancienne);
            $this->em->remove($ancienne);
        }

        $ordre = 0;
        foreach ($variantes as $slug => $medias) {
            [$nomCouleur, $hex] = self::COULEURS[$slug] ?? [ucfirst(str_replace('-', ' ', $slug)), '#888888'];
            $variante = new VarianteCouleur();
            $variante->setProduit($produit);
            $variante->setNomCouleur($nomCouleur);
            $variante->setCodeCouleur($hex);
            $variante->setReference(strtoupper(substr($cle, 0, 3) . '-' . $slug));
            $variante->setStock(100);
            $variante->setActif(true);
            $variante->setOrdre($ordre++);
            $variante->setVideoUrl($this->url($medias['video'] ?? null));

            foreach ($medias['images'] as $i => $base) {
                $image = new ImagesVarianteCouleur();
                $image->setVariante($variante);
                $image->setUrl($this->url($base . '-800.webp'));
                $image->setOrdre($i + 1);
                $variante->addImagesVariante($image);
            }
            $variante->setImageUrl($this->url(($medias['images'][0] ?? null) ? $medias['images'][0] . '-800.webp' : ($medias['poster'] ?? null)));

            $produit->addVariantesCouleur($variante);
            $this->em->persist($variante);
        }

        $produit->setImagePrincipale($produit->getVariantesCouleur()->first()?->getImageUrl());
        $this->em->persist($produit);
    }

    private function importPersonnalise(array $def, array $medias, Categories $categorie): void
    {
        $produit = $this->em->getRepository(ProduitPersonnalise::class)->findOneBy(['nom' => $def['nom']]) ?? new ProduitPersonnalise();
        $produit->setNom($def['nom']);
        $produit->setDescription($def['description']);
        $produit->setPrix($def['prix']);
        $produit->setStock(100);
        $produit->setCategorie($categorie);
        $produit->setActif(true);
        $produit->setVideoUrl($this->url($medias['video'] ?? null));
        if (!$produit->getId()) {
            $produit->setDateAjout(new \DateTime());
        }

        foreach ($produit->getImagesPersonnalise() as $ancienne) {
            $produit->getImagesPersonnalise()->removeElement($ancienne);
            $this->em->remove($ancienne);
        }
        foreach ($medias['images'] as $i => $base) {
            $image = new ImagesProduitPersonnalise();
            $image->setProduitPersonnalise($produit);
            $image->setUrl($this->url($base . '-800.webp'));
            $image->setOrdre($i + 1);
            $produit->addImagePersonnalise($image);
        }
        $produit->setImagePrincipale($this->url($medias['images'][0] . '-800.webp'));
        $this->em->persist($produit);
    }

    private function url(?string $chemin): ?string
    {
        return $chemin ? '/' . ltrim($chemin, '/') : null;
    }
}
