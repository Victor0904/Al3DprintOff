<template>
  <div class="products-management">
    <div class="management-header">
      <h2>🛍️ Gestion des Produits</h2>
      <button @click="showAddModal = true" class="add-btn">
        <span>➕</span>
        Ajouter un produit
      </button>
    </div>

    <!-- Filtres et Recherche -->
    <div class="filters-section">
      <div class="search-bar">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher un produit..."
          class="search-input"
        >
      </div>
      
      <div class="filter-controls">
        <select v-model="selectedCategory" class="filter-select">
          <option value="">Toutes les catégories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.nom }}
          </option>
        </select>
        
        <select v-model="statusFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="true">Actifs</option>
          <option value="false">Inactifs</option>
        </select>
      </div>
    </div>

    <!-- Grille des Produits -->
    <div class="products-grid">
      <div v-for="product in filteredProducts" :key="product.id" class="product-card">
        <div class="product-card-image">
          <img :src="getImageUrl(product.image_principale) || '/placeholder-product.jpg'" :alt="product.nom" class="product-thumb">
        </div>
        
        <div class="product-card-content">
          <div class="product-card-header">
            <h4 class="product-card-name">{{ product.nom }}</h4>
            <span :class="['status-badge', product.actif ? 'active' : 'inactive']">
              {{ product.actif ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          
          <p class="product-description">{{ product.description?.substring(0, 80) }}{{ product.description?.length > 80 ? '...' : '' }}</p>
          
          <div class="product-card-info">
            <div class="info-item">
              <span class="info-label">Catégorie:</span>
              <span class="category-badge">{{ getCategoryName(product.categorie?.id) }}</span>
            </div>
            
            <div class="info-item">
              <span class="info-label">Prix:</span>
              <span class="price">{{ product.prix }}€</span>
            </div>
            
          </div>
          
          <div class="product-card-actions">
            <button @click="editProduct(product)" class="action-btn edit" title="Modifier">
              ✏️
            </button>
            <button @click="toggleProductStatus(product)" class="action-btn toggle" :title="product.actif ? 'Désactiver' : 'Activer'">
              {{ product.actif ? '⏸️' : '▶️' }}
            </button>
            <button @click="deleteProduct(product.id)" class="action-btn delete" title="Supprimer">
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'Ajout/Modification -->
    <div v-if="showAddModal || editingProduct" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ editingProduct ? 'Modifier le produit' : 'Ajouter un produit' }}</h3>
          <button @click="closeModal" class="close-btn">×</button>
        </div>
        
        <form @submit.prevent="saveProduct" class="product-form">
          <div class="form-row">
            <div class="form-group">
              <label>Nom du produit</label>
              <input v-model="productForm.nom" type="text" required class="form-input">
            </div>
            
            <div class="form-group">
              <label>Prix (€)</label>
              <input v-model="productForm.prix" type="number" step="0.01" required class="form-input">
            </div>
          </div>
          
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="productForm.description" class="form-textarea" rows="3"></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Catégorie parente</label>
              <select v-model="selectedParentCategory" @change="onParentCategoryChange" class="form-select">
                <option value="">Sélectionner une catégorie</option>
                <option v-for="category in parentCategories" :key="category.id" :value="category.id">
                  {{ category.nom }}
                </option>
              </select>
            </div>

            <div class="form-group" v-if="selectedParentCategory && subcategoriesOfSelected.length > 0">
              <label>Sous-catégorie <span class="optional">(optionnel)</span></label>
              <select v-model="selectedSubcategory" @change="onSubcategoryChange" class="form-select">
                <option value="">Catégorie parente uniquement</option>
                <option v-for="sub in subcategoriesOfSelected" :key="sub.id" :value="sub.id">
                  {{ sub.nom }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label>Image principale</label>
            <div class="image-selector">
              <select v-model="productForm.image_principale" class="form-select">
                <option value="">Aucune image</option>
                <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                  {{ image.nom }} ({{ image.chemin }})
                </option>
              </select>
              <button type="button" @click="openImageBank" class="btn-select-image">
                🖼️ Banque d'images
              </button>
            </div>
            <div v-if="productForm.image_principale" class="image-preview-small">
              <img :src="getImageUrl(productForm.image_principale)" :alt="'Aperçu'" class="preview-thumb">
            </div>
          </div>
          
          <!-- Galerie produit -->
          <div class="form-group">
            <label>Galerie d'images</label>
            <div class="image-selector">
              <select v-model="newProductImage" class="form-select">
                <option value="">Choisir une image</option>
                <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                  {{ image.nom }} ({{ image.chemin }})
                </option>
              </select>
              <button type="button" @click="addProductImage" class="btn-select-image">+ Ajouter</button>
              <button type="button" @click="openImageBank" class="btn-select-image">🖼️ Banque</button>
            </div>
            <div v-if="productForm.images && productForm.images.length > 0" class="gallery-previews">
              <div v-for="(img, idx) in productForm.images" :key="idx" class="gallery-thumb-item">
                <img :src="getImageUrl(img.url)" class="gallery-thumb-img">
                <button type="button" @click="removeProductImage(idx)" class="btn-remove-gallery">×</button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input v-model="productForm.actif" type="checkbox" class="checkbox">
              Produit actif
            </label>
          </div>

          <!-- Section Variantes de Couleur -->
          <div class="form-section">
            <div class="section-header">
              <h4>🎨 Variantes de Couleur</h4>
              <button type="button" @click="addVariante" class="btn-add-variante">+ Ajouter une variante</button>
            </div>
            
            <div v-if="productForm.variantes_couleur && productForm.variantes_couleur.length > 0" class="variantes-list">
              <div v-for="(variante, index) in productForm.variantes_couleur" :key="index" class="variante-item">
                <div class="variante-header">
                  <h5>Variante {{ index + 1 }}</h5>
                  <button type="button" @click="removeVariante(index)" class="btn-remove-variante">×</button>
                </div>
                <div class="variante-form">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Nom de la couleur</label>
                      <input v-model="variante.nom_couleur" type="text" required class="form-input" placeholder="Ex: Rouge, Bleu, Noir...">
                    </div>
                    <div class="form-group">
                      <label>Code couleur (hex)</label>
                      <input v-model="variante.code_couleur" type="color" class="form-input color-input">
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Image de la variante <span class="optional">(optionnel)</span></label>
                      <div class="image-selector">
                        <select v-model="variante.image_url" class="form-select">
                          <option value="">Aucune image</option>
                          <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                            {{ image.nom }} ({{ image.chemin }})
                          </option>
                        </select>
                        <button type="button" @click="openImageBank" class="btn-select-image">
                          🖼️ Banque
                        </button>
                      </div>
                      <div v-if="variante.image_url" class="image-preview-small">
                        <img :src="getImageUrl(variante.image_url)" :alt="'Aperçu variante'" class="preview-thumb">
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Référence</label>
                      <input v-model="variante.reference" type="text" class="form-input" placeholder="Référence unique">
                    </div>
                  </div>
                  <!-- Galerie variante -->
                  <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Galerie d'images de la variante</label>
                    <div class="image-selector">
                      <select v-model="variante._newImage" class="form-select">
                        <option value="">Choisir une image</option>
                        <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                          {{ image.nom }} ({{ image.chemin }})
                        </option>
                      </select>
                      <button type="button" @click="addVarianteImage(variante)" class="btn-select-image">+ Ajouter</button>
                      <button type="button" @click="openImageBank" class="btn-select-image">🖼️ Banque</button>
                    </div>
                    <div v-if="variante.images && variante.images.length > 0" class="gallery-previews">
                      <div v-for="(img, idx) in variante.images" :key="idx" class="gallery-thumb-item">
                        <img :src="getImageUrl(img.url)" class="gallery-thumb-img">
                        <button type="button" @click="removeVarianteImage(variante, idx)" class="btn-remove-gallery">×</button>
                      </div>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Stock</label>
                      <input v-model.number="variante.stock" type="number" min="0" class="form-input">
                    </div>
                    <div class="form-group">
                      <label>Ordre d'affichage</label>
                      <input v-model.number="variante.ordre" type="number" min="0" class="form-input">
                    </div>
                    <div class="form-group">
                      <label class="checkbox-label">
                        <input v-model="variante.actif" type="checkbox" class="checkbox">
                        Actif
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="no-variantes">
              <p>Aucune variante de couleur. Cliquez sur "Ajouter une variante" pour en créer une.</p>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">Annuler</button>
            <button type="submit" class="btn btn-primary">
              {{ editingProduct ? 'Modifier' : 'Ajouter' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Banque d'Images -->
    <div v-if="showImageBankModal" class="modal-overlay" @click="showImageBankModal = false">
      <div class="modal modal-large" @click.stop>
        <div class="modal-header">
          <h3>🖼️ Banque d'Images</h3>
          <button @click="showImageBankModal = false" class="close-btn">×</button>
        </div>
        <ImageBankManagement @close="showImageBankModal = false" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAlert } from '../../composables/useAlert'
import { useAuthFetch } from '../../composables/useAuthFetch'
import ImageBankManagement from './ImageBankManagement.vue'

const { confirm: showConfirm } = useAlert()
const { apiFetch } = useAuthFetch()
const products = ref([])
const categories = ref([])
const availableImages = ref([])
const searchQuery = ref('')
const selectedCategory = ref('')
const statusFilter = ref('')
const showAddModal = ref(false)
const editingProduct = ref(null)
const showImageBankModal = ref(false)

const selectedParentCategory = ref('')
const selectedSubcategory = ref('')

const newProductImage = ref('')

const productForm = ref({
  nom: '',
  description: '',
  prix: 0,
  stock: 999,
  categorie_id: '',
  image_principale: '',
  actif: true,
  images: [],
  variantes_couleur: []
})

// Computed — category hierarchy
const parentCategories = computed(() =>
  categories.value.filter(c => !c.parent_id)
)

const subcategoriesOfSelected = computed(() => {
  if (!selectedParentCategory.value) return []
  return categories.value.filter(c => c.parent_id === selectedParentCategory.value)
})

const onParentCategoryChange = () => {
  selectedSubcategory.value = ''
  productForm.value.categorie_id = selectedParentCategory.value || ''
}

const onSubcategoryChange = () => {
  productForm.value.categorie_id = selectedSubcategory.value || selectedParentCategory.value || ''
}

const filteredProducts = computed(() => {
  let filtered = products.value

  if (searchQuery.value) {
    filtered = filtered.filter(product => 
      product.nom.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      product.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(product => product.categorie?.id == selectedCategory.value)
  }

  if (statusFilter.value !== '') {
    filtered = filtered.filter(product => product.actif.toString() === statusFilter.value)
  }

  return filtered
})

const getCategoryName = (categoryId) => {
  const category = categories.value.find(cat => cat.id === categoryId)
  return category ? category.nom : 'Sans catégorie'
}

const loadProducts = async () => {
  try {
    const response = await fetch('/api/produits')
    if (response.ok) {
      products.value = await response.json()
    }
  } catch (error) {
  }
}

const loadCategories = async () => {
  try {
    const response = await fetch('/api/categories')
    if (response.ok) {
      categories.value = await response.json()
    }
  } catch (error) {
  }
}

const loadImages = async () => {
  try {
    const response = await fetch('/api/banque-image')
    if (response.ok) {
      availableImages.value = await response.json()
    }
  } catch (error) {
  }
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

const openImageBank = () => {
  showImageBankModal.value = true
  // Recharger les images au cas où de nouvelles ont été ajoutées
  loadImages()
}

const editProduct = async (product) => {
  editingProduct.value = product
  const catId = product.categorie?.id || ''
  productForm.value = {
    ...product,
    stock: product.stock ?? 999,
    categorie_id: catId
  }

  // Set parent/subcategory selectors
  if (catId) {
    const cat = categories.value.find(c => c.id === catId)
    if (cat?.parent_id) {
      selectedParentCategory.value = cat.parent_id
      selectedSubcategory.value = catId
    } else {
      selectedParentCategory.value = catId
      selectedSubcategory.value = ''
    }
  } else {
    selectedParentCategory.value = ''
    selectedSubcategory.value = ''
  }

  // Charger les variantes et images du produit
  try {
    const response = await fetch(`/api/produits/${product.id}`)
    if (response.ok) {
      const productData = await response.json()
      productForm.value.images = productData.images || []
      productForm.value.variantes_couleur = (productData.variantes_couleur || []).map(v => ({
        ...v,
        images: v.images || [],
        _newImage: ''
      }))
    }
  } catch {
    productForm.value.images = []
    productForm.value.variantes_couleur = []
  }

  showAddModal.value = true
}

const addVariante = () => {
  if (!productForm.value.variantes_couleur) {
    productForm.value.variantes_couleur = []
  }
  productForm.value.variantes_couleur.push({
    nom_couleur: '',
    code_couleur: '#000000',
    image_url: '',
    reference: '',
    stock: 0,
    actif: true,
    ordre: productForm.value.variantes_couleur.length,
    images: [],
    _newImage: ''
  })
}

const addProductImage = () => {
  if (newProductImage.value) {
    productForm.value.images.push({ url: newProductImage.value, ordre: productForm.value.images.length + 1 })
    newProductImage.value = ''
  }
}

const removeProductImage = (idx) => {
  productForm.value.images.splice(idx, 1)
}

const addVarianteImage = (variante) => {
  if (variante._newImage) {
    variante.images.push({ url: variante._newImage, ordre: variante.images.length + 1 })
    variante._newImage = ''
  }
}

const removeVarianteImage = (variante, idx) => {
  variante.images.splice(idx, 1)
}

const removeVariante = (index) => {
  productForm.value.variantes_couleur.splice(index, 1)
}

// Fonctions supprimées - on utilise maintenant la banque d'images

const toggleProductStatus = async (product) => {
  try {
    const response = await apiFetch(`/api/produits/${product.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        ...product,
        categorie_id: product.categorie?.id,
        actif: !product.actif
      })
    })

    if (response.ok) {
      product.actif = !product.actif
    }
  } catch {
    // silently ignore
  }
}

const deleteProduct = async (productId) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer ce produit ?')
  if (confirmed) {
    try {
      const response = await apiFetch(`/api/produits/${productId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        products.value = products.value.filter(p => p.id !== productId)
      }
    } catch {
      // silently ignore
    }
  }
}

const saveProduct = async () => {
  try {
    const payload = { ...productForm.value }
    if (payload.variantes_couleur) {
      payload.variantes_couleur = payload.variantes_couleur.map(variante => {
        const { _newImage, ...rest } = variante
        if (rest.image_url && rest.image_url.trim() === '') {
          rest.image_url = null
        }
        if (!rest.code_couleur) {
          rest.code_couleur = '#000000'
        }
        return rest
      })
    }

    const url = editingProduct.value
      ? `/api/produits/${editingProduct.value.id}`
      : '/api/produits'

    const method = editingProduct.value ? 'PUT' : 'POST'

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify(payload)
    })

    if (response.ok) {
      await loadProducts()
      closeModal()
    }
  } catch {
    // silently ignore
  }
}

const closeModal = () => {
  showAddModal.value = false
  editingProduct.value = null
  selectedParentCategory.value = ''
  selectedSubcategory.value = ''
  newProductImage.value = ''
  productForm.value = {
    nom: '',
    description: '',
    prix: 0,
    stock: 999,
    categorie_id: '',
    image_principale: '',
    actif: true,
    images: [],
    variantes_couleur: []
  }
}

onMounted(() => {
  loadProducts()
  loadCategories()
  loadImages()
})
</script>

<style scoped>
.products-management {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.management-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.management-header h2 {
  color: var(--primary-color);
  margin: 0;
  font-size: 28px;
}

.add-btn {
  background: var(--primary-color);
  color: #000;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(208, 255, 89, 0.4);
}

.filters-section {
  display: flex;
  gap: 20px;
  align-items: center;
  background: rgba(20, 20, 20, 0.5);
  padding: 20px;
  border-radius: 10px;
  border: 1px solid var(--surface-2);
}

.search-bar {
  flex: 1;
}

.search-input {
  width: 100%;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  padding: 12px;
  color: #e0e0e0;
}

.filter-controls {
  display: flex;
  gap: 15px;
}

.filter-select {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  padding: 12px;
  color: #e0e0e0;
  min-width: 150px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.product-card {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 15px;
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 5px 20px rgba(208, 255, 89, 0.2);
  transform: translateY(-5px);
}

.product-card-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: rgba(208, 255, 89, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-card-image .product-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-card-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 15px;
  flex: 1;
}

.product-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.product-card-name {
  color: #e0e0e0;
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  flex: 1;
}

.product-card-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.info-label {
  color: #a0a0a0;
  font-size: 13px;
  font-weight: 500;
  min-width: 70px;
}

.product-card-actions {
  display: flex;
  gap: 10px;
  margin-top: auto;
  padding-top: 15px;
  border-top: 1px solid var(--surface-2);
}

.product-thumb {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
}

.product-description {
  color: #a0a0a0;
  font-size: 12px;
  margin: 5px 0 0 0;
}

.category-badge {
  background: rgba(204, 240, 212, 0.2);
  color: var(--accent-color);
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.price {
  color: var(--primary-color);
  font-weight: 600;
}

.stock-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.stock-badge.high {
  background: rgba(204, 240, 212, 0.2);
  color: var(--accent-color);
}

.stock-badge.medium {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.stock-badge.low {
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.active {
  background: rgba(204, 240, 212, 0.2);
  color: var(--accent-color);
}

.status-badge.inactive {
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
}

.col-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: none;
  border: 1px solid var(--surface-2);
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.action-btn.edit:hover {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
}

.action-btn.toggle:hover {
  border-color: var(--accent-color);
  background: rgba(204, 240, 212, 0.1);
}

.action-btn.delete:hover {
  border-color: #ff6b6b;
  background: rgba(255, 107, 107, 0.1);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(15px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
  box-sizing: border-box;
}

.modal {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 2px solid var(--primary-color);
  border-radius: 15px;
  padding: 30px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  overflow-x: hidden;
  box-sizing: border-box;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}

.modal-header h3 {
  color: var(--primary-color);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: #e0e0e0;
  font-size: 24px;
  cursor: pointer;
  padding: 5px;
}

.product-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #e0e0e0;
  font-weight: 600;
}

.form-input, .form-textarea, .form-select {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  padding: 12px;
  color: #e0e0e0;
  width: 100%;
  box-sizing: border-box;
  min-width: 0;
}

.form-input:focus, .form-textarea:focus, .form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px rgba(208, 255, 89, 0.3);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.checkbox {
  width: 18px;
  height: 18px;
}

.form-section {
  margin: 30px 0;
  padding: 20px;
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--surface-2);
  border-radius: 10px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h4 {
  color: var(--accent-color);
  margin: 0;
  font-size: 18px;
}

.btn-add-variante {
  padding: 8px 16px;
  background: var(--primary-color);
  color: #050505;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-add-variante:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(208, 255, 89, 0.3);
}

.variantes-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.variante-item {
  background: rgba(10, 10, 10, 0.5);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  padding: 15px;
}

.variante-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.variante-header h5 {
  color: var(--primary-color);
  margin: 0;
  font-size: 16px;
}

.btn-remove-variante {
  width: 30px;
  height: 30px;
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
  border: 1px solid #ff6b6b;
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-remove-variante:hover {
  background: #ff6b6b;
  color: white;
}

.variante-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.color-input {
  height: 40px;
  cursor: pointer;
}

.no-variantes {
  text-align: center;
  padding: 20px;
  color: #a0a0a0;
  font-style: italic;
}

.optional {
  color: #a0a0a0;
  font-size: 12px;
  font-weight: normal;
  font-style: italic;
}

.error-message {
  display: block;
  color: #ff6b6b;
  font-size: 12px;
  margin-top: 5px;
}

.image-selector {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.image-selector .form-select {
  flex: 1;
  min-width: 0;
}

.btn-select-image {
  padding: 12px 16px;
  background: rgba(208, 255, 89, 0.1);
  border: 1px solid var(--primary-color);
  border-radius: 8px;
  color: var(--primary-color);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-select-image:hover {
  background: rgba(208, 255, 89, 0.2);
  transform: translateY(-2px);
}

.image-preview-small {
  margin-top: 10px;
  width: 100%;
  max-width: 200px;
  height: 150px;
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  overflow: hidden;
  background: rgba(10, 10, 10, 0.5);
}

.preview-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-large {
  max-width: 90%;
  max-height: 90vh;
}

.form-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-secondary {
  background: var(--surface-2);
  color: #e0e0e0;
  border: 1px solid var(--surface-2);
}

.btn-primary {
  background: var(--primary-color);
  color: #000;
  border: none;
}

.btn:hover {
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .management-header {
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }
  
  .management-header h2 {
    font-size: 22px;
  }
  
  .filters-section {
    flex-direction: column;
    gap: 15px;
  }
  
  .search-bar {
    width: 100%;
  }
  
  .filter-controls {
    flex-direction: column;
    width: 100%;
  }
  
  .filter-select {
    width: 100%;
    min-width: auto;
  }
  
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
  
  .product-card-image {
    height: 150px;
  }
  
  .product-card-content {
    padding: 15px;
  }
  
  .product-card-name {
    font-size: 16px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal {
    max-width: 90%;
    padding: 20px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}

.gallery-previews {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.gallery-thumb-item {
  position: relative;
  width: 80px;
  height: 80px;
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  overflow: hidden;
}

.gallery-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-remove-gallery {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 20px;
  height: 20px;
  background: rgba(255, 107, 107, 0.85);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  padding: 0;
}

@media (max-width: 480px) {
  .management-header h2 {
    font-size: 20px;
  }
  
  .products-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .product-card-image {
    height: 180px;
  }
  
  .product-card-content {
    padding: 12px;
  }
  
  .product-card-name {
    font-size: 16px;
  }
  
  .product-description {
    font-size: 13px;
  }
  
  .info-label {
    min-width: 60px;
    font-size: 12px;
  }
  
  .product-card-actions {
    flex-wrap: wrap;
  }
  
  .action-btn {
    flex: 1;
    min-width: 60px;
  }
  
  .modal {
    max-width: 95%;
    padding: 15px;
    margin: 10px;
  }
  
  .modal h2 {
    font-size: 18px;
  }
  
  .form-input,
  .form-textarea,
  .form-select {
    font-size: 14px;
    padding: 10px;
  }
}
</style>
