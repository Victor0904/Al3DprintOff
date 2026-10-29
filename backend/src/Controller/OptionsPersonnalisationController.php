<?php

namespace App\Controller;

use App\Entity\OptionsPersonnalisation;
use App\Entity\Produits;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/options-personnalisation', name: 'api_options_personnalisation_')]
class OptionsPersonnalisationController extends AbstractController
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
        
        $repository = $this->entityManager->getRepository(OptionsPersonnalisation::class);
        $queryBuilder = $repository->createQueryBuilder('o');

        if ($produitId) {
            $queryBuilder->andWhere('o.produit = :produitId')
                        ->setParameter('produitId', $produitId);
        }

        $options = $queryBuilder->getQuery()->getResult();
        
        $data = [];
        foreach ($options as $option) {
            $data[] = [
                'id' => $option->getId(),
                'produit' => [
                    'id' => $option->getProduit()->getId(),
                    'nom' => $option->getProduit()->getNom()
                ],
                'nom' => $option->getNom(),
                'type' => $option->getType(),
                'valeurs_possibles' => $option->getValeursPossibles()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $option = $this->entityManager->getRepository(OptionsPersonnalisation::class)->find($id);
        
        if (!$option) {
            return new JsonResponse(['error' => 'Option de personnalisation non trouvée'], 404);
        }

        $data = [
            'id' => $option->getId(),
            'produit' => [
                'id' => $option->getProduit()->getId(),
                'nom' => $option->getProduit()->getNom()
            ],
            'nom' => $option->getNom(),
            'type' => $option->getType(),
            'valeurs_possibles' => $option->getValeursPossibles()
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

        if (!isset($data['nom']) || empty($data['nom'])) {
            return new JsonResponse(['error' => 'Le nom est requis'], 400);
        }

        if (!isset($data['type']) || empty($data['type'])) {
            return new JsonResponse(['error' => 'Le type est requis'], 400);
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $option = new OptionsPersonnalisation();
        $option->setProduit($produit);
        $option->setNom($data['nom']);
        $option->setType($data['type']);
        $option->setValeursPossibles($data['valeurs_possibles'] ?? null);

        $this->entityManager->persist($option);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $option->getId(),
            'produit_id' => $option->getProduit()->getId(),
            'nom' => $option->getNom(),
            'type' => $option->getType(),
            'valeurs_possibles' => $option->getValeursPossibles()
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $option = $this->entityManager->getRepository(OptionsPersonnalisation::class)->find($id);
        
        if (!$option) {
            return new JsonResponse(['error' => 'Option de personnalisation non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $option->setNom($data['nom']);
        }
        if (isset($data['type'])) {
            $option->setType($data['type']);
        }
        if (isset($data['valeurs_possibles'])) {
            $option->setValeursPossibles($data['valeurs_possibles']);
        }
        if (isset($data['produit_id'])) {
            $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
            if ($produit) {
                $option->setProduit($produit);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $option->getId(),
            'produit_id' => $option->getProduit()->getId(),
            'nom' => $option->getNom(),
            'type' => $option->getType(),
            'valeurs_possibles' => $option->getValeursPossibles()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $option = $this->entityManager->getRepository(OptionsPersonnalisation::class)->find($id);
        
        if (!$option) {
            return new JsonResponse(['error' => 'Option de personnalisation non trouvée'], 404);
        }

        $this->entityManager->remove($option);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Option de personnalisation supprimée avec succès']);
    }
}
