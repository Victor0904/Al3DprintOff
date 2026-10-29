<template>
  <div class="image-bank-management">
    <div class="management-header">
      <h2>🖼️ Banque d'Images</h2>
      <button @click="showUploadModal = true" class="add-btn">
        <span>📤</span>
        Ajouter une image
      </button>
    </div>

    <!-- Filtres -->
    <div class="filters-section">
      <div class="search-bar">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une image..."
          class="search-input"
        >
      </div>
      
      <div class="filter-controls">
        <select v-model="selectedType" class="filter-select">
          <option value="">Tous les types</option>
          <option value="produit">Produit</option>
          <option value="variante">Variante</option>
          <option value="categorie">Catégorie</option>
          <option value="autre">Autre</option>
        </select>
        
        <select v-model="selectedCategorie" class="filter-select">
          <option value="">Toutes les catégories</option>
          <option v-for="cat in categories" :key="cat" :value="cat">
            {{ cat }}
          </option>
        </select>
      </div>
    </div>

    <!-- Grille d'images -->
    <div class="images-grid">
      <div 
        v-for="image in filteredImages" 
        :key="image.id" 
        class="image-card"
        @click="selectImage(image)"
        :class="{ 'selected': selectedImageId === image.id }"
      >
        <div class="image-preview">
          <img 
            :src="getImageUrl(image.chemin)" 
            :alt="image.nom"
            @error="handleImageError"
            class="preview-img"
          >
          <div class="image-overlay">
            <button @click.stop="deleteImage(image.id)" class="delete-btn">🗑️</button>
          </div>
        </div>
        <div class="image-info">
          <h4 class="image-name">{{ image.nom }}</h4>
          <p class="image-path">{{ image.chemin }}</p>
          <div class="image-meta">
            <span v-if="image.type" class="meta-badge type">{{ image.type }}</span>
            <span v-if="image.categorie" class="meta-badge categorie">{{ image.categorie }}</span>
            <span v-if="image.format" class="meta-badge format">{{ image.format }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="filteredImages.length === 0" class="empty-state">
      <div class="empty-icon">🖼️</div>
      <p>Aucune image trouvée</p>
      <button @click="showUploadModal = true" class="btn btn-primary">
        Ajouter votre première image
      </button>
    </div>

    <!-- Modal d'upload -->
    <div v-if="showUploadModal" class="modal-overlay" @click="closeUploadModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>📤 Ajouter une image</h3>
          <button @click="closeUploadModal" class="close-btn">×</button>
        </div>
        
        <form @submit.prevent="uploadImage" class="upload-form">
          <div class="upload-area" 
               @dragover.prevent="dragOver = true"
               @dragleave.prevent="dragOver = false"
               @drop.prevent="handleDrop"
               :class="{ 'drag-over': dragOver }"
          >
            <input 
              type="file" 
              ref="fileInput"
              @change="handleFileSelect"
              accept="image/*"
              class="file-input"
              id="file-upload"
            >
            <label for="file-upload" class="upload-label">
              <div class="upload-icon">📤</div>
              <p v-if="!selectedFile">Glissez-déposez une image ici ou cliquez pour sélectionner</p>
              <p v-else class="selected-file">{{ selectedFile.name }}</p>
            </label>
          </div>

          <div class="form-group">
            <label>Nom de l'image</label>
            <input v-model="uploadForm.nom" type="text" required class="form-input" placeholder="Ex: Produit Rouge">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Type</label>
              <select v-model="uploadForm.type" class="form-select">
                <option value="">Sélectionner un type</option>
                <option value="produit">Produit</option>
                <option value="variante">Variante</option>
                <option value="categorie">Catégorie</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Catégorie</label>
              <input v-model="uploadForm.categorie" type="text" class="form-input" placeholder="Ex: Couleurs, Produits...">
            </div>
          </div>

          <div class="form-group">
            <label>Description (optionnel)</label>
            <textarea v-model="uploadForm.description" class="form-textarea" rows="2"></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeUploadModal" class="btn btn-secondary">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="!selectedFile || uploading">
              <span v-if="uploading">Upload en cours...</span>
              <span v-else>📤 Uploader</span>
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

const emit = defineEmits(['close'])

const { success: showSuccess, error: showError, confirm: showConfirm } = useAlert()
const { apiFetch } = useAuthFetch()

const images = ref([])
const searchQuery = ref('')
const selectedType = ref('')
const selectedCategorie = ref('')
const showUploadModal = ref(false)
const selectedFile = ref(null)
const dragOver = ref(false)
const uploading = ref(false)
const selectedImageId = ref(null)
const fileInput = ref(null)

const uploadForm = ref({
  nom: '',
  type: '',
  categorie: '',
  description: ''
})

const filteredImages = computed(() => {
  let filtered = images.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(img => 
      img.nom.toLowerCase().includes(query) ||
      img.chemin.toLowerCase().includes(query) ||
      (img.description && img.description.toLowerCase().includes(query))
    )
  }

  if (selectedType.value) {
    filtered = filtered.filter(img => img.type === selectedType.value)
  }

  if (selectedCategorie.value) {
    filtered = filtered.filter(img => img.categorie === selectedCategorie.value)
  }

  return filtered
})

