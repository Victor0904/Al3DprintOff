<template>
  <div class="products-page">
    <div class="container">
      <!-- En-tête de la page -->
      <div class="page-header">
        <h1 class="page-title">Nos Produits</h1>
        <p class="page-description">
          Découvrez notre collection complète de produits imprimés en 3D
        </p>
      </div>

      <!-- Filtres et recherche -->
      <div class="filters-section">
        <div class="filters-row">
          <!-- Recherche -->
          <div class="search-box">
            <input 
              type="text" 
              placeholder="Rechercher un produit..." 
              v-model="searchQuery"
              @input="debouncedSearch"
              class="search-input"
            >
            <button class="search-btn" @click="performSearch">🔍</button>
          </div>

          <!-- Filtres -->
          <div class="filters">
            <select v-model="selectedCategory" @change="onCategoryChange" class="filter-select">
              <option value="">Toutes les catégories</option>
              <option v-for="category in parentCategories" :key="category.id" :value="category.id">
                {{ category.nom }}
              </option>
            </select>

            <select
              v-if="selectedCategory && subcategoriesOfSelected.length > 0"
              v-model="selectedSubcategory"
              @change="filterProducts"
              class="filter-select"
            >
              <option value="">Toutes les sous-catégories</option>
              <option v-for="sub in subcategoriesOfSelected" :key="sub.id" :value="sub.id">
                {{ sub.nom }}
              </option>
            </select>

            <select v-model="sortBy" @change="sortProducts" class="filter-select">
              <option value="nom">Trier par nom</option>
              <option value="prix-asc">Prix croissant</option>
              <option value="prix-desc">Prix décroissant</option>
            </select>

            <div class="view-toggle">
              <button 
                class="view-btn"
                :class="{ active: viewMode === 'grid' }"
                @click="viewMode = 'grid'"
                title="Vue grille"
              >
                ⊞
              </button>
              <button 
                class="view-btn"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
                title="Vue liste"
              >
                ☰
              </button>
            </div>
          </div>
        </div>

        <!-- Résultats de recherche -->
        <div v-if="searchQuery || selectedCategory" class="search-results">
          <span class="results-count">{{ filteredProducts.length }} produit(s) trouvé(s)</span>
          <button v-if="searchQuery || selectedCategory" class="clear-filters-btn" @click="clearFilters">
            Effacer les filtres
          </button>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="products-content">
        <!-- Sidebar avec catégories -->
        <aside class="sidebar">
          <div class="sidebar-card">
            <h3 class="sidebar-title">Catégories</h3>
            <div class="categories-list">
              <button
                class="category-item"
                :class="{ active: selectedCategory === '' }"
                @click="selectCategory('')"
              >
                <span class="category-name">Toutes</span>
                <span class="category-count">{{ products.length }}</span>
              </button>
              <template v-for="category in parentCategories" :key="category.id">
                <button
                  class="category-item"
                  :class="{ active: selectedCategory === category.id && !selectedSubcategory }"
                  @click="selectCategory(category.id)"
                >
                  <span class="category-name">{{ category.nom }}</span>
                  <span class="category-count">{{ getCategoryGroupCount(category.id) }}</span>
                </button>
                <button
                  v-for="sub in getSubcategories(category.id)"
                  :key="sub.id"
                  class="category-item subcategory-item"
                  :class="{ active: selectedSubcategory === sub.id }"
                  @click="selectSubcategory(sub.id)"
                >
                  <span class="category-name">↳ {{ sub.nom }}</span>
                  <span class="category-count">{{ getCategoryProductCount(sub.id) }}</span>
                </button>
              </template>
            </div>
          </div>

          <div class="sidebar-card">
            <h3 class="sidebar-title">Filtres avancés</h3>
            <div class="price-filter">
              <label class="filter-label">Prix maximum</label>
              <input
                type="range"
                :min="0"
                :max="sliderMaxPrice"
                v-model="maxPriceFilter"
                @input="filterProducts"
                class="price-slider"
              >
              <div class="price-range">
                <span>0€</span>
                <span>{{ maxPriceFilter }}€</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- Liste des produits -->
        <main class="products-main">
          <div v-if="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Chargement des produits...</p>
          </div>

          <div v-else-if="filteredProducts.length === 0" class="no-products">
            <div class="no-products-icon">🔍</div>
            <h3>Aucun produit trouvé</h3>
            <p>Essayez de modifier vos critères de recherche</p>
            <button class="btn btn-primary" @click="clearFilters">
              Effacer les filtres
            </button>
          </div>

          <div v-else class="products-container" :class="viewMode">
            <ProductCard 
              v-for="product in paginatedProducts" 
              :key="product.id" 
              :product="product"
              @add-to-cart="addToCart"
            />
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination">
            <button 
              class="pagination-btn"
              :disabled="currentPage === 1"
              @click="currentPage = 1"
            >
              ⏮️
            </button>
            <button 
              class="pagination-btn"
              :disabled="currentPage === 1"
              @click="currentPage--"
            >
              ◀️
            </button>
            
            <span class="pagination-info">
              Page {{ currentPage }} sur {{ totalPages }}
            </span>
            
            <button 
              class="pagination-btn"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
            >
              ▶️
            </button>
            <button 
              class="pagination-btn"
              :disabled="currentPage === totalPages"
              @click="currentPage = totalPages"
            >
              ⏭️
            </button>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useCartStore } from '../stores/cart'
