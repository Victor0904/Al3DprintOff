# Installation de Stripe PHP - Guide Complet

## Problème SSL avec Composer

Si vous rencontrez une erreur SSL avec Composer (erreur 60), voici plusieurs solutions :

## Solution 1 : Configurer le certificat CA (Recommandé)

1. Téléchargez le certificat CA depuis https://curl.se/ca/cacert.pem
2. Placez-le dans un dossier accessible (ex: `C:\php\extras\ssl\cacert.pem`)
3. Configurez Composer :
```bash
composer config -g cafile "C:\php\extras\ssl\cacert.pem"
composer update stripe/stripe-php
```

## Solution 2 : Désactiver temporairement la vérification SSL

⚠️ **ATTENTION** : Cette méthode est moins sécurisée, utilisez-la uniquement en développement local.

```bash
cd backend
composer config -g secure-http false
composer update stripe/stripe-php
composer config -g secure-http true
```

## Solution 3 : Installation manuelle

Si Composer ne fonctionne toujours pas, vous pouvez installer Stripe manuellement :

1. Téléchargez Stripe PHP depuis : https://github.com/stripe/stripe-php/releases
2. Extrayez l'archive dans `backend/vendor/stripe/stripe-php/`
3. Assurez-vous que le fichier `vendor/stripe/stripe-php/init.php` existe

## Solution 4 : Utiliser un proxy ou VPN

Si le problème vient d'un pare-feu (comme Avast), vous pouvez :
- Désactiver temporairement le pare-feu
- Utiliser un VPN
- Configurer Composer pour utiliser un proxy

## Vérification de l'installation

Après l'installation, vérifiez que Stripe est bien installé :

```bash
cd backend
php -r "require 'vendor/autoload.php'; echo class_exists('Stripe\Stripe') ? 'Stripe installé ✓' : 'Stripe non installé ✗';"
```

## Après l'installation

1. Redémarrez votre serveur Symfony si nécessaire
2. Testez l'endpoint Stripe :
```bash
curl -X POST http://127.0.0.1:8000/api/stripe/create-payment-intent \
  -H "Content-Type: application/json" \
  -d '{"amount": 100}'
```

## Note importante

La dépendance `stripe/stripe-php` a déjà été ajoutée dans `composer.json`. 
Il suffit maintenant d'exécuter `composer update` pour l'installer.

