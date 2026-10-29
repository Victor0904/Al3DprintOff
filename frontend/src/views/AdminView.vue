<template>
  <div class="admin-dashboard">
    <!-- Header Admin -->
    <header class="admin-header">
      <div class="admin-header-content">
        <div class="admin-logo">
          <h1>🔐 Admin Panel</h1>
          <span class="admin-subtitle">Al3DPrint Administration</span>
        </div>
        <div class="admin-user">
          <span class="welcome-text">Bienvenue, {{ authStore.user?.nom }}</span>
          <button @click="logout" class="logout-btn">Déconnexion</button>
        </div>
      </div>
    </header>

    <!-- Navigation Admin -->
    <nav class="admin-nav">
      <div class="nav-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id; if (tab.id === 'orders') fetchPendingOrders()"
          :class="['nav-tab', { active: activeTab === tab.id }]"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
          <span v-if="tab.id === 'orders' && pendingOrdersCount > 0" class="orders-badge">
            {{ pendingOrdersCount > 99 ? '99+' : pendingOrdersCount }}
          </span>
        </button>
      </div>
    </nav>

    <!-- Contenu Principal -->
    <main class="admin-main">
      <!-- Dashboard -->
      <div v-if="activeTab === 'dashboard'" class="tab-content">
        <DashboardView @changeTab="activeTab = $event" />
      </div>

      <!-- Gestion des Produits -->
      <div v-if="activeTab === 'products'" class="tab-content">
        <ProductsManagement />
      </div>

      <!-- Gestion des Produits Personnalisés -->
      <div v-if="activeTab === 'custom-products'" class="tab-content">
        <CustomProductsManagement />
      </div>

      <!-- Gestion des Catégories -->
      <div v-if="activeTab === 'categories'" class="tab-content">
        <CategoriesManagement />
      </div>

      <!-- Gestion des Utilisateurs -->
      <div v-if="activeTab === 'users'" class="tab-content">
        <UsersManagement />
      </div>

      <!-- Commandes -->
      <div v-if="activeTab === 'orders'" class="tab-content">
        <OrdersManagement />
      </div>

      <!-- Codes Promo -->
      <div v-if="activeTab === 'promo'" class="tab-content">
        <CodePromoManagement />
      </div>

      <!-- Demandes personnalisation -->
      <div v-if="activeTab === 'demandes'" class="tab-content">
        <DemandesPersonnalisationManagement />
      </div>

      <!-- Statistiques -->
      <div v-if="activeTab === 'stats'" class="tab-content">
        <StatisticsView />
      </div>

      <!-- Banque d'Images -->
      <div v-if="activeTab === 'image-bank'" class="tab-content">
        <ImageBankManagement />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAuthFetch } from '../composables/useAuthFetch'
import DashboardView from '../components/admin/DashboardView.vue'
import ProductsManagement from '../components/admin/ProductsManagement.vue'
import CustomProductsManagement from '../components/admin/CustomProductsManagement.vue'
import CategoriesManagement from '../components/admin/CategoriesManagement.vue'
import UsersManagement from '../components/admin/UsersManagement.vue'
import OrdersManagement from '../components/admin/OrdersManagement.vue'
import StatisticsView from '../components/admin/StatisticsView.vue'
import ImageBankManagement from '../components/admin/ImageBankManagement.vue'
import CodePromoManagement from '../components/admin/CodePromoManagement.vue'
import DemandesPersonnalisationManagement from '../components/admin/DemandesPersonnalisationManagement.vue'

const router    = useRouter()
const authStore = useAuthStore()
const { apiFetch } = useAuthFetch()

const activeTab          = ref('dashboard')
const pendingOrdersCount = ref(0)
let pollInterval         = null

const fetchPendingOrders = async () => {
  try {
    const res = await apiFetch('/api/commandes?statut=En+attente&limit=100')
    if (res.ok) {
      const data = await res.json()
      pendingOrdersCount.value = data.length
    }
  } catch {
    // silently ignore
  }
}

const tabs = [
  { id: 'dashboard', label: 'Tableau de bord', icon: '📊' },
  { id: 'products', label: 'Produits', icon: '🛍️' },
  { id: 'custom-products', label: 'Produits Personnalisés', icon: '🎨' },
  { id: 'categories', label: 'Catégories', icon: '📁' },
  { id: 'image-bank', label: 'Banque d\'Images', icon: '🖼️' },
  { id: 'users', label: 'Utilisateurs', icon: '👥' },
  { id: 'orders', label: 'Commandes', icon: '📦' },
  { id: 'promo', label: 'Codes Promo', icon: '🎟️' },
  { id: 'demandes', label: 'Demandes perso', icon: '🎨' },
  { id: 'stats', label: 'Statistiques', icon: '📈' }
]

