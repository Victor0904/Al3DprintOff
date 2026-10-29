<template>
  <Teleport to="body">
    <transition name="cart-drawer">
      <div v-if="visible" class="cart-overlay" @click.self="close">
        <div class="cart-drawer" @click.stop>
          <!-- Header -->
          <header class="cart-header">
            <div class="cart-title">
              <span class="cart-icon">🛒</span>
              <div>
                <h2>Votre panier</h2>
                <p class="cart-subtitle">
                  {{ cartStore.totalItems }} article(s)
                </p>
              </div>
            </div>
            <button class="close-btn" @click="close">×</button>
          </header>

          <!-- Bannière frais de port -->
          <div v-if="cartStore.items.length > 0 && amountUntilFreeShipping > 0" class="shipping-banner">
            <span class="shipping-banner-icon">🚚</span>
            <span>Encore <strong>{{ formatPrice(amountUntilFreeShipping) }}€</strong> pour avoir les frais de port gratuit !</span>
          </div>
          <div v-else-if="cartStore.items.length > 0 && shippingCost === 0" class="shipping-banner shipping-banner-free">
            <span class="shipping-banner-icon">🎉</span>
            <span><strong>Livraison gratuite</strong> appliquée !</span>
          </div>

          <!-- Contenu -->
          <div class="cart-body">
            <div v-if="cartStore.items.length === 0" class="empty-cart">
              <div class="empty-icon">🛒</div>
              <p class="empty-title">Votre panier est vide</p>
              <p class="empty-text">
                Découvrez nos produits et ajoutez-les à votre panier.
              </p>
              <router-link
                to="/products"
                class="btn btn-primary empty-btn"
                @click.native="close"
              >
                Découvrir les produits
              </router-link>
            </div>

            <div v-else class="cart-items">
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
                  />
                  <div
                    class="item-placeholder"
                    :style="{
                      display:
                        !item.image || !getImageUrl(item.image) ? 'flex' : 'none'
                    }"
                  >
                    <img src="/placeholder.svg" alt="" class="placeholder-img">
                  </div>
                </div>

                <div class="item-info">
                  <h3 class="item-name">{{ item.name }}</h3>
                  <p v-if="item.variante_nom" class="item-variant">{{ item.variante_nom }}</p>
                  <p class="item-category">{{ item.category }}</p>
                  <p class="item-price">
                    {{ formatPrice(item.price) }}€
                  </p>

                  <div class="item-footer">
                    <div class="quantity-controls">
                      <button
                        class="quantity-btn"
                        @click="decreaseQuantity(item.id, item.variante_id)"
                        :disabled="item.quantity <= 1"
                      >
                        −
                      </button>
                      <span class="quantity-value">{{ item.quantity }}</span>
                      <button
                        class="quantity-btn"
                        @click="increaseQuantity(item.id, item.variante_id)"
                      >
                        +
                      </button>
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
              </div>
            </div>
          </div>

          <!-- Résumé -->
          <div v-if="cartStore.items.length > 0" class="cart-footer">
            <div class="summary-line">
              <span>Sous-total</span>
              <span>{{ formatPrice(cartStore.totalPrice) }}€</span>
            </div>
            <div class="summary-line">
              <span>Livraison</span>
              <span :class="{ 'delivery-free': shippingCost === 0 }">
                {{ shippingCost === 0 ? 'Gratuite' : formatPrice(shippingCost) + '€' }}
              </span>
            </div>
            <div class="summary-line">
              <span>Taxes estimées (20%)</span>
              <span>{{ formatPrice((cartStore.totalPrice + shippingCost) * 0.2) }}€</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-line total">
              <span>Total estimé TTC</span>
              <span>{{ formatPrice((cartStore.totalPrice + shippingCost) * 1.2) }}€</span>
            </div>

            <button class="btn btn-primary checkout-btn" @click="goToCheckout">
              Passer la commande
            </button>
            <button class="btn btn-secondary continue-btn" @click="close">
              Continuer mes achats
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const router = useRouter()
const cartStore = useCartStore()

const SHIPPING_FREE_THRESHOLD = 50
const SHIPPING_COST = 10

const shippingCost = computed(() =>
  cartStore.totalPrice >= SHIPPING_FREE_THRESHOLD ? 0 : SHIPPING_COST
)

