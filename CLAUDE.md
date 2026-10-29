# CLAUDE.md — Al3DPrint Project Reference

## Vue d'ensemble

**Al3DPrint** est une plateforme e-commerce full-stack pour des services d'impression 3D.
Design futuriste : dark theme + couleurs néon (cyan `#00d4ff`, mint `#4ecdc4`).

---

## Architecture

```
Al3DPrint - New version/
├── frontend/          # Vue.js 3 SPA (port 5173)
├── backend/           # Symfony 6 REST API (port 8000)
├── Sql.sql            # Schéma base de données
└── CLAUDE.md          # Ce fichier
```

---

## Stack technique

### Frontend
- **Vue.js 3** (Composition API) + **Vite 7**
- **Pinia** (state management) — stores: `auth.js`, `cart.js`
- **Vue Router 4** avec guards (`requiresAuth`, `requiresAdmin`)
- **Stripe.js** (paiements) + **Crypto-JS** (chiffrement côté client)
- Build output: `frontend/dist/`

### Backend
- **Symfony 6.4** + **PHP 8.1+**
- **Doctrine ORM 3** + **MySQL 8.0**
- **Symfony Mailer** via Resend SMTP (`smtp.resend.com`)
- **Stripe PHP SDK** pour les paiements
- URL de base API: `http://127.0.0.1:8000/api`

---

## Démarrage rapide

```bash
# Frontend
cd frontend
npm run dev        # http://localhost:5173
npm run build      # Génère dist/

# Backend
cd backend
symfony serve -d   # http://127.0.0.1:8000
composer install
php bin/console doctrine:migrations:migrate
```

---

## Base de données

```
URL: mysql://root:@127.0.0.1:3306/al3dprint
```

### Entités principales (16 au total)
| Entité | Description |
|---|---|
| `Produits` | Produits avec stock, prix, catégorie |
| `Categories` | Catégories de produits |
| `Clients` | Utilisateurs/clients (rôles: user/admin) |
| `Commandes` | Commandes avec statut, livraison, suivi |
| `DetailsCommande` | Lignes de commande (JSON personnalisation) |
| `VarianteCouleur` | Variants couleur par produit |
| `ImagesVarianteCouleur` | Images des variants |
| `ImagesProduit` | Galerie images produit |
| `AvisClients` | Avis/notes (1-5) |
| `OptionsPersonnalisation` | Options custom (text/choice/number/file) |
| `Paiements` | Paiements Stripe |
| `Impressions` | Suivi jobs d'impression 3D |
| `PanierItem` | Panier persistant en base |
| `ProduitPersonnalise` | Produits personnalisés |
| `BanqueImage` | Bibliothèque d'images |
| `ImagesProduitPersonnalise` | Images produits perso |

---

## API Endpoints

| Controller | Route | Méthodes |
|---|---|---|
| `CategoriesController` | `/api/categories` | GET, POST, PUT, DELETE |
| `ProduitsController` | `/api/produits` | GET, POST, PUT, DELETE |
| `ClientsController` | `/api/clients` | GET, POST (register), PUT, DELETE |
| `CommandesController` | `/api/commandes` | GET, POST, PUT (statut) |
| `CartController` | `/api/cart/{userId}` | GET, POST, DELETE |
| `PaiementsController` | `/api/paiements` | GET, POST |
| `StripeController` | `/api/stripe/*` | Webhooks Stripe |
| `AvisClientsController` | `/api/avis-clients` | GET, POST |
| `ImpressionsController` | `/api/impressions` | GET, POST |
| `ImagesProduitController` | `/api/images-produit` | GET, POST, DELETE |
| `OptionsPersonnalisationController` | `/api/options-personnalisation` | CRUD |
| `BanqueImageController` | `/api/banque-image` | Gestion fichiers |
| `ProduitPersonnaliseController` | `/api/produits-personnalises` | CRUD |

### Auth endpoints clés
- `POST /api/clients` → inscription
- `POST /api/clients/login` → connexion
- `GET /api/clients/{id}` → profil (auth requise)

---

## Authentification & Sécurité

- **Frontend**: token stocké dans localStorage
- **Backend**: classe de base `SecuredApiController` avec méthodes:
  - `requireAuth()` — utilisateurs connectés
  - `requireAdmin()` — admin seulement
  - `requireOwnerOrAdmin()` — propriétaire ou admin
