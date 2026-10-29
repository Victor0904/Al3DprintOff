<template>
  <div class="custom-products-page">

    <!-- ══════════════════════════════════════════
         HERO — PLEIN ÉCRAN IMPACTANT
    ══════════════════════════════════════════ -->
    <section class="hero">
      <div class="hero-bg">
        <div class="hero-grid"></div>
        <div class="hero-glow hero-glow-1"></div>
        <div class="hero-glow hero-glow-2"></div>
        <div class="hero-glow hero-glow-3"></div>
        <div class="scan-line"></div>
      </div>

      <div class="container">
        <div class="hero-content">
          <div class="hero-tag">
            <span class="tag-dot"></span>
            <span>Créations Exclusives · Impression 3D Haute Définition</span>
          </div>

          <h1 class="hero-title">
            Vos Projets<br>
            <span class="title-highlight">Prennent Vie</span>
          </h1>

          <p class="hero-desc">
            Chaque pièce est une œuvre unique, conçue sur mesure et imprimée
            avec précision. De l'idée à l'objet, nous transformons vos envies
            en réalité avec les meilleurs matériaux.
          </p>

          <div class="hero-cta">
            <a href="#products" class="btn-hero-primary">
              <span>Découvrir la collection</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
            </a>
          </div>

          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">100%</span>
              <span class="stat-label">Sur mesure</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-number">HD</span>
              <span class="stat-label">Impression haute résolution</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-number">∞</span>
              <span class="stat-label">Possibilités créatives</span>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-scroll-indicator">
        <div class="scroll-dot"></div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════
         BANDE USP
    ══════════════════════════════════════════ -->
    <section class="usp-strip">
      <div class="usp-inner">
        <div class="usp-item">
          <span class="usp-icon">⚡</span>
          <span>Impression ultra-précise</span>
        </div>
        <div class="usp-sep">·</div>
        <div class="usp-item">
          <span class="usp-icon">🎨</span>
          <span>Design 100% personnalisé</span>
        </div>
        <div class="usp-sep">·</div>
        <div class="usp-item">
          <span class="usp-icon">✦</span>
          <span>Matériaux premium sélectionnés</span>
        </div>
        <div class="usp-sep">·</div>
        <div class="usp-item">
          <span class="usp-icon">🛡️</span>
          <span>Qualité garantie</span>
        </div>
        <div class="usp-sep">·</div>
        <div class="usp-item">
          <span class="usp-icon">🚀</span>
          <span>Livraison rapide</span>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════
         PRODUITS
    ══════════════════════════════════════════ -->
    <section class="products-section" id="products">
      <div class="container">

        <!-- Titre section -->
        <div class="section-header">
          <div class="section-tag">Notre collection</div>
          <h2 class="section-title">Créations <span class="accent">d'Exception</span></h2>
          <p class="section-subtitle">
            Chaque produit est une pièce unique, imaginée et fabriquée avec passion
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loadingProducts" class="loading-state">
          <div class="loader-ring"></div>
          <p>Chargement des créations...</p>
        </div>

        <!-- Grille produits -->
        <div v-else-if="filteredProducts.length > 0" class="products-grid">
          <article
            v-for="(product, index) in filteredProducts"
            :key="product.id"
            class="product-card"
            :style="{ animationDelay: `${index * 0.08}s` }"
            @click="viewProduct(product.id)"
          >
            <!-- Image -->
            <div class="card-image">
              <img
                v-if="product.image_principale"
                :src="getImageUrl(product.image_principale)"
                :alt="product.nom"
                class="card-img"
                @error="handleImageError($event)"
              />
              <div v-else class="card-img-placeholder">
                <span>🎨</span>
              </div>

              <!-- Overlay hover -->
              <div class="card-overlay">
                <button class="overlay-btn" @click.stop="viewProduct(product.id)">
                  Voir le produit
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                </button>
              </div>

              <!-- Badges -->
              <div class="card-badges">
                <span class="badge-custom">Personnalisé</span>
              </div>
            </div>

            <!-- Infos -->
            <div class="card-body">
              <h3 class="card-title">{{ product.nom }}</h3>

              <p v-if="product.description" class="card-desc">
                {{ truncateText(product.description, 100) }}
              </p>

              <div class="card-footer">
                <div class="card-price">
                  <span class="price-amount">{{ formatPrice(product.prix) }}€</span>
                  <span v-if="isEntreprise" class="price-ttc">TTC</span>
                </div>

                <button
                  class="card-cta"
                  :class="{ disabled: !product.actif }"
                  @click.stop="addToCart(product)"
                  :disabled="!product.actif"
                >
                  <svg v-if="product.actif" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                  <span>{{ !product.actif ? 'Indisponible' : 'Ajouter' }}</span>
                </button>
              </div>
            </div>

            <!-- Glow border au hover -->
            <div class="card-glow"></div>
          </article>
        </div>

        <!-- Empty state -->
        <div v-else class="empty-state">
          <div class="empty-icon">🎨</div>
          <h3>Aucun produit pour le moment</h3>
          <p>Nos créations exclusives arrivent bientôt. Revenez découvrir notre collection premium.</p>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════
         SECTION CTA FINALE
    ══════════════════════════════════════════ -->
    <section class="cta-section">
      <div class="cta-bg">
        <div class="cta-glow"></div>
      </div>
      <div class="container">
        <div class="cta-content">
          <div class="cta-icon">✦</div>
          <h2 class="cta-title">Vous avez un projet unique en tête ?</h2>
          <p class="cta-desc">
            Nous réalisons vos créations les plus ambitieuses. Contactez-nous
            pour discuter de votre projet sur mesure.
          </p>
          <div class="cta-actions">
            <router-link to="/products" class="btn-cta-outline">
              Voir tous nos produits
            </router-link>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { success: showSuccess } = useAlert()