const logout = () => {
  authStore.logout()
  router.push('/')
}

onMounted(() => {
  fetchPendingOrders()
  // Rafraîchit le compteur toutes les 60 secondes
  pollInterval = setInterval(fetchPendingOrders, 60_000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.admin-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a0a0a 0%, var(--surface) 50%, var(--surface) 100%);
  color: #e0e0e0;
}

.admin-header {
  background: rgba(10, 10, 10, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 2px solid var(--primary-color);
  padding: 20px 0;
  position: sticky;
  top: 0;
  z-index: 1000;
}

.admin-header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.admin-logo h1 {
  font-size: 28px;
  color: var(--primary-color);
  margin: 0;
  text-shadow: 0 0 15px rgba(208, 255, 89, 0.5);
}

.admin-subtitle {
  color: var(--accent-color);
  font-size: 14px;
  font-weight: 500;
}

.admin-user {
  display: flex;
  align-items: center;
  gap: 20px;
}

.welcome-text {
  color: #e0e0e0;
  font-weight: 500;
}

.logout-btn {
  background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
}

.admin-nav {
  background: rgba(20, 20, 20, 0.8);
  border-bottom: 1px solid var(--surface-2);
  padding: 0;
}

.nav-tabs {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  gap: 0;
}

.nav-tab {
  background: none;
  border: none;
  color: #a0a0a0;
  padding: 20px 30px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 500;
  transition: all 0.3s ease;
  border-bottom: 3px solid transparent;
}

.nav-tab:hover {
  color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
}

.nav-tab.active {
  color: var(--primary-color);
  background: rgba(208, 255, 89, 0.15);
  border-bottom-color: var(--primary-color);
}

.tab-icon {
  font-size: 18px;
}

.orders-badge {
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  background: #ff6b6b;
  color: #fff;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: badgePulse 2s ease-in-out infinite;
  box-shadow: 0 0 8px rgba(255, 107, 107, 0.6);
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); box-shadow: 0 0 8px rgba(255, 107, 107, 0.6); }
  50%       { transform: scale(1.15); box-shadow: 0 0 14px rgba(255, 107, 107, 0.9); }
}

.admin-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 30px 20px;
}

.tab-content {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .admin-header {
    padding: 15px 0;
  }
  
  .admin-header-content {
    flex-direction: column;
    gap: 15px;
    text-align: center;
    padding: 0 15px;
  }
  
  .admin-logo h1 {
    font-size: 22px;
  }
  
  .admin-subtitle {
    font-size: 12px;
  }
  
  .admin-user {
    flex-direction: column;
    gap: 10px;
    width: 100%;
  }
  
  .welcome-text {
    font-size: 14px;
  }
  
  .logout-btn {
    padding: 8px 16px;
    font-size: 14px;
    width: 100%;
  }
  
  .nav-tabs {
    flex-wrap: wrap;
    justify-content: center;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .nav-tab {
    padding: 12px 15px;
    font-size: 13px;
    min-width: auto;
    flex: 1 1 auto;
  }
  
  .tab-icon {
    font-size: 16px;
  }
  
  .tab-label {
    font-size: 12px;
  }
  
  .admin-main {
    padding: 20px 10px;
  }
}

@media (max-width: 480px) {
  .admin-header {
    padding: 12px 0;
  }
  
  .admin-header-content {
    padding: 0 10px;
  }
  
  .admin-logo h1 {
    font-size: 18px;
  }
  
  .admin-subtitle {
    font-size: 11px;
  }
  
  .welcome-text {
    font-size: 12px;
  }
  
  .logout-btn {
    padding: 6px 12px;
    font-size: 12px;
  }
  
  .nav-tabs {
    gap: 0;
  }
  
  .nav-tab {
    padding: 10px 8px;
    font-size: 11px;
    flex: 1 1 50%;
  }
  
  .tab-icon {
    font-size: 14px;
  }
  
  .tab-label {
    font-size: 11px;
  }
  
  .admin-main {
    padding: 15px 8px;
  }
}
</style>
