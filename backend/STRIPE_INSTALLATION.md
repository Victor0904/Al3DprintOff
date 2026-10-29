# Installation de Stripe pour le Backend

## Installation de la dépendance PHP

Si vous avez rencontré un problème SSL lors de l'installation, voici les solutions :

### Option 1 : Installation directe
```bash
cd backend
composer require stripe/stripe-php
```

### Option 2 : Si problème SSL
Si vous avez un problème SSL avec Composer, vous pouvez :

1. Désactiver temporairement la vérification SSL :
```bash
composer config -g secure-http false
composer require stripe/stripe-php
composer config -g secure-http true
```

2. Ou configurer le certificat CA :
```bash
composer config -g cafile "C:/path/to/cacert.pem"
composer require stripe/stripe-php
```

### Option 3 : Installation manuelle
Téléchargez le package depuis https://github.com/stripe/stripe-php et placez-le dans `vendor/stripe/stripe-php`

## Configuration

Les clés Stripe sont déjà configurées dans `config/services.yaml` :
- Clé publique : `stripe.public_key`
- Clé secrète : `stripe.secret_key`

## Endpoints créés

- `POST /api/stripe/create-payment-intent` : Créer un PaymentIntent
- `POST /api/stripe/confirm-payment` : Confirmer un paiement
- `GET /api/stripe/public-key` : Récupérer la clé publique

## Test

Pour tester avec Stripe en mode test, utilisez les cartes de test :
- Carte réussie : `4242 4242 4242 4242`
- Carte refusée : `4000 0000 0000 0002`
- Date d'expiration : n'importe quelle date future
- CVC : n'importe quel code à 3 chiffres