const isEntreprise = computed(() => authStore.user?.type_client === 'entreprise')

const products = ref([])
const loadingProducts = ref(false)

const filteredProducts = computed(() => products.value)

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await fetch('/api/produits-personnalises?actif=true')
    if (response.ok) {
      products.value = await response.json()
    }
  } catch {
    // silently ignore
  } finally {
    loadingProducts.value = false
  }
}

const formatPrice = (price) => parseFloat(price).toFixed(2)

const truncateText = (text, max) => {
  if (!text) return ''
  return text.length > max ? text.substring(0, max) + '...' : text
}

const getImageUrl = (chemin) => {
  if (!chemin) return ''
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) return chemin
  if (chemin.startsWith('/uploads/')) return chemin
  if (!chemin.startsWith('/')) return `/uploads/${chemin}`
  return chemin
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.card-img-placeholder')
  if (placeholder) placeholder.style.display = 'flex'
}

const viewProduct = (productId) => {
  router.push({ path: `/product/${productId}`, query: { type: 'custom' } })
}

const addToCart = (product) => {
  if (product.actif) {
    cartStore.addToCart(product, 1)
    showSuccess('Produit ajouté au panier !')
  }
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
/* ═══════════════════════════════════
   BASE
═══════════════════════════════════ */
.custom-products-page {
  min-height: 100vh;
  background: var(--dark-bg);
  color: var(--text-primary);
  font-family: inherit;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ═══════════════════════════════════
   HERO
═══════════════════════════════════ */
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(208, 255, 89, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(208, 255, 89, 0.04) 1px, transparent 1px);
  background-size: 60px 60px;
}

.hero-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
}

.hero-glow-1 {
  width: 600px;
  height: 600px;
  background: rgba(208, 255, 89, 0.12);
  top: -200px;
  left: -150px;
}

.hero-glow-2 {
  width: 400px;
  height: 400px;
  background: rgba(204, 240, 212, 0.08);
  bottom: 0;
  right: -100px;
  animation: glow-pulse 6s ease-in-out infinite alternate;
}

.hero-glow-3 {
  width: 300px;
  height: 300px;
  background: rgba(208, 255, 89, 0.06);
  top: 40%;
  left: 55%;
  animation: glow-pulse 8s ease-in-out infinite alternate-reverse;
}

@keyframes glow-pulse {
  from { opacity: 0.5; transform: scale(1); }
  to { opacity: 1; transform: scale(1.15); }
}

.scan-line {
  position: absolute;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(208, 255, 89, 0.15), transparent);
  top: 0;
  animation: scan 8s linear infinite;
}

@keyframes scan {
  0% { top: 0; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}

.hero-content {
  position: relative;
  z-index: 2;
  padding: 120px 0 100px;
  max-width: 780px;
}

.hero-tag {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(208, 255, 89, 0.08);
  border: 1px solid rgba(208, 255, 89, 0.25);
  border-radius: 50px;
  padding: 8px 20px;
  font-size: 13px;
  font-weight: 600;
  color: var(--primary-color);
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 36px;
}

.tag-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--primary-color);
  animation: blink 2s ease-in-out infinite;
  flex-shrink: 0;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.2; }
}

