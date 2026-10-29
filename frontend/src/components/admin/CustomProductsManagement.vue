<template>
  <div class="products-management">
    <div class="management-header">
      <h2>🎨 Gestion des Produits Personnalisés</h2>
      <button @click="showAddModal = true" class="add-btn">
        <span>➕</span>
        Ajouter un produit personnalisé
      </button>
    </div>

    <!-- Filtres et Recherche -->
    <div class="filters-section">
      <div class="search-bar">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher un produit personnalisé..."
          class="search-input"
        >
      </div>
      
      <div class="filter-controls">
        <select v-model="statusFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="true">Actifs</option>
          <option value="false">Inactifs</option>
        </select>
      </div>
    </div>

    <!-- Tableau des Produits -->
    <div class="products-table">
      <div class="table-header">
        <div class="col-image">Image</div>
        <div class="col-name">Nom</div>
        <div class="col-price">Prix</div>
        <div class="col-stock">Stock</div>
        <div class="col-status">Statut</div>
        <div class="col-actions">Actions</div>
      </div>
      
      <div class="table-body">
        <div v-if="products.length === 0 && !loading" class="empty-state">
          <p>Aucun produit personnalisé pour le moment</p>
        </div>
        <div v-for="product in filteredProducts" :key="product.id" class="table-row">
          <div class="col-image">
            <img :src="getImageUrl(product.image_principale) || '/placeholder-product.jpg'" :alt="product.nom" class="product-thumb">
          </div>
          
          <div class="col-name">
            <h4>{{ product.nom }}</h4>
            <p class="product-description">{{ product.description?.substring(0, 50) }}...</p>
          </div>
          
          <div class="col-price">
            <span class="price">{{ product.prix }}€</span>
          </div>
          
          <div class="col-stock">
            <span :class="['stock-badge', product.stock > 10 ? 'high' : product.stock > 0 ? 'medium' : 'low']">
              {{ product.stock }}
            </span>
          </div>
          
          <div class="col-status">
            <span :class="['status-badge', product.actif ? 'active' : 'inactive']">
              {{ product.actif ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          
          <div class="col-actions">
            <button @click="editProduct(product)" class="action-btn edit">
              ✏️
            </button>
            <button @click="toggleProductStatus(product)" class="action-btn toggle">
              {{ product.actif ? '⏸️' : '▶️' }}
            </button>
            <button @click="deleteProduct(product.id)" class="action-btn delete">
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
          <h3>{{ editingProduct ? 'Modifier le produit personnalisé' : 'Ajouter un produit personnalisé' }}</h3>
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
          
          <div class="form-group">
            <label>Stock</label>
            <input v-model="productForm.stock" type="number" required class="form-input">
          </div>
          
          <div class="form-group">
            <label>Image principale</label>
            <div class="image-selector">
              <select v-model="productForm.image_principale" class="form-select">
                <option value="">Aucune image principale</option>
                <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                  {{ image.nom }} ({{ image.chemin }})
                </option>
              </select>
            </div>
            <div v-if="productForm.image_principale" class="main-image-preview">
              <img :src="getImageUrl(productForm.image_principale)" class="gallery-thumb-img" alt="Aperçu image principale">
            </div>
          </div>

          <div class="form-group">
            <label>Galerie d'images</label>
            <div class="image-selector">
              <select v-model="newProductImage" class="form-select">
                <option value="">Choisir une image à ajouter</option>
                <option v-for="image in availableImages" :key="image.id" :value="image.chemin">
                  {{ image.nom }} ({{ image.chemin }})
                </option>
              </select>
              <button type="button" @click="addProductImage" class="btn-select-image">+ Ajouter</button>
            </div>
            <div v-if="productForm.images && productForm.images.length > 0" class="gallery-previews">
              <div v-for="(img, idx) in productForm.images" :key="idx" class="gallery-thumb-item">
                <img :src="getImageUrl(img.url)" class="gallery-thumb-img" alt="Image galerie">
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
          
          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">Annuler</button>
            <button type="submit" class="btn btn-primary">
              {{ editingProduct ? 'Modifier' : 'Ajouter' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAlert } from '../../composables/useAlert'
import { useAuthFetch } from '../../composables/useAuthFetch'

const { error: showError, confirm: showConfirm } = useAlert()
const { apiFetch } = useAuthFetch()
const products = ref([])
const availableImages = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const showAddModal = ref(false)
const editingProduct = ref(null)
const loading = ref(false)
const newProductImage = ref('')

const productForm = ref({
  nom: '',
  description: '',
  prix: 0,
  stock: 0,
  image_principale: '',
  images: [],
  actif: true
})

const filteredProducts = computed(() => {
  let filtered = products.value

  if (searchQuery.value) {
    filtered = filtered.filter(product => 
      product.nom.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      product.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (statusFilter.value !== '') {
    filtered = filtered.filter(product => product.actif.toString() === statusFilter.value)
  }

  return filtered
})

const loadProducts = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/produits-personnalises')
    if (response.ok) {
      products.value = await response.json()
    }
  } catch (error) {
  } finally {
    loading.value = false
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

const addProductImage = () => {
  if (newProductImage.value) {
    productForm.value.images.push({ url: newProductImage.value, ordre: productForm.value.images.length + 1 })
    newProductImage.value = ''
  }
}

const removeProductImage = (idx) => {
  productForm.value.images.splice(idx, 1)
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

const editProduct = async (product) => {
  editingProduct.value = product
  productForm.value = {
    nom: product.nom,
    description: product.description || '',
    prix: product.prix,
    stock: product.stock,
    image_principale: product.image_principale || '',
    images: [],
    actif: product.actif
  }
  // Charger les images de la galerie depuis le détail
  try {
    const response = await fetch(`/api/produits-personnalises/${product.id}`)
    if (response.ok) {
      const productData = await response.json()
      productForm.value.images = productData.images || []
    }
  } catch {
    productForm.value.images = []
  }
  showAddModal.value = true
}

const toggleProductStatus = async (product) => {
  try {
    const response = await apiFetch(`/api/produits-personnalises/${product.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        ...product,
        actif: !product.actif
      })
    })

    if (response.ok) {
      product.actif = !product.actif
    }
  } catch (error) {
  }
}

const deleteProduct = async (productId) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer ce produit personnalisé ?')
  if (confirmed) {
    try {
      const response = await apiFetch(`/api/produits-personnalises/${productId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        products.value = products.value.filter(p => p.id !== productId)
      }
    } catch (error) {
    }
  }
}

const saveProduct = async () => {
  try {
    const url = editingProduct.value 
      ? `/api/produits-personnalises/${editingProduct.value.id}`
      : '/api/produits-personnalises'
    
    const method = editingProduct.value ? 'PUT' : 'POST'

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify(productForm.value)
    })

    if (response.ok) {
      await loadProducts()
      closeModal()
    } else {
      const errorData = await response.json()
      showError('Erreur: ' + (errorData.error || 'Erreur lors de la sauvegarde'))
    }
  } catch (error) {
    showError('Erreur de connexion lors de la sauvegarde')
  }
}

const closeModal = () => {
  showAddModal.value = false
  editingProduct.value = null
  newProductImage.value = ''
  productForm.value = {
    nom: '',
    description: '',
    prix: 0,
    stock: 0,
    image_principale: '',
    images: [],
    actif: true
  }
}

onMounted(() => {
  loadProducts()
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

.products-table {
  background: rgba(20, 20, 20, 0.5);
  border-radius: 10px;
  border: 1px solid var(--surface-2);
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 80px 2fr 80px 80px 100px 120px;
  gap: 15px;
  padding: 20px;
  background: rgba(208, 255, 89, 0.1);
  border-bottom: 1px solid var(--surface-2);
  font-weight: 600;
  color: var(--primary-color);
}

.table-row {
  display: grid;
  grid-template-columns: 80px 2fr 80px 80px 100px 120px;
  gap: 15px;
  padding: 20px;
  border-bottom: 1px solid var(--surface-2);
  align-items: center;
}

.table-row:hover {
  background: rgba(208, 255, 89, 0.05);
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #a0a0a0;
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
  grid-template-columns: 1fr 1fr;
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

.form-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  margin-top: 20px;
}

.image-selector {
  display: flex;
  gap: 8px;
  align-items: center;
}

.image-selector .form-select {
  flex: 1;
}

.btn-select-image {
  background: rgba(208, 255, 89, 0.15);
  border: 1px solid rgba(208, 255, 89, 0.4);
  color: var(--primary-color);
  padding: 10px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.btn-select-image:hover {
  background: rgba(208, 255, 89, 0.3);
}

.gallery-previews {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.gallery-thumb-item {
  position: relative;
  width: 72px;
  height: 72px;
}

.gallery-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--surface-2);
}

.btn-remove-gallery {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #ff6b6b;
  border: none;
  color: white;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.main-image-preview {
  margin-top: 10px;
  width: 72px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(208, 255, 89, 0.3);
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
  
  .table-header, .table-row {
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 15px;
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

@media (max-width: 480px) {
  .management-header h2 {
    font-size: 20px;
  }
  
  .table-header {
    display: none;
  }
  
  .table-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border: 1px solid var(--surface-2);
    border-radius: 8px;
    margin-bottom: 10px;
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

