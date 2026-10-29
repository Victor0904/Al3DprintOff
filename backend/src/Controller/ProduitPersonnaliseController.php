<?php

namespace App\Controller;

use App\Entity\ProduitPersonnalise;
use App\Entity\ImagesProduitPersonnalise;
use App\Entity\Categories;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/produits-personnalises', name: 'api_produit_personnalise_')]
class ProduitPersonnaliseController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $categorieId = $request->query->get('categorie_id');
            $actif = $request->query->get('actif');
            
            $repository = $this->entityManager->getRepository(ProduitPersonnalise::class);
            $queryBuilder = $repository->createQueryBuilder('p');

            if ($categorieId) {
                $queryBuilder->andWhere('p.categorie = :categorieId')
                            ->setParameter('categorieId', $categorieId);
            }

            if ($actif !== null) {
                $queryBuilder->andWhere('p.actif = :actif')
                            ->setParameter('actif', $actif === 'true');
            }

            $produits = $queryBuilder->getQuery()->getResult();
            
            $data = [];
            foreach ($produits as $produit) {
                $data[] = [
                    'id' => $produit->getId(),
                    'nom' => $produit->getNom(),
                    'description' => $produit->getDescription(),
                    'prix' => $produit->getPrix(),
                    'stock' => $produit->getStock(),
                    'categorie' => $produit->getCategorie() ? [
                        'id' => $produit->getCategorie()->getId(),
                        'nom' => $produit->getCategorie()->getNom()
                    ] : null,
                    'image_principale' => $produit->getImagePrincipale(),
                    'video_url' => $produit->getVideoUrl(),
                    'images' => array_map(
                        fn($img) => ['id' => $img->getId(), 'url' => $img->getUrl(), 'ordre' => $img->getOrdre()],
                        $produit->getImagesPersonnalise()->toArray()
                    ),
                    'date_ajout' => $produit->getDateAjout() ? $produit->getDateAjout()->format('Y-m-d H:i:s') : null,
                    'actif' => $produit->isActif()
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des produits personnalisés: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $produit = $this->entityManager->getRepository(ProduitPersonnalise::class)->find($id);
        
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit personnalisé non trouvé'], 404);
        }

        $data = [
            'id' => $produit->getId(),
            'nom' => $produit->getNom(),
            'description' => $produit->getDescription(),
            'prix' => $produit->getPrix(),
            'stock' => $produit->getStock(),
            'categorie' => $produit->getCategorie() ? [
                'id' => $produit->getCategorie()->getId(),
                'nom' => $produit->getCategorie()->getNom()
            ] : null,
            'image_principale' => $produit->getImagePrincipale(),
            'video_url' => $produit->getVideoUrl(),
            'images' => array_map(
                fn($img) => ['id' => $img->getId(), 'url' => $img->getUrl(), 'ordre' => $img->getOrdre()],
                $produit->getImagesPersonnalise()->toArray()
            ),
            'date_ajout' => $produit->getDateAjout() ? $produit->getDateAjout()->format('Y-m-d H:i:s') : null,
            'actif' => $produit->isActif()
        ];

        return new JsonResponse($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['nom']) || empty($data['nom'])) {
                return new JsonResponse(['error' => 'Le nom est requis'], 400);
            }

            if (!isset($data['prix']) || !is_numeric($data['prix'])) {
                return new JsonResponse(['error' => 'Le prix est requis et doit être numérique'], 400);
            }

            $produit = new ProduitPersonnalise();
            $produit->setNom($data['nom']);
            $produit->setDescription($data['description'] ?? null);
            $produit->setPrix($data['prix']);
            $produit->setStock($data['stock'] ?? 0);
            $produit->setImagePrincipale($data['image_principale'] ?? null);
            $produit->setVideoUrl(($data['video_url'] ?? null) ?: null);
            $produit->setActif($data['actif'] ?? true);

            // Associer la catégorie si fournie
            if (isset($data['categorie_id'])) {
                $categorie = $this->entityManager->getRepository(Categories::class)->find($data['categorie_id']);
                if ($categorie) {
                    $produit->setCategorie($categorie);
                }
            }

            // Galerie d'images
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $idx => $imageData) {
                    if (empty($imageData['url'])) continue;
                    $image = new ImagesProduitPersonnalise();
                    $image->setUrl($imageData['url']);
                    $image->setOrdre($imageData['ordre'] ?? ($idx + 1));
                    $image->setProduitPersonnalise($produit);
                    $this->entityManager->persist($image);
                    $produit->addImagePersonnalise($image);
                }
            }

            $this->entityManager->persist($produit);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $produit->getId(),
                'nom' => $produit->getNom(),
                'description' => $produit->getDescription(),
                'prix' => $produit->getPrix(),
                'stock' => $produit->getStock(),
                'categorie_id' => $produit->getCategorie()?->getId(),
                'image_principale' => $produit->getImagePrincipale(),
                'video_url' => $produit->getVideoUrl(),
                'images' => array_map(
                    fn($img) => ['id' => $img->getId(), 'url' => $img->getUrl(), 'ordre' => $img->getOrdre()],
                    $produit->getImagesPersonnalise()->toArray()
                ),
                'actif' => $produit->isActif()
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du produit personnalisé: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $produit = $this->entityManager->getRepository(ProduitPersonnalise::class)->find($id);
        
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit personnalisé non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $produit->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $produit->setDescription($data['description']);
        }
        if (isset($data['prix'])) {
            $produit->setPrix($data['prix']);
        }
        if (isset($data['stock'])) {
            $produit->setStock($data['stock']);
        }
        if (isset($data['image_principale'])) {
            $produit->setImagePrincipale($data['image_principale']);
        }
        if (array_key_exists('video_url', $data)) {
            $produit->setVideoUrl($data['video_url'] ?: null);
        }
        if (isset($data['actif'])) {
            $produit->setActif($data['actif']);
        }
        if (isset($data['categorie_id'])) {
            $categorie = $this->entityManager->getRepository(Categories::class)->find($data['categorie_id']);
            $produit->setCategorie($categorie);
        }

        // Galerie d'images : remplacement complet
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($produit->getImagesPersonnalise() as $existingImage) {
                $this->entityManager->remove($existingImage);
            }
            $produit->getImagesPersonnalise()->clear();

            foreach ($data['images'] as $idx => $imageData) {
                if (empty($imageData['url'])) continue;
                $image = new ImagesProduitPersonnalise();
                $image->setUrl($imageData['url']);
                $image->setOrdre($imageData['ordre'] ?? ($idx + 1));
                $image->setProduitPersonnalise($produit);
                $this->entityManager->persist($image);
                $produit->addImagePersonnalise($image);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $produit->getId(),
            'nom' => $produit->getNom(),
            'description' => $produit->getDescription(),
            'prix' => $produit->getPrix(),
            'stock' => $produit->getStock(),
            'categorie_id' => $produit->getCategorie()?->getId(),
            'image_principale' => $produit->getImagePrincipale(),
            'video_url' => $produit->getVideoUrl(),
            'images' => array_map(
                fn($img) => ['id' => $img->getId(), 'url' => $img->getUrl(), 'ordre' => $img->getOrdre()],
                $produit->getImagesPersonnalise()->toArray()
            ),
            'actif' => $produit->isActif()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $produit = $this->entityManager->getRepository(ProduitPersonnalise::class)->find($id);
        
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit personnalisé non trouvé'], 404);
        }

        $this->entityManager->remove($produit);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit personnalisé supprimé avec succès']);
    }
}

