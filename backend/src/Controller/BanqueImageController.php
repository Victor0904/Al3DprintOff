<?php

namespace App\Controller;

use App\Entity\BanqueImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/banque-image', name: 'api_banque_image_')]
class BanqueImageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private string $uploadDirectory;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        // Créer le dossier uploads s'il n'existe pas
        $projectDir = $params->get('kernel.project_dir');
        $this->uploadDirectory = $projectDir . '/public/uploads/';
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0777, true);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $type = $request->query->get('type');
            $categorie = $request->query->get('categorie');
            
            $repository = $this->entityManager->getRepository(BanqueImage::class);
            $queryBuilder = $repository->createQueryBuilder('b')
                ->orderBy('b.dateAjout', 'DESC');

            if ($type) {
                $queryBuilder->andWhere('b.type = :type')
                    ->setParameter('type', $type);
            }

            if ($categorie) {
                $queryBuilder->andWhere('b.categorie = :categorie')
                    ->setParameter('categorie', $categorie);
            }

            $images = $queryBuilder->getQuery()->getResult();
            
            $data = [];
            foreach ($images as $image) {
                $data[] = [
                    'id' => $image->getId(),
                    'nom' => $image->getNom(),
                    'chemin' => $image->getChemin(),
                    'type' => $image->getType(),
                    'categorie' => $image->getCategorie(),
                    'taille' => $image->getTaille(),
                    'format' => $image->getFormat(),
                    'date_ajout' => $image->getDateAjout() ? $image->getDateAjout()->format('Y-m-d H:i:s') : null,
                    'description' => $image->getDescription()
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des images: ' . $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            // Vérifier si c'est un upload de fichier ou une création manuelle
            $file = $request->files->get('file');
            
            if ($file) {
                // Upload de fichier
                return $this->uploadFile($file, $request);
            } else {
                // Création manuelle avec URL
                $data = json_decode($request->getContent(), true);
                
                if (!isset($data['nom']) || empty($data['nom'])) {
                    return new JsonResponse(['error' => 'Le nom est requis'], 400);
                }
                
                if (!isset($data['chemin']) || empty($data['chemin'])) {
                    return new JsonResponse(['error' => 'Le chemin/URL est requis'], 400);
                }

                $image = new BanqueImage();
                $image->setNom($data['nom']);
                $image->setChemin($data['chemin']);
                $image->setType($data['type'] ?? null);
                $image->setCategorie($data['categorie'] ?? null);
                $image->setDescription($data['description'] ?? null);
                $image->setFormat($data['format'] ?? null);
                $image->setTaille($data['taille'] ?? null);

                $this->entityManager->persist($image);
                $this->entityManager->flush();

                return new JsonResponse([
                    'id' => $image->getId(),
                    'nom' => $image->getNom(),
                    'chemin' => $image->getChemin(),
                    'type' => $image->getType(),
                    'categorie' => $image->getCategorie(),
                    'date_ajout' => $image->getDateAjout()->format('Y-m-d H:i:s')
                ], 201);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
        }
    }

    private function uploadFile(UploadedFile $file, Request $request): JsonResponse
    {
        try {
            // Obtenir la taille du fichier AVANT toute autre opération
            $fileSize = null;
            $filePath = $file->getPathname();
            
            if ($filePath && file_exists($filePath)) {
                $fileSize = filesize($filePath);
            } else {
                // Essayer avec getSize() si le fichier est encore accessible
                try {
                    $fileSize = $file->getSize();
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Impossible de lire la taille du fichier'], 400);
                }
            }
            
            // Valider la taille (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize && $fileSize > $maxSize) {
                return new JsonResponse(['error' => 'Le fichier est trop volumineux. Taille maximale : 10MB'], 400);
            }
            
            // Type réel détecté sur le contenu (jamais celui annoncé par le client),
            // l'extension en découle : impossible de déposer un .php
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            $mimeType = $file->getMimeType();
            if (!isset($extensionMap[$mimeType])) {
                return new JsonResponse([
                    'error' => 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP'
                ], 400);
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $extensionMap[$mimeType];
            $newFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename) . '.' . $extension;

            // Déplacer le fichier
            try {
                $file->move($this->uploadDirectory, $newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload : ' . $e->getMessage()], 500);
            }

            // Créer l'entrée en base de données
            $image = new BanqueImage();
            $image->setNom($request->request->get('nom') ?: $originalFilename);
            $image->setChemin('/uploads/' . $newFilename);
            $image->setType($request->request->get('type'));
            $image->setCategorie($request->request->get('categorie'));
            $image->setDescription($request->request->get('description'));
            $image->setFormat(strtolower($extension));
            $image->setTaille($fileSize);

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $image->getId(),
                'nom' => $image->getNom(),
                'chemin' => $image->getChemin(),
                'type' => $image->getType(),
                'categorie' => $image->getCategorie(),
                'format' => $image->getFormat(),
                'taille' => $image->getTaille(),
                'date_ajout' => $image->getDateAjout()->format('Y-m-d H:i:s')
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'upload : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $image = $this->entityManager->getRepository(BanqueImage::class)->find($id);
            
            if (!$image) {
                return new JsonResponse(['error' => 'Image non trouvée'], 404);
            }

            // Supprimer le fichier physique si c'est un fichier local
            $chemin = $image->getChemin();
            if (strpos($chemin, '/uploads/') === 0) {
                $filePath = $this->uploadDirectory . basename($chemin);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->entityManager->remove($image);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Image supprimée avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $image = $this->entityManager->getRepository(BanqueImage::class)->find($id);
            
            if (!$image) {
                return new JsonResponse(['error' => 'Image non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['nom'])) {
                $image->setNom($data['nom']);
            }
            if (isset($data['chemin'])) {
                $image->setChemin($data['chemin']);
            }
            if (isset($data['type'])) {
                $image->setType($data['type']);
            }
            if (isset($data['categorie'])) {
                $image->setCategorie($data['categorie']);
            }
            if (isset($data['description'])) {
                $image->setDescription($data['description']);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $image->getId(),
                'nom' => $image->getNom(),
                'chemin' => $image->getChemin(),
                'type' => $image->getType(),
                'categorie' => $image->getCategorie()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()], 500);
        }
    }
}

