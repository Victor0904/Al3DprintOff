<template>
  <div class="cart-page">
    <div class="container">
      <div class="cart-header">
        <h1 class="page-title">Mon Panier</h1>
        <div class="cart-summary">
          <span class="item-count">{{ cartStore.totalItems }} article(s)</span>
        </div>
      </div>

      <div v-if="cartStore.items.length === 0" class="empty-cart">
        <div class="empty-cart-icon">🛒</div>
        <h2>Votre panier est vide</h2>
        <p>Découvrez nos produits et ajoutez-les à votre panier pour commencer vos achats.</p>
        <router-link to="/products" class="btn btn-primary">
          Découvrir les produits
        </router-link>
      </div>

      <!-- Bannière frais de port -->
      <div v-if="cartStore.items.length > 0 && amountUntilFreeShipping > 0" class="shipping-banner">
        <span class="shipping-banner-icon">🚚</span>
        <span>Plus que <strong>{{ formatPrice(amountUntilFreeShipping) }}€</strong> pour bénéficier de la livraison gratuite !</span>
      </div>
      <div v-else-if="cartStore.items.length > 0 && shippingCost === 0" class="shipping-banner shipping-banner-free">
        <span class="shipping-banner-icon">🎉</span>
        <span><strong>Livraison gratuite</strong> appliquée à votre commande !</span>
      </div>

      <div v-if="cartStore.items.length > 0" class="cart-content">
        <div class="cart-items">
          <div
            v-for="item in cartStore.items"
            :key="`${item.id}_${item.variante_id || 0}`"
            class="cart-item"
          >
            <div class="item-image">
              <img 
                v-if="item.image && getImageUrl(item.image)" 
                :src="getImageUrl(item.image)" 
                :alt="item.name"
                class="item-img"
                @error="handleImageError"
              >
              <div class="item-placeholder" :style="{ display: (!item.image || !getImageUrl(item.image)) ? 'flex' : 'none' }">
                <div class="placeholder-icon">🔮</div>
              </div>
            </div>

            <div class="item-details">
              <h3 class="item-name">{{ item.name }}</h3>
              <p v-if="item.variante_nom" class="item-variant">{{ item.variante_nom }}</p>
              <p class="item-category">{{ item.category }}</p>
              <div class="item-price">{{ formatPrice(item.price) }}€</div>
            </div>

            <div class="item-quantity">
              <label class="quantity-label">Quantité</label>
              <div class="quantity-controls">
                <button
                  class="quantity-btn"
                  @click="decreaseQuantity(item.id, item.variante_id)"
                  :disabled="item.quantity <= 1"
                >
                  −
                </button>
                <input
                  type="number"
                  :value="item.quantity"
                  @change="updateQuantity(item.id, $event.target.value, item.variante_id)"
                  class="quantity-input"
                  min="1"
                  :max="getMaxQuantity(item)"
                >
                <button
                  class="quantity-btn"
                  @click="increaseQuantity(item.id, item.variante_id)"
                >
                  +
                </button>
              </div>
            </div>

            <div class="item-total">
              <div class="total-label">Total</div>
              <div class="total-price">{{ formatPrice(item.price * item.quantity) }}€</div>
            </div>

            <button 
              class="remove-btn"
              @click="removeItem(item.id, item.variante_id)"
              title="Supprimer l'article"
            >
              🗑️
            </button>
          </div>
        </div>

        <div class="cart-summary-section">
          <div class="summary-card">
            <h3 class="summary-title">Résumé de la commande</h3>
            
            <div class="summary-line">
              <span>Sous-total</span>
              <span>{{ formatPrice(cartStore.totalPrice) }}€</span>
            </div>
            
            <div class="summary-line">
              <span>Livraison</span>
              <span :class="shippingCost === 0 ? 'delivery-free' : ''">
                {{ shippingCost === 0 ? 'Gratuite' : formatPrice(shippingCost) + '€' }}
              </span>
            </div>

            <!-- Code promo -->
            <div class="promo-section">
              <div v-if="!cartStore.promoCode" class="promo-input-row">
                <input
                  v-model="promoInput"
                  type="text"
                  placeholder="Code promo"
                  class="promo-input"
                  @keyup.enter="applyPromo"
                >
                <button @click="applyPromo" class="btn-promo">Appliquer</button>
              </div>
              <div v-if="cartStore.promoError" class="promo-error">{{ cartStore.promoError }}</div>
              <div v-if="cartStore.promoCode" class="promo-applied">
                <span>✅ Code <strong>{{ cartStore.promoCode }}</strong> (-{{ cartStore.promoPercent }}%)</span>
                <button @click="cartStore.removePromoCode()" class="btn-remove-promo">×</button>
              </div>
            </div>

            <div v-if="cartStore.promoCode" class="summary-line discount-line">
              <span>Remise ({{ cartStore.promoPercent }}%)</span>
              <span class="discount-amount">-{{ formatPrice(cartStore.discountAmount) }}€</span>
            </div>

            <div class="summary-line">
              <span>TVA (estimation 20 %)</span>
              <span>{{ formatPrice((cartStore.discountedTotal + shippingCost) * 0.2) }}€</span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-line total-line">
              <span>Total estimé TTC</span>
              <span class="total-amount">{{ formatPrice((cartStore.discountedTotal + shippingCost) * 1.2) }}€</span>
            </div>

            <div class="summary-actions">
              <button 
                class="btn btn-primary checkout-btn"
                @click="proceedToCheckout"
                :disabled="!canCheckout"
              >
                <span v-if="!isLoggedIn">Se connecter pour commander</span>
                <span v-else>Passer la commande</span>
              </button>
              
              <button 
                class="btn btn-secondary clear-btn"
                @click="clearCart"
              >
                Vider le panier
              </button>
            </div>

            <div class="security-info">
              <div class="security-item">
                <span class="security-icon">🔒</span>
                <span>Paiement sécurisé</span>
              </div>
              <div class="security-item">
                <span class="security-icon">🚚</span>
                <span>Livraison gratuite</span>
              </div>
              <div class="security-item">
                <span class="security-icon">↩️</span>
                <span>Retour facile</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { confirm: showConfirm } = useAlert()

