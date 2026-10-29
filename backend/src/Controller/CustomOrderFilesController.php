<?php

namespace App\Controller;

use App\Entity\DemandePersonnalisation;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomOrderFilesController extends AbstractController
{
    public function __construct(
        private EmailService           $emailService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/custom-order-files', name: 'custom_order_files', methods: ['POST'])]
    public function sendFiles(Request $request): JsonResponse
    {
        $rawFiles = $request->files->get('files');
        if (!$rawFiles) {
            return new JsonResponse(['error' => 'Aucun fichier reçu'], 400);
        }

        $files        = is_array($rawFiles) ? $rawFiles : [$rawFiles];
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg'];
        $maxBytes     = 10 * 1024 * 1024; // 10 Mo

        // ── Validation ──────────────────────────────────────────────────────────
        $fileInfos = [];
        foreach ($files as $file) {
            if (!($file instanceof UploadedFile)) {
                continue;
            }
            if ($file->getSize() > $maxBytes) {
                return new JsonResponse(['error' => 'Chaque fichier doit faire moins de 10 Mo'], 400);
            }
            $mime = $file->getMimeType();
            if (!in_array($mime, $allowedMimes, true)) {
                return new JsonResponse(['error' => 'Seuls les fichiers PNG et JPEG sont acceptés'], 400);
            }
            $fileInfos[] = [
                'file' => $file,
                'mime' => $mime,
                'name' => $file->getClientOriginalName(),
                'ext'  => $mime === 'image/png' ? 'png' : 'jpg',
            ];
        }

        if (empty($fileInfos)) {
            return new JsonResponse(['error' => 'Aucun fichier valide reçu'], 400);
        }

        // ── Sauvegarde sur le filesystem ─────────────────────────────────────────
        $requestId = uniqid('order_', true);
        $uploadDir = $this->getParameter('kernel.project_dir')
            . '/public/uploads/custom-orders/' . $requestId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $savedPaths = [];
        foreach ($fileInfos as $info) {
            $filename     = uniqid() . '.' . $info['ext'];
            $info['file']->move($uploadDir, $filename);
            $savedPaths[] = '/uploads/custom-orders/' . $requestId . '/' . $filename;
        }

        // ── Sauvegarde en base de données ────────────────────────────────────────
        $productName = $request->request->get('product_name', 'Produit personnalisé');
        $clientEmail = $request->request->get('client_email', 'non renseigné');
        $clientNom   = $request->request->get('client_nom', 'Client');
        $productId   = $request->request->get('product_id');

        $demande = new DemandePersonnalisation();
        $demande->setProductName($productName);
        $demande->setClientEmail($clientEmail);
        $demande->setClientNom($clientNom);
        $demande->setProductId($productId ? (int) $productId : null);
        $demande->setFichiers($savedPaths);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        // ── Notification email à l'admin (pas de pièce jointe) ──────────────────
        try {
            $this->emailService->sendCustomOrderNotification($demande->toArray());
        } catch (\Exception) {
            // L'email ne bloque pas la soumission
        }

        return new JsonResponse(['success' => true, 'demande_id' => $demande->getId()]);
    }
}
