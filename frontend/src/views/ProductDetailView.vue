<template>
  <div class="product-detail-page">
    <div class="container">
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Chargement du produit...</p>
      </div>

      <div v-else-if="!product" class="not-found">
        <div class="not-found-icon">🔍</div>
        <h2>Produit non trouvé</h2>
        <p>Le produit que vous recherchez n'existe pas ou a été supprimé.</p>
        <router-link to="/products" class="btn btn-primary">
          Voir tous les produits
        </router-link>
      </div>

      <div v-else class="product-detail">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
          <router-link to="/" class="breadcrumb-link">Accueil</router-link>
          <span class="breadcrumb-separator">></span>
          <router-link to="/products" class="breadcrumb-link">Produits</router-link>
          <span class="breadcrumb-separator">></span>
          <span class="breadcrumb-current">{{ product.nom }}</span>
        </nav>

        <div class="product-content">
          <!-- Images -->
            <div class="product-images">
            <div class="main-image">
              <img 
                v-if="currentImage" 
                :src="currentImage" 
                :alt="product.nom"
                class="product-img" @error="e => { e.target.onerror = null; e.target.src = '/placeholder.svg' }"
              >
              <div v-else class="image-placeholder">
                <img src="/placeholder.svg" alt="" class="placeholder-img">
              </div>
            </div>
            
            <!-- Bande de miniatures -->
            <div v-if="galleryImages.length > 1" class="thumbnail-strip">
              <button
                v-for="(img, idx) in galleryImages"
                :key="idx"
                :class="['thumb-btn', { active: currentImage === getImageUrl(img.url) }]"
                @click="currentImage = getImageUrl(img.url)"
                type="button"
              >
                <img :src="getImageUrl(img.url)" class="thumb-img" @error="e => { e.target.onerror = null; e.target.src = '/placeholder.svg' }">
              </button>
            </div>

            <!-- Variantes de couleur -->
            <div v-if="product.variantes_couleur && product.variantes_couleur.length > 0" class="color-variants">
              <div class="variants-header">
                <h4 class="variants-title">Couleurs disponibles</h4>
                <button
                  v-if="selectedVariante !== null"
                  @click="resetVariante"
                  class="reset-variant-btn"
                  title="Retour au produit original"
                >
                  ↩ Produit original
                </button>
              </div>
              <div class="variants-list">
                <button
                  v-for="variante in product.variantes_couleur"
                  :key="variante.id"
                  @click="selectVariante(variante)"
                  :class="['variant-btn', { 'active': selectedVariante?.id === variante.id }]"
                  :style="{ backgroundColor: variante.code_couleur || '#333' }"
                  :title="variante.nom_couleur"
                  :disabled="!variante.actif"
                >
                  <span class="variant-name">{{ variante.nom_couleur }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Informations produit -->
          <div class="product-info">
            <div class="product-header">
              <div class="product-category" v-if="product.categorie">
                {{ product.categorie.nom }}
              </div>
              <h1 class="product-title">{{ product.nom }}</h1>
              <div v-if="selectedVariante?.reference" class="product-reference">
                <span class="reference-label">Référence :</span>
                <span class="reference-value">{{ selectedVariante.reference }}</span>
              </div>
              <div class="product-price">
                <span class="price-amount">{{ formatPrice(product.prix) }}</span>
                <span class="price-currency">€</span>
                <span v-if="isEntreprise" class="price-ttc"> TTC</span>
              </div>
            </div>

            <div class="product-description" v-if="product.description">
              <h3>Description</h3>
              <p>{{ product.description }}</p>
            </div>

            <div class="product-specs" v-if="selectedVariante">
              <h3>Spécifications</h3>
              <div class="specs-grid">
                <div class="spec-item">
                  <span class="spec-label">Couleur sélectionnée</span>
                  <span class="spec-value">{{ selectedVariante.nom_couleur }}</span>
                </div>
              </div>
            </div>

            <!-- ── Section upload (produits personnalisés uniquement) ── -->
            <div v-if="isCustomProduct" class="custom-upload-section">
              <!-- Bannière d'avertissement -->
              <div class="custom-notice">
                <span class="notice-icon">🎨</span>
                <div>
                  <strong>Produit personnalisé</strong>
                  <p>L'image présentée est un exemple. Vous ne recevrez pas ce produit avec cette image. Envoyez vos propres photos (PNG ou JPEG) pour que nous puissions créer votre produit sur mesure.</p>
                </div>
              </div>

              <!-- Zone drag & drop -->
              <div
                class="upload-zone"
                :class="{ dragging: isDragging, 'has-files': uploadedFiles.length > 0, 'files-sent': filesSent }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                @click="!filesSent && fileInput.click()"
              >
                <input
                  ref="fileInput"
                  type="file"
                  accept="image/png,image/jpeg"
                  multiple
                  class="file-input-hidden"
                  @change="onFileChange"
                >
                <div v-if="filesSent" class="upload-state">
                  <span class="state-icon">✅</span>
                  <p>Photos envoyées à l'admin avec succès !</p>
                </div>
                <div v-else-if="uploadedFiles.length === 0" class="upload-state">
                  <span class="state-icon">📁</span>
                  <p><strong>Glissez vos photos ici</strong></p>
                  <p class="upload-hint">ou cliquez pour parcourir</p>
                  <span class="upload-formats">PNG · JPEG uniquement · Max 10 Mo / fichier</span>
                </div>
                <div v-else class="upload-state">
                  <span class="state-icon">📸</span>
                  <p><strong>{{ uploadedFiles.length }} photo{{ uploadedFiles.length > 1 ? 's' : '' }} sélectionnée{{ uploadedFiles.length > 1 ? 's' : '' }}</strong></p>
                  <p class="upload-hint">Cliquez pour en ajouter d'autres (max 10)</p>
                </div>
              </div>

              <!-- Aperçus fichiers -->
              <div v-if="filePreviewUrls.length > 0 && !filesSent" class="files-preview">
                <div v-for="(file, idx) in filePreviewUrls" :key="idx" class="file-preview-item">
                  <img :src="file.url" :alt="file.name" class="preview-img" @error="e => { e.target.onerror = null; e.target.src = '/placeholder.svg' }">
                  <button @click.stop="removeFile(idx)" class="remove-file-btn" title="Supprimer">×</button>
                  <span class="file-name">{{ file.name }}</span>
                </div>
              </div>

              <p v-if="!filesSent && uploadedFiles.length === 0" class="upload-required-hint">
                ⚠️ Ajoutez au moins une photo pour pouvoir commander ce produit
              </p>
            </div>

            <div class="product-actions">
              <div class="quantity-selector">
                <label for="quantity">Quantité</label>
                <div class="quantity-controls">
                  <button
                    class="quantity-btn"
                    @click="decreaseQuantity"
                    :disabled="quantity <= 1"
                  >
                    −
                  </button>
                  <input
                    type="number"
                    id="quantity"
                    v-model="quantity"
                    class="quantity-input"
                    min="1"
                    :max="maxQuantity"
                  >
                  <button
                    class="quantity-btn"
                    @click="increaseQuantity"
                  >
                    +
                  </button>
                </div>
              </div>

              <button
                class="btn btn-primary add-to-cart-btn"
                @click="addToCart"
                :disabled="!canAddToCart || addingToCart"
              >
                <span v-if="!product.actif">Produit indisponible</span>
                <span v-else-if="addingToCart">⏳ Envoi en cours…</span>
                <span v-else-if="isCustomProduct && uploadedFiles.length === 0">📁 Ajoutez vos photos d'abord</span>
                <span v-else>🛒 Ajouter au panier</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'
import { useSeo, BASE_URL } from '../composables/useSeo'

const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { success: showSuccess, error: showError } = useAlert()

const isEntreprise = computed(() => authStore.user?.type_client === 'entreprise')

const loading = ref(true)
const product = ref(null)
const quantity = ref(1)
const selectedVariante = ref(null)
const currentImage = ref(null)

// ── Produit personnalisé — upload fichiers ─────────────────────────────────
const isCustomProduct = computed(() => route.query.type === 'custom')
const uploadedFiles   = ref([])
const filePreviewUrls = ref([])
const isDragging      = ref(false)
const filesSent       = ref(false)
const addingToCart    = ref(false)
const fileInput       = ref(null)
const ALLOWED_TYPES   = ['image/png', 'image/jpeg', 'image/jpg']

const addFiles = (newFiles) => {
  for (const f of newFiles) {
    if (uploadedFiles.value.length >= 10) break
    if (!ALLOWED_TYPES.includes(f.type)) continue
    if (f.size > 10 * 1024 * 1024) continue
    uploadedFiles.value.push(f)
    filePreviewUrls.value.push({ url: URL.createObjectURL(f), name: f.name })
  }
}
const onFileChange = (e) => { addFiles(Array.from(e.target.files)); e.target.value = '' }
const onDrop       = (e) => { isDragging.value = false; addFiles(Array.from(e.dataTransfer.files)) }
const removeFile   = (idx) => {
  URL.revokeObjectURL(filePreviewUrls.value[idx].url)
  uploadedFiles.value.splice(idx, 1)
  filePreviewUrls.value.splice(idx, 1)
}
// ──────────────────────────────────────────────────────────────────────────

// Gallery images: variante gallery if selected, otherwise product gallery
const galleryImages = computed(() => {
  if (selectedVariante.value) {
    // Variante sélectionnée → afficher uniquement ses images
    const imgs = selectedVariante.value.images ? [...selectedVariante.value.images] : []
    const imageUrl = selectedVariante.value.image_url
    if (imageUrl && !imgs.some(i => i.url === imageUrl)) {
      imgs.unshift({ url: imageUrl })
    }
    return imgs
  }
  if (product.value) {
    const imgs = product.value.images ? [...product.value.images] : []
    const principale = product.value.image_principale
    if (principale && !imgs.some(i => i.url === principale)) {
      imgs.unshift({ url: principale })
    }
    return imgs
  }
  return []
})

const maxQuantity = computed(() => 99)

const canAddToCart = computed(() => {
  if (!product.value?.actif) return false
  if (isCustomProduct.value && uploadedFiles.value.length === 0) return false
  return true
})

const loadProduct = async () => {
  try {
    const isCustom = route.query.type === 'custom'
    const endpoint = isCustom
      ? `/api/produits-personnalises/${route.params.id}`
      : `/api/produits/${route.params.id}`
    const response = await fetch(endpoint)
    if (response.ok) {
      product.value = await response.json()
      const firstGallery = product.value.images && product.value.images.length > 0
        ? product.value.images[0].url
        : null
      currentImage.value = getImageUrl(firstGallery || product.value.image_principale)

      // SEO dynamique — titre + description + JSON-LD Product
      const p = product.value
      const pageUrl = `${BASE_URL}/product/${p.id}${isCustom ? '?type=custom' : ''}`
      const imageAbs = p.image_principale
        ? (p.image_principale.startsWith('http') ? p.image_principale : `${BASE_URL}${p.image_principale.startsWith('/') ? '' : '/uploads/'}${p.image_principale}`)
        : `${BASE_URL}/logo.png`

      useSeo({
        title: p.nom,
        description: p.description
          ? p.description.replace(/<[^>]+>/g, '').substring(0, 155)
          : `${p.nom} — Impression 3D de qualité sur Al3DPrint.`,
        keywords: `impression 3D, ${p.nom}, ${p.categorie || 'objet 3D'}, al3dprint`,
        image: imageAbs,
        url: pageUrl,
        jsonLd: {
          '@context': 'https://schema.org',
          '@type': 'Product',
          name: p.nom,
          description: p.description ? p.description.replace(/<[^>]+>/g, '') : p.nom,
          image: imageAbs,
          url: pageUrl,
          brand: { '@type': 'Brand', name: 'Al3DPrint' },
          offers: {
            '@type': 'Offer',
            priceCurrency: 'EUR',
            price: parseFloat(p.prix || 0).toFixed(2),
            availability: p.actif ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            seller: { '@type': 'Organization', name: 'Al3DPrint' }
          }
        }
      })
    } else if (response.status === 404) {
      product.value = null
    }
  } catch {
    product.value = null
  } finally {
    loading.value = false
  }
}

const getImageUrl = (chemin) => {
  if (!chemin) return ''
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) {
    return chemin
  }
  if (chemin.startsWith('/uploads/')) {
    return chemin
  }
  if (!chemin.startsWith('/')) {
    return `/uploads/${chemin}`
  }
  return chemin
}

