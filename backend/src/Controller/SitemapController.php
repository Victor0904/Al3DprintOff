<?php

namespace App\Controller;

use App\Entity\ProduitPersonnalise;
use App\Entity\Produits;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * sitemap.xml et robots.txt générés pour le domaine courant,
 * avec une URL par produit actif.
 */
class SitemapController extends AbstractController
{
    private const PAGES = ['/', '/products', '/custom-products', '/about', '/mentions-legales', '/politique-confidentialite', '/cgv'];

    // Priorité supérieure à la route attrape-tout de la SPA (HomeController)
    #[Route('/sitemap.xml', name: 'sitemap', priority: 10)]
    public function sitemap(Request $request, EntityManagerInterface $em): Response
    {
        $base = $request->getSchemeAndHttpHost();
        $urls = array_map(fn ($p) => $base . $p, self::PAGES);

        foreach ($em->getRepository(Produits::class)->findBy(['actif' => true]) as $produit) {
            $urls[] = $base . '/product/' . $produit->getId();
        }
        foreach ($em->getRepository(ProduitPersonnalise::class)->findBy(['actif' => true]) as $produit) {
            $urls[] = $base . '/product/' . $produit->getId() . '?type=custom';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    #[Route('/robots.txt', name: 'robots', priority: 10)]
    public function robots(Request $request): Response
    {
        $txt = "User-agent: *\nAllow: /\n\n# Pages privées\n";
        foreach (['/admin', '/profile', '/checkout', '/cart', '/order/', '/login', '/register', '/forgot-password', '/reset-password', '/api/'] as $path) {
            $txt .= "Disallow: $path\n";
        }
        $txt .= "\nSitemap: " . $request->getSchemeAndHttpHost() . "/sitemap.xml\n";

        return new Response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