// Shipping constants
const SHIPPING_FREE_THRESHOLD = 50
const SHIPPING_COST = 10

// Computed
const isLoggedIn = computed(() => authStore.isAuthenticated)
const canCheckout = computed(() => cartStore.items.length > 0)

const promoInput = ref('')

const applyPromo = async () => {
  await cartStore.applyPromoCode(promoInput.value)
  if (cartStore.promoCode) promoInput.value = ''
}

const shippingCost = computed(() =>
  cartStore.totalPrice >= SHIPPING_FREE_THRESHOLD ? 0 : SHIPPING_COST
)

const amountUntilFreeShipping = computed(() =>
  Math.max(0, SHIPPING_FREE_THRESHOLD - cartStore.totalPrice)
)

// Méthodes
const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
}

const increaseQuantity = (itemId, varianteId = null) => {
  const item = cartStore.items.find(item => item.id === itemId && item.variante_id === varianteId)
  if (item) {
    cartStore.updateQuantity(itemId, item.quantity + 1, varianteId)
  }
}

const decreaseQuantity = (itemId, varianteId = null) => {
  const item = cartStore.items.find(item => item.id === itemId && item.variante_id === varianteId)
  if (item && item.quantity > 1) {
    cartStore.updateQuantity(itemId, item.quantity - 1, varianteId)
  }
}

const updateQuantity = (itemId, newQuantity, varianteId = null) => {
  const quantity = parseInt(newQuantity)
  if (quantity > 0) {
    cartStore.updateQuantity(itemId, quantity, varianteId)
  }
}

const removeItem = async (itemId, varianteId = null) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer cet article ?')
  if (confirmed) {
    cartStore.removeFromCart(itemId, varianteId)
  }
}