const selectVariante = (variante) => {
  selectedVariante.value = variante
  const firstImg = variante.images && variante.images.length > 0
    ? variante.images[0].url
    : null
  currentImage.value = getImageUrl(firstImg || variante.image_url || product.value.image_principale)
}

const resetVariante = () => {
  selectedVariante.value = null
  const firstImg = product.value.images && product.value.images.length > 0
    ? product.value.images[0].url
    : null
  currentImage.value = getImageUrl(firstImg || product.value.image_principale)
  quantity.value = 1
}

const addToCart = async () => {
  if (!canAddToCart.value || addingToCart.value) return
  addingToCart.value = true

  // Pour les produits personnalisés : envoyer les fichiers à l'admin d'abord
  if (isCustomProduct.value && !filesSent.value && uploadedFiles.value.length > 0) {
    const formData = new FormData()
    uploadedFiles.value.forEach(f => formData.append('files[]', f))
    formData.append('product_name', product.value.nom)
    formData.append('product_id',   product.value.id)
    formData.append('client_email', authStore.user?.email || 'non renseigné')
    formData.append('client_nom',   authStore.user?.nom   || 'Client')
    try {
      const res = await fetch('/api/custom-order-files', { method: 'POST', body: formData })
      if (!res.ok) throw new Error()
      filesSent.value = true
    } catch {
      showError("Erreur lors de l'envoi des fichiers. Veuillez réessayer.")
      addingToCart.value = false
      return
    }
  }

  const productToAdd = {
    ...product.value,
    variante_selectionnee: selectedVariante.value ? {
      id: selectedVariante.value.id,
      nom_couleur: selectedVariante.value.nom_couleur,
      reference: selectedVariante.value.reference
    } : null
  }
  cartStore.addToCart(productToAdd, quantity.value)
  addingToCart.value = false
  showSuccess(isCustomProduct.value
    ? 'Vos photos ont été envoyées à l\'admin et le produit a été ajouté au panier !'
    : 'Produit ajouté au panier !'
  )
}