const categories = computed(() => {
  const cats = new Set()
  images.value.forEach(img => {
    if (img.categorie) {
      cats.add(img.categorie)
    }
  })
  return Array.from(cats).sort()
})

const getImageUrl = (chemin) => {
  if (chemin.startsWith('http://') || chemin.startsWith('https://')) {
    return chemin
  }
  // Si c'est un chemin relatif, l'ajuster selon votre configuration
  if (chemin.startsWith('/uploads/')) {
    return `${chemin}`
  }
  return chemin
}

const handleImageError = (event) => {
  event.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect width="200" height="200" fill="%23333"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3EImage%3C/text%3E%3C/svg%3E'
}

const loadImages = async () => {
  try {
    const response = await fetch('/api/banque-image')
    if (response.ok) {
      images.value = await response.json()
    }
  } catch (error) {
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    if (!uploadForm.value.nom) {
      uploadForm.value.nom = file.name.replace(/\.[^/.]+$/, '')
    }
  }
}

const handleDrop = (event) => {
  dragOver.value = false
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    selectedFile.value = file
    if (!uploadForm.value.nom) {
      uploadForm.value.nom = file.name.replace(/\.[^/.]+$/, '')
    }
  }
}

const uploadImage = async () => {
  if (!selectedFile.value) {
    showError('Veuillez sélectionner un fichier')
    return
  }

  uploading.value = true

  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('nom', uploadForm.value.nom)
    if (uploadForm.value.type) {
      formData.append('type', uploadForm.value.type)
    }
    if (uploadForm.value.categorie) {
      formData.append('categorie', uploadForm.value.categorie)
    }
    if (uploadForm.value.description) {
      formData.append('description', uploadForm.value.description)
    }

    const response = await apiFetch('/api/banque-image', {
      method: 'POST',
      body: formData
    })

    if (response.ok) {
      showSuccess('Image uploadée avec succès !')
      await loadImages()
      closeUploadModal()
    } else {
      const errorData = await response.json()
      showError(errorData.error || 'Erreur lors de l\'upload')
    }
  } catch (error) {
    showError('Erreur lors de l\'upload : ' + error.message)
  } finally {
    uploading.value = false
  }
}

