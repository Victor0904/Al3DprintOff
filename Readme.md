# 🔮 Al3DPrint - Site de Vente de Produits 3D

Un site web futuriste et moderne pour la vente de produits imprimés en 3D, développé avec Vue.js 3 et Symfony 6.

## ✨ Fonctionnalités

### 🎨 Design Futuriste
- **Thème sombre** avec des couleurs néon (bleu cyan, vert menthe)
- **Animations fluides** et effets de particules
- **Interface responsive** adaptée à tous les écrans
- **Design moderne** avec des cartes glassmorphism

### 🛍️ E-commerce Complet
- **Catalogue de produits** avec filtres et recherche
- **Gestion du panier** avec persistance locale
- **Pages de détail** des produits
- **Processus de commande** en 3 étapes
- **Gestion des catégories**

### 👤 Système d'Authentification
- **Inscription/Connexion** avec validation
- **Profil utilisateur** personnalisé
- **Gestion des commandes** dans le profil
- **Sécurité** avec gardes de navigation

### 🔐 Interface d'Administration
- **Connexion admin locale** (sans base de données)
- **Identifiants admin** : `AdminUper@gmail.com` / `(voir gestionnaire de mots de passe)`
- **Gestion complète** : produits, catégories, utilisateurs, commandes
- **Tableau de bord** avec statistiques
- **Design futuriste** cohérent avec le site

### 🔧 Backend API
- **API REST** complète avec Symfony 6
- **Base de données** SQLite avec Doctrine ORM
- **CORS** configuré pour le frontend
- **Gestion des entités** (Produits, Catégories, Clients, etc.)

## 🚀 Technologies Utilisées

### Frontend
- **Vue.js 3** avec Composition API
- **Vue Router** pour la navigation
- **Pinia** pour la gestion d'état
- **CSS3** avec variables personnalisées
- **Vite** pour le build et le développement

### Backend
- **Symfony 6** framework PHP
- **Doctrine ORM** pour la base de données
- **SQLite** base de données
- **API REST** avec contrôleurs dédiés

## 📁 Structure du Projet

```
Al3DPrint/
├── frontend/                 # Application Vue.js
│   ├── src/
│   │   ├── components/       # Composants réutilisables
│   │   │   ├── admin/        # Composants d'administration
│   │   │   ├── NavBar.vue    # Barre de navigation
│   │   │   ├── Footer.vue    # Pied de page
│   │   │   ├── LoginModal.vue # Modale de connexion
│   │   │   └── RegisterModal.vue # Modale d'inscription
│   │   ├── views/           # Pages principales
│   │   │   ├── AdminView.vue # Interface d'administration
│   │   │   ├── HomeView.vue  # Page d'accueil
│   │   │   ├── ProductsView.vue # Catalogue produits
│   │   │   └── ...
│   │   ├── stores/          # Stores Pinia
│   │   │   ├── auth.js      # Authentification (admin + users)
│   │   │   └── cart.js      # Gestion du panier
│   │   ├── router/          # Configuration du routage
│   │   └── App.vue          # Composant principal
│   └── package.json
├── backend/                  # API Symfony
│   ├── src/
│   │   ├── Controller/      # Contrôleurs API
│   │   ├── Entity/         # Entités Doctrine
│   │   ├── EventListener/   # Listeners personnalisés
│   │   └── Command/         # Commandes console
│   ├── config/              # Configuration Symfony
│   └── composer.json
└── README.md
```

## 🛠️ Installation et Démarrage

### Prérequis
- **Node.js** 20+ et npm
- **PHP** 8.1+ et Composer
- **Symfony CLI** (optionnel)

### Backend (API)
```bash
cd backend
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
symfony serve -d
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## 🌐 Accès aux Applications

- **Frontend** : http://localhost:5173
- **Backend API** : http://127.0.0.1:8000/api
- **Interface de test API** : http://127.0.0.1:8000/test-api.html

## 🔐 Accès Administrateur

### Connexion Admin
- **Email** : `AdminUper@gmail.com`
- **Mot de passe** : `(voir gestionnaire de mots de passe)`
- **Type** : Connexion locale (pas de base de données requise)

### Fonctionnalités Admin
- **Tableau de bord** avec statistiques
- **Gestion des produits** (CRUD complet)
- **Gestion des catégories**
- **Gestion des utilisateurs**
- **Gestion des commandes**
- **Statistiques avancées**

## 📱 Pages Disponibles

### Pages Publiques
- **Accueil** (`/`) - Hero section avec produits populaires
- **Produits** (`/products`) - Catalogue avec filtres
- **Détail produit** (`/product/:id`) - Informations complètes
- **À propos** (`/about`) - Présentation de l'entreprise
- **Connexion** (`/login`) - Authentification
- **Inscription** (`/register`) - Création de compte

### Pages Authentifiées
- **Profil** (`/profile`) - Informations utilisateur
- **Panier** (`/cart`) - Gestion du panier
- **Commande** (`/checkout`) - Processus de commande

### Pages Administrateur
- **Admin** (`/admin`) - Interface d'administration complète

## 🎨 Palette de Couleurs

```css
:root {
  --primary-color: #00d4ff;      /* Bleu cyan */
  --secondary-color: #ff6b6b;    /* Rouge coral */
  --accent-color: #4ecdc4;       /* Vert menthe */
  --dark-bg: #0a0a0a;           /* Noir profond */
  --dark-secondary: #1a1a2e;    /* Bleu très sombre */
  --dark-tertiary: #16213e;     /* Bleu sombre */
  --text-primary: #e0e0e0;      /* Blanc cassé */
  --text-secondary: #a0a0a0;    /* Gris clair */
}
```

## 🔧 Fonctionnalités Techniques

### Gestion d'État (Pinia)
- **Auth Store** : Authentification unifiée (admin + users)
- **Cart Store** : Panier avec persistance localStorage
- **Réactivité** complète avec Vue 3

### API Backend
- **Endpoints REST** : GET, POST, PUT, DELETE
- **CORS** configuré pour toutes les requêtes
- **Validation** des données d'entrée
- **Gestion d'erreurs** avec try/catch

### Authentification Admin
- **Connexion locale** : Pas de dépendance à la base de données
- **Détection automatique** : Rôle admin détecté par l'email
- **Interface dynamique** : Bouton Admin apparaît/disparaît selon le rôle
- **Sécurité** : Identifiants hardcodés dans le code frontend

### Responsive Design
- **Mobile First** approche
- **Breakpoints** : 768px, 1024px
- **Grilles flexibles** avec CSS Grid
- **Navigation mobile** avec menu hamburger

## 🚀 Déploiement

### Frontend (Vercel/Netlify)
```bash
npm run build
# Déployer le dossier dist/
```

### Backend (Heroku/DigitalOcean)
```bash
composer install --no-dev --optimize-autoloader
# Configurer les variables d'environnement
```

## 📝 Notes de Développement

- **CORS** : Configuré pour accepter toutes les origines en développement
- **Base de données** : SQLite pour la simplicité (peut être changé en production)
- **Authentification** : Système unifié avec connexion admin locale
- **Paiement** : Interface simulée (intégrer Stripe/PayPal en production)

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -m 'Ajouter nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

**Développé avec ❤️ pour l'avenir de l'impression 3D** 🔮✨