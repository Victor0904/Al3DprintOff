<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h2>📊 Tableau de Bord Administrateur</h2>
      <p>Vue d'ensemble de votre boutique Al3DPrint</p>
    </div>

    <!-- Statistiques Rapides -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">🛍️</div>
        <div class="stat-content">
          <h3>{{ stats.totalProducts }}</h3>
          <p>Produits</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
          <h3>{{ stats.totalUsers }}</h3>
          <p>Utilisateurs</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <h3>{{ stats.totalOrders }}</h3>
          <p>Commandes</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <h3>{{ formatRevenue(stats.totalRevenue) }}€</h3>
          <p>Chiffre d'affaires</p>
        </div>
      </div>
    </div>

    <!-- Actions Rapides -->
    <div class="quick-actions">
      <h3>⚡ Actions Rapides</h3>
      <div class="actions-grid">
        <button @click="goToTab('products')" class="action-btn">
          <span class="action-icon">➕</span>
          <span>Ajouter un produit</span>
        </button>
        
        <button @click="goToTab('categories')" class="action-btn">
          <span class="action-icon">📁</span>
          <span>Gérer les catégories</span>
        </button>
        
        <button @click="goToTab('orders')" class="action-btn">
          <span class="action-icon">📋</span>
          <span>Voir les commandes</span>
        </button>
        
        <button @click="goToTab('users')" class="action-btn">
          <span class="action-icon">👤</span>
          <span>Gérer les utilisateurs</span>
        </button>
      </div>
    </div>

    <!-- Produits Récents -->
    <div class="recent-section">
      <h3>🆕 Produits Récents</h3>
      <div class="recent-products">
        <div v-for="product in recentProducts" :key="product.id" class="product-item">
          <div class="product-image">
            <img :src="getImageUrl(product.image_principale) || '/placeholder-product.jpg'" :alt="product.nom">
          </div>
          <div class="product-info">
            <h4>{{ product.nom }}</h4>
            <p>{{ product.prix }}€</p>
            <span class="product-status" :class="product.actif ? 'active' : 'inactive'">
              {{ product.actif ? 'Actif' : 'Inactif' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Commandes Récentes -->
    <div class="recent-section">
      <h3>📦 Commandes Récentes</h3>
      <div class="recent-orders">
        <div v-for="order in recentOrders" :key="order.id" class="order-item">
          <div class="order-info">
            <h4>Commande #{{ order.id }}</h4>
            <p>{{ order.client_nom }} - {{ order.total }}€</p>
            <span class="order-status" :class="order.statut">{{ order.statut }}</span>
          </div>
          <div class="order-date">
            {{ formatDate(order.date_commande) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthFetch } from '../../composables/useAuthFetch'

const { apiFetch } = useAuthFetch()

const emit = defineEmits(['changeTab'])

const stats = ref({
  totalProducts: 0,
  totalUsers: 0,
  totalOrders: 0,
  totalRevenue: 0
})

const recentProducts = ref([])
const recentOrders = ref([])

const goToTab = (tabId) => {
  emit('changeTab', tabId)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatRevenue = (revenue) => {
  return parseFloat(revenue || 0).toFixed(2)
}

const getImageUrl = (chemin) => {
  if (!chemin) return ''
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) {
    return chemin
  }
  if (chemin.startsWith('/uploads/')) {
    return `${chemin}`
  }
  return chemin
}

const loadStats = async () => {
  try {
    const [productsRes, usersRes, ordersRes] = await Promise.all([
      fetch('/api/produits'),
      apiFetch('/api/clients'),
      apiFetch('/api/commandes?limit=10000')
    ])

    if (productsRes.ok) {
      const products = await productsRes.json()
      stats.value.totalProducts = products.length
      recentProducts.value = [...products].sort((a, b) => b.id - a.id).slice(0, 5)
    }

    if (usersRes.ok) {
      const users = await usersRes.json()
      stats.value.totalUsers = users.length
    }

    if (ordersRes.ok) {
      const orders = await ordersRes.json()
      stats.value.totalOrders = orders.length
      recentOrders.value = orders.slice(0, 5)

      // Seules les commandes encaissées comptent dans le chiffre d'affaires
      stats.value.totalRevenue = orders
        .filter(order => !['En attente', 'Annulée'].includes(order.statut))
        .reduce((sum, order) => sum + parseFloat(order.total || 0), 0)
    }
  } catch {
    // silently ignore
  }
}

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.dashboard-header {
  text-align: center;
  margin-bottom: 20px;
}

.dashboard-header h2 {
  font-size: 32px;
  color: var(--primary-color);
  margin: 0 0 10px 0;
  text-shadow: 0 0 15px rgba(208, 255, 89, 0.5);
}

.dashboard-header p {
  color: #a0a0a0;
  font-size: 16px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  padding: 25px;
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(208, 255, 89, 0.2);
  border-color: var(--primary-color);
}

.stat-icon {
  font-size: 40px;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(208, 255, 89, 0.1);
  border-radius: 50%;
}

.stat-content h3 {
  font-size: 28px;
  color: var(--primary-color);
  margin: 0;
  font-weight: bold;
}

.stat-content p {
  color: #a0a0a0;
  margin: 5px 0 0 0;
  font-size: 14px;
}

.quick-actions {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  padding: 25px;
}

.quick-actions h3 {
  color: var(--accent-color);
  margin: 0 0 20px 0;
  font-size: 20px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.action-btn {
  background: var(--primary-color);
  color: #000;
  border: none;
  border-radius: 10px;
  padding: 15px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(208, 255, 89, 0.4);
}

.action-icon {
  font-size: 18px;
}

.recent-section {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  padding: 25px;
}

.recent-section h3 {
  color: var(--accent-color);
  margin: 0 0 20px 0;
  font-size: 20px;
}

.recent-products, .recent-orders {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.product-item, .order-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: rgba(20, 20, 20, 0.5);
  border-radius: 10px;
  border: 1px solid var(--surface-2);
}

.product-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info h4, .order-info h4 {
  color: #e0e0e0;
  margin: 0 0 5px 0;
  font-size: 16px;
}

.product-info p, .order-info p {
  color: #a0a0a0;
  margin: 0;
  font-size: 14px;
}

.product-status, .order-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.product-status.active {
  background: rgba(204, 240, 212, 0.2);
  color: var(--accent-color);
}

.product-status.inactive {
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
}

.order-status {
  background: rgba(208, 255, 89, 0.2);
  color: var(--primary-color);
}

.order-date {
  color: #a0a0a0;
  font-size: 12px;
  margin-left: auto;
}

@media (max-width: 768px) {
  .dashboard-view h2 {
    font-size: 22px;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .action-card {
    padding: 20px;
  }
  
  .action-card h3 {
    font-size: 18px;
  }
  
  .product-item, .order-item {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .order-date {
    margin-left: 0;
  }
  
  .section-title {
    font-size: 20px;
  }
}

@media (max-width: 480px) {
  .dashboard-view h2 {
    font-size: 20px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .stat-card {
    padding: 15px;
  }
  
  .stat-card h3 {
    font-size: 13px;
  }
  
  .stat-value {
    font-size: 20px;
  }
  
  .action-card {
    padding: 15px;
  }
  
  .action-card h3 {
    font-size: 16px;
  }
  
  .action-card p {
    font-size: 13px;
  }
  
  .section-title {
    font-size: 18px;
  }
  
  .product-item,
  .order-item {
    padding: 12px;
    font-size: 13px;
  }
}
</style>
