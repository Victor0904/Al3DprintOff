<template>
  <nav class="navbar">
    <div class="container">
      <div class="nav-content">

        <!-- Logo -->
        <router-link to="/" class="logo" @click="closeMobileMenu">
          <img :src="logoImage" alt="Al3DPrint" class="logo-image" />
          <span class="logo-text">Al3DPrint</span>
        </router-link>

        <!-- Liens desktop -->
        <div class="nav-links">
          <router-link to="/" class="nav-link">Accueil</router-link>
          <router-link to="/products" class="nav-link">Produits</router-link>
          <router-link to="/custom-products" class="nav-link">Personnalisés</router-link>
          <router-link v-if="isAdmin" to="/admin" class="admin-nav-link">🔐 Admin</router-link>
        </div>

        <!-- Actions -->
        <div class="nav-actions">

          <!-- Panier (toujours visible) -->
          <div class="cart-container">
            <button type="button" class="cart-btn" @click="showCartDrawer = true" aria-label="Panier">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
              </svg>
              <span v-if="cartItemCount > 0" class="cart-badge">{{ cartItemCount }}</span>
              <div v-if="isGuestCart && cartItemCount > 0" class="guest-dot" title="Panier invité"></div>
            </button>
            <transition name="notif">
              <div v-if="cartStore.showNotification" class="cart-notif">✅ Ajouté</div>
            </transition>
          </div>

          <!-- Zone auth (desktop uniquement) -->
          <div class="desktop-auth">
            <div v-if="!isLoggedIn" class="auth-buttons">
              <button class="btn btn-secondary" @click="showLogin = true">Connexion</button>
              <button class="btn btn-primary" @click="showRegister = true">Inscription</button>
            </div>
            <div v-else class="user-section">
              <transition name="welcome-anim">
                <div v-if="showWelcomeMessage" class="welcome-message">
                  <span class="welcome-text">Bienvenue {{ user?.nom }} ! 👋</span>
                  <button class="close-welcome" @click="hideWelcomeMessage">×</button>
                </div>
              </transition>
              <span v-if="!showWelcomeMessage && user?.nom" class="greeting-chip">{{ user.nom }}</span>
              <div class="user-dropdown">
                <button class="avatar-btn" @click="toggleDropdown" aria-label="Menu utilisateur">
                  <Icon name="user" :size="20" class="avatar-icon" />
                </button>
                <transition name="dropdown-anim">
                  <div v-if="showUserDropdown" class="dropdown-menu">
                    <router-link to="/profile" class="dropdown-item" @click="showUserDropdown = false">Mon profil</router-link>
                    <button class="dropdown-item" @click="logout">Déconnexion</button>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Hamburger (mobile uniquement) -->
          <button
            class="hamburger"
            :class="{ 'is-open': showMobileMenu }"
            @click="toggleMobileMenu"
            aria-label="Menu"
          >
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <transition name="mobile-slide">
      <div v-if="showMobileMenu" class="mobile-menu">
        <div class="mobile-inner">

          <!-- Navigation -->
          <nav class="mobile-nav">
            <router-link to="/" class="m-link" @click="closeMobileMenu">
              <span class="m-link-icon">⌂</span> Accueil
            </router-link>
            <router-link to="/products" class="m-link" @click="closeMobileMenu">
              <span class="m-link-icon">◈</span> Produits
            </router-link>
            <router-link to="/custom-products" class="m-link" @click="closeMobileMenu">
              <span class="m-link-icon">✦</span> Personnalisés
            </router-link>
            <router-link v-if="isAdmin" to="/admin" class="m-link m-admin" @click="closeMobileMenu">
              <span class="m-link-icon">🔐</span> Admin
            </router-link>
          </nav>

          <div class="m-divider"></div>

          <!-- Auth si non connecté -->
          <div v-if="!isLoggedIn" class="m-auth">
            <button class="btn btn-secondary m-btn" @click="showLogin = true; closeMobileMenu()">Connexion</button>
            <button class="btn btn-primary m-btn" @click="showRegister = true; closeMobileMenu()">Inscription</button>
          </div>

          <!-- User si connecté -->
          <div v-else class="m-user">
            <div class="m-user-greeting">
              <Icon name="user" :size="22" class="m-avatar" />
              <div>
                <p class="m-user-name">{{ user?.nom }}</p>
                <p class="m-user-sub">Mon compte</p>
              </div>
            </div>
            <router-link to="/profile" class="m-user-link" @click="closeMobileMenu">
              Mon profil
            </router-link>
            <button class="m-logout-btn" @click="mobileLogout">
              Déconnexion
            </button>
          </div>

        </div>
      </div>
    </transition>

    <!-- Modales -->
    <LoginModal    v-if="showLogin"    @close="showLogin = false"    @login="handleLogin"    />
    <RegisterModal v-if="showRegister" @close="showRegister = false" @register="handleRegister" />
    <CartDrawer    :visible="showCartDrawer"                          @close="showCartDrawer = false" />
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import LoginModal from './LoginModal.vue'
import RegisterModal from './RegisterModal.vue'
import CartDrawer from './CartDrawer.vue'
import Icon from './Icon.vue'
import logoImage from '../assets/logo/logo.png'

