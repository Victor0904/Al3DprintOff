<?php

namespace App\Controller;

use App\Entity\DemandePersonnalisation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DemandesPersonnalisationController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/api/demandes-personnalisation', name: 'demandes_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $demandes = $this->entityManager
            ->getRepository(DemandePersonnalisation::class)
            ->findBy([], ['dateCreation' => 'DESC']);

        return new JsonResponse(array_map(fn($d) => $d->toArray(), $demandes));
    }

    #[Route('/api/demandes-personnalisation/{id}', name: 'demandes_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $demande = $this->entityManager->find(DemandePersonnalisation::class, $id);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['statut'])) {
            $demande->setStatut($data['statut']);
        }
        if (array_key_exists('notes', $data)) {
            $demande->setNotes($data['notes'] ?: null);
        }

        $this->entityManager->flush();

        return new JsonResponse($demande->toArray());
    }

    #[Route('/api/demandes-personnalisation/{id}', name: 'demandes_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $demande = $this->entityManager->find(DemandePersonnalisation::class, $id);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande introuvable'], 404);
        }

        // Suppression des fichiers du filesystem
        $projectDir = $this->getParameter('kernel.project_dir');
        $dirToRemove = null;

        foreach ($demande->getFichiers() as $path) {
            $fullPath = $projectDir . '/public' . $path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            if ($dirToRemove === null) {
                $dirToRemove = dirname($fullPath);
            }
        }

        // Suppression du dossier si vide
        if ($dirToRemove && is_dir($dirToRemove)) {
            $remaining = array_diff(scandir($dirToRemove), ['.', '..']);
            if (empty($remaining)) {
                rmdir($dirToRemove);
            }
        }

        $this->entityManager->remove($demande);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
