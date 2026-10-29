import { h } from 'vue'
import { createRouter, createWebHistory, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useSeo, BASE_URL, SITE_NAME } from '../composables/useSeo'
import HomeView from '../views/HomeView.vue'
import ProductsView from '../views/ProductsView.vue'
import CartView from '../views/CartView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import AboutView from '../views/AboutView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'
import ProfileView from '../views/ProfileView.vue'
import CheckoutView from '../views/CheckoutView.vue'
import AdminView from '../views/AdminView.vue'
import CustomProductsView from '../views/CustomProductsView.vue'
import OrderDetailView from '../views/OrderDetailView.vue'
import LegalView from '../views/LegalView.vue'
import PrivacyView from '../views/PrivacyView.vue'
import CgvView from '../views/CgvView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'smooth' }
  },
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: {
        title: 'Al3DPrint — Impression 3D Personnalisée',
        description: "Al3DPrint — Spécialiste de l'impression 3D personnalisée. Figurines, prototypes, objets décoratifs et bien plus. Commandez en ligne.",
        keywords: 'impression 3D, personnalisation, figurines, objets 3D, al3dprint'
      }
    },
    {
      path: '/products',
      name: 'products',
      component: ProductsView,
      meta: {
        title: 'Catalogue Produits',
        description: "Parcourez notre catalogue d'impressions 3D : figurines, décorations, prototypes et objets sur mesure. Livraison rapide en France.",
        keywords: 'catalogue impression 3D, acheter figurine 3D, objet personnalisé 3D'
      }
    },
    {
      path: '/product/:id',
      name: 'product-detail',
      component: ProductDetailView,
      props: true,
      // Le titre/desc sont mis à jour dynamiquement dans ProductDetailView via useSeo()
      meta: {
        title: 'Produit',
        description: "Détail du produit Al3DPrint. Impression 3D de qualité, livraison rapide.",
        keywords: 'impression 3D, produit personnalisé'
      }
    },
    {
      path: '/cart',
      name: 'cart',
      component: CartView,
      meta: {
        title: 'Mon Panier',
        description: "Votre panier Al3DPrint — vérifiez vos articles et finalisez votre commande d'impression 3D.",
        noindex: true
      }
    },
    {
      path: '/checkout',
      name: 'checkout',
      component: CheckoutView,
      meta: {
        requiresAuth: true,
        title: 'Finaliser la commande',
        description: "Finalisez votre commande d'impression 3D sur Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/about',
      name: 'about',
      component: AboutView,
      meta: {
        title: 'À propos',
        description: "Découvrez Al3DPrint : notre histoire, notre savoir-faire en impression 3D et notre engagement envers la qualité.",
        keywords: 'à propos al3dprint, impression 3D France, notre histoire'
      }
    },
    {
      path: '/custom-products',
      name: 'custom-products',
      component: CustomProductsView,
      meta: {
        title: 'Personnalisation 3D',
        description: "Créez vos objets 3D sur mesure avec Al3DPrint. Choisissez parmi nos modèles personnalisables ou envoyez votre propre design.",
        keywords: 'produit personnalisé 3D, objet sur mesure, impression 3D personnalisée'
      }
    },
    {
      path: '/order/:id',
      name: 'order-detail',
      component: OrderDetailView,
      meta: {
        title: 'Détail commande',
        description: "Suivi de votre commande Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: {
        title: 'Connexion',
        description: "Connectez-vous à votre compte Al3DPrint pour accéder à vos commandes et personnaliser votre expérience.",
        noindex: true
      }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: {
        title: 'Créer un compte',
        description: "Créez votre compte Al3DPrint et commencez à commander vos impressions 3D personnalisées.",
        noindex: true
      }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: ForgotPasswordView,
      meta: {
        title: 'Mot de passe oublié',
        description: "Réinitialisez votre mot de passe Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: {
        title: 'Réinitialiser le mot de passe',
        description: "Créez un nouveau mot de passe pour votre compte Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: {
        requiresAuth: true,
        title: 'Mon profil',
        description: "Gérez vos informations personnelles et vos commandes Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/search',
      name: 'search',
      component: ProductsView,
      meta: {
        title: 'Recherche',
        description: "Recherchez parmi nos produits d'impression 3D sur Al3DPrint.",
        keywords: 'recherche impression 3D, trouver objet 3D'
      }
    },
    {
      path: '/admin',
      name: 'admin',
      component: AdminView,
      meta: {
        requiresAdmin: true,
        title: 'Administration',
        description: "Interface d'administration Al3DPrint.",
        noindex: true
      }
    },
    {
      path: '/mentions-legales',
      name: 'mentions-legales',
      component: LegalView,
      meta: {
        title: 'Mentions légales',
        description: "Mentions légales du site Al3DPrint — éditeur, hébergeur et informations légales.",
        keywords: 'mentions légales al3dprint'
      }
    },
    {
      path: '/politique-confidentialite',
      name: 'politique-confidentialite',
      component: PrivacyView,
      meta: {
        title: 'Politique de confidentialité',
        description: "Politique de confidentialité d'Al3DPrint — comment nous utilisons et protégeons vos données personnelles.",
        keywords: 'politique confidentialité al3dprint, RGPD'
      }
    },
    {
      path: '/cgv',
      name: 'cgv',
      component: CgvView,
      meta: {
        title: 'Conditions Générales de Vente',
        description: "Conditions Générales de Vente d'Al3DPrint — modalités de commande, livraison et retour.",
        keywords: 'CGV al3dprint, conditions vente impression 3D'
      }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: {
        render: () => h('section', { class: 'container', style: 'padding:120px 0;text-align:center' }, [
          h('h1', 'Page introuvable'),
          h('p', "Cette page n'existe pas ou a été déplacée."),
          h(RouterLink, { to: '/', class: 'btn btn-primary' }, () => "Retour à l'accueil")
        ])
      },
      meta: { title: 'Page introuvable', noindex: true }
    }
  ]
})

// ── Garde d'authentification ─────────────────────────────────────────────────
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { returnTo: to.fullPath } })
  } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'home' })
  } else {
    next()
  }
})

// ── SEO automatique après chaque navigation ──────────────────────────────────
router.afterEach((to) => {
  const meta = to.meta || {}

  // Les pages produit mettent à jour le SEO elles-mêmes (titre dynamique + JSON-LD)
  if (to.name === 'product-detail') return

  useSeo({
    title: meta.title || SITE_NAME,
    description: meta.description,
    keywords: meta.keywords || null,
    url: `${BASE_URL}${to.path}`,
    noindex: meta.noindex || false
  })
})

export default router
