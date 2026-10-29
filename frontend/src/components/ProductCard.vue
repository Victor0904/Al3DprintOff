<template>
  <div class="product-card">
    <div class="product-image">
      <img 
        v-if="product.image_principale" 
        :src="getImageUrl(product.image_principale)" 
        :alt="product.nom"
        class="product-img"
      >
      <div v-else class="product-placeholder">
        <div class="placeholder-icon">🔮</div>
      </div>
      <div class="product-overlay">
        <button class="quick-view-btn" @click="viewProduct">
          👁️ Voir
        </button>
      </div>
    </div>
    
    <div class="product-info">
      <div class="product-category" v-if="product.categorie">
        {{ product.categorie.nom }}
      </div>
      
      <h3 class="product-name">{{ product.nom }}</h3>
      
      <p class="product-description" v-if="product.description">
        {{ truncateText(product.description, 100) }}
      </p>
      
      <div class="product-footer">
        <div class="product-price">
          <span class="price-amount">{{ formatPrice(product.prix) }}</span>
          <span class="price-currency">€</span>
          <span v-if="isEntreprise" class="price-ttc"> TTC</span>
        </div>
        
        <button
          class="add-to-cart-btn"
          @click="addToCart"
          :disabled="!product.actif"
          :class="{ 'disabled': !product.actif }"
        >
          <span v-if="!product.actif">Indisponible</span>
          <span v-else>🛒 Ajouter</span>
        </button>
      </div>
    </div>
    
    <!-- Badge de statut -->
    <div class="product-badges">
      <span v-if="!product.actif" class="badge badge-inactive">Inactif</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['add-to-cart'])

const router = useRouter()
const authStore = useAuthStore()

// Computed
const isEntreprise = computed(() => authStore.user?.type_client === 'entreprise')

// Méthodes
const addToCart = () => {
  if (props.product.actif !== false) {
    emit('add-to-cart', props.product)
  }
}

const viewProduct = () => {
  router.push({ name: 'product-detail', params: { id: props.product.id } })
}

const truncateText = (text, maxLength) => {
  if (!text) return ''
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
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
</script>

<style scoped>
.product-card {
  background: #181818;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 18px;
  overflow: hidden;
  transition: all 0.4s ease;
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  transform-style: preserve-3d;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}

.product-card:hover {
  transform: translateY(-10px) translateZ(20px);
  box-shadow:
    0 20px 40px rgba(0, 0, 0, 0.8),
    0 0 30px rgba(208, 255, 89, 0.25);
  border-color: var(--primary-color);
}

.product-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.product-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center center;
  transition: all 0.3s ease;
  background: #111;
}

.product-card:hover .product-img {
  transform: scale(1.05);
}

.product-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--dark-secondary), var(--dark-tertiary));
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-icon {
  font-size: 48px;
  opacity: 0.5;
}

.product-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.3s ease;
}

.product-card:hover .product-overlay {
  opacity: 1;
}

.quick-view-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 25px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.quick-view-btn:hover {
  background: var(--accent-color);
  transform: scale(1.05);
}

.product-info {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-category {
  color: var(--primary-color);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
}

.product-name {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 10px;
  line-height: 1.3;
}

.product-description {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 15px;
  flex: 1;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  font-size: 12px;
  color: var(--text-secondary);
}

.product-date {
  display: flex;
  align-items: center;
  gap: 5px;
}

.date-icon {
  font-size: 14px;
}

.product-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.product-price {
  display: flex;
  align-items: baseline;
  gap: 2px;
}

.price-amount {
  font-size: 24px;
  font-weight: bold;
  color: var(--primary-color);
}

.price-currency {
  font-size: 16px;
  color: var(--text-secondary);
}

.add-to-cart-btn {
  background: #050505;
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.25);
  padding: 10px 20px;
  border-radius: 999px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
  box-shadow: 0 10px 26px var(--glow-color);
  position: relative;
  overflow: hidden;
}

.add-to-cart-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--primary-color);
  transform: translateX(-100%);
  transition: transform 0.3s ease;
  z-index: -1;
}

.add-to-cart-btn:hover:not(.disabled) {
  transform: translateY(-2px);
  color: #050505 !important;
  border-color: var(--primary-color);
  box-shadow: 0 16px 34px var(--glow-color);
}

.add-to-cart-btn:hover:not(.disabled)::before {
  transform: translateX(0);
}

.add-to-cart-btn.disabled {
  background: var(--border-color);
  color: var(--text-secondary);
  cursor: not-allowed;
}

.product-badges {
  position: absolute;
  top: 15px;
  right: 15px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-new {
  background: var(--accent-color);
  color: white;
}


.badge-inactive {
  background: var(--border-color);
  color: var(--text-secondary);
}

/* Responsive */
@media (max-width: 768px) {
  .product-image {
    height: 150px;
  }
  
  .product-info {
    padding: 15px;
  }
  
  .product-name {
    font-size: 16px;
  }
  
  .price-amount {
    font-size: 20px;
  }
  
  .add-to-cart-btn {
    padding: 8px 16px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .product-image {
    height: 120px;
  }
  
  .product-info {
    padding: 12px;
  }
  
  .product-category {
    font-size: 10px;
    margin-bottom: 5px;
  }
  
  .product-name {
    font-size: 14px;
    margin-bottom: 8px;
  }
  
  .product-description {
    font-size: 12px;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .product-meta {
    font-size: 11px;
    margin-bottom: 12px;
  }
  
  .price-amount {
    font-size: 18px;
  }
  
  .price-currency {
    font-size: 14px;
  }
  
  .add-to-cart-btn {
    padding: 6px 12px;
    font-size: 11px;
  }
  
  .product-footer {
    margin-top: auto;
  }
}
</style>
