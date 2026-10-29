<template>
  <div class="statistics-view">
    <h2>📈 Statistiques</h2>
    
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Ventes du mois</h3>
        <div class="stat-value">{{ formatCurrency(monthlySales) }}€</div>
      </div>
      
      <div class="stat-card">
        <h3>Produits populaires</h3>
        <div class="stat-value">{{ popularProducts }}</div>
      </div>
      
      <div class="stat-card">
        <h3>Nouveaux clients</h3>
        <div class="stat-value">{{ newClients }}</div>
      </div>
      
      <div class="stat-card">
        <h3>Taux de conversion</h3>
        <div class="stat-value">{{ conversionRate }}%</div>
      </div>
    </div>
    
    <div class="chart-section">
      <h3>Évolution des ventes</h3>
      <div class="chart-placeholder">
        📊 Graphique des ventes (intégration future)
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthFetch } from '../../composables/useAuthFetch'

const { apiFetch } = useAuthFetch()
const monthlySales = ref(0)
const popularProducts = ref(0)
const newClients = ref(0)
const conversionRate = ref(0)

const formatCurrency = (value) => {
  return parseFloat(value || 0).toFixed(2)
}

const loadStatistics = async () => {
  try {
    // Récupérer toutes les commandes (avec une limite élevée pour avoir toutes les commandes)
    const ordersResponse = await apiFetch('/api/commandes?limit=10000')
    
    let orders = []
    if (ordersResponse.ok) {
      orders = await ordersResponse.json()
      
      // Calculer les ventes du mois en cours
      const now = new Date()
      const currentMonth = now.getMonth()
      const currentYear = now.getFullYear()
      
      const monthlyOrders = orders.filter(order => {
        // Gérer les deux formats possibles de date
        const orderDateStr = order.date_commande || order.date_creation || order.dateCommande
        if (!orderDateStr) return false
        
        try {
          const orderDate = new Date(orderDateStr)
          // Vérifier que la date est valide
          if (isNaN(orderDate.getTime())) return false
          
          return orderDate.getMonth() === currentMonth && 
                 orderDate.getFullYear() === currentYear
        } catch (e) {
          return false
        }
      })
      
      // Calculer la somme des ventes du mois
      monthlySales.value = monthlyOrders.filter(order => !['En attente', 'Annulée'].includes(order.statut)).reduce((sum, order) => {
        const orderTotal = parseFloat(order.total || 0)
        if (isNaN(orderTotal)) return sum
        return sum + orderTotal
      }, 0)
      
      // Compter les produits populaires (nombre de commandes différentes)
      popularProducts.value = orders.length
    }
    
    // Récupérer les clients pour calculer les nouveaux clients du mois et le taux de conversion
    const clientsResponse = await apiFetch('/api/clients')
    
    if (clientsResponse.ok) {
      const clients = await clientsResponse.json()
      
      // Calculer les nouveaux clients du mois en cours
      const now = new Date()
      const currentMonth = now.getMonth()
      const currentYear = now.getFullYear()
      
      const newClientsThisMonth = clients.filter(client => {
        if (!client.date_inscription) return false
        
        try {
          const inscriptionDate = new Date(client.date_inscription)
          if (isNaN(inscriptionDate.getTime())) return false
          
          return inscriptionDate.getMonth() === currentMonth && 
                 inscriptionDate.getFullYear() === currentYear
        } catch (e) {
          return false
        }
      })
      
      newClients.value = newClientsThisMonth.length
      
      // Calculer le taux de conversion (nombre de clients ayant commandé / nombre total de clients) * 100
      if (orders.length > 0) {
        // Récupérer les IDs des clients ayant passé commande
        const clientsWithOrders = new Set()
        orders.forEach(order => {
          // Essayer différents formats pour récupérer l'ID du client
          const clientId = order.client_id || order.client?.id || order.client
          if (clientId) {
            clientsWithOrders.add(clientId)
          }
        })
        
        const totalClients = clients.length
        
        if (totalClients > 0) {
          conversionRate.value = parseFloat(((clientsWithOrders.size / totalClients) * 100).toFixed(1))
        } else {
          conversionRate.value = 0
        }
      } else {
        conversionRate.value = 0
      }
    }
    
  } catch (error) {
    // Valeurs par défaut en cas d'erreur
    monthlySales.value = 0
    popularProducts.value = 0
    newClients.value = 0
    conversionRate.value = 0
  }
}

onMounted(() => {
  loadStatistics()
})
</script>

<style scoped>
.statistics-view {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.statistics-view h2 {
  color: var(--primary-color);
  margin: 0;
  font-size: 28px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.stat-card {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  padding: 25px;
  text-align: center;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(208, 255, 89, 0.2);
  border-color: var(--primary-color);
}

.stat-card h3 {
  color: var(--accent-color);
  margin: 0 0 15px 0;
  font-size: 16px;
}

.stat-value {
  color: var(--primary-color);
  font-size: 32px;
  font-weight: bold;
}

.chart-section {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  padding: 25px;
}

.chart-section h3 {
  color: var(--accent-color);
  margin: 0 0 20px 0;
}

.chart-placeholder {
  background: rgba(20, 20, 20, 0.5);
  border: 2px dashed var(--surface-2);
  border-radius: 10px;
  padding: 60px;
  text-align: center;
  color: #a0a0a0;
  font-size: 18px;
}

/* Responsive */
@media (max-width: 768px) {
  .statistics-view h2 {
    font-size: 22px;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .stat-card h3 {
    font-size: 14px;
  }
  
  .stat-value {
    font-size: 24px;
  }
  
  .chart-section {
    padding: 20px;
  }
  
  .chart-placeholder {
    padding: 40px 20px;
    font-size: 16px;
  }
}

@media (max-width: 480px) {
  .statistics-view h2 {
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
    margin-bottom: 10px;
  }
  
  .stat-value {
    font-size: 20px;
  }
  
  .chart-section {
    padding: 15px;
  }
  
  .chart-section h3 {
    font-size: 16px;
  }
  
  .chart-placeholder {
    padding: 30px 15px;
    font-size: 14px;
  }
}
</style>
