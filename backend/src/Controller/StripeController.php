<?php

namespace App\Controller;

use App\Entity\Commandes;
use App\Entity\Paiements;
use App\Entity\VarianteCouleur;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/stripe', name: 'api_stripe_')]
class StripeController extends SecuredApiController
{
    private EntityManagerInterface $entityManager;
    private string $stripeSecretKey;
    private string $stripePublicKey;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        TokenService $tokenService
    ) {
        parent::__construct($tokenService);
        $this->entityManager    = $entityManager;
        $this->stripeSecretKey  = $parameterBag->get('stripe.secret_key');
        $this->stripePublicKey  = $parameterBag->get('stripe.public_key');
    }

    /**
     * Configure curl pour Stripe (résolution problèmes SSL sur Windows)
     */
    private function configureStripeCurl(): void
    {
        $possibleCACertPaths = [
            __DIR__ . '/../../vendor/composer/ca-bundle/res/cacert.pem',
            __DIR__ . '/../../cacert.pem',
            'C:/php/extras/ssl/cacert.pem',
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
        ];

        $caCertPath = null;
        foreach ($possibleCACertPaths as $path) {
            if ($path && file_exists($path)) {
                $caCertPath = $path;
                break;
            }
        }

        if ($caCertPath && method_exists('\Stripe\Stripe', 'setCABundlePath')) {
            \Stripe\Stripe::setCABundlePath($caCertPath);
        }
        // Sans bundle trouvé, on garde la vérification TLS par défaut de Stripe (jamais désactivée)
    }

    // ─── CRÉER UN PAYMENT INTENT (authentifié) ─────────────────────────────────

    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        try {
            if (!class_exists('\Stripe\Stripe')) {
                return new JsonResponse([
                    'error' => 'Stripe PHP n\'est pas installé. Veuillez exécuter: composer require stripe/stripe-php',
                ], 500);
            }

            $data     = json_decode($request->getContent(), true);
            $commande = $this->findOwnedCommande($data['commande_id'] ?? null, $authUser);
            if ($commande instanceof JsonResponse) {
                return $commande;
            }
            if (!$commande->getPaiements()->isEmpty()) {
                return new JsonResponse(['error' => 'Cette commande est déjà payée'], 409);
            }

            // Le montant vient toujours de la commande enregistrée, jamais du navigateur
            $amountInCents = (int) round((float) $commande->getTotal() * 100);
            if ($amountInCents <= 0) {
                return new JsonResponse(['error' => 'Montant invalide'], 400);
            }
            $commandeId = $commande->getId();

            $this->configureStripeCurl();
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount'                    => $amountInCents,
                'currency'                  => 'eur',
                'metadata'                  => [
                    'commande_id' => $commandeId,
                    'client_id'   => $authUser['id'],
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return new JsonResponse([
                'clientSecret'    => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la création du PaymentIntent: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── CONFIRMER UN PAIEMENT (authentifié) ───────────────────────────────────

    #[Route('/confirm-payment', name: 'confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request): JsonResponse
    {
        $authUser = $this->requireAuth($request);
        if ($authUser instanceof JsonResponse) {
            return $authUser;
        }

        try {
            if (!class_exists('\Stripe\Stripe')) {
                return new JsonResponse([
                    'error' => 'Stripe PHP n\'est pas installé.',
                ], 500);
            }

            $data            = json_decode($request->getContent(), true);
            $paymentIntentId = $data['payment_intent_id'] ?? null;
            $commandeId      = $data['commande_id'] ?? null;

            if (!$paymentIntentId) {
                return new JsonResponse(['error' => 'PaymentIntent ID manquant'], 400);
            }

            $this->configureStripeCurl();
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                $commande = $this->findOwnedCommande($commandeId, $authUser);
                if ($commande instanceof JsonResponse) {
                    return $commande;
                }

                // Le PaymentIntent doit correspondre exactement à cette commande et à ce montant
                $expectedCents = (int) round((float) $commande->getTotal() * 100);
                if ((string) ($paymentIntent->metadata['commande_id'] ?? '') !== (string) $commande->getId()
                    || $paymentIntent->amount !== $expectedCents
                    || $paymentIntent->currency !== 'eur') {
                    return new JsonResponse(['error' => 'Paiement ne correspondant pas à la commande'], 400);
                }

                $existing = $this->entityManager->getRepository(Paiements::class)
                    ->findOneBy(['stripePaymentId' => $paymentIntentId]);
                if ($existing) {
                    return new JsonResponse(['success' => true, 'message' => 'Paiement déjà enregistré']);
                }

                $paiement = new Paiements();
                $paiement->setCommande($commande);
                $paiement->setMontant((string) ($paymentIntent->amount / 100));
                $paiement->setDatePaiement(new \DateTime());
                $paiement->setStatut('payé');
                $paiement->setMethode('stripe');
                $paiement->setStripePaymentId($paymentIntentId);
                $this->entityManager->persist($paiement);

                if ($commande->getStatut() === 'En attente') {
                    $commande->setStatut('Payée');
                }

                // Le stock n'est décrémenté qu'une fois le paiement encaissé (produit et coloris)
                foreach ($commande->getDetailsCommandes() as $detail) {
                    $produit = $detail->getProduit();
                    $produit->setStock(max(0, $produit->getStock() - $detail->getQuantite()));
                    $varianteId = $detail->getPersonnalisation()['variante_id'] ?? null;
                    $variante = $varianteId ? $this->entityManager->getRepository(VarianteCouleur::class)->find($varianteId) : null;
                    if ($variante && $variante->getProduit() === $produit) {
                        $variante->setStock(max(0, $variante->getStock() - $detail->getQuantite()));
                    }
                }

                $this->entityManager->flush();

                return new JsonResponse([
                    'success'  => true,
                    'message'  => 'Paiement confirmé avec succès',
                    'paiement' => [
                        'id'                => $paiement->getId(),
                        'montant'           => $paiement->getMontant(),
                        'statut'            => $paiement->getStatut(),
                        'methode'           => $paiement->getMethode(),
                        'stripe_payment_id' => $paiement->getStripePaymentId(),
                    ],
                ]);
            }

            return new JsonResponse([
                'error'  => 'Le paiement n\'a pas été complété',
                'status' => $paymentIntent->status,
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retourne la commande si elle appartient à l'utilisateur (ou s'il est admin), sinon une erreur.
     */
    private function findOwnedCommande(mixed $commandeId, array $authUser): Commandes|JsonResponse
    {
        $commande = is_numeric($commandeId)
            ? $this->entityManager->getRepository(Commandes::class)->find((int) $commandeId)
            : null;
        if (!$commande) {
            return new JsonResponse(['error' => 'Commande introuvable'], 404);
        }
        $isAdmin = ($authUser['role'] ?? '') === 'admin';
        if (!$isAdmin && $commande->getClient()->getId() !== $authUser['id']) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $commande;
    }

    // ─── CLÉ PUBLIQUE (public) ─────────────────────────────────────────────────

    #[Route('/public-key', name: 'public_key', methods: ['GET'])]
    public function getPublicKey(): JsonResponse
    {
        return new JsonResponse(['publicKey' => $this->stripePublicKey]);
    }
}
