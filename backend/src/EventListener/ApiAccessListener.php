<?php

namespace App\EventListener;

use App\Service\TokenService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Contrôle d'accès centralisé pour les routes admin des contrôleurs
 * qui n'héritent pas de SecuredApiController.
 */
#[AsEventListener(event: 'kernel.request', priority: 7)]
class ApiAccessListener
{
    // Admin uniquement, quelle que soit la méthode HTTP
    private const ADMIN_ALL = [
        '/api/paiements',
        '/api/impressions',
        '/api/demandes-personnalisation',
    ];

    // Lecture publique, écriture admin uniquement
    private const ADMIN_WRITE = [
        '/api/avis-clients',
        '/api/images-produit',
        '/api/options-personnalisation',
        '/api/produits-personnalises',
        '/api/banque-image',
    ];

    public function __construct(private TokenService $tokenService)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || $request->isMethod('OPTIONS')) {
            return;
        }

        $path = $request->getPathInfo();
        $matches = fn (array $prefixes) => (bool) array_filter(
            $prefixes,
            fn ($p) => $path === $p || str_starts_with($path, $p . '/')
        );

        $needsAdmin = $matches(self::ADMIN_ALL)
            || (!$request->isMethodSafe() && $matches(self::ADMIN_WRITE));
        if (!$needsAdmin) {
            return;
        }

        $user = $this->tokenService->extractFromRequest($request);
        if (!$user) {
            $event->setResponse(new JsonResponse(['error' => 'Authentification requise'], 401));
        } elseif (($user['role'] ?? '') !== 'admin') {
            $event->setResponse(new JsonResponse(['error' => 'Accès administrateur requis'], 403));
        }
    }
}
