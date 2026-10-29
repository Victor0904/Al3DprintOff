<template>
  <div class="profile-page">
    <div class="container">
      <h1 class="page-title">Mon Profil</h1>
      
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Chargement du profil...</p>
      </div>

      <div v-else class="profile-content">
        <div class="profile-header">
          <div class="profile-avatar">
            <div class="avatar-icon">👤</div>
          </div>
          <div class="profile-info">
            <h2 class="profile-name">{{ user?.nom || 'Utilisateur' }}</h2>
            <p class="profile-email">{{ user?.email || 'email@example.com' }}</p>
            <div class="profile-stats">
              <div class="stat-item">
                <span class="stat-number">{{ ordersCount }}</span>
                <span class="stat-label">Commandes</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">{{ totalSpent }}</span>
                <span class="stat-label">Total dépensé</span>
              </div>
            </div>
          </div>
        </div>

        <div class="profile-sections">
          <div class="profile-section">
            <div class="section-header">
            <h3 class="section-title">Informations personnelles</h3>
              <button 
                class="btn btn-secondary edit-btn" 
                @click="toggleEditMode"
                v-if="!isEditing"
              >
                ✏️ Modifier
              </button>
              <div v-else class="edit-actions">
                <button class="btn btn-primary" @click="saveProfile" :disabled="saving">
                  {{ saving ? 'Sauvegarde...' : '💾 Sauvegarder' }}
                </button>
                <button class="btn btn-secondary" @click="cancelEdit">
                  ❌ Annuler
                </button>
              </div>
            </div>
            
            <div v-if="!isEditing" class="info-grid">
              <div class="info-item">
                <label>Nom complet</label>
                <span>{{ user?.nom || 'Non renseigné' }}</span>
              </div>
              <div class="info-item">
                <label>Email</label>
                <span>{{ user?.email || 'Non renseigné' }}</span>
              </div>
              <div class="info-item">
                <label>Téléphone</label>
                <span>{{ user?.telephone || 'Non renseigné' }}</span>
              </div>
              </div>

            <div v-else class="edit-form">
              <div class="form-grid">
                <div class="form-group">
                  <label for="nom">Nom complet *</label>
                  <input 
                    type="text" 
                    id="nom" 
                    v-model="editForm.nom" 
                    required
                    class="form-input"
                  >
              </div>
                
                <div class="form-group">
                  <label for="email">Email *</label>
                  <input 
                    type="email" 
                    id="email" 
                    v-model="editForm.email" 
                    required
                    class="form-input"
                  >
            </div>

                <div class="form-group">
                  <label for="telephone">Téléphone</label>
                  <input 
                    type="tel" 
                    id="telephone" 
                    v-model="editForm.telephone" 
                    class="form-input"
                    placeholder="Ex: +33 1 23 45 67 89"
                    @input="validatePhone"
                    pattern="[0-9+\s\-\(\)]+"
                    title="Seuls les chiffres, le signe +, les espaces, les tirets et les parenthèses sont autorisés"
                  >
            </div>

                
          </div>

              <div class="password-section">
                <h4>Changer le mot de passe</h4>
                <div class="form-group">
                  <label for="current_password">Mot de passe actuel</label>
                  <input 
                    type="password" 
                    id="current_password" 
                    v-model="editForm.current_password" 
                    class="form-input"
                    placeholder="Votre mot de passe actuel"
                  >
                </div>
                <div class="form-group">
                  <label for="new_password">Nouveau mot de passe</label>
                  <input 
                    type="password" 
                    id="new_password" 
                    v-model="editForm.new_password" 
                    class="form-input"
                    placeholder="Nouveau mot de passe"
                  >
                </div>
                <div class="form-group">
                  <label for="confirm_password">Confirmer le mot de passe</label>
                  <input 
                    type="password" 
                    id="confirm_password" 
                    v-model="editForm.confirm_password" 
                    class="form-input"
                    placeholder="Confirmer le nouveau mot de passe"
                  >
                </div>
              </div>
            </div>
          </div>


          <!-- Section des adresses de livraison -->
          <div class="profile-section">
            <div class="section-header">
              <div>
                <h3 class="section-title">Mes adresses de livraison</h3>
                <p class="section-description">Gérez vos adresses de livraison personnalisées</p>
              </div>
              <button 
                v-if="canAddAddress" 
                @click="addNewAddress" 
                class="btn btn-primary"
              >
                <i class="fas fa-plus"></i>
                Ajouter une adresse
              </button>
            </div>
            
            <div class="profile-addresses-list">
              <!-- Adresse principale -->
              <div v-if="user?.adresse" class="address-card profile-address">
                <div class="address-header">
                  <div class="address-info">
                    <h4>Adresse principale</h4>
                    <div class="profile-badge">Profil</div>
                  </div>
                  <div class="address-actions">
                    <button @click="editProfileAddress(1)" class="btn btn-sm btn-outline">
                      <i class="fas fa-edit"></i>
                      Modifier
                    </button>
                    <button @click="deleteProfileAddress(1)" class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                      Supprimer
                    </button>
                  </div>
                </div>
                
                <div class="address-details">
                  <p class="address-text">{{ user.adresse }}</p>
                  <p class="address-city">{{ user.code_postal }} {{ user.ville }}</p>
                </div>
              </div>

              <!-- Adresse secondaire -->
              <div v-if="user?.adresse2" class="address-card profile-address">
                <div class="address-header">
                  <div class="address-info">
                    <h4>Adresse secondaire</h4>
                    <div class="profile-badge">Profil</div>
                  </div>
                  <div class="address-actions">
                    <button @click="editProfileAddress(2)" class="btn btn-sm btn-outline">
                      <i class="fas fa-edit"></i>
                      Modifier
                    </button>
                    <button @click="deleteProfileAddress(2)" class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                      Supprimer
                    </button>
                  </div>
                </div>
                
                <div class="address-details">
                  <p class="address-text">{{ user.adresse2 }}</p>
                  <p class="address-city">{{ user.code_postal2 }} {{ user.ville2 }}</p>
                </div>
              </div>

              <!-- Adresse tertiaire -->
              <div v-if="user?.adresse3" class="address-card profile-address">
                <div class="address-header">
                  <div class="address-info">
                    <h4>Adresse tertiaire</h4>
                    <div class="profile-badge">Profil</div>
                  </div>
                  <div class="address-actions">
                    <button @click="editProfileAddress(3)" class="btn btn-sm btn-outline">
                      <i class="fas fa-edit"></i>
                      Modifier
                    </button>
                    <button @click="deleteProfileAddress(3)" class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                      Supprimer
                    </button>
                  </div>
                </div>
                
                <div class="address-details">
                  <p class="address-text">{{ user.adresse3 }}</p>
                  <p class="address-city">{{ user.code_postal3 }} {{ user.ville3 }}</p>
                </div>
              </div>

              <!-- Message si aucune adresse de profil -->
              <div v-if="!user?.adresse && !user?.adresse2 && !user?.adresse3" class="no-profile-addresses">
                <div class="no-addresses-icon">🏠</div>
                <h4>Aucune adresse de profil</h4>
                <p>Vous n'avez pas encore d'adresses enregistrées dans votre profil personnel.</p>
                <button @click="addNewAddress" class="btn btn-primary" style="margin-top: 1rem;">
                  <i class="fas fa-plus"></i>
                  Ajouter votre première adresse
                </button>
              </div>

              <!-- Message si toutes les adresses sont utilisées -->
              <div v-else-if="!canAddAddress" class="all-addresses-used">
                <div class="no-addresses-icon">✅</div>
                <h4>Toutes vos adresses sont configurées</h4>
                <p>Vous avez configuré les 3 adresses de livraison disponibles. Vous pouvez modifier ou supprimer une adresse existante pour en ajouter une nouvelle.</p>
              </div>
            </div>
          </div>

          <div class="profile-section">
            <div class="section-header">
              <h3 class="section-title">Mes commandes passées</h3>
              <button @click="refreshOrders" class="refresh-btn" :disabled="ordersLoading">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': ordersLoading }"></i>
                Actualiser
              </button>
            </div>
            
            <div v-if="ordersLoading" class="loading-orders">
              <div class="loading-spinner"></div>
              <p>Chargement des commandes...</p>
            </div>
            
            <div v-else-if="orders.length === 0" class="no-orders">
              <div class="no-orders-icon">📦</div>
              <h4>Aucune commande</h4>
              <p>Vous n'avez pas encore passé de commande.</p>
              <router-link to="/products" class="btn btn-primary">Découvrir nos produits</router-link>
            </div>
            
            <div v-else class="orders-list">
              <div v-for="order in orders" :key="order.id" class="order-item">
                <div class="order-header">
                <div class="order-info">
                    <h4>Commande #{{ order.id }}</h4>
                    <p class="order-date">{{ formatDate(order.date_commande) }}</p>
                  </div>
                  <div class="order-status" :class="getStatusClass(order.statut)">
                    {{ getStatusText(order.statut) }}
                </div>
              </div>
                
                <div class="order-details">
                  <div class="order-summary">
                    <div class="summary-item">
                      <span>Total</span>
                      <span class="total-price">{{ formatPrice(order.total) }}€</span>
                    </div>
                    <div class="summary-item">
                      <span>Articles</span>
                      <span>{{ order.details_count }} article(s)</span>
                    </div>
                    <div v-if="order.mode_paiement" class="summary-item">
                      <span>Paiement</span>
                      <span>{{ getPaymentMethodName(order.mode_paiement) }}</span>
            </div>
          </div>
                  
                  <div class="order-actions">
                    <router-link :to="`/order/${order.id}`" class="btn btn-outline">
                      <i class="fas fa-eye"></i>
                      Voir le détail
                    </router-link>
                  </div>
                </div>
              </div>
              
              <!-- Message si plus de commandes disponibles -->
              <div v-if="orders.length >= 10" class="more-orders-info">
                <p>Affichage des 10 commandes les plus récentes</p>
                <button @click="loadAllOrders" class="btn btn-secondary" :disabled="ordersLoading">
                  <i class="fas fa-list"></i>
                  Voir toutes les commandes
                </button>
              </div>
            </div>
          </div>


          <div class="profile-section">
            <h3 class="section-title">Préférences</h3>
            <div class="preferences">
              <div class="preference-item">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="preferences.newsletter">
                  <span class="checkmark"></span>
                  Recevoir la newsletter
                </label>
              </div>
              <div class="preference-item">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="preferences.notifications">
                  <span class="checkmark"></span>
                  Notifications par email
                </label>
              </div>
            </div>
            <button class="btn btn-primary">Sauvegarder les préférences</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout/édition d'adresse -->
    <div v-if="showAddAddressModal" class="modal-overlay" @click="closeAddressModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingAddress ? 'Modifier l\'adresse' : 'Ajouter une adresse de livraison' }}</h3>
          <button @click="closeAddressModal" class="close-btn">&times;</button>
        </div>
        
        <form @submit.prevent="saveAddress" class="address-form">
          <div class="form-group">
            <label for="addressName">Nom de l'adresse *</label>
            <input 
              type="text" 
              id="addressName" 
              v-model="addressForm.nom" 
              class="form-input"
              placeholder="Ex: Domicile, Bureau, etc."
              required
            >
          </div>
          
          <div class="form-group">
            <label for="addressStreet">Adresse *</label>
            <textarea 
              id="addressStreet" 
              v-model="addressForm.adresse" 
              class="form-input"
              rows="3"
              placeholder="123 rue de la Paix"
              required
            ></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="addressPostal">Code postal *</label>
              <input 
                type="text" 
                id="addressPostal" 
                v-model="addressForm.code_postal" 
                class="form-input"
                placeholder="75001"
                maxlength="10"
                required
              >
            </div>
            
            <div class="form-group">
              <label for="addressCity">Ville *</label>
              <input 
                type="text" 
                id="addressCity" 
                v-model="addressForm.ville" 
                class="form-input"
                placeholder="Paris"
                required
              >
            </div>
          </div>
          
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input 
                type="checkbox" 
                v-model="addressForm.is_default"
                :disabled="!editingAddress"
              >
              <span class="checkmark"></span>
              Définir comme adresse par défaut
            </label>
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="closeAddressModal" class="btn btn-secondary">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="savingAddress">
              {{ savingAddress ? 'Sauvegarde...' : (editingAddress ? 'Modifier' : 'Ajouter') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'
import { useAuthFetch } from '../composables/useAuthFetch'

const authStore = useAuthStore()
const { apiFetch } = useAuthFetch()
const { success: showSuccess, error: showError, warning: showWarning, confirm: showConfirm } = useAlert()

const loading = ref(true)
const orders = ref([])
const ordersLoading = ref(false)
const showAddAddressModal = ref(false)
const editingAddress = ref(null)
const savingAddress = ref(false)

// Formulaire d'adresse
const addressForm = reactive({
  nom: '',
  adresse: '',
  code_postal: '',
  ville: '',
  is_default: false
})
const isEditing = ref(false)
const saving = ref(false)
const preferences = reactive({
  newsletter: true,
  notifications: true
})

const user = computed(() => authStore.user)

// Formulaire d'édition
const editForm = reactive({
  nom: '',
  email: '',
  telephone: '',
  current_password: '',
  new_password: '',
  confirm_password: ''
})

const ordersCount = computed(() => orders.value.length)

const totalSpent = computed(() => {
  return orders.value.reduce((total, order) => total + parseFloat(order.total), 0).toFixed(2)
})

// Computed pour vérifier si on peut ajouter une adresse
const canAddAddress = computed(() => {
  if (!user.value) return false
  const addresses = [user.value.adresse, user.value.adresse2, user.value.adresse3]
  return addresses.some(addr => !addr || addr.trim() === '')
})



const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR')
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
}

// Validation du numéro de téléphone
const validatePhone = (event) => {
  const value = event.target.value
  // Autoriser seulement les chiffres, le signe +, les espaces, les tirets et les parenthèses
  const phoneRegex = /^[0-9+\s\-\(\)]*$/
  
  if (!phoneRegex.test(value)) {
    // Supprimer les caractères non autorisés
    const cleanValue = value.replace(/[^0-9+\s\-\(\)]/g, '')
    editForm.telephone = cleanValue
    event.target.value = cleanValue
  }
}


const getStatusClass = (status) => {
  const statusClasses = {
    'en attente': 'status-pending',
    'payée': 'status-processing',
    'en cours': 'status-processing',
    'expédiée': 'status-shipped',
    'livrée': 'status-delivered',
    'annulée': 'status-cancelled'
  }
  return statusClasses[status] || 'status-unknown'
}

const getStatusText = (status) => {
  const statusTexts = {
    'en attente': 'En attente de paiement',
    'payée': 'Payée',
    'en cours': 'En cours de traitement',
    'expédiée': 'Expédiée',
    'livrée': 'Livrée',
    'annulée': 'Annulée'
  }
  return statusTexts[status] || status
}

const getPaymentMethodName = (method) => {
  const methods = {
    'card': 'Carte bancaire',
    'stripe': 'Carte bancaire'
  }
  return methods[method] || 'Carte bancaire'
}

const loadOrders = async () => {
  if (!user.value?.id) return

  try {
    ordersLoading.value = true

    const response = await apiFetch(`/api/commandes?limit=10&offset=0`)

    if (response.ok) {
      const data = await response.json()
      orders.value = data
    }
  } catch {
    // silently ignore
  } finally {
    ordersLoading.value = false
  }
}

const loadAllOrders = async () => {
  if (!user.value?.id) return

  try {
    ordersLoading.value = true

    const response = await apiFetch(`/api/commandes?limit=100&offset=0`)

    if (response.ok) {
      const data = await response.json()
      orders.value = data
    }
  } catch {
    // silently ignore
  } finally {
    ordersLoading.value = false
  }
}

const refreshOrders = async () => {
  await loadOrders()
}

// Fonctions pour les adresses de livraison





const saveAddress = async () => {
  savingAddress.value = true
  
  try {
    const addressNumber = editingAddress.value.number
    const updateData = {}
    
    // Créer une copie des données actuelles pour éviter de perdre les autres adresses
    const currentUser = { ...user.value }
    
    if (addressNumber === 1) {
      updateData.adresse = addressForm.adresse
      updateData.code_postal = addressForm.code_postal
      updateData.ville = addressForm.ville
      // Préserver les autres adresses
      updateData.adresse2 = currentUser.adresse2 || ''
      updateData.code_postal2 = currentUser.code_postal2 || ''
      updateData.ville2 = currentUser.ville2 || ''
      updateData.adresse3 = currentUser.adresse3 || ''
      updateData.code_postal3 = currentUser.code_postal3 || ''
      updateData.ville3 = currentUser.ville3 || ''
    } else if (addressNumber === 2) {
      updateData.adresse2 = addressForm.adresse
      updateData.code_postal2 = addressForm.code_postal
      updateData.ville2 = addressForm.ville
      // Préserver les autres adresses
      updateData.adresse = currentUser.adresse || ''
      updateData.code_postal = currentUser.code_postal || ''
      updateData.ville = currentUser.ville || ''
      updateData.adresse3 = currentUser.adresse3 || ''
      updateData.code_postal3 = currentUser.code_postal3 || ''
      updateData.ville3 = currentUser.ville3 || ''
    } else if (addressNumber === 3) {
      updateData.adresse3 = addressForm.adresse
      updateData.code_postal3 = addressForm.code_postal
      updateData.ville3 = addressForm.ville
      // Préserver les autres adresses
      updateData.adresse = currentUser.adresse || ''
      updateData.code_postal = currentUser.code_postal || ''
      updateData.ville = currentUser.ville || ''
      updateData.adresse2 = currentUser.adresse2 || ''
      updateData.code_postal2 = currentUser.code_postal2 || ''
      updateData.ville2 = currentUser.ville2 || ''
    }
    
    
    const response = await apiFetch(`/api/clients/profile/${user.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(updateData)
    })

    if (response.ok) {
      const updatedUser = await response.json()

      authStore.user = updatedUser
      localStorage.setItem('user', JSON.stringify(updatedUser))

      closeAddressModal()
    }
  } catch {
    // silently ignore
  } finally {
    savingAddress.value = false
  }
}

// Fonctions pour le modal d'adresse
const closeAddressModal = () => {
  showAddAddressModal.value = false
  editingAddress.value = null
  resetAddressForm()
}

const resetAddressForm = () => {
  addressForm.nom = ''
  addressForm.adresse = ''
  addressForm.code_postal = ''
  addressForm.ville = ''
  addressForm.is_default = false
}

// Fonction pour ajouter une nouvelle adresse
const addNewAddress = () => {
  if (!user.value) return
  
  // Trouver le premier slot vide
  let addressNumber = 1
  if (user.value.adresse && user.value.adresse.trim() !== '') {
    addressNumber = 2
  }
  if (addressNumber === 2 && user.value.adresse2 && user.value.adresse2.trim() !== '') {
    addressNumber = 3
  }
  
  editingAddress.value = { type: 'profile', number: addressNumber }
  
  // Pré-remplir le formulaire
  if (addressNumber === 1) {
    addressForm.nom = 'Adresse principale'
    addressForm.adresse = ''
    addressForm.code_postal = ''
    addressForm.ville = ''
  } else if (addressNumber === 2) {
    addressForm.nom = 'Adresse secondaire'
    addressForm.adresse = ''
    addressForm.code_postal = ''
    addressForm.ville = ''
  } else if (addressNumber === 3) {
    addressForm.nom = 'Adresse tertiaire'
    addressForm.adresse = ''
    addressForm.code_postal = ''
    addressForm.ville = ''
  }
  
  showAddAddressModal.value = true
}

// Fonction pour supprimer une adresse (la vider)
const deleteProfileAddress = async (addressNumber) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')
  if (!confirmed) return
  
  // Créer une copie des données actuelles pour préserver les autres adresses
  const currentUser = { ...user.value }
  const updateData = {
    adresse: currentUser.adresse || '',
    code_postal: currentUser.code_postal || '',
    ville: currentUser.ville || '',
    adresse2: currentUser.adresse2 || '',
    code_postal2: currentUser.code_postal2 || '',
    ville2: currentUser.ville2 || '',
    adresse3: currentUser.adresse3 || '',
    code_postal3: currentUser.code_postal3 || '',
    ville3: currentUser.ville3 || ''
  }
  
  // Vider seulement l'adresse spécifiée
  if (addressNumber === 1) {
    updateData.adresse = ''
    updateData.code_postal = ''
    updateData.ville = ''
  } else if (addressNumber === 2) {
    updateData.adresse2 = ''
    updateData.code_postal2 = ''
    updateData.ville2 = ''
  } else if (addressNumber === 3) {
    updateData.adresse3 = ''
    updateData.code_postal3 = ''
    updateData.ville3 = ''
  }
  
  
  try {
    const response = await apiFetch(`/api/clients/profile/${user.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(updateData)
    })

    if (response.ok) {
      const updatedUser = await response.json()

      authStore.user = updatedUser
      localStorage.setItem('user', JSON.stringify(updatedUser))
    }
  } catch {
    // silently ignore
  }
}

// Fonction pour éditer les adresses du profil
const editProfileAddress = (addressNumber) => {
  editingAddress.value = { type: 'profile', number: addressNumber }
  
  if (addressNumber === 1) {
    addressForm.nom = 'Adresse principale'
    addressForm.adresse = user.value?.adresse || ''
    addressForm.code_postal = user.value?.code_postal || ''
    addressForm.ville = user.value?.ville || ''
  } else if (addressNumber === 2) {
    addressForm.nom = 'Adresse secondaire'
    addressForm.adresse = user.value?.adresse2 || ''
    addressForm.code_postal = user.value?.code_postal2 || ''
    addressForm.ville = user.value?.ville2 || ''
  } else if (addressNumber === 3) {
    addressForm.nom = 'Adresse tertiaire'
    addressForm.adresse = user.value?.adresse3 || ''
    addressForm.code_postal = user.value?.code_postal3 || ''
    addressForm.ville = user.value?.ville3 || ''
  }
  
  addressForm.is_default = false
  showAddAddressModal.value = true
}

// Méthodes d'édition du profil
const toggleEditMode = () => {
  if (!isEditing.value) {
    // Remplir le formulaire avec les données actuelles
    editForm.nom = user.value?.nom || ''
    editForm.email = user.value?.email || ''
    editForm.telephone = user.value?.telephone || ''
    editForm.current_password = ''
    editForm.new_password = ''
    editForm.confirm_password = ''
  }
  isEditing.value = !isEditing.value
}

const cancelEdit = () => {
  isEditing.value = false
  // Réinitialiser le formulaire
  Object.keys(editForm).forEach(key => {
    editForm[key] = ''
  })
}

const saveProfile = async () => {
  saving.value = true
  
  try {
    // Validation des mots de passe si fournis
    if (editForm.new_password && editForm.new_password !== editForm.confirm_password) {
      showWarning('Les mots de passe ne correspondent pas')
      saving.value = false
      return
    }
    
    if (editForm.new_password && editForm.new_password.length < 6) {
      showWarning('Le mot de passe doit contenir au moins 6 caractères')
      saving.value = false
      return
    }

    // Préparer les données à envoyer
    const updateData = {
      nom: editForm.nom,
      email: editForm.email,
      telephone: editForm.telephone
    }

    // Ajouter le mot de passe seulement s'il est fourni
    if (editForm.new_password) {
      updateData.mot_de_passe = editForm.new_password
    }

    const response = await apiFetch(`/api/clients/profile/${user.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(updateData)
    })

    if (response.ok) {
      const updatedUser = await response.json()

      authStore.user = updatedUser
      localStorage.setItem('user', JSON.stringify(updatedUser))

      showSuccess('Profil mis à jour avec succès !')
      isEditing.value = false

      Object.keys(editForm).forEach(key => {
        editForm[key] = ''
      })
    } else {
      const errorData = await response.json()
      showError(`Erreur: ${errorData.error || 'Impossible de mettre à jour le profil'}`)
    }
  } catch {
    showError('Erreur lors de la mise à jour du profil')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  // Attendre que l'utilisateur soit chargé
  await authStore.loadUser()
  
  loading.value = false
  
  await loadOrders()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  padding: 40px 0;
}

.page-title {
  font-size: 36px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 40px;
}

.loading-container {
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

.profile-content {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.profile-header {
  display: flex;
  gap: 30px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.profile-avatar {
  width: 100px;
  height: 100px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-icon {
  font-size: 40px;
  color: white;
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.profile-email {
  color: var(--text-secondary);
  font-size: 16px;
  margin-bottom: 20px;
}

.profile-stats {
  display: flex;
  gap: 30px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-number {
  font-size: 24px;
  font-weight: bold;
  color: var(--primary-color);
}

.stat-label {
  color: var(--text-secondary);
  font-size: 14px;
}

.profile-sections {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.profile-section {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.edit-btn {
  padding: 8px 16px;
  font-size: 14px;
}

.edit-actions {
  display: flex;
  gap: 10px;
}

.edit-actions .btn {
  padding: 8px 16px;
  font-size: 14px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.info-item span {
  color: var(--text-primary);
  font-size: 16px;
}

/* Styles pour le formulaire d'édition */
.edit-form {
  background: rgba(20, 20, 20, 0.3);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 25px;
  margin-top: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
}

.form-input {
  padding: 12px 16px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: rgba(20, 20, 20, 0.5);
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(208, 255, 89, 0.2);
}

.form-input::placeholder {
  color: var(--text-secondary);
}

.form-input.error {
  border-color: #dc3545;
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
}

.error-message {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
  font-weight: 500;
}

.password-section {
  border-top: 1px solid var(--border-color);
  padding-top: 25px;
  margin-top: 25px;
}

.password-section h4 {
  color: var(--primary-color);
  font-size: 16px;
  margin-bottom: 20px;
  font-weight: 600;
}

.no-orders {
  text-align: center;
  padding: 40px 20px;
}

.no-orders-icon {
  font-size: 48px;
  margin-bottom: 15px;
  opacity: 0.5;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--border-color);
  border-radius: 10px;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.order-number {
  font-weight: 600;
  color: var(--text-primary);
}

.order-date {
  color: var(--text-secondary);
  font-size: 14px;
}

.order-status {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 12px;
  text-transform: uppercase;
}

.order-status.livré {
  background: var(--accent-color);
  color: white;
}

.order-status.en-cours {
  background: var(--primary-color);
  color: #050505;
}

.order-total {
  font-size: 18px;
  font-weight: bold;
  color: var(--primary-color);
}

.preferences {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.preference-item {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-primary);
  font-size: 14px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkmark {
  width: 18px;
  height: 18px;
  border: 2px solid var(--border-color);
  border-radius: 4px;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
  background: var(--primary-color);
  border-color: var(--primary-color);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }
  
  .profile-stats {
    justify-content: center;
  }
  
  .order-item {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .page-title {
    font-size: 28px;
  }
}

/* Nouveaux styles pour les commandes */
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.order-info h4 {
  margin: 0 0 0.25rem 0;
  color: #333;
  font-size: 1.1rem;
  font-weight: 600;
}

.order-date {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

.order-status {
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status-processing {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status-shipped {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-delivered {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status-cancelled {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.order-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.order-summary {
  display: flex;
  gap: 2rem;
  flex: 1;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-item span:first-child {
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
}

.summary-item span:last-child {
  font-weight: 600;
  color: #333;
}

.total-price {
  color: var(--primary-color) !important;
  font-size: 1.1rem;
}

.order-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-outline {
  background: transparent;
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
  padding: 0.5rem 1rem;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-outline:hover {
  background: var(--primary-color);
  color: #050505;
  transform: translateY(-1px);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.refresh-btn {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  color: #666;
  padding: 0.5rem 1rem;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.refresh-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: #050505;
  border-color: var(--primary-color);
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-orders {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #666;
}

.loading-orders .loading-spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-orders {
  text-align: center;
  padding: 3rem 2rem;
  color: #666;
}

.no-orders-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.no-orders h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
  font-size: 1.2rem;
}

.no-orders p {
  margin: 0 0 1.5rem 0;
  color: #666;
}

.more-orders-info {
  text-align: center;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 10px;
  margin-top: 1rem;
  border: 1px solid #e9ecef;
}

.more-orders-info p {
  margin: 0 0 1rem 0;
  color: #666;
  font-size: 0.9rem;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 25px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-secondary:hover:not(:disabled) {
  background: #5a6268;
  transform: translateY(-1px);
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Styles pour le modal d'adresse */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.25rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f8f9fa;
  color: #495057;
}

.address-form {
  padding: 1.5rem;
}

.address-form .form-group {
  margin-bottom: 1rem;
}

.address-form .form-row {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1rem;
}

.address-form label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #495057;
}

.address-form .form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 0.9rem;
  transition: border-color 0.2s ease;
}

.address-form .form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(208, 255, 89, 0.1);
}

.address-form textarea.form-input {
  resize: vertical;
  min-height: 80px;
}

.address-form .checkbox-group {
  margin-top: 1rem;
}

.address-form .checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 0.9rem;
}

.address-form .checkbox-label input[type="checkbox"] {
  margin-right: 0.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.modal-actions .btn {
  min-width: 100px;
}

/* Responsive pour le modal */
@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    max-width: calc(100% - 2rem);
  }
  
  .address-form .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .modal-actions .btn {
    width: 100%;
  }
}


.address-card {
  background: rgba(20, 20, 20, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 25px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.address-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
}

.address-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(208, 255, 89, 0.2);
  border-color: var(--primary-color);
  background: rgba(20, 20, 20, 0.8);
}

.address-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border-color);
}

.address-info h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.address-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.address-actions .btn {
  padding: 8px 16px;
  font-size: 14px;
  border-radius: 25px;
  transition: all 0.2s ease;
}

.address-actions .btn-outline {
  border: 2px solid var(--primary-color);
  color: var(--primary-color);
  background: transparent;
}

.address-actions .btn-outline:hover {
  background: var(--primary-color);
  color: #050505;
  transform: translateY(-1px);
}

.address-actions .btn-danger {
  border: 2px solid #dc3545;
  color: #dc3545;
  background: transparent;
}

.address-actions .btn-danger:hover {
  background: #dc3545;
  color: white;
  transform: translateY(-1px);
}

.address-details {
  margin-bottom: 20px;
}

.address-text {
  font-size: 16px;
  color: var(--text-primary);
  line-height: 1.5;
  margin-bottom: 8px;
  font-weight: 500;
}

.address-city {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 8px;
  font-weight: 500;
}

.address-phone {
  color: #28a745;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.address-phone::before {
  content: '📞';
  font-size: 0.8rem;
}

.address-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 15px;
  border-top: 1px solid var(--border-color);
  margin-top: 15px;
}

.address-date {
  color: var(--text-secondary);
  font-size: 12px;
  font-style: italic;
}

.default-badge {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.default-address {
  border: 2px solid #28a745;
  background: rgba(20, 20, 20, 0.8);
}

.default-address::before {
  background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

/* Styles pour les adresses du profil */
.profile-address {
  border-left: 4px solid #17a2b8;
  background: rgba(20, 20, 20, 0.6);
  position: relative;
}

.profile-address::before {
  background: linear-gradient(90deg, #17a2b8 0%, #20c997 100%);
}

.profile-badge {
  background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(23, 162, 184, 0.3);
}

.profile-address .address-header {
  border-bottom: 1px solid var(--border-color);
}

.profile-address .address-details {
  background: rgba(20, 20, 20, 0.3);
  border-radius: 8px;
  padding: 15px;
  margin: 10px 0;
  border: 1px solid var(--border-color);
}

.profile-address .address-text {
  font-weight: 600;
  color: var(--text-primary);
}

.profile-address .address-city {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}


/* Styles pour les adresses de profil */
.profile-addresses-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.address-card {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  background: rgba(20, 20, 20, 0.3);
  transition: all 0.3s ease;
  position: relative;
}

.address-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: var(--primary-color);
}

.section-description {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 8px 0 0 0;
  font-style: italic;
}

.no-profile-addresses {
  text-align: center;
  padding: 3rem;
  color: var(--text-secondary);
  grid-column: 1 / -1;
}

.no-profile-addresses .no-addresses-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-profile-addresses h4 {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.no-profile-addresses p {
  font-size: 14px;
  margin: 0;
}

.all-addresses-used {
  text-align: center;
  padding: 3rem;
  color: var(--text-secondary);
  grid-column: 1 / -1;
}

.all-addresses-used .no-addresses-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.all-addresses-used h4 {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.all-addresses-used p {
  font-size: 14px;
  margin: 0;
}


/* Responsive pour les adresses */
@media (max-width: 768px) {
  .profile-addresses-list {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .address-card {
    padding: 20px;
    margin-bottom: 20px;
  }
  
  .address-header {
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }
  
  .address-actions {
    justify-content: center;
  }
  
  .address-actions .btn {
    flex: 1;
    min-width: 120px;
  }
  
  .address-footer {
    flex-direction: column;
    gap: 10px;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .address-actions {
    flex-direction: column;
  }
  
  .address-actions .btn {
    width: 100%;
  }
}
</style>
