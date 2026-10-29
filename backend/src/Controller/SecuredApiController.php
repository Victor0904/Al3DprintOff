<?php

namespace App\Controller;

use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur de base pour les routes API sécurisées.
 * Fournit des helpers d'authentification via token HMAC.
 */
abstract class SecuredApiController extends AbstractController
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    /**
     * Retourne les données du token si valide, null sinon.
     */
    protected function getTokenUser(Request $request): ?array
    {
        return $this->tokenService->extractFromRequest($request);
    }

    /**
     * Vérifie qu'un utilisateur est authentifié.
     * Retourne les données du token ou une JsonResponse 401.
     */
    protected function requireAuth(Request $request): JsonResponse|array
    {
        $user = $this->getTokenUser($request);
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }
        return $user;
    }

    /**
     * Vérifie que l'utilisateur est administrateur.
     * Retourne les données du token ou une JsonResponse 401/403.
     */
    protected function requireAdmin(Request $request): JsonResponse|array
    {
        $user = $this->getTokenUser($request);
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }
        if (($user['role'] ?? '') !== 'admin') {
            return new JsonResponse(['error' => 'Accès administrateur requis'], 403);
        }
        return $user;
    }

    /**
     * Vérifie que l'utilisateur est l'owner de la ressource ou admin.
     */
    protected function requireOwnerOrAdmin(Request $request, int $resourceOwnerId): JsonResponse|array
    {
        $user = $this->getTokenUser($request);
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }
        if ($user['id'] !== $resourceOwnerId && ($user['role'] ?? '') !== 'admin') {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $user;
    }
}
