<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/{reactRouting}', name: 'app_home', requirements: ['reactRouting' => '^(?!api).+'], defaults: ['reactRouting' => null])]
    public function index()
    {
        // On utilise DISPOSITION_INLINE pour dire au navigateur d'afficher le fichier
        $response = $this->file($this->getParameter('kernel.project_dir') . '/public/index.html', null, ResponseHeaderBag::DISPOSITION_INLINE);
        // Toujours revalider : sinon un ancien index.html en cache pointe vers des assets supprimés (page blanche)
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');

        return $response;
    }
} 