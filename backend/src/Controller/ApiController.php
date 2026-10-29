<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api', name: 'api_home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API Al3DPrint - Système d\'impression 3D',
            'version' => '1.0.0',
            'endpoints' => [
                'categories' => '/api/categories',
                'produits' => '/api/produits',
                'clients' => '/api/clients',
                'commandes' => '/api/commandes',
                'options-personnalisation' => '/api/options-personnalisation',
                'paiements' => '/api/paiements',
                'avis-clients' => '/api/avis-clients',
                'impressions' => '/api/impressions',
                'images-produit' => '/api/images-produit'
            ],
            'test_interface' => '/test-api.html',
            'documentation' => 'Chaque endpoint supporte les méthodes GET, POST, PUT, DELETE'
        ]);
    }
}