const increaseQuantity = () => {
  if (quantity.value < maxQuantity.value) {
    quantity.value++
  }
}

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
}

onMounted(() => {
  loadProduct()
})

onUnmounted(() => {
  filePreviewUrls.value.forEach(f => URL.revokeObjectURL(f.url))
})
</script>

<style scoped>
.product-detail-page {
  min-height: 100vh;
  padding: 40px 0;
}

.loading-container,
.not-found {
  text-align: center;
  padding: 80px 20px;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 3px solid var(--border-color);
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.not-found-icon {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 30px;
  font-size: 14px;
}

.breadcrumb-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: all 0.3s ease;
}

.breadcrumb-link:hover {
  color: var(--accent-color);
}

.breadcrumb-separator {
  color: var(--text-secondary);
}

.breadcrumb-current {
  color: var(--text-primary);
  font-weight: 600;
}

.product-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
}

.product-images {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.color-variants {
  margin-top: 20px;
}

.variants-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 15px;
}

.variants-title {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  margin: 0;
}

.reset-variant-btn {
  background: none;
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.reset-variant-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.variants-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.variant-btn {
  min-width: 80px;
  height: 50px;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px 12px;
  transition: all 0.3s ease;
  position: relative;
  background: var(--dark-secondary);
  color: white;
  font-size: 12px;
  font-weight: 600;
}

.variant-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(208, 255, 89, 0.3);
  border-color: var(--primary-color);
}

