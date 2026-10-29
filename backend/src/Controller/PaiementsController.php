<?php

namespace App\Controller;

use App\Entity\Paiements;
use App\Entity\Commandes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/paiements', name: 'api_paiements_')]
class PaiementsController extends AbstractController
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
        $statut = $request->query->get('statut');
        
        $repository = $this->entityManager->getRepository(Paiements::class);
        $queryBuilder = $repository->createQueryBuilder('p');

        if ($commandeId) {
            $queryBuilder->andWhere('p.commande = :commandeId')
                        ->setParameter('commandeId', $commandeId);
        }

        if ($statut) {
            $queryBuilder->andWhere('p.statut = :statut')
                        ->setParameter('statut', $statut);
        }

        $paiements = $queryBuilder->getQuery()->getResult();
        
        $data = [];
        foreach ($paiements as $paiement) {
            $data[] = [
                'id' => $paiement->getId(),
                'commande' => [
                    'id' => $paiement->getCommande()->getId(),
                    'client' => [
                        'id' => $paiement->getCommande()->getClient()->getId(),
                        'nom' => $paiement->getCommande()->getClient()->getNom()
                    ]
                ],
                'montant' => $paiement->getMontant(),
                'methode' => $paiement->getMethode(),
                'statut' => $paiement->getStatut(),
                'stripe_payment_id' => $paiement->getStripePaymentId(),
                'date_paiement' => $paiement->getDatePaiement()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $paiement = $this->entityManager->getRepository(Paiements::class)->find($id);
        
        if (!$paiement) {
            return new JsonResponse(['error' => 'Paiement non trouvé'], 404);
        }

        $data = [
            'id' => $paiement->getId(),
            'commande' => [
                'id' => $paiement->getCommande()->getId(),
                'client' => [
                    'id' => $paiement->getCommande()->getClient()->getId(),
                    'nom' => $paiement->getCommande()->getClient()->getNom(),
                    'email' => $paiement->getCommande()->getClient()->getEmail()
                ],
                'total' => $paiement->getCommande()->getTotal(),
                'statut' => $paiement->getCommande()->getStatut()
            ],
            'montant' => $paiement->getMontant(),
            'methode' => $paiement->getMethode(),
            'statut' => $paiement->getStatut(),
            'stripe_payment_id' => $paiement->getStripePaymentId(),
            'date_paiement' => $paiement->getDatePaiement()->format('Y-m-d H:i:s')
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

        if (!isset($data['montant']) || !is_numeric($data['montant'])) {
            return new JsonResponse(['error' => 'Le montant est requis et doit être numérique'], 400);
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($data['commande_id']);
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $paiement = new Paiements();
        $paiement->setCommande($commande);
        $paiement->setMontant($data['montant']);
        $paiement->setMethode($data['methode'] ?? null);
        $paiement->setStatut($data['statut'] ?? 'en attente');
        $paiement->setStripePaymentId($data['stripe_payment_id'] ?? null);

        $this->entityManager->persist($paiement);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $paiement->getId(),
            'commande_id' => $paiement->getCommande()->getId(),
            'montant' => $paiement->getMontant(),
            'methode' => $paiement->getMethode(),
            'statut' => $paiement->getStatut(),
            'stripe_payment_id' => $paiement->getStripePaymentId(),
            'date_paiement' => $paiement->getDatePaiement()->format('Y-m-d H:i:s')
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $paiement = $this->entityManager->getRepository(Paiements::class)->find($id);
        
        if (!$paiement) {
            return new JsonResponse(['error' => 'Paiement non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['montant'])) {
            $paiement->setMontant($data['montant']);
        }
        if (isset($data['methode'])) {
            $paiement->setMethode($data['methode']);
        }
        if (isset($data['statut'])) {
            $paiement->setStatut($data['statut']);
        }
        if (isset($data['stripe_payment_id'])) {
            $paiement->setStripePaymentId($data['stripe_payment_id']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $paiement->getId(),
            'commande_id' => $paiement->getCommande()->getId(),
            'montant' => $paiement->getMontant(),
            'methode' => $paiement->getMethode(),
            'statut' => $paiement->getStatut(),
            'stripe_payment_id' => $paiement->getStripePaymentId(),
            'date_paiement' => $paiement->getDatePaiement()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $paiement = $this->entityManager->getRepository(Paiements::class)->find($id);
        
        if (!$paiement) {
            return new JsonResponse(['error' => 'Paiement non trouvé'], 404);
        }

        $this->entityManager->remove($paiement);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Paiement supprimé avec succès']);
    }
}