const router = useRouter()
const route  = useRoute()
const cartStore  = useCartStore()
const authStore  = useAuthStore()

const showMobileMenu    = ref(false)
const showUserDropdown  = ref(false)
const showLogin         = ref(false)
const showRegister      = ref(false)
const showWelcomeMessage = ref(false)
const showCartDrawer    = ref(false)

const cartItemCount = computed(() => cartStore.totalItems)
const isGuestCart   = computed(() => cartStore.isGuestCart)
const isLoggedIn    = computed(() => authStore.isAuthenticated)
const isAdmin       = computed(() => authStore.isAdmin)
const user          = computed(() => authStore.user)

// Ferme le menu mobile lors d'un changement de route
watch(() => route.path, () => closeMobileMenu())

const toggleMobileMenu = () => { showMobileMenu.value = !showMobileMenu.value }
const closeMobileMenu  = () => { showMobileMenu.value = false }
const toggleDropdown   = () => { showUserDropdown.value = !showUserDropdown.value }

const mobileLogout = () => {
  closeMobileMenu()
  logout()
}

const handleLogin = () => {
  showLogin.value = false
  setTimeout(() => {
    showWelcomeMessage.value = true
    setTimeout(() => { showWelcomeMessage.value = false }, 5000)
  }, 100)
}

const handleRegister = () => {
  showRegister.value = false
  setTimeout(() => {
    showWelcomeMessage.value = true
    setTimeout(() => { showWelcomeMessage.value = false }, 5000)
  }, 100)
}

const hideWelcomeMessage = () => { showWelcomeMessage.value = false }

const logout = () => {
  authStore.logout()
  showUserDropdown.value = false
  showWelcomeMessage.value = false
  router.push('/')
}

const handleClickOutside = (e) => {
  if (!e.target.closest('.user-dropdown')) showUserDropdown.value = false
  if (!e.target.closest('.navbar') && showMobileMenu.value) closeMobileMenu()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  if (isLoggedIn.value && user.value?.nom) {
    showWelcomeMessage.value = true
    setTimeout(() => { showWelcomeMessage.value = false }, 5000)
  }
})
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────────────────── */
.navbar {
  background: rgba(5, 5, 5, 0.92);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.10);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 62px;
}

/* ── Logo ──────────────────────────────────────────────────────── */
.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex-shrink: 0;
}
.logo-image {
  height: 36px;
  width: auto;
  object-fit: contain;
}
.logo-text {
  font-size: 22px;
  font-weight: 700;
  color: var(--primary-color);
  letter-spacing: -0.02em;
}

/* ── Liens desktop ─────────────────────────────────────────────── */
.nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav-link {
  color: rgba(255, 255, 255, 0.75);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  transition: color 0.2s;
  position: relative;
  padding-bottom: 2px;
}
.nav-link::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--primary-color);
  transition: width 0.25s ease;
}
.nav-link:hover,
.nav-link.router-link-active {
  color: var(--primary-color);
}
.nav-link:hover::after,
.nav-link.router-link-active::after {
  width: 100%;
}

.admin-nav-link {
  background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
  color: #fff !important;
  padding: 7px 14px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}
.admin-nav-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255,107,107,0.4);
}

/* ── Actions ───────────────────────────────────────────────────── */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

/* ── Panier ────────────────────────────────────────────────────── */
.cart-container {
  position: relative;
}
.cart-btn {
  position: relative;
  background: none;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 6px;
  border-radius: 8px;
  transition: color 0.2s, background 0.2s;
}
.cart-btn:hover {
  color: var(--primary-color);
  background: rgba(208,255,89,0.07);
}
.cart-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--primary-color);
  color: #050505;
  border-radius: 999px;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  padding: 0 4px;
}
.guest-dot {
  position: absolute;
  top: -4px;
  left: -4px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent-color);
  border: 2px solid #050505;
}
.cart-notif {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: #050505;
  border: 1px solid rgba(208,255,89,0.4);
  color: var(--primary-color);
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  z-index: 100;
}
.notif-enter-active { transition: all 0.25s ease; }
.notif-leave-active { transition: all 0.2s ease; }
.notif-enter-from, .notif-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── Desktop auth ──────────────────────────────────────────────── */
.desktop-auth { display: flex; align-items: center; }

.auth-buttons { display: flex; gap: 10px; }

.user-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.welcome-message {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(208,255,89,0.1);
  border: 1px solid rgba(208,255,89,0.3);
  border-radius: 999px;
  padding: 6px 14px;
  font-size: 13px;
}
.welcome-text { color: var(--primary-color); white-space: nowrap; }
.close-welcome {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 16px;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}
.close-welcome:hover { color: var(--primary-color); }

