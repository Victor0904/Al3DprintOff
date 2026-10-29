<template>
  <div class="categories-management">
    <div class="management-header">
      <h2>📁 Gestion des Catégories</h2>
      <button @click="showAddModal = true" class="add-btn">
        <span>➕</span>
        Ajouter une catégorie
      </button>
    </div>

    <!-- Catégories parentes -->
    <div v-for="parent in parentCategories" :key="parent.id" class="category-group">
      <div class="category-card parent-card">
        <div class="category-info">
          <h3>{{ parent.nom }}</h3>
          <p>{{ parent.description }}</p>
        </div>
        <div class="category-actions">
          <button @click="editCategory(parent)" class="action-btn edit">✏️</button>
          <button @click="deleteCategory(parent.id)" class="action-btn delete">🗑️</button>
        </div>
      </div>
      <!-- Sous-catégories -->
      <div
        v-for="sub in getChildren(parent.id)"
        :key="sub.id"
        class="category-card subcategory-card"
      >
        <div class="category-info">
          <h3><span class="sub-icon">↳</span> {{ sub.nom }}</h3>
          <p>{{ sub.description }}</p>
        </div>
        <div class="category-actions">
          <button @click="editCategory(sub)" class="action-btn edit">✏️</button>
          <button @click="deleteCategory(sub.id)" class="action-btn delete">🗑️</button>
        </div>
      </div>
    </div>

    <!-- Modal d'Ajout/Modification -->
    <div v-if="showAddModal || editingCategory" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ editingCategory ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}</h3>
          <button @click="closeModal" class="close-btn">×</button>
        </div>
        
        <form @submit.prevent="saveCategory" class="category-form">
          <div class="form-group">
            <label>Nom de la catégorie</label>
            <input v-model="categoryForm.nom" type="text" required class="form-input">
          </div>

          <div class="form-group">
            <label>Catégorie parente (optionnel)</label>
            <select v-model="categoryForm.parent_id" class="form-input">
              <option :value="null">Aucune (catégorie principale)</option>
              <option
                v-for="parent in parentCategories"
                :key="parent.id"
                :value="parent.id"
                :disabled="editingCategory?.id === parent.id"
              >
                {{ parent.nom }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea v-model="categoryForm.description" class="form-textarea" rows="3"></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">Annuler</button>
            <button type="submit" class="btn btn-primary">
              {{ editingCategory ? 'Modifier' : 'Ajouter' }}
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

const { confirm: showConfirm } = useAlert()
const { apiFetch } = useAuthFetch()
const categories = ref([])
const showAddModal = ref(false)
const editingCategory = ref(null)

const categoryForm = ref({
  nom: '',
  description: '',
  parent_id: null
})

// Computed — hierarchy
const parentCategories = computed(() =>
  categories.value.filter(c => !c.parent_id)
)

const getChildren = (parentId) =>
  categories.value.filter(c => c.parent_id === parentId)

const loadCategories = async () => {
  try {
    const response = await fetch('/api/categories')
    if (response.ok) {
      categories.value = await response.json()
    }
  } catch (error) {
  }
}

const editCategory = (category) => {
  editingCategory.value = category
  categoryForm.value = {
    nom: category.nom,
    description: category.description || '',
    parent_id: category.parent_id || null
  }
  showAddModal.value = true
}

const deleteCategory = async (categoryId) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')
  if (confirmed) {
    try {
      const response = await apiFetch(`/api/categories/${categoryId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        categories.value = categories.value.filter(c => c.id !== categoryId)
      }
    } catch (error) {
    }
  }
}

const saveCategory = async () => {
  try {
    const url = editingCategory.value 
      ? `/api/categories/${editingCategory.value.id}`
      : '/api/categories'
    
    const method = editingCategory.value ? 'PUT' : 'POST'

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify({
        nom: categoryForm.value.nom,
        description: categoryForm.value.description,
        parent_id: categoryForm.value.parent_id || null
      })
    })

    if (response.ok) {
      await loadCategories()
      closeModal()
    }
  } catch (error) {
  }
}

const closeModal = () => {
  showAddModal.value = false
  editingCategory.value = null
  categoryForm.value = {
    nom: '',
    description: '',
    parent_id: null
  }
}

onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
.categories-management {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.management-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.management-header h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 28px;
}

.add-btn {
  background: linear-gradient(45deg, #00d4ff, #4ecdc4);
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
  box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.category-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid #2e2e4a;
  border-radius: 15px;
  padding: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
  border-color: #00d4ff;
}

.subcategory-card {
  margin-left: 30px;
  border-left: 3px solid rgba(0, 212, 255, 0.3);
  border-radius: 10px;
}

.sub-icon {
  color: #4ecdc4;
  margin-right: 5px;
}

.category-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.category-info h3 {
  color: #00d4ff;
  margin: 0 0 10px 0;
  font-size: 20px;
}

.category-info p {
  color: #a0a0a0;
  margin: 0;
  font-size: 14px;
}

.category-actions {
  display: flex;
  gap: 10px;
}

.action-btn {
  background: none;
  border: 1px solid #2e2e4a;
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.action-btn.edit:hover {
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
}

.action-btn.delete:hover {
  border-color: #ff6b6b;
  background: rgba(255, 107, 107, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
  .categories-management h2 {
    font-size: 22px;
  }
  
  .categories-list {
    grid-template-columns: 1fr;
  }
  
  .category-card {
    padding: 15px;
  }
  
  .category-info h3 {
    font-size: 18px;
  }
  
  .modal {
    max-width: 90%;
    padding: 20px;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
}

@media (max-width: 480px) {
  .categories-management h2 {
    font-size: 20px;
  }
  
  .category-card {
    padding: 12px;
  }
  
  .category-info h3 {
    font-size: 16px;
  }
  
  .category-info p {
    font-size: 12px;
  }
  
  .category-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .action-btn {
    width: 100%;
    padding: 10px;
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
  .form-textarea {
    font-size: 14px;
    padding: 10px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
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
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid #00d4ff;
  border-radius: 15px;
  padding: 30px;
  width: 100%;
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}

.modal-header h3 {
  color: #00d4ff;
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

.category-form {
  display: flex;
  flex-direction: column;
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

.form-input, .form-textarea {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 12px;
  color: #e0e0e0;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: #00d4ff;
  box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
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
  background: #2e2e4a;
  color: #e0e0e0;
  border: 1px solid #2e2e4a;
}

.btn-primary {
  background: linear-gradient(45deg, #00d4ff, #4ecdc4);
  color: #000;
  border: none;
}

.btn:hover {
  transform: translateY(-2px);
}
</style>