.hero-title {
  font-size: clamp(48px, 8vw, 88px);
  font-weight: 900;
  line-height: 1.05;
  letter-spacing: -2px;
  color: #fff;
  margin: 0 0 28px;
}

.title-highlight {
  display: block;
  color: var(--primary-color);
  text-shadow: 0 0 80px rgba(208, 255, 89, 0.4);
}

.hero-desc {
  font-size: clamp(16px, 2vw, 19px);
  color: var(--text-secondary);
  line-height: 1.75;
  max-width: 600px;
  margin: 0 0 44px;
}

.hero-cta {
  margin-bottom: 64px;
}

.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: var(--primary-color);
  color: #0b0b0b;
  font-weight: 800;
  font-size: 15px;
  padding: 16px 32px;
  border-radius: 50px;
  text-decoration: none;
  transition: all 0.3s ease;
  letter-spacing: 0.02em;
}

.btn-hero-primary:hover {
  background: #e0ff7a;
  transform: translateY(-2px);
  box-shadow: 0 16px 48px rgba(208, 255, 89, 0.4);
}

.hero-stats {
  display: flex;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-number {
  font-size: 28px;
  font-weight: 900;
  color: var(--primary-color);
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.12);
}

.hero-scroll-indicator {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.scroll-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--primary-color);
  animation: bounce-dot 2s ease-in-out infinite;
  margin: 0 auto;
}

@keyframes bounce-dot {
  0%, 100% { transform: translateY(0); opacity: 1; }
  50% { transform: translateY(10px); opacity: 0.4; }
}

/* ═══════════════════════════════════
   BANDE USP
═══════════════════════════════════ */
.usp-strip {
  background: rgba(208, 255, 89, 0.04);
  border-top: 1px solid rgba(208, 255, 89, 0.15);
  border-bottom: 1px solid rgba(208, 255, 89, 0.15);
  overflow: hidden;
}

.usp-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  padding: 18px 24px;
  flex-wrap: wrap;
}

.usp-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.75);
  white-space: nowrap;
}

.usp-icon {
  font-size: 16px;
}

.usp-sep {
  color: rgba(208, 255, 89, 0.4);
  font-size: 20px;
  font-weight: 300;
}

/* ═══════════════════════════════════
   PRODUITS SECTION
═══════════════════════════════════ */
.products-section {
  padding: 100px 0;
}

.section-header {
  text-align: center;
  margin-bottom: 60px;
}

.section-tag {
  display: inline-block;
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-color);
  text-transform: uppercase;
  letter-spacing: 0.15em;
  margin-bottom: 16px;
}

.section-title {
  font-size: clamp(32px, 5vw, 52px);
  font-weight: 900;
  letter-spacing: -1px;
  margin: 0 0 16px;
  color: #fff;
}

.section-title .accent {
  color: var(--primary-color);
}

.section-subtitle {
  font-size: 16px;
  color: var(--text-secondary);
  max-width: 500px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 100px 0;
  color: var(--text-secondary);
}

.loader-ring {
  width: 52px;
  height: 52px;
  border: 3px solid rgba(208, 255, 89, 0.15);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ═══════════════════════════════════
   GRILLE PRODUITS
═══════════════════════════════════ */
.products-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* ═══════════════════════════════════
   CARTE PRODUIT
═══════════════════════════════════ */
.product-card {
  position: relative;
  background: #111;
  border: 1px solid var(--border-color);
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.3s ease, box-shadow 0.3s ease;
  opacity: 0;
  animation: card-appear 0.5s ease forwards;
}

@keyframes card-appear {
  from { opacity: 0; transform: translateY(24px); }
  to { opacity: 1; transform: translateY(0); }
}

.product-card:hover {
  transform: translateY(-6px);
  border-color: rgba(208, 255, 89, 0.35);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(208, 255, 89, 0.15);
}

.card-glow {
  position: absolute;
  inset: 0;
  border-radius: 20px;
  opacity: 0;
  background: radial-gradient(circle at 50% 0%, rgba(208, 255, 89, 0.08), transparent 60%);
  transition: opacity 0.4s ease;
  pointer-events: none;
  z-index: 0;
}

.product-card:hover .card-glow {
  opacity: 1;
}

/* Image */
.card-image {
  position: relative;
  height: 260px;
  overflow: hidden;
  background: #1a1a1a;
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
  display: block;
}

.product-card:hover .card-img {
  transform: scale(1.06);
}

.card-img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 56px;
  background: linear-gradient(135deg, #1a1a1a, #111);
}

/* Overlay hover */
.card-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.product-card:hover .card-overlay {
  opacity: 1;
}

.overlay-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--primary-color);
  color: #0b0b0b;
  font-weight: 800;
  font-size: 14px;
  padding: 12px 24px;
  border-radius: 50px;
  border: none;
  cursor: pointer;
  transform: translateY(12px);
  transition: transform 0.3s ease, background 0.2s ease;
}