.welcome-anim-enter-active { transition: all 0.3s ease; }
.welcome-anim-leave-active { transition: all 0.25s ease; }
.welcome-anim-enter-from, .welcome-anim-leave-to { opacity: 0; transform: scale(0.95); }

.greeting-chip {
  font-size: 13px;
  color: var(--primary-color);
  background: rgba(208,255,89,0.08);
  border: 1px solid rgba(208,255,89,0.2);
  border-radius: 999px;
  padding: 5px 12px;
  white-space: nowrap;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-dropdown { position: relative; }
.avatar-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 7px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  transition: background 0.2s;
}
.avatar-btn:hover { background: rgba(255,255,255,0.08); }
.avatar-icon { color: var(--text-primary); }

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #0f0f0f;
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 10px;
  padding: 6px 0;
  min-width: 155px;
  box-shadow: 0 16px 40px rgba(0,0,0,0.8);
  z-index: 200;
}
.dropdown-item {
  display: block;
  padding: 10px 18px;
  font-size: 14px;
  color: var(--text-primary);
  text-decoration: none;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.dropdown-item:hover { background: rgba(208,255,89,0.07); color: var(--primary-color); }

.dropdown-anim-enter-active { transition: all 0.18s ease; }
.dropdown-anim-leave-active { transition: all 0.15s ease; }
.dropdown-anim-enter-from, .dropdown-anim-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── Hamburger ─────────────────────────────────────────────────── */
.hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  gap: 0;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  transition: background 0.2s;
}
.hamburger:hover { background: rgba(255,255,255,0.07); }
.hamburger span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--text-primary);
  margin: 4px auto 0;
  border-radius: 2px;
  transition: transform 0.3s ease, opacity 0.3s ease, background 0.2s;
  transform-origin: center;
}
.hamburger span:first-child { margin-top: 0; }
.hamburger.is-open span:nth-child(1) { transform: translateY(10px) rotate(45deg); background: var(--primary-color); }
.hamburger.is-open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.is-open span:nth-child(3) { transform: translateY(-10px) rotate(-45deg); background: var(--primary-color); }

/* ── Mobile menu ───────────────────────────────────────────────── */
.mobile-menu {
  background: rgba(5, 5, 5, 0.98);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border-top: 1px solid rgba(255,255,255,0.08);
  border-bottom: 1px solid rgba(208,255,89,0.12);
}
.mobile-inner {
  padding: 20px 0 24px;
}

.mobile-nav {
  display: flex;
  flex-direction: column;
}
.m-link {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  color: rgba(255,255,255,0.80);
  text-decoration: none;
  font-size: 16px;
  font-weight: 500;
  border-left: 3px solid transparent;
  transition: all 0.2s;
}
.m-link:hover,
.m-link.router-link-active {
  color: var(--primary-color);
  background: rgba(208,255,89,0.05);
  border-left-color: var(--primary-color);
}
.m-link-icon {
  font-size: 14px;
  width: 18px;
  text-align: center;
  opacity: 0.6;
}
.m-admin {
  color: #ff6b6b !important;
  border-left-color: transparent;
}
.m-admin:hover { background: rgba(255,107,107,0.06); border-left-color: #ff6b6b; }

.m-divider {
  height: 1px;
  background: rgba(255,255,255,0.08);
  margin: 16px 20px;
}

.m-auth {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 0 20px;
}
.m-btn { width: 100%; justify-content: center; }

.m-user {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.m-user-greeting {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: rgba(208,255,89,0.06);
  border: 1px solid rgba(208,255,89,0.14);
  border-radius: 10px;
  margin-bottom: 8px;
}
.m-avatar { color: var(--primary-color); }
.m-user-name { font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 0; }
.m-user-sub  { font-size: 12px; color: var(--text-secondary); margin: 0; }

.m-user-link {
  display: block;
  padding: 13px 14px;
  font-size: 15px;
  color: var(--text-primary);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s;
}
.m-user-link:hover { background: rgba(255,255,255,0.05); color: var(--primary-color); }

.m-logout-btn {
  display: block;
  width: 100%;
  text-align: left;
  padding: 13px 14px;
  font-size: 15px;
  color: rgba(255,107,107,0.85);
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.m-logout-btn:hover { background: rgba(255,107,107,0.07); color: #ff6b6b; }

/* Animation menu mobile */
.mobile-slide-enter-active { animation: mSlide 0.28s ease; }
.mobile-slide-leave-active { animation: mSlide 0.22s ease reverse; }
@keyframes mSlide {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0);     }
}

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .nav-content { height: 56px; }
  .logo-image  { height: 30px; }
  .logo-text   { font-size: 19px; }

  /* Masquer les liens et l'auth desktop */
  .nav-links    { display: none; }
  .desktop-auth { display: none; }

  /* Afficher le hamburger */
  .hamburger    { display: flex; }

  .nav-actions { gap: 6px; }
}

@media (max-width: 480px) {
  .logo-text  { font-size: 17px; }
  .logo-image { height: 26px; }
}
</style>