- **Admin hardcodé côté frontend**:
  - Email: `AdminUper@gmail.com`
  - Password: `(voir gestionnaire de mots de passe)`
- **Détection admin**: basée sur l'email (pas seulement en base)
- CORS configuré pour les requêtes cross-origin
- Services: `TokenService.php`, `EncryptionService.php`, `EmailService.php`

---

## Structure frontend détaillée

### Views (`src/views/`)
- Pages publiques: `HomeView`, `ProductsView`, `ProductDetailView`, `AboutView`, `CustomProductsView`
- Pages user (auth): `ProfileView`, `CartView`, `CheckoutView`, `OrderDetailView`
- Auth: `LoginView`, `RegisterView`, `ForgotPasswordView`, `ResetPasswordView`
- Légal: `LegalView`, `PrivacyView`, `CgvView`

### Components (`src/components/`)
- Shared: `NavBar.vue`, `Footer.vue`, `LoginModal.vue`, `RegisterModal.vue`, `CartDrawer.vue`, `ProductCard.vue`, `Alert.vue`
- Admin (`components/admin/`): Dashboard, ProductsManagement, CategoriesManagement, UsersManagement, OrdersManagement, CustomProductsManagement, ImageBankManagement, StatisticsView

### Composables (`src/composables/`)
- `useSeo.js` — méta tags dynamiques, JSON-LD, canonical
- `useAlert.js` — système de notifications
- `useAuthFetch.js` — requêtes API avec token

---

## Variables d'environnement (backend/.env)

```env
APP_ENV=dev
DATABASE_URL=mysql://root:@127.0.0.1:3306/al3dprint?serverVersion=8.0
MAILER_DSN=smtps://resend:<clé Resend dans backend/.env.local>@smtp.resend.com
MAILER_FROM=noreply@al3dprint.fr
```

---

## Commandes Symfony utiles

```bash
php bin/console app:init-database               # Init BDD
php bin/console app:create-admin                # Créer admin
php bin/console doctrine:migrations:migrate     # Migrations
php bin/console doctrine:schema:validate        # Valider schéma
php bin/console app:fix-statut-enum            # Corriger enum statut
php bin/console app:migrate-encryption         # Migration chiffrement
```

---

## Fonctionnalités principales

- Catalogue produits avec filtres, recherche, variants couleur
- Panier persistant (localStorage + BDD)
- Checkout 3 étapes avec paiement Stripe
- Suivi de commandes + jobs d'impression 3D
- Avis/notes produits
- Personnalisation produits (options JSON flexibles)
- Panel admin complet (stats, CRUD tout)
- Notifications email (Resend)
- SEO optimisé par route (useSeo composable)
- Design responsive mobile-first

---

## Points importants à retenir

1. **CORS**: géré par `CorsController` + config Symfony — vérifier si problèmes de requêtes
2. **Admin fronted**: l'admin est détecté par email hardcodé, PAS uniquement par la BDD
3. **Cart**: double persistance — localStorage ET base de données (sync au login/logout)
4. **Images**: uploadées dans `backend/public/uploads/` — chemin à respecter
5. **Stripe**: webhooks sur `/api/stripe/*` — nécessite le secret webhook en production
6. **Emails**: via Resend SMTP — domaine vérifié `al3dprint.fr`
7. **Docker**: `compose.yaml` présent mais développement local principalement utilisé
8. **Migrations**: toujours utiliser `doctrine:migrations:migrate` et non `schema:update` en production

---

## Fichiers clés à connaître

| Fichier | Rôle |
|---|---|
| `frontend/src/main.js` | Point d'entrée Vue |
| `frontend/src/router/index.js` | Toutes les routes + guards |
| `frontend/src/stores/auth.js` | Auth state (login, admin detection) |
| `frontend/src/stores/cart.js` | Cart state + sync BDD |
| `backend/src/Controller/` | Tous les controllers API |
| `backend/src/Entity/` | Toutes les entités Doctrine |
| `backend/src/Service/TokenService.php` | Gestion tokens auth |
| `backend/.env` | Config BDD, mail, secrets |
| `Sql.sql` | Schéma SQL complet de référence |