const deleteImage = async (imageId) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer cette image ?')
  if (confirmed) {
    try {
      const response = await apiFetch(`/api/banque-image/${imageId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        showSuccess('Image supprimée avec succès')
        await loadImages()
      } else {
        showError('Erreur lors de la suppression')
      }
    } catch (error) {
      showError('Erreur lors de la suppression : ' + error.message)
    }
  }
}

const selectImage = (image) => {
  selectedImageId.value = image.id
  // Émettre un événement pour que le composant parent puisse récupérer l'image sélectionnée
  // Cette fonctionnalité sera utilisée dans les formulaires de produits
}

const closeUploadModal = () => {
  showUploadModal.value = false
  selectedFile.value = null
  dragOver.value = false
  uploadForm.value = {
    nom: '',
    type: '',
    categorie: '',
    description: ''
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

onMounted(() => {
  loadImages()
})

// Exposer les fonctions pour que les composants parents puissent récupérer les images
defineExpose({
  getSelectedImage: () => {
    return images.value.find(img => img.id === selectedImageId.value)
  },
  getAllImages: () => images.value,
  getImagesByType: (type) => images.value.filter(img => img.type === type),
  close: () => {
    emit('close')
  }
})
</script>

<style scoped>
.image-bank-management {
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
  color: var(--primary-color);
  margin: 0;
  font-size: 28px;
}

.add-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: var(--primary-color);
  color: #050505;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(208, 255, 89, 0.3);
}

.filters-section {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.search-bar {
  flex: 1;
  min-width: 250px;
}

.search-input {
  width: 100%;
  padding: 12px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  color: #e0e0e0;
  font-size: 14px;
}

.filter-controls {
  display: flex;
  gap: 10px;
}

.filter-select {
  padding: 12px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  color: #e0e0e0;
  font-size: 14px;
  cursor: pointer;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.image-card {
  background: rgba(20, 20, 20, 0.8);
  border: 2px solid var(--surface-2);
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
}

.image-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(208, 255, 89, 0.2);
  border-color: var(--primary-color);
}

.image-card.selected {
  border-color: var(--primary-color);
  box-shadow: 0 0 20px rgba(208, 255, 89, 0.5);
}

.image-preview {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: rgba(10, 10, 10, 0.5);
}

.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-card:hover .image-overlay {
  opacity: 1;
}

.delete-btn {
  background: rgba(255, 107, 107, 0.9);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 18px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.delete-btn:hover {
  background: #ff6b6b;
  transform: scale(1.1);
}

.image-info {
  padding: 15px;
}

.image-name {
  color: #e0e0e0;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 5px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.image-path {
  color: #a0a0a0;
  font-size: 11px;
  margin: 0 0 10px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.image-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.meta-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}

.meta-badge.type {
  background: rgba(208, 255, 89, 0.2);
  color: var(--primary-color);
  border: 1px solid var(--primary-color);
}

.meta-badge.categorie {
  background: rgba(204, 240, 212, 0.2);
  color: var(--accent-color);
  border: 1px solid var(--accent-color);
}

.meta-badge.format {
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
  border: 1px solid #ff6b6b;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a0a0a0;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.modal {
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface) 100%);
  border: 2px solid var(--surface-2);
  border-radius: 16px;
  padding: 30px;
  max-width: 600px;
  width: 100%;
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

.upload-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.upload-area {
  border: 2px dashed var(--surface-2);
  border-radius: 12px;
  padding: 40px;
  text-align: center;
  background: rgba(10, 10, 10, 0.5);
  transition: all 0.3s ease;
  cursor: pointer;
}

.upload-area.drag-over {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
}

.file-input {
  display: none;
}

.upload-label {
  cursor: pointer;
  display: block;
}

.upload-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.upload-label p {
  color: #a0a0a0;
  margin: 0;
}

.selected-file {
  color: var(--primary-color) !important;
  font-weight: 600;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #e0e0e0;
  font-weight: 500;
  font-size: 14px;
}

.form-input,
.form-textarea,
.form-select {
  padding: 12px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--surface-2);
  border-radius: 8px;
  color: #e0e0e0;
  font-size: 14px;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px rgba(208, 255, 89, 0.3);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.form-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  margin-top: 10px;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: var(--primary-color);
  color: #050505;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(208, 255, 89, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: transparent;
  color: #a0a0a0;
  border: 1px solid var(--surface-2);
}

.btn-secondary:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

@media (max-width: 768px) {
  .image-bank-management h2 {
    font-size: 22px;
  }
  
  .images-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
  }
  
  .image-card {
    padding: 10px;
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
  .image-bank-management h2 {
    font-size: 20px;
  }
  
  .images-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  
  .image-card {
    padding: 8px;
  }
  
  .image-preview {
    height: 120px;
  }
  
  .image-name {
    font-size: 12px;
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
}
</style>

