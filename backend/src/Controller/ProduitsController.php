<?php

namespace App\Controller;

use App\Entity\Produits;
use App\Entity\Categories;
use App\Entity\VarianteCouleur;
use App\Entity\ImagesProduit;
use App\Entity\ImagesVarianteCouleur;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/produits', name: 'api_produits_')]
class ProduitsController extends SecuredApiController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        parent::__construct($tokenService);
        $this->entityManager = $entityManager;
    }

    // ─── LISTE (public) ────────────────────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $categorieId = $request->query->get('categorie_id');
            $actif       = $request->query->get('actif');

            $repository   = $this->entityManager->getRepository(Produits::class);
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
                    'id'               => $produit->getId(),
                    'nom'              => $produit->getNom(),
                    'description'      => $produit->getDescription(),
                    'prix'             => $produit->getPrix(),
                    'categorie'        => $produit->getCategorie() ? [
                        'id'  => $produit->getCategorie()->getId(),
                        'nom' => $produit->getCategorie()->getNom(),
                    ] : null,
                    'image_principale' => $produit->getImagePrincipale(),
                    'actif'            => $produit->isActif(),
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des produits: ' . $e->getMessage()], 500);
        }
    }

    // ─── DÉTAIL (public) ───────────────────────────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $produit = $this->entityManager->getRepository(Produits::class)->find($id);

            if (!$produit) {
                return new JsonResponse(['error' => 'Produit non trouvé'], 404);
            }

            $data = [
                'id'                       => $produit->getId(),
                'nom'                      => $produit->getNom(),
                'description'              => $produit->getDescription(),
                'prix'                     => $produit->getPrix(),
                'categorie'                => $produit->getCategorie() ? [
                    'id'  => $produit->getCategorie()->getId(),
                    'nom' => $produit->getCategorie()->getNom(),
                ] : null,
                'image_principale'         => $produit->getImagePrincipale(),
                'actif'                    => $produit->isActif(),
                'options_personnalisation'  => [],
                'images'                   => [],
                'avis'                     => [],
                'variantes_couleur'        => [],
            ];

            foreach ($produit->getOptionsPersonnalisation() as $option) {
                $data['options_personnalisation'][] = [
                    'id'               => $option->getId(),
                    'nom'              => $option->getNom(),
                    'type'             => $option->getType(),
                    'valeurs_possibles' => $option->getValeursPossibles(),
                ];
            }

            foreach ($produit->getImagesProduit() as $image) {
                $data['images'][] = [
                    'id'    => $image->getId(),
                    'url'   => $image->getUrl(),
                    'ordre' => $image->getOrdre(),
                ];
            }

            $variantes = $produit->getVariantesCouleur()->toArray();
            usort($variantes, fn ($a, $b) => $a->getOrdre() <=> $b->getOrdre());
            foreach ($variantes as $variante) {
                if ($variante->isActif()) {
                    $varianteImages = [];
                    foreach ($variante->getImagesVariante() as $img) {
                        $varianteImages[] = [
                            'id'    => $img->getId(),
                            'url'   => $img->getUrl(),
                            'ordre' => $img->getOrdre(),
                        ];
                    }
                    $data['variantes_couleur'][] = [
                        'id'           => $variante->getId(),
                        'nom_couleur'  => $variante->getNomCouleur(),
                        'code_couleur' => $variante->getCodeCouleur(),
                        'image_url'    => $variante->getImageUrl(),
                        'reference'    => $variante->getReference(),
                        'stock'        => $variante->getStock(),
                        'actif'        => $variante->isActif(),
                        'ordre'        => $variante->getOrdre(),
                        'images'       => $varianteImages,
                    ];
                }
            }

            foreach ($produit->getAvisClients() as $avis) {
                $data['avis'][] = [
                    'id'          => $avis->getId(),
                    'note'        => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'date'        => $avis->getDate() ? $avis->getDate()->format('Y-m-d H:i:s') : null,
                    'client'      => [
                        'id'  => $avis->getClient()->getId(),
                        'nom' => $avis->getClient()->getNom(),
                    ],
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération du produit: ' . $e->getMessage()], 500);
        }
    }

    // ─── CRÉER (admin uniquement) ──────────────────────────────────────────────

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['nom']) || empty($data['nom'])) {
                return new JsonResponse(['error' => 'Le nom est requis'], 400);
            }
            if (!isset($data['prix']) || !is_numeric($data['prix'])) {
                return new JsonResponse(['error' => 'Le prix est requis et doit être numérique'], 400);
            }

            $produit = new Produits();
            $produit->setNom($data['nom']);
            $produit->setDescription($data['description'] ?? null);
            $produit->setPrix($data['prix']);
            $produit->setStock($data['stock'] ?? 0);
            $produit->setImagePrincipale($data['image_principale'] ?? null);
            $produit->setActif($data['actif'] ?? true);

            if (isset($data['categorie_id'])) {
                $categorie = $this->entityManager->getRepository(Categories::class)->find($data['categorie_id']);
                if ($categorie) {
                    $produit->setCategorie($categorie);
                }
            }

            if (isset($data['variantes_couleur']) && is_array($data['variantes_couleur'])) {
                foreach ($data['variantes_couleur'] as $varianteData) {
                    $variante = new VarianteCouleur();
                    $variante->setProduit($produit);
                    $variante->setNomCouleur($varianteData['nom_couleur'] ?? '');
                    $variante->setCodeCouleur($varianteData['code_couleur'] ?? null);
                    $variante->setImageUrl($varianteData['image_url'] ?? null);
                    $variante->setReference($varianteData['reference'] ?? null);
                    $variante->setStock($varianteData['stock'] ?? 0);
                    $variante->setActif($varianteData['actif'] ?? true);
                    $variante->setOrdre($varianteData['ordre'] ?? 0);
                    $this->entityManager->persist($variante);
                    if (isset($varianteData['images']) && is_array($varianteData['images'])) {
                        foreach ($varianteData['images'] as $ordre => $imgData) {
                            $img = new ImagesVarianteCouleur();
                            $img->setVariante($variante);
                            $img->setUrl($imgData['url'] ?? '');
                            $img->setOrdre($imgData['ordre'] ?? ($ordre + 1));
                            $this->entityManager->persist($img);
                        }
                    }
                }
            }

            $this->entityManager->persist($produit);

            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $ordre => $imgData) {
                    $img = new ImagesProduit();
                    $img->setProduit($produit);
                    $img->setUrl($imgData['url'] ?? '');
                    $img->setOrdre($imgData['ordre'] ?? ($ordre + 1));
                    $this->entityManager->persist($img);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'id'               => $produit->getId(),
                'nom'              => $produit->getNom(),
                'description'      => $produit->getDescription(),
                'prix'             => $produit->getPrix(),
                'categorie'        => $produit->getCategorie() ? [
                    'id'  => $produit->getCategorie()->getId(),
                    'nom' => $produit->getCategorie()->getNom(),
                ] : null,
                'image_principale' => $produit->getImagePrincipale(),
                'actif'            => $produit->isActif(),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du produit: ' . $e->getMessage()], 500);
        }
    }

    // ─── MODIFIER (admin uniquement) ───────────────────────────────────────────

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        try {
            $produit = $this->entityManager->getRepository(Produits::class)->find($id);

            if (!$produit) {
                return new JsonResponse(['error' => 'Produit non trouvé'], 404);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['error' => 'Données JSON invalides'], 400);
            }

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
            if (isset($data['actif'])) {
                $produit->setActif($data['actif']);
            }
            if (isset($data['categorie_id'])) {
                $categorie = $this->entityManager->getRepository(Categories::class)->find($data['categorie_id']);
                $produit->setCategorie($categorie);
            }

            // ── Images produit ───────────────────────────────────────────────
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($produit->getImagesProduit()->toArray() as $img) {
                    $this->entityManager->remove($img);
                }
                $this->entityManager->flush();

                foreach ($data['images'] as $ordre => $imgData) {
                    if (empty($imgData['url'])) continue;
                    $img = new ImagesProduit();
                    $img->setProduit($produit);
                    $img->setUrl($imgData['url']);
                    $img->setOrdre($imgData['ordre'] ?? ($ordre + 1));
                    $this->entityManager->persist($img);
                }
            }

            // ── Variantes couleur ────────────────────────────────────────────
            if (isset($data['variantes_couleur']) && is_array($data['variantes_couleur'])) {
                // Supprimer les images des variantes existantes puis les variantes
                foreach ($produit->getVariantesCouleur()->toArray() as $variante) {
                    foreach ($variante->getImagesVariante()->toArray() as $img) {
                        $this->entityManager->remove($img);
                    }
                    $this->entityManager->remove($variante);
                }
                // Flush intermédiaire : les suppressions doivent être commitées
                // avant l'insertion de nouvelles variantes pour éviter les conflits
                // sur les contraintes UNIQUE (ex: reference)
                $this->entityManager->flush();

                foreach ($data['variantes_couleur'] as $varianteData) {
                    $variante = new VarianteCouleur();
                    $variante->setProduit($produit);
                    $variante->setNomCouleur($varianteData['nom_couleur'] ?? '');
                    $variante->setCodeCouleur(
                        isset($varianteData['code_couleur'])
                            ? substr($varianteData['code_couleur'], 0, 7)
                            : null
                    );
                    $variante->setImageUrl($varianteData['image_url'] ?? null);
                    $variante->setReference($varianteData['reference'] ?? null);
                    $variante->setStock($varianteData['stock'] ?? 0);
                    $variante->setActif($varianteData['actif'] ?? true);
                    $variante->setOrdre($varianteData['ordre'] ?? 0);
                    $this->entityManager->persist($variante);

                    if (isset($varianteData['images']) && is_array($varianteData['images'])) {
                        foreach ($varianteData['images'] as $ordre => $imgData) {
                            if (empty($imgData['url'])) continue;
                            $img = new ImagesVarianteCouleur();
                            $img->setVariante($variante);
                            $img->setUrl($imgData['url']);
                            $img->setOrdre($imgData['ordre'] ?? ($ordre + 1));
                            $this->entityManager->persist($img);
                        }
                    }
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'id'               => $produit->getId(),
                'nom'              => $produit->getNom(),
                'description'      => $produit->getDescription(),
                'prix'             => $produit->getPrix(),
                'categorie'        => $produit->getCategorie() ? [
                    'id'  => $produit->getCategorie()->getId(),
                    'nom' => $produit->getCategorie()->getNom(),
                ] : null,
                'image_principale' => $produit->getImagePrincipale(),
                'actif'            => $produit->isActif(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la mise à jour du produit: ' . $e->getMessage()], 500);
        }
    }

    // ─── SUPPRIMER (admin uniquement) ──────────────────────────────────────────

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($id);

        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $this->entityManager->remove($produit);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit supprimé avec succès']);
    }
}
