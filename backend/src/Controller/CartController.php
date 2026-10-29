<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Entity\PanierItem;
use App\Entity\Produits;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cart', name: 'api_cart_')]
class CartController extends SecuredApiController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        parent::__construct($tokenService);
        $this->entityManager = $entityManager;
    }

    #[Route('/{clientId}', name: 'get', methods: ['GET'])]
    public function getCart(int $clientId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $panierItems = $this->entityManager->getRepository(PanierItem::class)
            ->findBy(['client' => $client]);

        $items = [];
        foreach ($panierItems as $item) {
            $produit = $item->getProduit();
            $items[] = [
                'id' => $item->getId(),
                'produit_id' => $produit->getId(),
                'name' => $produit->getNom(),
                'price' => (float) $produit->getPrix(),
                'image' => $produit->getImagePrincipale(),
                'quantity' => $item->getQuantite(),
                'category' => $produit->getCategorie() ? $produit->getCategorie()->getNom() : 'Sans catégorie',
                'personnalisation' => $item->getPersonnalisation()
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/{clientId}/add', name: 'add', methods: ['POST'])]
    public function addToCart(int $clientId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['produit_id']) || !isset($data['quantite'])) {
            return new JsonResponse(['error' => 'produit_id et quantite sont requis'], 400);
        }

        $produit = $this->entityManager->getRepository(Produits::class)->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        // Trouver l'article existant en tenant compte de la variante
        $requestVarianteId = isset($data['personnalisation']['variante_id'])
            ? (int) $data['personnalisation']['variante_id']
            : null;

        $candidats = $this->entityManager->getRepository(PanierItem::class)
            ->findBy(['client' => $client, 'produit' => $produit]);

        $existingItem = null;
        foreach ($candidats as $candidat) {
            $pers = $candidat->getPersonnalisation();
            $itemVarianteId = is_array($pers) ? ($pers['variante_id'] ?? null) : null;
            if ($itemVarianteId === $requestVarianteId) {
                $existingItem = $candidat;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantite($existingItem->getQuantite() + (int) $data['quantite']);
        } else {
            // Créer un nouvel article
            $panierItem = new PanierItem();
            $panierItem->setClient($client);
            $panierItem->setProduit($produit);
            $panierItem->setQuantite((int) $data['quantite']);
            if (isset($data['personnalisation'])) {
                $panierItem->setPersonnalisation($data['personnalisation']);
            }
            $this->entityManager->persist($panierItem);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Article ajouté au panier', 'success' => true]);
    }

    #[Route('/{clientId}/update', name: 'update', methods: ['PUT'])]
    public function updateCartItem(int $clientId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['item_id']) || !isset($data['quantite'])) {
            return new JsonResponse(['error' => 'item_id et quantite sont requis'], 400);
        }

        $panierItem = $this->entityManager->getRepository(PanierItem::class)->find($data['item_id']);
        
        if (!$panierItem || $panierItem->getClient()->getId() !== $clientId) {
            return new JsonResponse(['error' => 'Article du panier non trouvé'], 404);
        }

        $quantite = (int) $data['quantite'];
        if ($quantite <= 0) {
            // Supprimer l'article si la quantité est 0 ou négative
            $this->entityManager->remove($panierItem);
        } else {
            $panierItem->setQuantite($quantite);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Panier mis à jour', 'success' => true]);
    }

    #[Route('/{clientId}/item/{itemId}', name: 'remove', methods: ['DELETE'])]
    public function removeFromCart(int $clientId, int $itemId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $panierItem = $this->entityManager->getRepository(PanierItem::class)->find($itemId);
        
        if (!$panierItem || $panierItem->getClient()->getId() !== $clientId) {
            return new JsonResponse(['error' => 'Article du panier non trouvé'], 404);
        }

        $this->entityManager->remove($panierItem);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Article supprimé du panier', 'success' => true]);
    }

    #[Route('/{clientId}/clear', name: 'clear', methods: ['POST'])]
    public function clearCart(int $clientId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $panierItems = $this->entityManager->getRepository(PanierItem::class)
            ->findBy(['client' => $client]);

        foreach ($panierItems as $item) {
            $this->entityManager->remove($item);
        }

        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Panier vidé avec succès', 'success' => true]);
    }

    #[Route('/{clientId}/sync', name: 'sync', methods: ['POST'])]
    public function syncCart(int $clientId, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $clientId);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($clientId);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            return new JsonResponse(['error' => 'Données du panier invalides'], 400);
        }

        // Supprimer tous les articles existants
        $existingItems = $this->entityManager->getRepository(PanierItem::class)
            ->findBy(['client' => $client]);
        foreach ($existingItems as $item) {
            $this->entityManager->remove($item);
        }

        // Ajouter les nouveaux articles
        foreach ($data['items'] as $itemData) {
            if (!isset($itemData['id']) || !isset($itemData['quantity'])) {
                continue;
            }

            $produit = $this->entityManager->getRepository(Produits::class)->find($itemData['id']);
            if (!$produit) {
                continue;
            }

            $panierItem = new PanierItem();
            $panierItem->setClient($client);
            $panierItem->setProduit($produit);
            $panierItem->setQuantite((int) $itemData['quantity']);
            if (isset($itemData['personnalisation'])) {
                $panierItem->setPersonnalisation($itemData['personnalisation']);
            }
            $this->entityManager->persist($panierItem);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Panier synchronisé', 'success' => true]);
    }
}
