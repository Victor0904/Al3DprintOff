<?php

namespace App\Controller;

use App\Entity\Impressions;
use App\Entity\Commandes;
use App\Entity\Produits;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/impressions', name: 'api_impressions_')]
class ImpressionsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $commandeId = $request->query->get('commande_id');
        $produitId = $request->query->get('produit_id');
        $statut = $request->query->get('statut');
        
        $repository = $this->entityManager->getRepository(Impressions::class);
        $queryBuilder = $repository->createQueryBuilder('i');

        if ($commandeId) {
            $queryBuilder->andWhere('i.commande = :commandeId')
                        ->setParameter('commandeId', $commandeId);
        }

        if ($produitId) {
            $queryBuilder->andWhere('i.produit = :produitId')
                        ->setParameter('produitId', $produitId);
        }

        if ($statut) {
            $queryBuilder->andWhere('i.statut = :statut')
                        ->setParameter('statut', $statut);
        }

        $impressions = $queryBuilder->getQuery()->getResult();
        
        $data = [];
        foreach ($impressions as $impression) {
            $data[] = [
                'id' => $impression->getId(),
                'commande' => [
                    'id' => $impression->getCommande()->getId(),
                    'client' => [
                        'id' => $impression->getCommande()->getClient()->getId(),
                        'nom' => $impression->getCommande()->getClient()->getNom()
                    ]
                ],
                'produit' => [
                    'id' => $impression->getProduit()->getId(),
                    'nom' => $impression->getProduit()->getNom()
                ],
                'statut' => $impression->getStatut(),
                'materiau' => $impression->getMateriau(),
                'temps_estime' => $impression->getTempsEstime(),
                'date_debut' => $impression->getDateDebut()?->format('Y-m-d H:i:s'),
                'date_fin' => $impression->getDateFin()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $impression = $this->entityManager->getRepository(Impressions::class)->find($id);
        
        if (!$impression) {
            return new JsonResponse(['error' => 'Impression non trouvée'], 404);
        }

        $data = [
            'id' => $impression->getId(),
            'commande' => [
                'id' => $impression->getCommande()->getId(),
                'client' => [
                    'id' => $impression->getCommande()->getClient()->getId(),
                    'nom' => $impression->getCommande()->getClient()->getNom(),
                    'email' => $impression->getCommande()->getClient()->getEmail()
                ],
                'total' => $impression->getCommande()->getTotal(),
                'statut' => $impression->getCommande()->getStatut()
            ],
            'produit' => [
                'id' => $impression->getProduit()->getId(),
                'nom' => $impression->getProduit()->getNom(),
                'prix' => $impression->getProduit()->getPrix()
            ],
            'statut' => $impression->getStatut(),
            'materiau' => $impression->getMateriau(),
            'temps_estime' => $impression->getTempsEstime(),
            'date_debut' => $impression->getDateDebut()?->format('Y-m-d H:i:s'),
            'date_fin' => $impression->getDateFin()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['commande_id']) || !is_numeric($data['commande_id'])) {
            return new JsonResponse(['error' => 'L\'ID de la commande est requis'], 400);
        }

        if (!isset($data['produit_id']) || !is_numeric($data['produit_id'])) {
            return new JsonResponse(['error' => 'L\'ID du produit est requis'], 400);
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($data['commande_id']);
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $impression = new Impressions();
        $impression->setCommande($commande);
        $impression->setProduit($produit);
        $impression->setStatut($data['statut'] ?? 'à faire');
        $impression->setMateriau($data['materiau'] ?? null);
        $impression->setTempsEstime($data['temps_estime'] ?? null);

        if (isset($data['date_debut'])) {
            $impression->setDateDebut(new \DateTime($data['date_debut']));
        }
        if (isset($data['date_fin'])) {
            $impression->setDateFin(new \DateTime($data['date_fin']));
        }

        $this->entityManager->persist($impression);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $impression->getId(),
            'commande_id' => $impression->getCommande()->getId(),
            'produit_id' => $impression->getProduit()->getId(),
            'statut' => $impression->getStatut(),
            'materiau' => $impression->getMateriau(),
            'temps_estime' => $impression->getTempsEstime(),
            'date_debut' => $impression->getDateDebut()?->format('Y-m-d H:i:s'),
            'date_fin' => $impression->getDateFin()?->format('Y-m-d H:i:s')
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $impression = $this->entityManager->getRepository(Impressions::class)->find($id);
        
        if (!$impression) {
            return new JsonResponse(['error' => 'Impression non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['statut'])) {
            $impression->setStatut($data['statut']);
        }
        if (isset($data['materiau'])) {
            $impression->setMateriau($data['materiau']);
        }
        if (isset($data['temps_estime'])) {
            $impression->setTempsEstime($data['temps_estime']);
        }
        if (isset($data['date_debut'])) {
            $impression->setDateDebut($data['date_debut'] ? new \DateTime($data['date_debut']) : null);
        }
        if (isset($data['date_fin'])) {
            $impression->setDateFin($data['date_fin'] ? new \DateTime($data['date_fin']) : null);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $impression->getId(),
            'commande_id' => $impression->getCommande()->getId(),
            'produit_id' => $impression->getProduit()->getId(),
            'statut' => $impression->getStatut(),
            'materiau' => $impression->getMateriau(),
            'temps_estime' => $impression->getTempsEstime(),
            'date_debut' => $impression->getDateDebut()?->format('Y-m-d H:i:s'),
            'date_fin' => $impression->getDateFin()?->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $impression = $this->entityManager->getRepository(Impressions::class)->find($id);
        
        if (!$impression) {
            return new JsonResponse(['error' => 'Impression non trouvée'], 404);
        }

        $this->entityManager->remove($impression);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Impression supprimée avec succès']);
    }
}
