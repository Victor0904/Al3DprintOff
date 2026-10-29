# Configuration de Symfony Mailer

## Installation des dépendances

Exécutez la commande suivante dans le dossier `backend` :

```bash
composer require symfony/mailer symfony/twig-bridge twig/twig
```

## Configuration

### 1. Configuration du mailer dans `.env`

Ajoutez la variable d'environnement `MAILER_DSN` dans votre fichier `.env` :

#### Pour les tests (MailHog ou MailCatcher)
```env
MAILER_DSN=smtp://localhost:1025
```

#### Pour Gmail (avec mot de passe d'application)
```env
MAILER_DSN=gmail://USERNAME:PASSWORD@default
```

#### Pour un serveur SMTP personnalisé
```env
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

#### Pour les tests locaux (null://)
```env
MAILER_DSN=null://null
```

### 2. Configuration de l'email expéditeur

Dans `src/Service/EmailService.php`, vous pouvez modifier l'email expéditeur :

```php
private string $fromEmail = 'noreply@al3dprint.com'
```

### 3. Migration de la base de données

Exécutez la migration pour ajouter les champs `reset_token` et `reset_token_expires_at` :

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Test de l'envoi d'emails

### Avec MailHog (recommandé pour le développement)

1. Installez MailHog : https://github.com/mailhog/MailHog
2. Démarrez MailHog
3. Configurez `MAILER_DSN=smtp://localhost:1025`
4. Accédez à http://localhost:8025 pour voir les emails envoyés

### Test avec l'email de test

L'email de test configuré est : **victor090498@gmail.com**

## Endpoints API

### Demander une réinitialisation
```
POST /api/clients/forgot-password
Body: { "email": "user@example.com" }
```

### Réinitialiser le mot de passe
```
POST /api/clients/reset-password
Body: { 
    "token": "token_from_email",
    "new_password": "nouveau_mot_de_passe"
}
```