const clearCart = async () => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir vider votre panier ?')
  if (confirmed) {
    cartStore.clearCart()
  }
}

const proceedToCheckout = () => {
  if (!isLoggedIn.value) {
    // Rediriger vers la page de connexion avec retour direct au checkout
    router.push({ name: 'login', query: { returnTo: '/checkout' } })
  } else {
    // Rediriger vers la page de commande
    router.push({ name: 'checkout' })
  }
}

const getMaxQuantity = (item) => {
  // Pour l'instant, on limite à 99, mais on pourrait récupérer le stock réel
  return 99
}

const getImageUrl = (chemin) => {
  if (!chemin) return ''
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) {
    return chemin
  }
  if (chemin.startsWith('/uploads/')) {
    return `${chemin}`
  }
  // Si le chemin ne commence pas par /uploads/, essayer de l'ajouter
  if (chemin && !chemin.startsWith('/')) {
    return `/uploads/${chemin}`
  }
  return chemin
}

const handleImageError = (event) => {
  // Si l'image ne charge pas, afficher le placeholder
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.item-placeholder')
  if (placeholder) {
    placeholder.style.display = 'flex'
  }
}
</script>

<style scoped>
.cart-page {
  min-height: 100vh;
  padding: 40px 0;
}

.shipping-banner {
  background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(78, 205, 196, 0.1));
  border: 1px solid rgba(0, 212, 255, 0.3);
  border-radius: 10px;
  padding: 12px 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-primary);
  font-size: 15px;
}

.shipping-banner-free {
  border-color: rgba(78, 205, 196, 0.4);
}

.shipping-banner-icon {
  font-size: 20px;
}

.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.page-title {
  font-size: 36px;
  font-weight: bold;
  color: var(--text-primary);
  margin: 0;
}

.cart-summary {
  color: var(--text-secondary);
  font-size: 16px;
}

.empty-cart {
  text-align: center;
  padding: 80px 20px;
}

.empty-cart-icon {
  font-size: 80px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.empty-cart h2 {
  font-size: 24px;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.empty-cart p {
  color: var(--text-secondary);
  margin-bottom: 30px;
  font-size: 16px;
}

.cart-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.cart-item {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 20px;
  display: grid;
  grid-template-columns: 100px 1fr 150px 120px 40px;
  gap: 20px;
  align-items: center;
  transition: all 0.3s ease;
}

.cart-item:hover {
  border-color: var(--primary-color);
  box-shadow: 0 5px 20px var(--shadow-color);
}

.item-image {
  width: 100px;
  height: 100px;
  border-radius: 10px;
  overflow: hidden;
}

.item-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--dark-secondary), var(--dark-tertiary));
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-icon {
  font-size: 24px;
  opacity: 0.5;
}