import ProductCard from '../components/ProductCard.vue'

const cartStore = useCartStore()

// État
const loading = ref(true)
const products = ref([])
const categories = ref([])
const searchQuery = ref('')
const selectedCategory = ref('')
const selectedSubcategory = ref('')
const sortBy = ref('nom')
const viewMode = ref('grid')
const maxPriceFilter = ref(1000)
const currentPage = ref(1)
const itemsPerPage = 12

// Computed — catégories hiérarchiques
// Catégories sans produit actif masquées (évite une liste de catégories vides)
const parentCategories = computed(() =>
  categories.value.filter(c => !c.parent_id && getCategoryGroupCount(c.id) > 0)
)

const subcategoriesOfSelected = computed(() => {
  if (!selectedCategory.value) return []
  return categories.value.filter(c => c.parent_id === selectedCategory.value)
})

const getSubcategories = (parentId) =>
  categories.value.filter(c => c.parent_id === parentId && getCategoryProductCount(c.id) > 0)

// Computed — prix
const maxPrice = computed(() =>
  Math.max(...products.value.map(p => parseFloat(p.prix)), 0)
)

const sliderMaxPrice = computed(() =>
  Math.max(1000, maxPrice.value)
)

// Get all category IDs that belong to a parent group (parent + all its subcategories)
const getCategoryGroupIds = (parentId) => {
  const ids = [parentId]
  getSubcategories(parentId).forEach(sub => ids.push(sub.id))
  return ids
}

const filteredProducts = computed(() => {
  let filtered = [...products.value]

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(product =>
      product.nom.toLowerCase().includes(query) ||
      product.description?.toLowerCase().includes(query) ||
      product.categorie?.nom.toLowerCase().includes(query)
    )
  }

  // Filtre par sous-catégorie (prioritaire sur catégorie parente)
  if (selectedSubcategory.value) {
    filtered = filtered.filter(product =>
      product.categorie?.id === selectedSubcategory.value
    )
  } else if (selectedCategory.value) {
    // Inclure produits de la catégorie parente ET de toutes ses sous-catégories
    const ids = getCategoryGroupIds(selectedCategory.value)
    filtered = filtered.filter(product =>
      ids.includes(product.categorie?.id)
    )
  }

  // Filtre par prix
  filtered = filtered.filter(product =>
    parseFloat(product.prix) <= maxPriceFilter.value
  )

  // Tri
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'prix-asc':
        return parseFloat(a.prix) - parseFloat(b.prix)
      case 'prix-desc':
        return parseFloat(b.prix) - parseFloat(a.prix)
      default:
        return a.nom.localeCompare(b.nom)
    }
  })

  return filtered
})

const totalPages = computed(() =>
  Math.ceil(filteredProducts.value.length / itemsPerPage)
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredProducts.value.slice(start, start + itemsPerPage)
})

// Méthodes
const addToCart = (product) => {
  cartStore.addToCart(product)
}

const selectCategory = (categoryId) => {
  selectedCategory.value = categoryId
  selectedSubcategory.value = ''
  currentPage.value = 1
}

const selectSubcategory = (subId) => {
  selectedSubcategory.value = subId
  currentPage.value = 1
}

const onCategoryChange = () => {
  selectedSubcategory.value = ''
  currentPage.value = 1
}

const filterProducts = () => {
  currentPage.value = 1
}

const sortProducts = () => {
  currentPage.value = 1
}

const performSearch = () => {
  currentPage.value = 1
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedSubcategory.value = ''
  maxPriceFilter.value = 1000
  currentPage.value = 1
}

const getCategoryProductCount = (categoryId) =>
  products.value.filter(p => p.categorie?.id === categoryId).length

const getCategoryGroupCount = (parentId) => {
  const ids = getCategoryGroupIds(parentId)
  return products.value.filter(p => ids.includes(p.categorie?.id)).length
}

// Recherche avec debounce
let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(performSearch, 300)
}

// Chargement des données
const loadProducts = async () => {
  try {
    const response = await fetch('/api/produits?actif=true')
    if (response.ok) {
      products.value = await response.json()
    }
  } catch {
    // silently ignore
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await fetch('/api/categories')
    if (response.ok) {
      categories.value = await response.json()
    }
  } catch {
    // silently ignore
  }
}

