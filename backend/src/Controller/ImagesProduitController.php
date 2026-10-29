<?php

namespace App\Controller;

use App\Entity\ImagesProduit;
use App\Entity\Produits;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/images-produit', name: 'api_images_produit_')]
class ImagesProduitController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $produitId = $request->query->get('produit_id');
        
        $repository = $this->entityManager->getRepository(ImagesProduit::class);
        $queryBuilder = $repository->createQueryBuilder('i');

        if ($produitId) {
            $queryBuilder->andWhere('i.produit = :produitId')
                        ->setParameter('produitId', $produitId);
        }

        $images = $queryBuilder->getQuery()->getResult();
        
        $data = [];
        foreach ($images as $image) {
            $data[] = [
                'id' => $image->getId(),
                'produit' => [
                    'id' => $image->getProduit()->getId(),
                    'nom' => $image->getProduit()->getNom()
                ],
                'url' => $image->getUrl(),
                'ordre' => $image->getOrdre()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $image = $this->entityManager->getRepository(ImagesProduit::class)->find($id);
        
        if (!$image) {
            return new JsonResponse(['error' => 'Image non trouvée'], 404);
        }

        $data = [
            'id' => $image->getId(),
            'produit' => [
                'id' => $image->getProduit()->getId(),
                'nom' => $image->getProduit()->getNom(),
                'prix' => $image->getProduit()->getPrix()
            ],
            'url' => $image->getUrl(),
            'ordre' => $image->getOrdre()
        ];

        return new JsonResponse($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['produit_id']) || !is_numeric($data['produit_id'])) {
            return new JsonResponse(['error' => 'L\'ID du produit est requis'], 400);
        }

        if (!isset($data['url']) || empty($data['url'])) {
            return new JsonResponse(['error' => 'L\'URL est requise'], 400);
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $image = new ImagesProduit();
        $image->setProduit($produit);
        $image->setUrl($data['url']);
        $image->setOrdre($data['ordre'] ?? 1);

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $image->getId(),
            'produit_id' => $image->getProduit()->getId(),
            'url' => $image->getUrl(),
            'ordre' => $image->getOrdre()
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $image = $this->entityManager->getRepository(ImagesProduit::class)->find($id);
        
        if (!$image) {
            return new JsonResponse(['error' => 'Image non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['url'])) {
            $image->setUrl($data['url']);
        }
        if (isset($data['ordre'])) {
            $image->setOrdre($data['ordre']);
        }
        if (isset($data['produit_id'])) {
            $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
            if ($produit) {
                $image->setProduit($produit);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $image->getId(),
            'produit_id' => $image->getProduit()->getId(),
            'url' => $image->getUrl(),
            'ordre' => $image->getOrdre()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $image = $this->entityManager->getRepository(ImagesProduit::class)->find($id);
        
        if (!$image) {
            return new JsonResponse(['error' => 'Image non trouvée'], 404);
        }

        $this->entityManager->remove($image);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Image supprimée avec succès']);
    }
}