.variant-btn.active {
  border-color: var(--primary-color);
  box-shadow: 0 0 15px rgba(208, 255, 89, 0.5);
  transform: scale(1.05);
}

.variant-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  position: relative;
}

.variant-name {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
  font-size: 11px;
}


.product-reference {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 10px 0;
  padding: 8px 12px;
  background: rgba(208, 255, 89, 0.1);
  border: 1px solid var(--primary-color);
  border-radius: 6px;
  font-size: 14px;
}

.reference-label {
  color: var(--text-secondary);
  font-weight: 500;
}

.reference-value {
  color: var(--primary-color);
  font-weight: 600;
  font-family: monospace;
}

.main-image {
  width: 100%;
  height: 400px;
  border-radius: 15px;
  overflow: hidden;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
}

.product-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center center;
  background: #111;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-icon {
  font-size: 64px;
  opacity: 0.5;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.product-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.product-category {
  color: var(--primary-color);
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.product-title {
  font-size: 32px;
  font-weight: bold;
  color: var(--text-primary);
  line-height: 1.2;
}

.product-price {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.price-amount {
  font-size: 36px;
  font-weight: bold;
  color: var(--primary-color);
}

.price-currency {
  font-size: 20px;
  color: var(--text-secondary);
}

.product-description h3,
.product-specs h3 {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 15px;
}

.product-description p {
  color: var(--text-secondary);
  line-height: 1.6;
  font-size: 16px;
}

.specs-grid {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.spec-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--border-color);
  border-radius: 10px;
}

.spec-label {
  color: var(--text-secondary);
  font-weight: 500;
}

.spec-value {
  color: var(--text-primary);
  font-weight: 600;
}


.spec-value.inactive {
  color: var(--text-secondary);
}

.product-actions {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 30px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
}

.quantity-selector {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quantity-selector label {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.quantity-btn {
  width: 40px;
  height: 40px;
  border: 1px solid var(--border-color);
  background: var(--dark-secondary);
  color: var(--text-primary);
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  transition: all 0.3s ease;
}

.quantity-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: #050505;
}

.quantity-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quantity-input {
  width: 80px;
  height: 40px;
  border: 1px solid var(--border-color);
  background: var(--dark-secondary);
  color: var(--text-primary);
  border-radius: 8px;
  text-align: center;
  font-size: 16px;
  font-weight: 600;
}

.quantity-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.add-to-cart-btn {
  width: 100%;
  padding: 15px;
  font-size: 16px;
  font-weight: 600;
}

.add-to-cart-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.thumbnail-strip {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.thumb-btn {
  width: 70px;
  height: 70px;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  padding: 0;
  background: none;
  transition: border-color 0.2s;
}

.thumb-btn:hover,
.thumb-btn.active {
  border-color: var(--primary-color);
}

.thumb-btn.active {
  box-shadow: 0 0 8px rgba(208, 255, 89, 0.5);
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center center;
  display: block;
  background: #111;
}

/* ── Section upload produit personnalisé ───────────────────────────────── */
.custom-upload-section {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.custom-notice {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(124, 58, 237, 0.08);
  border: 1px solid rgba(124, 58, 237, 0.35);
  border-radius: 10px;
  align-items: flex-start;
}

.notice-icon {
  font-size: 22px;
  flex-shrink: 0;
  margin-top: 1px;
}

.custom-notice strong {
  display: block;
  color: #a78bfa;
  font-size: 14px;
  margin-bottom: 4px;
}

.custom-notice p {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.55;
  margin: 0;
}

.upload-zone {
  border: 2px dashed var(--border-color);
  border-radius: 12px;
  padding: 28px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
  background: rgba(20, 20, 20, 0.4);
  user-select: none;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-zone:hover,
.upload-zone.dragging {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.05);
}

.upload-zone.has-files {
  border-color: rgba(204, 240, 212, 0.6);
  background: rgba(204, 240, 212, 0.04);
}

.upload-zone.files-sent {
  border-color: rgba(16, 185, 129, 0.6);
  background: rgba(16, 185, 129, 0.05);
  cursor: default;
}

.file-input-hidden { display: none; }

.upload-state { display: flex; flex-direction: column; align-items: center; gap: 4px; }

.state-icon {
  font-size: 34px;
  display: block;
  margin-bottom: 6px;
}

.upload-state p {
  color: var(--text-primary);
  margin: 0;
  font-size: 14px;
}

.upload-hint {
  color: var(--text-secondary) !important;
  font-size: 12px !important;
}

.upload-formats {
  display: inline-block;
  margin-top: 8px;
  background: rgba(208, 255, 89, 0.1);
  color: var(--primary-color);
  font-size: 11px;
  padding: 3px 12px;
  border-radius: 20px;
  font-weight: 600;
}

.files-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.file-preview-item {
  position: relative;
  width: 76px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.preview-img {
  width: 76px;
  height: 76px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  display: block;
}

.remove-file-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #ff6b6b;
  border: none;
  color: white;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.file-name {
  font-size: 10px;
  color: var(--text-secondary);
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 76px;
}

.upload-required-hint {
  color: #fbbf24;
  font-size: 13px;
  text-align: center;
  margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .product-content {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  
  .main-image {
    height: 300px;
  }
}

@media (max-width: 768px) {
  .product-detail-page { padding: 24px 0; }
  .product-title  { font-size: 22px; }
  .price-amount   { font-size: 26px; }
  .main-image     { height: 240px; }
  .product-actions { padding: 18px; gap: 16px; }
  .spec-item { padding: 12px; }
  .breadcrumb { font-size: 13px; }

  .custom-notice { padding: 12px; }
  .custom-notice p { font-size: 12px; }
  .upload-zone { padding: 20px 14px; }
  .file-preview-item { width: 66px; }
  .preview-img { width: 66px; height: 66px; }
  .file-name { max-width: 66px; }
}

@media (max-width: 480px) {
  .product-detail-page { padding: 16px 0; }
  .product-title  { font-size: 20px; }
  .price-amount   { font-size: 24px; }
  .price-currency { font-size: 16px; }
  .main-image     { height: 200px; border-radius: 10px; }
  .product-content { gap: 24px; }
  .product-info   { gap: 20px; }

  .thumb-btn { width: 58px; height: 58px; }

  .variant-btn { min-width: 68px; height: 44px; font-size: 11px; }

  .quantity-btn   { width: 36px; height: 36px; font-size: 16px; }
  .quantity-input { width: 60px; height: 36px; font-size: 14px; }

  .add-to-cart-btn { padding: 13px; font-size: 14px; }
}

.placeholder-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