// Watchers
watch(currentPage, () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

<style scoped>
.products-page {
  min-height: 100vh;
  padding: 40px 0;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-size: 36px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.page-description {
  color: var(--text-secondary);
  font-size: 18px;
}

.filters-section {
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 30px;
}

.filters-row {
  display: flex;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 300px;
  position: relative;
}

@media (max-width: 768px) {
  .search-box {
    min-width: auto;
    width: 100%;
  }
}

.search-input {
  width: 100%;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 25px;
  padding: 12px 50px 12px 20px;
  color: var(--text-primary);
  font-size: 16px;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.search-btn {
  position: absolute;
  right: 5px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--primary-color);
  border: none;
  border-radius: 50%;
  width: 35px;
  height: 35px;
  cursor: pointer;
  color: white;
  transition: all 0.3s ease;
}

.search-btn:hover {
  background: var(--accent-color);
}

.filters {
  display: flex;
  gap: 15px;
  align-items: center;
}

.filter-select {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 15px;
  color: var(--text-primary);
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
}

.view-toggle {
  display: flex;
  gap: 5px;
}

.view-btn {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 15px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.3s ease;
}

.view-btn.active {
  background: var(--primary-color);
  color: #000;
  border-color: var(--primary-color);
}

.search-results {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid var(--border-color);
}

.results-count {
  color: var(--text-secondary);
  font-size: 14px;
}

.clear-filters-btn {
  background: none;
  border: 1px solid var(--secondary-color);
  color: var(--secondary-color);
  padding: 8px 16px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.3s ease;
}

.clear-filters-btn:hover {
  background: var(--secondary-color);
  color: white;
}

.products-content {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: 30px;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sidebar-card {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 20px;
}

.sidebar-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 15px;
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.category-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: none;
  border: none;
  color: var(--text-secondary);
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: left;
}

.category-item:hover,
.category-item.active {
  background: rgba(208, 255, 89, 0.1);
  color: var(--primary-color);
}

.subcategory-item {
  padding-left: 25px;
  font-size: 13px;
}

.category-count {
  background: var(--border-color);
  color: var(--text-secondary);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
}

.price-filter {
  margin-bottom: 20px;
}

.filter-label {
  display: block;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 10px;
}

.price-slider {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: var(--border-color);
  outline: none;
  -webkit-appearance: none;
}

.price-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary-color);
  cursor: pointer;
}

.price-range {
  display: flex;
  justify-content: space-between;
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 5px;
}

.products-main {
  min-height: 500px;
}

.products-container.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
}

.products-container.list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.loading-container,
.no-products {
  text-align: center;
  padding: 60px 20px;
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

.no-products-icon {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 40px;
}

.pagination-btn {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 15px;
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--primary-color);
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 0 10px;
}

/* Responsive */
@media (max-width: 1024px) {
  .products-content {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    order: 2;
  }
  
  .products-main {
    order: 1;
  }
}

@media (max-width: 768px) {
  .products-page {
    padding: 20px 0;
  }
  
  .page-header {
    margin-bottom: 25px;
  }
  
  .page-title {
    font-size: 28px;
  }
  
  .page-description {
    font-size: 16px;
  }
  
  .filters-section {
    padding: 15px;
    margin-bottom: 20px;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
  }
  
  .search-box {
    min-width: auto;
    width: 100%;
  }
  
  .search-input {
    padding: 10px 45px 10px 15px;
    font-size: 14px;
  }
  
  .search-btn {
    width: 30px;
    height: 30px;
    right: 3px;
  }
  
  .filters {
    flex-direction: column;
    gap: 10px;
    width: 100%;
  }
  
  .filter-select {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
  }
  
  .view-toggle {
    width: 100%;
    justify-content: center;
  }
  
  .clear-filters-btn {
    width: 100%;
    padding: 10px;
    font-size: 14px;
  }
  
  .products-container.grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
  }
  
  .sidebar {
    margin-bottom: 20px;
  }
  
  .sidebar-card {
    padding: 15px;
  }
  
  .sidebar-title {
    font-size: 18px;
  }
}

@media (max-width: 480px) {
  .products-page {
    padding: 15px 0;
  }
  
  .page-title {
    font-size: 24px;
  }
  
  .page-description {
    font-size: 14px;
  }
  
  .filters-section {
    padding: 12px;
  }
  
  .search-input {
    padding: 8px 40px 8px 12px;
    font-size: 13px;
  }
  
  .search-btn {
    width: 28px;
    height: 28px;
  }
  
  .filter-select {
    padding: 7px 10px;
    font-size: 13px;
  }
  
  .products-container.grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  /* Cacher les sections Catégories et Filtres avancés sur mobile */
  .sidebar {
    display: none;
  }
  
  .sidebar-card {
    padding: 12px;
  }
  
  .sidebar-title {
    font-size: 16px;
  }
  
  .category-item {
    padding: 10px;
    font-size: 14px;
  }
  
  .price-filter {
    margin-bottom: 15px;
  }
  
  .filter-label {
    font-size: 13px;
  }
  
  .price-range {
    font-size: 13px;
  }
}
</style>
