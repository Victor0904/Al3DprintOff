<?php

namespace App\Controller;

use App\Entity\AvisClients;
use App\Entity\Produits;
use App\Entity\Clients;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/avis-clients', name: 'api_avis_clients_')]
class AvisClientsController extends AbstractController
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
        $clientId = $request->query->get('client_id');
        
        $repository = $this->entityManager->getRepository(AvisClients::class);
        $queryBuilder = $repository->createQueryBuilder('a');

        if ($produitId) {
            $queryBuilder->andWhere('a.produit = :produitId')
                        ->setParameter('produitId', $produitId);
        }

        if ($clientId) {
            $queryBuilder->andWhere('a.client = :clientId')
                        ->setParameter('clientId', $clientId);
        }

        $avis = $queryBuilder->getQuery()->getResult();
        
        $data = [];
        foreach ($avis as $avisClient) {
            $data[] = [
                'id' => $avisClient->getId(),
                'produit' => [
                    'id' => $avisClient->getProduit()->getId(),
                    'nom' => $avisClient->getProduit()->getNom()
                ],
                'client' => [
                    'id' => $avisClient->getClient()->getId(),
                    'nom' => $avisClient->getClient()->getNom()
                ],
                'note' => $avisClient->getNote(),
                'commentaire' => $avisClient->getCommentaire(),
                'date' => $avisClient->getDate()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $avis = $this->entityManager->getRepository(AvisClients::class)->find($id);
        
        if (!$avis) {
            return new JsonResponse(['error' => 'Avis non trouvé'], 404);
        }

        $data = [
            'id' => $avis->getId(),
            'produit' => [
                'id' => $avis->getProduit()->getId(),
                'nom' => $avis->getProduit()->getNom(),
                'prix' => $avis->getProduit()->getPrix()
            ],
            'client' => [
                'id' => $avis->getClient()->getId(),
                'nom' => $avis->getClient()->getNom(),
                'email' => $avis->getClient()->getEmail()
            ],
            'note' => $avis->getNote(),
            'commentaire' => $avis->getCommentaire(),
            'date' => $avis->getDate()->format('Y-m-d H:i:s')
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

        if (!isset($data['client_id']) || !is_numeric($data['client_id'])) {
            return new JsonResponse(['error' => 'L\'ID du client est requis'], 400);
        }

        if (!isset($data['note']) || !is_numeric($data['note']) || $data['note'] < 1 || $data['note'] > 5) {
            return new JsonResponse(['error' => 'La note est requise et doit être entre 1 et 5'], 400);
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($data['client_id']);
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $avis = new AvisClients();
        $avis->setProduit($produit);
        $avis->setClient($client);
        $avis->setNote($data['note']);
        $avis->setCommentaire($data['commentaire'] ?? null);

        $this->entityManager->persist($avis);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $avis->getId(),
            'produit_id' => $avis->getProduit()->getId(),
            'client_id' => $avis->getClient()->getId(),
            'note' => $avis->getNote(),
            'commentaire' => $avis->getCommentaire(),
            'date' => $avis->getDate()->format('Y-m-d H:i:s')
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $avis = $this->entityManager->getRepository(AvisClients::class)->find($id);
        
        if (!$avis) {
            return new JsonResponse(['error' => 'Avis non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['note'])) {
            if ($data['note'] < 1 || $data['note'] > 5) {
                return new JsonResponse(['error' => 'La note doit être entre 1 et 5'], 400);
            }
            $avis->setNote($data['note']);
        }
        if (isset($data['commentaire'])) {
            $avis->setCommentaire($data['commentaire']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $avis->getId(),
            'produit_id' => $avis->getProduit()->getId(),
            'client_id' => $avis->getClient()->getId(),
            'note' => $avis->getNote(),
            'commentaire' => $avis->getCommentaire(),
            'date' => $avis->getDate()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->entityManager->getRepository(AvisClients::class)->find($id);
        
        if (!$avis) {
            return new JsonResponse(['error' => 'Avis non trouvé'], 404);
        }

        $this->entityManager->remove($avis);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Avis supprimé avec succès']);
    }
}