.product-card:hover .overlay-btn {
  transform: translateY(0);
}

.overlay-btn:hover {
  background: #e0ff7a;
}

/* Badges */
.card-badges {
  position: absolute;
  top: 14px;
  left: 14px;
  right: 14px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  pointer-events: none;
  z-index: 2;
}

.badge-custom {
  background: var(--primary-color);
  color: #0b0b0b;
  font-size: 10px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}


/* Body de la carte */
.card-body {
  position: relative;
  z-index: 1;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card-title {
  font-size: 17px;
  font-weight: 700;
  color: #fff;
  margin: 0;
  line-height: 1.3;
}

.card-desc {
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.55;
  margin: 0;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  padding-top: 14px;
  border-top: 1px solid var(--border-color);
}

.card-price {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.price-amount {
  font-size: 22px;
  font-weight: 900;
  color: var(--primary-color);
}

.price-ttc {
  font-size: 11px;
  color: var(--text-secondary);
  font-weight: 500;
}

.card-cta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(208, 255, 89, 0.1);
  border: 1px solid rgba(208, 255, 89, 0.3);
  color: var(--primary-color);
  font-size: 13px;
  font-weight: 700;
  padding: 9px 16px;
  border-radius: 50px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.card-cta:hover:not(.disabled) {
  background: var(--primary-color);
  color: #0b0b0b;
  border-color: var(--primary-color);
}

.card-cta.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: var(--text-secondary);
}

/* ═══════════════════════════════════
   EMPTY STATE
═══════════════════════════════════ */
.empty-state {
  text-align: center;
  padding: 100px 20px;
}

.empty-icon {
  font-size: 80px;
  opacity: 0.3;
  margin-bottom: 24px;
}

.empty-state h3 {
  font-size: 24px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 12px;
}

.empty-state p {
  font-size: 15px;
  color: var(--text-secondary);
  max-width: 440px;
  margin: 0 auto;
  line-height: 1.65;
}

/* ═══════════════════════════════════
   CTA FINALE
═══════════════════════════════════ */
.cta-section {
  position: relative;
  padding: 120px 0;
  overflow: hidden;
  border-top: 1px solid var(--border-color);
}

.cta-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, #0b0b0b 0%, #0e110a 100%);
}

.cta-glow {
  position: absolute;
  width: 700px;
  height: 400px;
  background: rgba(208, 255, 89, 0.07);
  border-radius: 50%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  filter: blur(100px);
}

.cta-content {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 640px;
  margin: 0 auto;
}

.cta-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 24px;
  display: block;
  animation: rotate 20s linear infinite;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}

.cta-title {
  font-size: clamp(28px, 4vw, 42px);
  font-weight: 900;
  letter-spacing: -1px;
  color: #fff;
  margin: 0 0 18px;
  line-height: 1.15;
}

.cta-desc {
  font-size: 16px;
  color: var(--text-secondary);
  line-height: 1.7;
  margin: 0 0 40px;
}

.cta-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-cta-outline {
  display: inline-flex;
  align-items: center;
  padding: 14px 32px;
  border-radius: 50px;
  border: 1px solid rgba(208, 255, 89, 0.35);
  color: var(--primary-color);
  font-weight: 700;
  font-size: 14px;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-cta-outline:hover {
  background: rgba(208, 255, 89, 0.08);
  border-color: var(--primary-color);
  transform: translateY(-2px);
}

/* ═══════════════════════════════════
   RESPONSIVE
═══════════════════════════════════ */
@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .hero-content {
    padding: 80px 0 60px;
  }

  .hero-title {
    letter-spacing: -1px;
  }

  .hero-stats {
    gap: 20px;
  }

  .stat-divider {
    display: none;
  }

  .products-section {
    padding: 60px 0;
  }

  .products-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .usp-sep {
    display: none;
  }

  .usp-inner {
    gap: 16px;
  }

  .cta-section {
    padding: 80px 0;
  }
}

@media (max-width: 480px) {
  .hero-tag {
    font-size: 11px;
    padding: 6px 14px;
  }

  .hero-stats {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

}
</style>
