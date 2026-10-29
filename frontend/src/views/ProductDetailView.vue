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
            <ProductGallery
              :images="galleryUrls"
              :video="currentVideo"
              :alt="selectedVariante ? `${product.nom} — ${selectedVariante.nom_couleur}` : product.nom"
            />

            <!-- Variantes de couleur -->
            <div v-if="product.variantes_couleur && product.variantes_couleur.length > 0" class="color-variants">
              <h4 class="variants-title">
                Coloris : <span class="variant-current">{{ selectedVariante?.nom_couleur }}</span>
              </h4>
              <div class="variants-list">
                <button
                  v-for="variante in product.variantes_couleur"
                  :key="variante.id"
                  type="button"
                  @click="selectVariante(variante)"
                  :class="['variant-swatch', { active: selectedVariante?.id === variante.id }]"
                  :title="variante.nom_couleur"
                  :aria-label="`Coloris ${variante.nom_couleur}`"
                  :aria-pressed="selectedVariante?.id === variante.id"
                  :disabled="!variante.actif"
                >
                  <img
                    v-if="variante.image_url"
                    :src="sizedUrl(getImageUrl(variante.image_url), 400)"
                    alt=""
                    loading="lazy"
                  >
                  <span v-else class="swatch-dot" :style="{ backgroundColor: variante.code_couleur || '#333' }"></span>
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
                <span v-if="isCustomProduct" class="price-from">À partir de</span>
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
                  <p>Les photos présentées sont des exemples de réalisations. Envoyez-nous vos photos (PNG ou JPEG) et décrivez votre projet : nous revenons vers vous par e-mail avec un devis et un aperçu avant impression. Vos photos ne servent qu'à votre commande, dans le respect du droit à l'image.</p>
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
                  <p>Demande envoyée ! Nous revenons vers vous par e-mail avec un devis.</p>
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
                Ajoutez au moins une photo pour envoyer votre demande
              </p>
            </div>

            <div v-if="!isCustomProduct" class="product-actions">
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
                <span v-else>Ajouter au panier</span>
              </button>
              <ul class="delivery-info">
                <li>Livraison offerte dès 50 € d'achat</li>
                <li>Expédition sous 5 à 8 jours</li>
                <li>Paiement sécurisé par carte</li>
              </ul>
            </div>

            <form v-else-if="!filesSent" class="product-actions quote-form" @submit.prevent="sendQuote">
              <label for="quote-email">Votre e-mail</label>
              <input id="quote-email" v-model="quoteEmail" type="email" required autocomplete="email" class="quote-input">
              <label for="quote-message">Votre projet</label>
              <textarea
                id="quote-message"
                v-model="quoteMessage"
                rows="4"
                maxlength="2000"
                class="quote-input"
                placeholder="Qui représenter, pose souhaitée, taille, couleur, délai…"
              ></textarea>
              <button type="submit" class="btn btn-primary add-to-cart-btn" :disabled="!canSendQuote || addingToCart">
                {{ addingToCart ? 'Envoi en cours…' : 'Envoyer ma demande de devis' }}
              </button>
            </form>
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
import { sizedUrl } from '../composables/useResponsiveImage'
import ProductGallery from '../components/ProductGallery.vue'

const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { success: showSuccess, error: showError } = useAlert()

const isEntreprise = computed(() => authStore.user?.type_client === 'entreprise')

const loading = ref(true)
const product = ref(null)
const quantity = ref(1)
const selectedVariante = ref(null)

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

const galleryUrls = computed(() => galleryImages.value.map(i => getImageUrl(i.url)))
const currentVideo = computed(() => selectedVariante.value?.video_url || product.value?.video_url || null)

const maxQuantity = computed(() => 99)

const canAddToCart = computed(() => !!product.value?.actif)

// Demande de devis (produits personnalisés)
const quoteEmail = ref(authStore.user?.email || '')
const quoteMessage = ref('')
const canSendQuote = computed(() => uploadedFiles.value.length > 0 && /\S+@\S+\.\S+/.test(quoteEmail.value))

