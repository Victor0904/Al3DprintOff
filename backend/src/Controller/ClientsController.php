<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Service\EmailService;
use App\Service\EncryptionService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/clients', name: 'api_clients_')]
class ClientsController extends SecuredApiController
{
    private EntityManagerInterface $entityManager;
    private EncryptionService $encryptionService;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EncryptionService $encryptionService,
        TokenService $tokenService,
        EmailService $emailService
    ) {
        parent::__construct($tokenService);
        $this->entityManager     = $entityManager;
        $this->encryptionService = $encryptionService;
        $this->emailService      = $emailService;
    }

    // ─── LISTE (admin uniquement) ──────────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $clients = $this->entityManager->getRepository(Clients::class)->findAll();

        $data = [];
        foreach ($clients as $client) {
            $clientData = [
                'id'               => $client->getId(),
                'nom'              => $client->getNom(),
                'email'            => $client->getEmail(),
                'adresse'          => $client->getAdresse(),
                'code_postal'      => $client->getCodePostal(),
                'ville'            => $client->getVille(),
                'adresse2'         => $client->getAdresse2(),
                'code_postal2'     => $client->getCodePostal2(),
                'ville2'           => $client->getVille2(),
                'adresse3'         => $client->getAdresse3(),
                'code_postal3'     => $client->getCodePostal3(),
                'ville3'           => $client->getVille3(),
                'telephone'        => $client->getTelephone(),
                'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
                'type_client'      => $client->getTypeClient(),
                'commandes_count'  => $client->getCommandes()->count(),
            ];

            $clientData = $this->encryptionService->decryptArray($clientData);
            $data[] = $clientData;
        }

        return new JsonResponse($data);
    }

    // ─── AFFICHER UN CLIENT (soi-même ou admin) ────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $id);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = [
            'id'               => $client->getId(),
            'nom'              => $client->getNom(),
            'email'            => $client->getEmail(),
            'adresse'          => $client->getAdresse(),
            'code_postal'      => $client->getCodePostal(),
            'ville'            => $client->getVille(),
            'adresse2'         => $client->getAdresse2(),
            'code_postal2'     => $client->getCodePostal2(),
            'ville2'           => $client->getVille2(),
            'adresse3'         => $client->getAdresse3(),
            'code_postal3'     => $client->getCodePostal3(),
            'ville3'           => $client->getVille3(),
            'telephone'        => $client->getTelephone(),
            'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
            'type_client'      => $client->getTypeClient(),
            'commandes'        => [],
            'avis'             => [],
        ];

        $data = $this->encryptionService->decryptArray($data);

        foreach ($client->getCommandes() as $commande) {
            $data['commandes'][] = [
                'id'            => $commande->getId(),
                'date_commande' => $commande->getDateCommande()->format('Y-m-d H:i:s'),
                'statut'        => $commande->getStatut(),
                'total'         => $commande->getTotal(),
            ];
        }

        foreach ($client->getAvisClients() as $avis) {
            $data['avis'][] = [
                'id'          => $avis->getId(),
                'note'        => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
                'date'        => $avis->getDate()->format('Y-m-d H:i:s'),
                'produit'     => [
                    'id'  => $avis->getProduit()->getId(),
                    'nom' => $avis->getProduit()->getNom(),
                ],
            ];
        }

        return new JsonResponse($data);
    }

    // ─── CONNEXION (public) ────────────────────────────────────────────────────

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['mot_de_passe'])) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }

        $client = $this->entityManager
            ->getRepository(Clients::class)
            ->findOneBy(['email' => $data['email']]);

        if (!$client) {
            return new JsonResponse(['error' => 'Identifiants incorrects'], 401);
        }

        if (!password_verify($data['mot_de_passe'], $client->getMotDePasse())) {
            return new JsonResponse(['error' => 'Identifiants incorrects'], 401);
        }

        // Générer un vrai token signé côté serveur
        $token = $this->tokenService->generateToken(
            $client->getId(),
            $client->getEmail(),
            $client->getRole() ?? 'user'
        );

        $userData = [
            'id'               => $client->getId(),
            'nom'              => $client->getNom(),
            'email'            => $client->getEmail(),
            'adresse'          => $client->getAdresse(),
            'code_postal'      => $client->getCodePostal(),
            'ville'            => $client->getVille(),
            'adresse2'         => $client->getAdresse2(),
            'code_postal2'     => $client->getCodePostal2(),
            'ville2'           => $client->getVille2(),
            'adresse3'         => $client->getAdresse3(),
            'code_postal3'     => $client->getCodePostal3(),
            'ville3'           => $client->getVille3(),
            'telephone'        => $client->getTelephone(),
            'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
            'type_client'      => $client->getTypeClient(),
            'role'             => $client->getRole() ?? 'user',
        ];

        $userData = $this->encryptionService->decryptArray($userData);
        $userData['token'] = $token;

        return new JsonResponse($userData);
    }

    // ─── INSCRIPTION (public) ──────────────────────────────────────────────────

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || empty($data['nom'])) {
            return new JsonResponse(['error' => 'Le nom est requis'], 400);
        }
        if (!isset($data['email']) || empty($data['email'])) {
            return new JsonResponse(['error' => 'L\'email est requis'], 400);
        }
        if (!isset($data['mot_de_passe']) || empty($data['mot_de_passe'])) {
            return new JsonResponse(['error' => 'Le mot de passe est requis'], 400);
        }

        $existingClient = $this->entityManager
            ->getRepository(Clients::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingClient) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
        }

        $sensitiveData   = [
            'adresse'      => $data['adresse'] ?? null,
            'code_postal'  => $data['code_postal'] ?? null,
            'ville'        => $data['ville'] ?? null,
            'adresse2'     => $data['adresse2'] ?? null,
            'code_postal2' => $data['code_postal2'] ?? null,
            'ville2'       => $data['ville2'] ?? null,
            'adresse3'     => $data['adresse3'] ?? null,
            'code_postal3' => $data['code_postal3'] ?? null,
            'ville3'       => $data['ville3'] ?? null,
            'telephone'    => $data['telephone'] ?? null,
        ];
        $encryptedData = $this->encryptionService->encryptArray($sensitiveData);

        $client = new Clients();
        $client->setNom($data['nom']);
        $client->setEmail($data['email']);
        $client->setMotDePasse(password_hash($data['mot_de_passe'], PASSWORD_DEFAULT));
        $client->setAdresse($encryptedData['adresse']);
        $client->setCodePostal($encryptedData['code_postal']);
        $client->setVille($encryptedData['ville']);
        $client->setAdresse2($encryptedData['adresse2']);
        $client->setCodePostal2($encryptedData['code_postal2']);
        $client->setVille2($encryptedData['ville2']);
        $client->setAdresse3($encryptedData['adresse3']);
        $client->setCodePostal3($encryptedData['code_postal3']);
        $client->setVille3($encryptedData['ville3']);
        $client->setTelephone($encryptedData['telephone']);
        $client->setTypeClient($data['type_client'] ?? 'particulier');

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        // Email de bienvenue
        try {
            $this->emailService->sendWelcomeEmail($client->getEmail(), $client->getNom());
        } catch (\Exception) {
            // L'échec d'email ne bloque pas l'inscription
        }

        // Générer un token pour l'inscription automatique
        $token = $this->tokenService->generateToken(
            $client->getId(),
            $client->getEmail(),
            $client->getRole() ?? 'user'
        );

        $responseData = [
            'id'               => $client->getId(),
            'nom'              => $client->getNom(),
            'email'            => $client->getEmail(),
            'adresse'          => $client->getAdresse(),
            'code_postal'      => $client->getCodePostal(),
            'ville'            => $client->getVille(),
            'telephone'        => $client->getTelephone(),
            'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
            'type_client'      => $client->getTypeClient(),
            'role'             => $client->getRole() ?? 'user',
        ];

        $responseData = $this->encryptionService->decryptArray($responseData);
        $responseData['token'] = $token;

        return new JsonResponse($responseData, 201);
    }

    // ─── MODIFIER UN CLIENT (admin uniquement) ─────────────────────────────────

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $client->setNom($data['nom']);
        }
        if (isset($data['email'])) {
            $existingClient = $this->entityManager
                ->getRepository(Clients::class)
                ->findOneBy(['email' => $data['email']]);
            if ($existingClient && $existingClient->getId() !== $id) {
                return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
            }
            $client->setEmail($data['email']);
        }
        if (isset($data['mot_de_passe'])) {
            $client->setMotDePasse(password_hash($data['mot_de_passe'], PASSWORD_DEFAULT));
        }
        if (isset($data['type_client'])) {
            $client->setTypeClient($data['type_client']);
        }

        if (isset($data['adresse']) || isset($data['code_postal']) || isset($data['ville']) || isset($data['telephone'])) {
            $sensitiveData = [
                'adresse'     => $data['adresse'] ?? null,
                'code_postal' => $data['code_postal'] ?? null,
                'ville'       => $data['ville'] ?? null,
                'telephone'   => $data['telephone'] ?? null,
            ];
            $encryptedData = $this->encryptionService->encryptArray($sensitiveData);

            if (isset($data['adresse'])) {
                $client->setAdresse($encryptedData['adresse']);
            }
            if (isset($data['code_postal'])) {
                $client->setCodePostal($encryptedData['code_postal']);
            }
            if (isset($data['ville'])) {
                $client->setVille($encryptedData['ville']);
            }
            if (isset($data['telephone'])) {
                $client->setTelephone($encryptedData['telephone']);
            }
        }

        $this->entityManager->flush();

        $responseData = [
            'id'               => $client->getId(),
            'nom'              => $client->getNom(),
            'email'            => $client->getEmail(),
            'adresse'          => $client->getAdresse(),
            'code_postal'      => $client->getCodePostal(),
            'ville'            => $client->getVille(),
            'telephone'        => $client->getTelephone(),
            'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
            'type_client'      => $client->getTypeClient(),
        ];

        return new JsonResponse($this->encryptionService->decryptArray($responseData));
    }

    // ─── METTRE À JOUR LE PROFIL (soi-même) ───────────────────────────────────

    #[Route('/profile/{id}', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireOwnerOrAdmin($request, $id);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $client->setNom($data['nom']);
        }

        // Chiffrer et mettre à jour les champs sensibles
        $sensitiveFields = [
            'adresse'      => 'setAdresse',
            'code_postal'  => 'setCodePostal',
            'ville'        => 'setVille',
            'adresse2'     => 'setAdresse2',
            'code_postal2' => 'setCodePostal2',
            'ville2'       => 'setVille2',
            'adresse3'     => 'setAdresse3',
            'code_postal3' => 'setCodePostal3',
            'ville3'       => 'setVille3',
            'telephone'    => 'setTelephone',
        ];

        foreach ($sensitiveFields as $field => $setter) {
            if (isset($data[$field])) {
                $client->$setter($this->encryptionService->encrypt((string) $data[$field]));
            }
        }

        if (isset($data['email'])) {
            $existingClient = $this->entityManager
                ->getRepository(Clients::class)
                ->findOneBy(['email' => $data['email']]);
            if ($existingClient && $existingClient->getId() !== $id) {
                return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
            }
            $client->setEmail($data['email']);
        }

        if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
            $client->setMotDePasse(password_hash($data['mot_de_passe'], PASSWORD_DEFAULT));
        }

        if (isset($data['type_client'])) {
            $client->setTypeClient($data['type_client']);
        }

        $this->entityManager->flush();

        $responseData = [
            'id'               => $client->getId(),
            'nom'              => $client->getNom(),
            'email'            => $client->getEmail(),
            'adresse'          => $client->getAdresse(),
            'code_postal'      => $client->getCodePostal(),
            'ville'            => $client->getVille(),
            'adresse2'         => $client->getAdresse2(),
            'code_postal2'     => $client->getCodePostal2(),
            'ville2'           => $client->getVille2(),
            'adresse3'         => $client->getAdresse3(),
            'code_postal3'     => $client->getCodePostal3(),
            'ville3'           => $client->getVille3(),
            'telephone'        => $client->getTelephone(),
            'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
            'type_client'      => $client->getTypeClient(),
            'message'          => 'Profil mis à jour avec succès',
        ];

        return new JsonResponse($this->encryptionService->decryptArray($responseData));
    }

    // ─── MOT DE PASSE OUBLIÉ (public) ─────────────────────────────────────────

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || empty($data['email'])) {
            return new JsonResponse(['error' => 'Email requis'], 400);
        }

        $client = $this->entityManager
            ->getRepository(Clients::class)
            ->findOneBy(['email' => $data['email']]);

        // Toujours retourner succès pour ne pas révéler si l'email existe
        if (!$client) {
            return new JsonResponse([
                'message' => 'Si cet email existe dans notre base de données, vous recevrez un email de réinitialisation.',
            ], 200);
        }

        $resetToken = bin2hex(random_bytes(32));
        $expiresAt  = new \DateTime('+1 hour');

        $client->setResetToken($resetToken);
        $client->setResetTokenExpiresAt($expiresAt);
        $this->entityManager->flush();

        try {
            $nom = $client->getNom();
            if ($this->encryptionService->isEncrypted($nom)) {
                $nom = $this->encryptionService->decrypt($nom);
            }
            $this->emailService->sendPasswordResetEmail($client->getEmail(), $resetToken, $nom);
        } catch (\Throwable $e) {
            error_log('Envoi email réinitialisation échoué : ' . $e->getMessage());
        }

        return new JsonResponse([
            'message' => 'Si cet email existe dans notre base de données, vous recevrez un email de réinitialisation.',
        ], 200);
    }

    // ─── RÉINITIALISATION MDP (public) ────────────────────────────────────────

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || empty($data['token'])) {
            return new JsonResponse(['error' => 'Token requis'], 400);
        }
        if (!isset($data['new_password']) || empty($data['new_password'])) {
            return new JsonResponse(['error' => 'Nouveau mot de passe requis'], 400);
        }
        if (strlen($data['new_password']) < 8) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        $client = $this->entityManager
            ->getRepository(Clients::class)
            ->findOneBy(['resetToken' => $data['token']]);

        if (!$client) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        if (!$client->isResetTokenValid()) {
            return new JsonResponse(['error' => 'Le token a expiré. Veuillez demander une nouvelle réinitialisation.'], 400);
        }

        $client->setMotDePasse(password_hash($data['new_password'], PASSWORD_DEFAULT));
        $client->setResetToken(null);
        $client->setResetTokenExpiresAt(null);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès'], 200);
    }

    // ─── SUPPRIMER (admin uniquement) ─────────────────────────────────────────

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $this->entityManager->remove($client);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Client supprimé avec succès']);
    }
}
