<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/categories', name: 'api_categories_')]
class CategoriesController extends SecuredApiController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        parent::__construct($tokenService);
        $this->entityManager = $entityManager;
    }

    // ─── LISTE (public) ────────────────────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $categories = $this->entityManager->getRepository(Categories::class)->findAll();

            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'id'          => $category->getId(),
                    'nom'         => $category->getNom(),
                    'description' => $category->getDescription(),
                    'parent_id'   => $category->getParent()?->getId(),
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des catégories: ' . $e->getMessage()], 500);
        }
    }

    // ─── DÉTAIL (public) ───────────────────────────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $category = $this->entityManager->getRepository(Categories::class)->find($id);

        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], 404);
        }

        $enfants = [];
        foreach ($category->getEnfants() as $enfant) {
            $enfants[] = [
                'id'  => $enfant->getId(),
                'nom' => $enfant->getNom(),
            ];
        }

        $data = [
            'id'          => $category->getId(),
            'nom'         => $category->getNom(),
            'description' => $category->getDescription(),
            'parent_id'   => $category->getParent()?->getId(),
            'enfants'     => $enfants,
            'produits'    => [],
        ];

        foreach ($category->getProduits() as $produit) {
            $data['produits'][] = [
                'id'    => $produit->getId(),
                'nom'   => $produit->getNom(),
                'prix'  => $produit->getPrix(),
                'actif' => $produit->isActif(),
            ];
        }

        return new JsonResponse($data);
    }

    // ─── CRÉER (admin uniquement) ──────────────────────────────────────────────

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || empty($data['nom'])) {
            return new JsonResponse(['error' => 'Le nom est requis'], 400);
        }

        $category = new Categories();
        $category->setNom($data['nom']);
        $category->setDescription($data['description'] ?? null);

        if (!empty($data['parent_id'])) {
            $parent = $this->entityManager->getRepository(Categories::class)->find($data['parent_id']);
            if ($parent) {
                $category->setParent($parent);
            }
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return new JsonResponse([
            'id'          => $category->getId(),
            'nom'         => $category->getNom(),
            'description' => $category->getDescription(),
            'parent_id'   => $category->getParent()?->getId(),
        ], 201);
    }

    // ─── MODIFIER (admin uniquement) ───────────────────────────────────────────

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $category = $this->entityManager->getRepository(Categories::class)->find($id);

        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $category->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }
        if (array_key_exists('parent_id', $data)) {
            if ($data['parent_id']) {
                $parent = $this->entityManager->getRepository(Categories::class)->find($data['parent_id']);
                $category->setParent($parent ?: null);
            } else {
                $category->setParent(null);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id'          => $category->getId(),
            'nom'         => $category->getNom(),
            'description' => $category->getDescription(),
            'parent_id'   => $category->getParent()?->getId(),
        ]);
    }

    // ─── SUPPRIMER (admin uniquement) ──────────────────────────────────────────

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $category = $this->entityManager->getRepository(Categories::class)->find($id);

        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], 404);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Catégorie supprimée avec succès']);
    }
}