const loadProduct = async () => {
  try {
    const isCustom = route.query.type === 'custom'
    const endpoint = isCustom
      ? `/api/produits-personnalises/${route.params.id}`
      : `/api/produits/${route.params.id}`
    const response = await fetch(endpoint)
    if (response.ok) {
      product.value = await response.json()
      const premiere = product.value.variantes_couleur?.find(v => v.actif)
      if (premiere) selectVariante(premiere)

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
}

const addToCart = () => {
  if (!canAddToCart.value) return
  cartStore.addToCart({
    ...product.value,
    variante_selectionnee: selectedVariante.value ? {
      id: selectedVariante.value.id,
      nom_couleur: selectedVariante.value.nom_couleur,
      reference: selectedVariante.value.reference,
      image_url: selectedVariante.value.image_url
    } : null
  }, quantity.value)
  showSuccess('Produit ajouté au panier !')
}

const sendQuote = async () => {
  if (!canSendQuote.value || addingToCart.value) return
  addingToCart.value = true
  const formData = new FormData()
  uploadedFiles.value.forEach(f => formData.append('files[]', f))
  formData.append('product_name', product.value.nom)
  formData.append('product_id', product.value.id)
  formData.append('client_email', quoteEmail.value)
  formData.append('client_nom', authStore.user?.nom || 'Client')
  formData.append('message', quoteMessage.value)
  try {
    const res = await fetch('/api/custom-order-files', { method: 'POST', body: formData })
    if (!res.ok) throw new Error()
    filesSent.value = true
    showSuccess('Demande envoyée ! Nous revenons vers vous par e-mail avec un devis.')
  } catch {
    showError("Erreur lors de l'envoi de votre demande. Veuillez réessayer.")
  } finally {
    addingToCart.value = false
  }
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

/* Sans ça, la bande de miniatures élargit la colonne et la page déborde sur mobile */
.product-content > * { min-width: 0; }

.product-images {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.color-variants {
  margin-top: 20px;
}


.variants-title {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  margin: 0;
}



.variants-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
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
}

@media (max-width: 768px) {
  .product-detail-page { padding: 24px 0; }
  .product-title  { font-size: 22px; }
  .price-amount   { font-size: 26px; }
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
  .product-content { gap: 24px; }
  .product-info   { gap: 20px; }

  .quantity-btn   { width: 36px; height: 36px; font-size: 16px; }
  .quantity-input { width: 60px; height: 36px; font-size: 14px; }

  .add-to-cart-btn { padding: 13px; font-size: 14px; }
}

.placeholder-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.variants-title { margin-bottom: 12px; }
.variant-current { color: var(--primary-color); font-weight: 600; }
.variants-list { display: flex; flex-wrap: wrap; gap: 10px; }
.variant-swatch {
  width: 64px;
  height: 64px;
  padding: 0;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: var(--surface);
  border: 2px solid var(--border-color);
  transition: border-color 0.2s, transform 0.2s;
}
.variant-swatch img { width: 100%; height: 100%; object-fit: contain; }
.variant-swatch:hover:not(:disabled) { transform: translateY(-2px); border-color: rgba(208, 255, 89, 0.5); }
.variant-swatch.active { border-color: var(--primary-color); }
.variant-swatch:disabled { opacity: 0.35; cursor: not-allowed; }
.swatch-dot { display: block; width: 100%; height: 100%; }
@media (max-width: 600px) {
  .variant-swatch { width: 56px; height: 56px; }
}

.price-from { color: var(--text-secondary); font-size: 14px; margin-right: 6px; }
.delivery-info {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}
.delivery-info li::before { content: '✓ '; color: var(--primary-color); }
.quote-form { display: flex; flex-direction: column; gap: 8px; }
.quote-form label { font-size: 13px; color: var(--text-secondary); }
.quote-input {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  background: var(--dark-bg);
  color: var(--text-primary);
  font: inherit;
  resize: vertical;
}
.quote-input:focus { outline: none; border-color: var(--primary-color); }
.quote-form .add-to-cart-btn { margin-top: 8px; }
</style>
