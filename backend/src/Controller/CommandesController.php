<?php

namespace App\Controller;

use App\Entity\Commandes;
use App\Entity\Clients;
use App\Entity\CodePromo;
use App\Entity\Produits;
use App\Entity\DetailsCommande;
use App\Service\EmailService;
use App\Service\EncryptionService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commandes', name: 'api_commandes_')]
class CommandesController extends SecuredApiController
{
    private const SHIPPING_FREE_THRESHOLD = 50;
    private const SHIPPING_COST = 10;

    private EntityManagerInterface $entityManager;
    private EmailService $emailService;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService, EmailService $emailService, private EncryptionService $encryption)
    {
        parent::__construct($tokenService);
        $this->entityManager = $entityManager;
        $this->emailService  = $emailService;
    }

    // ─── LISTE (authentifié — admin voit tout, user voit le sien) ─────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        $isAdmin  = ($authUser['role'] ?? '') === 'admin';
        $clientId = $request->query->get('client_id');
        $statut   = $request->query->get('statut');
        $limit    = (int) ($request->query->get('limit', 20));
        $offset   = (int) ($request->query->get('offset', 0));

        $repository   = $this->entityManager->getRepository(Commandes::class);
        $queryBuilder = $repository->createQueryBuilder('c')
            ->select('c.id, c.numeroCommande, c.dateCommande, c.statut, c.total, c.fraisLivraison, c.modePaiement, c.numeroSuivi')
            ->addSelect('cl.nom as client_nom')
            ->addSelect('COUNT(d.id) as details_count')
            ->leftJoin('c.detailsCommandes', 'd')
            ->leftJoin('c.client', 'cl')
            ->groupBy('c.id, cl.nom');

        // Un utilisateur non-admin ne voit que ses propres commandes
        if (!$isAdmin) {
            $queryBuilder->andWhere('c.client = :clientId')
                ->setParameter('clientId', $authUser['id']);
        } elseif ($clientId) {
            $queryBuilder->andWhere('c.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($statut) {
            $queryBuilder->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $queryBuilder->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $commandes = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($commandes as $row) {
            $data[] = [
                'id'              => $row['id'],
                'numero_commande' => $row['numeroCommande'],
                'date_commande'   => $row['dateCommande']->format('Y-m-d H:i:s'),
                'statut'          => $row['statut'],
                'total'           => $row['total'],
                'frais_livraison' => $row['fraisLivraison'] ?? '0.00',
                'mode_paiement'   => $row['modePaiement'],
                'numero_suivi'    => $row['numeroSuivi'],
                'client_nom'      => $row['client_nom'] ?? null,
                'details_count'   => (int) $row['details_count'],
            ];
        }

        return new JsonResponse($data);
    }

    // ─── DÉTAIL (authentifié — propriétaire ou admin) ──────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): JsonResponse
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        // Vérification d'ownership : seul le propriétaire ou un admin peut voir la commande
        $isAdmin = ($authUser['role'] ?? '') === 'admin';
        if (!$isAdmin && $commande->getClient()->getId() !== $authUser['id']) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = [
            'id'               => $commande->getId(),
            'numero_commande'  => $commande->getNumeroCommande(),
            'client'           => $this->clientData($commande),
            'date_commande'    => $commande->getDateCommande()->format('Y-m-d H:i:s'),
            'statut'           => $commande->getStatut(),
            'total'            => $commande->getTotal(),
            'adresse_livraison' => $commande->getAdresseLivraison(),
            'mode_paiement'    => $commande->getModePaiement(),
            'numero_suivi'     => $commande->getNumeroSuivi(),
            'frais_livraison'  => $commande->getFraisLivraison(),
            'details'          => [],
            'paiements'        => [],
            'impressions'      => [],
        ];

        foreach ($commande->getDetailsCommandes() as $detail) {
            $data['details'][] = [
                'id'              => $detail->getId(),
                'produit'         => [
                    'id'  => $detail->getProduit()->getId(),
                    'nom' => $detail->getProduit()->getNom(),
                    'prix' => $detail->getProduit()->getPrix(),
                ],
                'quantite'        => $detail->getQuantite(),
                'prix_unitaire'   => $detail->getPrixUnitaire(),
                'personnalisation' => $detail->getPersonnalisation(),
            ];
        }

        foreach ($commande->getPaiements() as $paiement) {
            $data['paiements'][] = [
                'id'            => $paiement->getId(),
                'montant'       => $paiement->getMontant(),
                'methode'       => $paiement->getMethode(),
                'statut'        => $paiement->getStatut(),
                'date_paiement' => $paiement->getDatePaiement()->format('Y-m-d H:i:s'),
            ];
        }

        foreach ($commande->getImpressions() as $impression) {
            $data['impressions'][] = [
                'id'           => $impression->getId(),
                'produit'      => [
                    'id'  => $impression->getProduit()->getId(),
                    'nom' => $impression->getProduit()->getNom(),
                ],
                'statut'       => $impression->getStatut(),
                'materiau'     => $impression->getMateriau(),
                'temps_estime' => $impression->getTempsEstime(),
                'date_debut'   => $impression->getDateDebut()?->format('Y-m-d H:i:s'),
                'date_fin'     => $impression->getDateFin()?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data);
    }

    // ─── CRÉER (authentifié) ───────────────────────────────────────────────────

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['client_id']) || !is_numeric($data['client_id'])) {
            return new JsonResponse(['error' => 'L\'ID du client est requis'], 400);
        }

        // Un utilisateur ne peut créer des commandes que pour lui-même
        $isAdmin = ($authUser['role'] ?? '') === 'admin';
        if (!$isAdmin && (int) $data['client_id'] !== $authUser['id']) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        if (!isset($data['details']) || !is_array($data['details']) || empty($data['details'])) {
            return new JsonResponse(['error' => 'Les détails de la commande sont requis'], 400);
        }

        $client = $this->entityManager->getRepository(Clients::class)->find($data['client_id']);
        if (!$client) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $commande = new Commandes();
        $commande->setClient($client);
        $commande->setStatut('En attente');
        $commande->setAdresseLivraison($data['adresse_livraison'] ?? null);
        $commande->setModePaiement($data['mode_paiement'] ?? null);

        $total = 0;
        foreach ($data['details'] as $detailData) {
            if (!isset($detailData['produit_id']) || !isset($detailData['quantite'])) {
                return new JsonResponse(['error' => 'Chaque détail doit avoir un produit_id et une quantité'], 400);
            }

            $quantite = filter_var($detailData['quantite'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
            if ($quantite === false) {
                return new JsonResponse(['error' => 'Quantité invalide'], 400);
            }

            $produit = $this->entityManager->getRepository(Produits::class)->find($detailData['produit_id']);
            if (!$produit) {
                return new JsonResponse(['error' => 'Produit non trouvé'], 404);
            }
            if ($produit->getStock() < $quantite) {
                return new JsonResponse(['error' => 'Stock insuffisant pour « ' . $produit->getNom() . ' »'], 409);
            }

            $detail = new DetailsCommande();
            $detail->setCommande($commande);
            $detail->setProduit($produit);
            $detail->setQuantite($quantite);
            $detail->setPrixUnitaire($produit->getPrix());
            $detail->setPersonnalisation($detailData['personnalisation'] ?? null);

            $total += $produit->getPrix() * $quantite;
            $this->entityManager->persist($detail);
        }

        // Frais de port calculés côté serveur (gratuits dès 50 €, comme affiché au panier)
        $fraisLivraison = $total >= self::SHIPPING_FREE_THRESHOLD ? 0 : self::SHIPPING_COST;
        $commande->setFraisLivraison((string) $fraisLivraison);

        // Appliquer le code promo si fourni
        if (!empty($data['code_promo'])) {
            $promo = $this->entityManager->getRepository(CodePromo::class)->findOneBy([
                'code'  => strtoupper(trim($data['code_promo'])),
                'actif' => true,
            ]);
            if ($promo) {
                $discount = $total * ((float) $promo->getRemise() / 100);
                $total   -= $discount;
                $commande->setCodePromoUtilise($promo->getCode());
            }
        }

        $commande->setTotal((string) round($total + $fraisLivraison, 2));
        $commande->setNumeroCommande($this->generateNumeroCommande());

        try {
            $this->entityManager->persist($commande);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'enum') !== false
                || strpos($errorMessage, 'Data truncated') !== false) {
                return new JsonResponse([
                    'error'    => 'Erreur de schéma pour la commande.',
                    'details'  => $errorMessage,
                ], 500);
            }
            return new JsonResponse(['error' => 'Erreur lors de la sauvegarde: ' . $errorMessage], 500);
        }

        // Emails : confirmation client + notification admin
        try {
            $orderData = $this->getOrderData($commande);
            $this->emailService->sendOrderConfirmationEmail($orderData, $client->getEmail());
            $this->emailService->sendNewOrderAdminEmail($orderData);
        } catch (\Exception) {
            // L'échec d'email ne bloque pas la commande
        }

        return new JsonResponse([
            'id'               => $commande->getId(),
            'numero_commande'  => $commande->getNumeroCommande(),
            'client_id'        => $commande->getClient()->getId(),
            'date_commande'    => $commande->getDateCommande()->format('Y-m-d H:i:s'),
            'statut'           => $commande->getStatut(),
            'total'            => $commande->getTotal(),
            'frais_livraison'  => $commande->getFraisLivraison(),
            'adresse_livraison' => $commande->getAdresseLivraison(),
            'mode_paiement'    => $commande->getModePaiement(),
        ], 201);
    }

    // ─── MODIFIER (admin uniquement) ───────────────────────────────────────────

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $oldStatut   = $commande->getStatut();
        $statutChanged = false;

        if (isset($data['statut'])) {
            if ($data['statut'] !== $oldStatut) {
                $statutChanged = true;
            }
            $commande->setStatut($data['statut']);
        }
        if (isset($data['adresse_livraison'])) {
            $commande->setAdresseLivraison($data['adresse_livraison']);
        }
        if (isset($data['mode_paiement'])) {
            $commande->setModePaiement($data['mode_paiement']);
        }
        if (isset($data['numero_suivi'])) {
            $commande->setNumeroSuivi($data['numero_suivi']);
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'enum') !== false || strpos($errorMessage, 'Data truncated') !== false) {
                return new JsonResponse([
                    'error'   => 'Statut non accepté par la base de données.',
                    'details' => $errorMessage,
                ], 500);
            }
            return new JsonResponse(['error' => 'Erreur lors de la sauvegarde: ' . $errorMessage], 500);
        }

        // Envoyer un email de mise à jour si le statut a changé
        if ($statutChanged) {
            $clientEmail = $commande->getClient()->getEmail();
            if ($clientEmail) {
                try {
                    $orderData = $this->getOrderData($commande);
                    $this->emailService->sendStatusUpdateEmail($orderData, $clientEmail);
                } catch (\Exception $e) {
                    // DEBUG temporaire : retourne l'erreur dans la réponse
                    return new JsonResponse([
                        'id'          => $commande->getId(),
                        'statut'      => $commande->getStatut(),
                        'email_error' => $e->getMessage(),
                    ], 200);
                }
            }
        }

        return new JsonResponse([
            'id'               => $commande->getId(),
            'numero_commande'  => $commande->getNumeroCommande(),
            'client_id'        => $commande->getClient()->getId(),
            'date_commande'    => $commande->getDateCommande()->format('Y-m-d H:i:s'),
            'statut'           => $commande->getStatut(),
            'total'            => $commande->getTotal(),
            'adresse_livraison' => $commande->getAdresseLivraison(),
            'mode_paiement'    => $commande->getModePaiement(),
            'numero_suivi'     => $commande->getNumeroSuivi(),
        ]);
    }

    // ─── APERÇU EMAIL (admin) ──────────────────────────────────────────────────

    #[Route('/{id}/email-preview', name: 'email_preview', methods: ['GET'])]
    public function emailPreview(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $orderData = $this->getOrderData($commande);
        $emailHtml = $this->generateBasicEmailPreview($orderData);

        return new JsonResponse([
            'html'    => $emailHtml,
            'to'      => $orderData['client']['email'],
            'subject' => 'Détails de votre commande #' . $orderData['id'] . ' - Al3DPrint',
            'warning' => 'Symfony Mailer non configuré. Ceci est une prévisualisation.',
        ]);
    }

    // ─── ENVOYER EMAIL (admin) ─────────────────────────────────────────────────

    #[Route('/{id}/send-email', name: 'send_email', methods: ['POST'])]
    public function sendEmail(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $client = $commande->getClient();
        if (!$client || !$client->getEmail()) {
            return new JsonResponse(['error' => 'Email du client non disponible'], 400);
        }

        $orderData = $this->getOrderData($commande);

        try {
            $this->emailService->sendOrderEmail($orderData, $client->getEmail());
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['message' => 'Email envoyé avec succès à ' . $client->getEmail()]);
    }

    // ─── FACTURE PDF (authentifié — propriétaire ou admin) ────────────────────

    #[Route('/{id}/facture', name: 'facture', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generateInvoice(int $id, Request $request): Response
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $isAdmin = ($authUser['role'] ?? '') === 'admin';
        if (!$isAdmin && $commande->getClient()->getId() !== $authUser['id']) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $orderData = $this->getOrderData($commande);
        $html      = $this->getInvoiceHtml($orderData);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'facture-' . ($commande->getNumeroCommande() ?? $id) . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            ]
        );
    }

    // ─── SUPPRIMER (admin uniquement) ──────────────────────────────────────────

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $commande = $this->entityManager->getRepository(Commandes::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée'], 404);
        }

        $this->entityManager->remove($commande);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Commande supprimée avec succès']);
    }

    // ─── HELPERS PRIVÉS ────────────────────────────────────────────────────────

    private function generateNumeroCommande(): string
    {
        $now   = new \DateTime();
        $month = $now->format('m');   // ex: "01"
        $year  = $now->format('y');   // ex: "26"

        // Récupère l'incrément max pour l'année en cours (pattern __YY-.....)
        $conn    = $this->entityManager->getConnection();
        $pattern = '__' . $year . '-%';
        $sql     = 'SELECT MAX(CAST(SUBSTRING_INDEX(numero_commande, \'-\', -1) AS UNSIGNED))
                    FROM commandes
                    WHERE numero_commande LIKE :pattern';

        $maxIncrement = $conn->fetchOne($sql, ['pattern' => $pattern]);
        $increment    = ($maxIncrement ? (int) $maxIncrement : 0) + 1;

        return $month . $year . '-' . str_pad((string) $increment, 5, '0', STR_PAD_LEFT);
    }

    private function formatPaymentMethod(?string $method): string
    {
        return match ($method) {
            'stripe', 'card' => 'Carte bancaire (Stripe)',
            'virement'       => 'Virement bancaire',
            'cheque'         => 'Chèque',
            default          => $method ?? 'Non spécifié',
        };
    }

    private function clientData(Commandes $commande): array
    {
        $client = $commande->getClient();

        return [
            'id'          => $client->getId(),
            'nom'         => $client->getNom(),
            'email'       => $client->getEmail(),
            // Le téléphone est chiffré en base
            'telephone'   => $this->encryption->decryptArray(['telephone' => $client->getTelephone()])['telephone'],
            'type_client' => $client->getTypeClient(),
        ];
    }

    private function getOrderData(Commandes $commande): array
    {
        $data = [
            'id'               => $commande->getId(),
            'numero_commande'  => $commande->getNumeroCommande(),
            'client'           => $this->clientData($commande),
            'date_commande'    => $commande->getDateCommande()->format('Y-m-d H:i:s'),
            'statut'           => $commande->getStatut(),
            'total'            => $commande->getTotal(),
            'frais_livraison'  => $commande->getFraisLivraison(),
            'adresse_livraison' => $commande->getAdresseLivraison(),
            'mode_paiement'    => $this->formatPaymentMethod($commande->getModePaiement()),
            'mode_paiement_raw' => $commande->getModePaiement(),
            'numero_suivi'     => $commande->getNumeroSuivi(),
            'paye'             => $commande->getPaiements()->exists(fn($k, $p) => $p->getStatut() === 'payé'),
            'details'          => [],
        ];

        foreach ($commande->getDetailsCommandes() as $detail) {
            $data['details'][] = [
                'id'              => $detail->getId(),
                'produit'         => [
                    'id'  => $detail->getProduit()->getId(),
                    'nom' => $detail->getProduit()->getNom(),
                    'prix' => $detail->getProduit()->getPrix(),
                ],
                'quantite'        => $detail->getQuantite(),
                'prix_unitaire'   => $detail->getPrixUnitaire(),
                'personnalisation' => $detail->getPersonnalisation(),
            ];
        }

        return $data;
    }

    private function getInvoiceHtml(array $orderData): string
    {
        $numeroCommande = htmlspecialchars($orderData['numero_commande'] ?? ('CMD-' . $orderData['id']), ENT_QUOTES, 'UTF-8');
        $clientNom      = htmlspecialchars($orderData['client']['nom'] ?? '', ENT_QUOTES, 'UTF-8');
        $clientEmail    = htmlspecialchars($orderData['client']['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $clientTel      = htmlspecialchars($orderData['client']['telephone'] ?? '', ENT_QUOTES, 'UTF-8');
        $adresse        = nl2br(htmlspecialchars($orderData['adresse_livraison'] ?? '', ENT_QUOTES, 'UTF-8'));
        $dateCommande   = htmlspecialchars($orderData['date_commande'] ?? '', ENT_QUOTES, 'UTF-8');
        $dateParts      = explode(' ', $dateCommande);
        $dateFormatted  = $dateParts[0] ?? $dateCommande;
        $modePaiement   = htmlspecialchars($orderData['mode_paiement'] ?? '', ENT_QUOTES, 'UTF-8');
        $statut         = htmlspecialchars($orderData['statut'] ?? '', ENT_QUOTES, 'UTF-8');
        $fraisLivraison = (float)($orderData['frais_livraison'] ?? 0);
        $totalTTC       = (float)($orderData['total'] ?? 0);
        $sousTotal      = $totalTTC - $fraisLivraison;
        $currentYear    = date('Y');
        $dateFacture    = date('d/m/Y');

        // Lignes articles
        $lignes = '';
        foreach ($orderData['details'] ?? [] as $item) {
            $nom      = htmlspecialchars($item['produit']['nom'] ?? 'Article', ENT_QUOTES, 'UTF-8');
            if (!empty($item['personnalisation']['variante_nom'])) {
                $nom .= '<br><span style="color:#888;font-size:11px;">Coloris : ' . htmlspecialchars($item['personnalisation']['variante_nom'], ENT_QUOTES, 'UTF-8') . '</span>';
            }
            $qty      = (int)($item['quantite'] ?? 1);
            $pu       = (float)($item['prix_unitaire'] ?? 0);
            $total    = $pu * $qty;
            $lignes  .= '<tr>'
                . '<td style="padding:8px 6px;border-bottom:1px solid #e8e8e8;">' . $nom . '</td>'
                . '<td style="padding:8px 6px;border-bottom:1px solid #e8e8e8;text-align:center;">' . $qty . '</td>'
                . '<td style="padding:8px 6px;border-bottom:1px solid #e8e8e8;text-align:right;">' . number_format($pu, 2, ',', ' ') . ' &euro;</td>'
                . '<td style="padding:8px 6px;border-bottom:1px solid #e8e8e8;text-align:right;font-weight:bold;">' . number_format($total, 2, ',', ' ') . ' &euro;</td>'
                . '</tr>';
        }

        $totalTTCFormatted   = number_format($totalTTC, 2, ',', ' ');
        $sousTotalFormatted  = number_format($sousTotal, 2, ',', ' ');
        $tamponPaye          = !empty($orderData['paye']) ? '<br><span class="paid-stamp">PAYÉ</span>' : '';
        $fraisFormatted      = $fraisLivraison > 0 ? number_format($fraisLivraison, 2, ',', ' ') . ' &euro;' : 'Gratuit';

        $statutColor = match(strtolower($statut)) {
            'payée', 'payé', 'paye', 'livrée', 'livree', 'expédiée', 'expediee' => '#059669',
            'en attente' => '#d97706',
            'annulée', 'annulee' => '#dc2626',
            default => '#2563eb',
        };

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {$numeroCommande}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size:13px; color:#222; background:#fff; }
  .page { padding: 30px 40px; }
  .header { display:table; width:100%; border-bottom:3px solid #00d4ff; padding-bottom:18px; margin-bottom:22px; }
  .logo-block { display:table-cell; vertical-align:middle; }
  .logo { font-size:26px; font-weight:900; color:#00d4ff; letter-spacing:-1px; }
  .logo-sub { font-size:10px; color:#888; letter-spacing:2px; text-transform:uppercase; margin-top:2px; }
  .invoice-meta { display:table-cell; vertical-align:middle; text-align:right; }
  .invoice-title { font-size:20px; font-weight:700; color:#1a1a2e; }
  .invoice-num { font-size:13px; color:#555; margin-top:3px; }
  .invoice-date { font-size:12px; color:#888; margin-top:2px; }
  .two-col { display:table; width:100%; margin-bottom:22px; }
  .col { display:table-cell; width:50%; vertical-align:top; }
  .col-right { text-align:right; }
  .section-label { font-size:10px; font-weight:700; color:#00d4ff; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
  .info-block { background:#f8f9fc; border:1px solid #e8eaf0; border-radius:6px; padding:12px 14px; font-size:12px; line-height:1.7; color:#333; }
  .info-block strong { color:#1a1a2e; font-size:13px; }
  table.items { width:100%; border-collapse:collapse; margin-bottom:16px; }
  table.items thead tr { background:#1a1a2e; }
  table.items thead th { padding:10px 6px; color:#00d4ff; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; text-align:left; }
  table.items thead th:last-child { text-align:right; }
  table.items thead th:nth-child(2) { text-align:center; }
  table.items thead th:nth-child(3) { text-align:right; }
  table.items tbody td { font-size:12px; }
  .totals-table { width:280px; margin-left:auto; border-collapse:collapse; }
  .totals-table td { padding:5px 8px; font-size:12px; }
  .totals-table .label { color:#666; }
  .totals-table .val { text-align:right; color:#333; }
  .totals-table .sep td { border-top:1px solid #e0e0e0; padding-top:8px; }
  .totals-table .ttc .label { font-weight:700; color:#1a1a2e; font-size:14px; }
  .totals-table .ttc .val { font-weight:900; color:#00d4ff; font-size:16px; }
  .statut-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; color:#fff; background:{$statutColor}; }
  .footer { margin-top:30px; padding-top:14px; border-top:1px solid #e0e0e0; font-size:10px; color:#999; text-align:center; line-height:1.6; }
  .paid-stamp { border:3px solid #059669; border-radius:6px; color:#059669; font-size:18px; font-weight:900; padding:6px 16px; display:inline-block; transform:rotate(-5deg); letter-spacing:2px; margin-top:10px; }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <div class="logo-block">
      <div class="logo">Al3DPrint</div>
      <div class="logo-sub">Impression 3D Professionnelle</div>
    </div>
    <div class="invoice-meta">
      <div class="invoice-title">FACTURE</div>
      <div class="invoice-num">N° {$numeroCommande}</div>
      <div class="invoice-date">Date : {$dateFacture}</div>
    </div>
  </div>

  <!-- Infos vendeur / client -->
  <div class="two-col">
    <div class="col" style="padding-right:20px;">
      <div class="section-label">Vendeur</div>
      <div class="info-block">
        <strong>Al3DPrint</strong><br>
        contact@al3dprint.fr<br>
        &copy; {$currentYear} Al3DPrint
      </div>
    </div>
    <div class="col col-right" style="padding-left:20px;">
      <div class="section-label">Client</div>
      <div class="info-block" style="text-align:left;">
        <strong>{$clientNom}</strong><br>
        {$clientEmail}<br>
        {$clientTel}
      </div>
    </div>
  </div>

  <!-- Détails commande -->
  <div class="two-col" style="margin-bottom:22px;">
    <div class="col" style="padding-right:20px;">
      <div class="section-label">Commande</div>
      <div class="info-block">
        <strong>N° {$numeroCommande}</strong><br>
        Date : {$dateFormatted}<br>
        Paiement : {$modePaiement}
      </div>
    </div>
    <div class="col" style="padding-left:20px;">
      <div class="section-label">Livraison</div>
      <div class="info-block">
        {$adresse}
      </div>
    </div>
  </div>

  <!-- Articles -->
  <table class="items">
    <thead>
      <tr>
        <th>Article</th>
        <th style="text-align:center;">Qté</th>
        <th style="text-align:right;">Prix unit. TTC</th>
        <th style="text-align:right;">Total TTC</th>
      </tr>
    </thead>
    <tbody>
      {$lignes}
    </tbody>
  </table>

  <!-- Totaux -->
  <table class="totals-table">
    <tr>
      <td class="label">Sous-total articles</td>
      <td class="val">{$sousTotalFormatted} &euro;</td>
    </tr>
    <tr>
      <td class="label">Frais de livraison</td>
      <td class="val">{$fraisFormatted}</td>
    </tr>
    <tr class="sep">
      <td class="label">Total HT estimé</td>
      <td class="val">{$totalTTCFormatted} &euro;</td>
    </tr>
    <tr class="ttc">
      <td class="label">TOTAL TTC</td>
      <td class="val">{$totalTTCFormatted} &euro;</td>
    </tr>
  </table>

  <!-- Statut + tampon payé -->
  <div style="margin-top:20px; text-align:right;">
    <span class="statut-badge">{$statut}</span>
    {$tamponPaye}
  </div>

  <!-- Pied de page -->
  <div class="footer">
    <p>Al3DPrint — contact@al3dprint.fr — al3dprint.fr</p>
    <p>Ce document est une facture officielle. Merci pour votre confiance.</p>
    <p>&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
  </div>

</div>
</body>
</html>
HTML;
    }

    private function generateBasicEmailPreview(array $orderData): string
    {
        $orderId    = htmlspecialchars((string) ($orderData['id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $clientName = htmlspecialchars($orderData['client']['nom'] ?? 'Client', ENT_QUOTES, 'UTF-8');
        $orderDate  = htmlspecialchars($orderData['date_commande'] ?? '', ENT_QUOTES, 'UTF-8');
        $total      = htmlspecialchars(number_format((float) ($orderData['total'] ?? 0), 2, ',', ' ') . '€', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Commande #{$orderId}</title></head>
<body>
  <h1>Détails de votre commande #{$orderId}</h1>
  <p>Bonjour {$clientName},</p>
  <p>Date : {$orderDate}</p>
  <p>Total : {$total}</p>
</body>
</html>
HTML;
    }
}