.item-details {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.item-name {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.item-variant {
  color: var(--primary-color);
  font-size: 14px;
  font-weight: 600;
  margin: 0;
}

.item-category {
  color: var(--primary-color);
  font-size: 14px;
  margin: 0;
}

.item-price {
  color: var(--text-secondary);
  font-size: 16px;
}

.item-quantity {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.quantity-label {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 600;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.quantity-btn {
  width: 30px;
  height: 30px;
  border: 1px solid var(--border-color);
  background: var(--dark-secondary);
  color: var(--text-primary);
  border-radius: 5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  transition: all 0.3s ease;
}

.quantity-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: white;
}

.quantity-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quantity-input {
  width: 50px;
  height: 30px;
  border: 1px solid var(--border-color);
  background: var(--dark-secondary);
  color: var(--text-primary);
  border-radius: 5px;
  text-align: center;
  font-size: 14px;
}

.quantity-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.item-total {
  text-align: right;
}

.total-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 5px;
}

.total-price {
  font-size: 18px;
  font-weight: bold;
  color: var(--primary-color);
}

.remove-btn {
  background: none;
  border: none;
  color: var(--secondary-color);
  cursor: pointer;
  padding: 5px;
  border-radius: 5px;
  transition: all 0.3s ease;
}

.remove-btn:hover {
  background: rgba(255, 107, 107, 0.1);
  transform: scale(1.1);
}

.cart-summary-section {
  position: sticky;
  top: 100px;
}

.summary-card {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.summary-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  color: var(--text-secondary);
}

.summary-line.total-line {
  color: var(--text-primary);
  font-weight: bold;
  font-size: 18px;
}

.total-amount {
  color: var(--primary-color);
  font-size: 24px;
}

.delivery-free {
  color: var(--accent-color);
  font-weight: 600;
}

.promo-section {
  margin: 12px 0;
}

.promo-input-row {
  display: flex;
  gap: 8px;
}

.promo-input {
  flex: 1;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 10px 12px;
  color: #e0e0e0;
  font-size: 14px;
  min-width: 0;
  box-sizing: border-box;
}

.promo-input:focus {
  outline: none;
  border-color: #00d4ff;
}

.btn-promo {
  padding: 10px 14px;
  background: linear-gradient(45deg, #00d4ff, #4ecdc4);
  color: #000;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
  font-size: 13px;
  white-space: nowrap;
  flex-shrink: 0;
  transition: opacity 0.2s;
}

.btn-promo:hover { opacity: 0.85; }

.promo-error {
  margin-top: 6px;
  font-size: 12px;
  color: #ff6b6b;
}

.promo-applied {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(78, 205, 196, 0.1);
  border: 1px solid rgba(78, 205, 196, 0.4);
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: #4ecdc4;
}

.btn-remove-promo {
  background: none;
  border: none;
  color: #ff6b6b;
  font-size: 18px;
  cursor: pointer;
  padding: 0 2px;
  line-height: 1;
}

.discount-line {
  color: #4ecdc4;
}

.discount-amount {
  font-weight: 700;
  color: #4ecdc4;
}

.summary-divider {
  height: 1px;
  background: var(--border-color);
  margin: 20px 0;
}

.summary-actions {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 30px;
}

.checkout-btn,
.clear-btn {
  width: 100%;
  padding: 15px;
  font-size: 16px;
  font-weight: 600;
}

.checkout-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.security-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.security-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: 14px;
}

.security-icon {
  font-size: 16px;
}

/* Responsive */
@media (max-width: 1024px) {
  .cart-content {
    grid-template-columns: 1fr;
    gap: 30px;
  }
  
  .cart-summary-section {
    position: static;
  }
}

@media (max-width: 768px) {
  .cart-page { padding: 24px 0; }

  .cart-header {
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
    margin-bottom: 24px;
  }
  .page-title { font-size: 26px; }

  .cart-item {
    grid-template-columns: 80px 1fr;
    gap: 14px;
  }
  /* remove-btn reste dans le coin */
  .remove-btn {
    grid-column: 2;
    grid-row: 1;
    justify-self: end;
    align-self: start;
  }
  .item-image {
    width: 80px;
    height: 80px;
    grid-row: 1;
  }
  .item-details { grid-column: 2; }
  .item-quantity,
  .item-total {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
  }
  .item-name { font-size: 15px; }
}

@media (max-width: 480px) {
  .cart-page { padding: 16px 0; }

  .page-title { font-size: 22px; }

  .cart-item {
    grid-template-columns: 68px 1fr;
    padding: 14px;
    gap: 12px;
  }
  .item-image { width: 68px; height: 68px; }
  .item-name  { font-size: 14px; }
  .item-price { font-size: 14px; }

  .quantity-btn {
    width: 28px;
    height: 28px;
    font-size: 16px;
  }
  .quantity-input {
    width: 40px;
    height: 28px;
    font-size: 13px;
  }
  .total-price { font-size: 15px; }

  .summary-card { padding: 20px; }
  .summary-title { font-size: 17px; }
  .total-amount { font-size: 20px; }

  .checkout-btn,
  .clear-btn {
    padding: 13px;
    font-size: 14px;
  }

  .shipping-banner { font-size: 13px; padding: 10px 14px; }
}
</style>