const amountUntilFreeShipping = computed(() =>
  Math.max(0, SHIPPING_FREE_THRESHOLD - cartStore.totalPrice)
)

const close = () => {
  emit('close')
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
}

const increaseQuantity = (itemId, varianteId = null) => {
  const item = cartStore.items.find((item) => item.id === itemId && item.variante_id === varianteId)
  if (item) {
    cartStore.updateQuantity(itemId, item.quantity + 1, varianteId)
  }
}

const decreaseQuantity = (itemId, varianteId = null) => {
  const item = cartStore.items.find((item) => item.id === itemId && item.variante_id === varianteId)
  if (item && item.quantity > 1) {
    cartStore.updateQuantity(itemId, item.quantity - 1, varianteId)
  }
}

const removeItem = (itemId, varianteId = null) => {
  cartStore.removeFromCart(itemId, varianteId)
}

const goToCheckout = () => {
  close()
  router.push({ name: 'checkout' })
}

const getImageUrl = (chemin) => {
  if (!chemin) return ''
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) {
    return chemin
  }
  if (chemin.startsWith('/uploads/')) {
    return `${chemin}`
  }
  if (chemin && !chemin.startsWith('/')) {
    return `/uploads/${chemin}`
  }
  return chemin
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.item-placeholder')
  if (placeholder) {
    placeholder.style.display = 'flex'
  }
}
</script>

<style scoped>
.cart-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: flex-end;
  z-index: 2000;
}

.cart-drawer {
  width: 100%;
  max-width: 420px;
  background: #0b0b0b;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 100vh;
}

.cart-header {
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cart-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cart-title h2 {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.cart-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0;
}

.cart-icon {
  font-size: 22px;
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 22px;
  cursor: pointer;
  border-radius: 999px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--primary-color);
}

.cart-body {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
}

.empty-cart {
  text-align: center;
  padding: 40px 10px;
}

.empty-icon {
  width: 70px;
  height: 70px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  margin: 0 auto 16px;
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 6px;
}

.empty-text {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.empty-btn {
  width: 100%;
  justify-content: center;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.cart-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  background: rgba(24, 24, 24, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.item-image {
  width: 70px;
  height: 70px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.04);
}

.item-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-icon {
  font-size: 24px;
  opacity: 0.5;
}

.item-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.item-name {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: var(--text-primary);
}

.item-variant {
  font-size: 12px;
  color: var(--primary-color);
  font-weight: 600;
}

.item-category {
  font-size: 12px;
  color: var(--primary-color);
}

.item-price {
  font-size: 13px;
  color: var(--text-secondary);
}

.item-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.quantity-btn {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(24, 24, 24, 0.9);
  color: var(--text-primary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: all 0.2s ease;
}

.quantity-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: #050505;
}

.quantity-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quantity-value {
  min-width: 22px;
  text-align: center;
  font-size: 13px;
}

.remove-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  font-size: 16px;
  padding: 4px;
  border-radius: 999px;
  transition: all 0.2s ease;
}

.remove-btn:hover {
  background: rgba(255, 107, 107, 0.1);
  color: #ff6b6b;
}

.cart-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 14px 20px 18px;
  background: #0b0b0b;
}

.summary-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 6px;
}

.summary-line.total {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.summary-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.12);
  margin: 8px 0 10px;
}

.checkout-btn,
.continue-btn {
  width: 100%;
  margin-top: 10px;
}

.shipping-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: rgba(208, 255, 89, 0.08);
  border-top: 1px solid rgba(208, 255, 89, 0.2);
  border-bottom: 1px solid rgba(208, 255, 89, 0.2);
  font-size: 13px;
  color: var(--text-secondary);
}

.shipping-banner strong {
  color: var(--primary-color);
}

.shipping-banner-free {
  background: rgba(204, 240, 212, 0.08);
  border-color: rgba(204, 240, 212, 0.25);
}

.shipping-banner-free strong {
  color: var(--accent-color);
}

.shipping-banner-icon {
  font-size: 16px;
  flex-shrink: 0;
}

.delivery-free {
  color: var(--accent-color);
  font-weight: 600;
}

/* Animations du drawer */
.cart-drawer-enter-active,
.cart-drawer-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.cart-drawer-enter-from,
.cart-drawer-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

@media (max-width: 600px) {
  .cart-drawer {
    max-width: 100%;
  }
}

.placeholder-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>

