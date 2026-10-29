<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/uploads', name: 'image_')]
class ImageController extends AbstractController
{
    private string $uploadDirectory;

    public function __construct(ParameterBagInterface $params)
    {
        $projectDir = $params->get('kernel.project_dir');
        $this->uploadDirectory = $projectDir . '/public/uploads/';
    }

    #[Route('/{filename}', name: 'serve', methods: ['GET'])]
    public function serveImage(string $filename): Response
    {
        $filePath = $this->uploadDirectory . basename($filename);
        
        // Vérifier que le fichier existe
        if (!file_exists($filePath) || !is_file($filePath)) {
            return new Response('Image non trouvée', 404);
        }

        // Vérifier que le fichier est bien dans le dossier uploads (sécurité)
        $realPath = realpath($filePath);
        $realUploadDir = realpath($this->uploadDirectory);
        
        if (!$realPath || strpos($realPath, $realUploadDir) !== 0) {
            return new Response('Accès non autorisé', 403);
        }

        // Déterminer le type MIME
        $mimeType = mime_content_type($filePath);
        if (!$mimeType) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        }

        // Créer la réponse avec le fichier
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_INLINE . '; filename="' . basename($filename) . '"');
        
        // Headers pour le cache
        $response->setPublic();
        $response->setMaxAge(3600); // Cache pendant 1 heure
        
        return $response;
    }
}

