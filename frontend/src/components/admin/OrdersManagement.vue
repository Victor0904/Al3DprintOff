<template>
  <div class="orders-management">
    <h2>📦 Gestion des Commandes</h2>
    <div class="orders-table">
      <div class="table-header">
        <div class="col-id">ID</div>
        <div class="col-client">Client</div>
        <div class="col-total">Total</div>
        <div class="col-status">Statut</div>
        <div class="col-date">Date</div>
        <div class="col-actions">Actions</div>
        <div class="col-tracking">Numéro de suivi</div>
      </div>
      <div v-for="order in orders" :key="order.id" class="table-row">
        <div class="col-id">{{ order.id }}</div>
        <div class="col-client">{{ order.client_nom }}</div>
        <div class="col-total">{{ order.total }}€</div>
        <div class="col-status">
          <select 
            :value="order.statut" 
            @change="updateStatus(order, $event.target.value)"
            class="status-select"
          >
            <option value="En attente">En attente</option>
            <option value="En cours">En cours</option>
            <option value="Expédié">Expédié</option>
            <option value="Terminé">Terminé</option>
            <option value="Annulée">Annulée</option>
          </select>
        </div>
        <div class="col-date">{{ formatDate(order.date_creation || order.date_commande) }}</div>
        <div class="col-actions">
          <button @click="viewOrder(order)" class="action-btn view">👁️</button>
        </div>
        <div class="col-tracking">
          <input 
            type="text" 
            :value="order.numero_suivi || ''" 
            @blur="updateTrackingNumber(order, $event.target.value)"
            @keyup.enter="updateTrackingNumber(order, $event.target.value)"
            placeholder="Numéro de suivi"
            class="tracking-input"
          />
        </div>
      </div>
    </div>

    <!-- Modale de détails de commande -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>📋 Détails de la commande #{{ selectedOrder?.id }}</h2>
          <div class="header-actions">
            <button 
              @click="previewEmail" 
              class="action-btn preview-email"
              :disabled="loadingEmailPreview || !orderDetails?.client?.email"
              title="Prévisualiser l'email"
            >
              👁️ Prévisualiser
            </button>
            <button 
              @click="sendEmail" 
              class="action-btn send-email"
              :disabled="sendingEmail || loadingEmailPreview || !orderDetails?.client?.email"
              title="Envoyer l'email au client"
            >
              📧 Envoyer
            </button>
            <button @click="closeModal" class="close-btn">✕</button>
          </div>
        </div>

        <div v-if="loadingOrder" class="loading-state">
          <div class="spinner"></div>
          <p>Chargement des détails...</p>
        </div>

        <div v-else-if="orderDetails" class="modal-body">
          <!-- Informations générales -->
          <div class="detail-section">
            <h3>📊 Informations générales</h3>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Date de commande :</span>
                <span class="info-value">{{ formatFullDate(orderDetails.date_commande) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Statut :</span>
                <span class="info-value status-badge" :class="orderDetails.statut.toLowerCase().replace(' ', '-')">
                  {{ orderDetails.statut }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Total :</span>
                <span class="info-value total-amount">{{ parseFloat(orderDetails.total).toFixed(2) }}€</span>
              </div>
              <div class="info-item">
                <span class="info-label">Mode de paiement :</span>
                <span class="info-value">{{ getPaymentMethodName(orderDetails.mode_paiement) }}</span>
              </div>
            </div>
          </div>

          <!-- Informations client -->
          <div class="detail-section" v-if="orderDetails.client">
            <h3>👤 Informations client</h3>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Nom :</span>
                <span class="info-value">{{ orderDetails.client.nom }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email :</span>
                <span class="info-value">{{ orderDetails.client.email }}</span>
              </div>
              <div class="info-item" v-if="orderDetails.client.telephone">
                <span class="info-label">Téléphone :</span>
                <span class="info-value">{{ orderDetails.client.telephone }}</span>
              </div>
            </div>
          </div>

          <!-- Adresse de livraison -->
          <div class="detail-section">
            <h3>📍 Adresse de livraison</h3>
            <div class="address-edit-container">
              <textarea
                v-model="editingAddress"
                @blur="updateDeliveryAddress"
                @keyup.ctrl.enter="updateDeliveryAddress"
                class="address-input"
                rows="4"
                placeholder="Adresse de livraison"
              ></textarea>
              <div class="address-actions">
                <button 
                  @click="updateDeliveryAddress" 
                  class="save-address-btn"
                  :disabled="isSavingAddress"
                >
                  {{ isSavingAddress ? 'Sauvegarde...' : '💾 Sauvegarder' }}
                </button>
                <span v-if="addressSaveStatus" class="address-status" :class="addressSaveStatus.type">
                  {{ addressSaveStatus.message }}
                </span>
              </div>
            </div>
          </div>

          <!-- Articles commandés -->
          <div class="detail-section" v-if="orderDetails.details && orderDetails.details.length > 0">
            <h3>🛍️ Articles commandés ({{ orderDetails.details.length }})</h3>
            <div class="items-table">
              <div class="items-header">
                <div class="item-col-ref">Référence</div>
                <div class="item-col-name">Article</div>
                <div class="item-col-qty">Quantité</div>
                <div class="item-col-price">Prix unitaire</div>
                <div class="item-col-total">Total</div>
              </div>
              <div v-for="(item, index) in orderDetails.details" :key="item.id || index" class="items-row">
                <div class="item-col-ref">#{{ item.produit?.id || 'N/A' }}</div>
                <div class="item-col-name">
                  <strong>{{ item.produit?.nom || 'Produit supprimé' }}</strong>
                  <div v-if="item.personnalisation" class="customization-info">
                    <small>Personnalisation : {{ formatCustomization(item.personnalisation) }}</small>
                  </div>
                </div>
                <div class="item-col-qty">{{ item.quantite }}</div>
                <div class="item-col-price">{{ parseFloat(item.prix_unitaire).toFixed(2) }}€</div>
                <div class="item-col-total">{{ (parseFloat(item.prix_unitaire) * item.quantite).toFixed(2) }}€</div>
              </div>
            </div>
          </div>

          <!-- Informations de paiement -->
          <div class="detail-section" v-if="orderDetails.paiements && orderDetails.paiements.length > 0">
            <h3>💳 Paiements</h3>
            <div class="payments-list">
              <div v-for="paiement in orderDetails.paiements" :key="paiement.id" class="payment-item">
                <div class="payment-info">
                  <span class="payment-amount">{{ parseFloat(paiement.montant).toFixed(2) }}€</span>
                  <span class="payment-method">{{ paiement.methode }}</span>
                  <span class="payment-status" :class="paiement.statut.toLowerCase().replace(' ', '-')">
                    {{ paiement.statut }}
                  </span>
                </div>
                <div class="payment-date">{{ formatFullDate(paiement.date_paiement) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="orderError" class="error-state">
          <p>❌ Erreur : {{ orderError }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAlert } from '../../composables/useAlert'
import { useAuthFetch } from '../../composables/useAuthFetch'

const { error: showError, success: showSuccess } = useAlert()
const { apiFetch } = useAuthFetch()
const orders = ref([])
const showModal = ref(false)
const selectedOrder = ref(null)
const orderDetails = ref(null)
const loadingOrder = ref(false)
const orderError = ref(null)
const editingAddress = ref('')
const isSavingAddress = ref(false)
const addressSaveStatus = ref(null)
const showEmailPreview = ref(false)
const emailPreview = ref(null)
const loadingEmailPreview = ref(false)
const emailPreviewError = ref(null)
const sendingEmail = ref(false)

const loadOrders = async () => {
  try {
    const response = await apiFetch('/api/commandes')
    if (response.ok) {
      // Utiliser directement les données de la BDD sans normalisation
      orders.value = await response.json()
    }
  } catch (error) {
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const getPaymentMethodName = (method) => {
  const methods = { card: 'Carte bancaire', stripe: 'Carte bancaire' }
  return methods[method] || method || 'Non spécifié'
}

const formatFullDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCustomization = (customization) => {
  if (!customization) return ''
  if (typeof customization === 'string') {
    try {
      customization = JSON.parse(customization)
    } catch (e) {
      return customization
    }
  }
  if (typeof customization === 'object') {
    return Object.entries(customization)
      .map(([key, value]) => `${key}: ${value}`)
      .join(', ')
  }
  return String(customization)
}

const viewOrder = async (order) => {
  selectedOrder.value = order
  showModal.value = true
  loadingOrder.value = true
  orderDetails.value = null
  orderError.value = null
  editingAddress.value = ''
  addressSaveStatus.value = null

  try {
    const response = await apiFetch(`/api/commandes/${order.id}`)
    if (response.ok) {
      orderDetails.value = await response.json()
      editingAddress.value = orderDetails.value.adresse_livraison || ''
    } else {
      orderError.value = 'Erreur lors du chargement des détails de la commande'
    }
  } catch (error) {
    orderError.value = error.message || 'Erreur de connexion'
  } finally {
    loadingOrder.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  selectedOrder.value = null
  orderDetails.value = null
  orderError.value = null
  editingAddress.value = ''
  addressSaveStatus.value = null
}

const previewEmail = async () => {
  if (!selectedOrder.value || !orderDetails.value) {
    return
  }

  showEmailPreview.value = true
  loadingEmailPreview.value = true
  emailPreview.value = null
  emailPreviewError.value = null

  try {
    const response = await apiFetch(`/api/commandes/${selectedOrder.value.id}/email-preview`)
    if (response.ok) {
      emailPreview.value = await response.json()
    } else {
      const errorData = await response.json()
      emailPreviewError.value = errorData.error || 'Erreur lors du chargement de la prévisualisation'
    }
  } catch (error) {
    emailPreviewError.value = error.message || 'Erreur de connexion'
  } finally {
    loadingEmailPreview.value = false
  }
}

const closeEmailPreview = () => {
  showEmailPreview.value = false
  emailPreview.value = null
  emailPreviewError.value = null
}

const sendEmail = async () => {
  if (!selectedOrder.value || !orderDetails.value) {
    return
  }

  if (!orderDetails.value.client?.email) {
    showError('Email du client non disponible')
    return
  }

  sendingEmail.value = true

  try {
    const response = await apiFetch(`/api/commandes/${selectedOrder.value.id}/send-email`, {
      method: 'POST'
    })

    const data = await response.json()

    if (response.ok) {
      if (data.warning) {
        showError(data.message)
      } else {
        showSuccess(data.message || 'Email envoyé avec succès')
      }
    } else {
      showError(data.error || 'Erreur lors de l\'envoi de l\'email')
    }
  } catch (error) {
    showError(`Erreur de connexion: ${error.message}`)
  } finally {
    sendingEmail.value = false
  }
}

const updateDeliveryAddress = async () => {
  if (!orderDetails.value || !selectedOrder.value) {
    return
  }

  const trimmedAddress = editingAddress.value ? editingAddress.value.trim() : ''
  
  // Si l'adresse n'a pas changé, ne rien faire
  if (trimmedAddress === (orderDetails.value.adresse_livraison || '')) {
    return
  }

  // Sauvegarder l'ancienne adresse au cas où il faudrait la restaurer
  const oldAddress = orderDetails.value.adresse_livraison
  isSavingAddress.value = true
  addressSaveStatus.value = null

  try {
    const response = await apiFetch(`/api/commandes/${selectedOrder.value.id}`, {
      method: 'PUT',
      body: JSON.stringify({ adresse_livraison: trimmedAddress })
    })

    if (response.ok) {
      const updatedOrder = await response.json()
      // Mettre à jour avec les données du serveur pour garantir la cohérence
      orderDetails.value.adresse_livraison = updatedOrder.adresse_livraison
      editingAddress.value = updatedOrder.adresse_livraison || ''
      
      // Mettre à jour aussi dans la liste des commandes
      const orderInList = orders.value.find(o => o.id === selectedOrder.value.id)
      if (orderInList) {
        orderInList.adresse_livraison = updatedOrder.adresse_livraison
      }
      
      addressSaveStatus.value = {
        type: 'success',
        message: '✅ Adresse de livraison mise à jour avec succès'
      }
      
      // Effacer le message de succès après 3 secondes
      setTimeout(() => {
        addressSaveStatus.value = null
      }, 3000)
      
    } else {
      // En cas d'erreur, restaurer l'ancienne adresse
      editingAddress.value = oldAddress || ''
      const errorText = await response.text()
      
      let errorData = {}
      try {
        errorData = JSON.parse(errorText)
      } catch (e) {
        errorData = { error: errorText || 'Erreur inconnue' }
      }
      
      addressSaveStatus.value = {
        type: 'error',
        message: `❌ Erreur: ${errorData.error || 'Erreur inconnue'}`
      }
      
      showError(`Erreur lors de la mise à jour de l'adresse de livraison (${response.status}): ${errorData.error || 'Erreur inconnue'}`)
    }
  } catch (error) {
    // En cas d'erreur réseau, restaurer l'ancienne adresse
    editingAddress.value = oldAddress || ''
    
    addressSaveStatus.value = {
      type: 'error',
      message: '❌ Erreur de connexion'
    }
    
    showError(`Erreur de connexion: ${error.message}. Veuillez vérifier que le serveur est démarré.`)
  } finally {
    isSavingAddress.value = false
  }
}

const updateStatus = async (order, newStatus) => {
  if (!newStatus || newStatus === order.statut) {
    return
  }

  // Sauvegarder l'ancien statut au cas où il faudrait le restaurer
  const oldStatus = order.statut
  

  try {
    const response = await apiFetch(`/api/commandes/${order.id}`, {
      method: 'PUT',
      body: JSON.stringify({ statut: newStatus })
    })


    if (response.ok) {
      const updatedOrder = await response.json()
      
      // Mettre à jour avec les données du serveur pour garantir la cohérence
      order.statut = updatedOrder.statut
    } else {
      // En cas d'erreur, restaurer l'ancien statut
      order.statut = oldStatus
      const errorText = await response.text()
      
      let errorData = {}
      try {
        errorData = JSON.parse(errorText)
      } catch (e) {
        errorData = { error: errorText || 'Erreur inconnue' }
      }
      
      showError(`Erreur lors de la mise à jour du statut (${response.status}): ${errorData.error || 'Erreur inconnue'}`)
    }
  } catch (error) {
    // En cas d'erreur réseau, restaurer l'ancien statut
    order.statut = oldStatus
    showError(`Erreur de connexion: ${error.message}. Veuillez vérifier que le serveur est démarré.`)
  }
}

const updateTrackingNumber = async (order, newTrackingNumber) => {
  // Normaliser la valeur (trim et null si vide)
  const trimmedValue = newTrackingNumber ? newTrackingNumber.trim() : null
  
  // Si la valeur n'a pas changé, ne rien faire
  if (trimmedValue === (order.numero_suivi || null)) {
    return
  }

  // Sauvegarder l'ancien numéro au cas où il faudrait le restaurer
  const oldTrackingNumber = order.numero_suivi
  
  try {
    const response = await apiFetch(`/api/commandes/${order.id}`, {
      method: 'PUT',
      body: JSON.stringify({ numero_suivi: trimmedValue })
    })

    if (response.ok) {
      const updatedOrder = await response.json()
      // Mettre à jour avec les données du serveur pour garantir la cohérence
      order.numero_suivi = updatedOrder.numero_suivi
    } else {
      // En cas d'erreur, restaurer l'ancien numéro
      order.numero_suivi = oldTrackingNumber
      const errorText = await response.text()
      
      let errorData = {}
      try {
        errorData = JSON.parse(errorText)
      } catch (e) {
        errorData = { error: errorText || 'Erreur inconnue' }
      }
      
      showError(`Erreur lors de la mise à jour du numéro de suivi (${response.status}): ${errorData.error || 'Erreur inconnue'}`)
    }
  } catch (error) {
    // En cas d'erreur réseau, restaurer l'ancien numéro
    order.numero_suivi = oldTrackingNumber
    showError(`Erreur de connexion: ${error.message}. Veuillez vérifier que le serveur est démarré.`)
  }
}

onMounted(() => {
  loadOrders()
})
</script>

<style scoped>
.orders-management {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.orders-management h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 28px;
}

.orders-table {
  background: rgba(26, 26, 46, 0.5);
  border-radius: 10px;
  border: 1px solid #2e2e4a;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 60px 2fr 100px 120px 150px 120px 180px;
  gap: 15px;
  padding: 20px;
  background: rgba(0, 212, 255, 0.1);
  border-bottom: 1px solid #2e2e4a;
  font-weight: 600;
  color: #00d4ff;
}

.table-row {
  display: grid;
  grid-template-columns: 60px 2fr 100px 120px 150px 120px 180px;
  gap: 15px;
  padding: 20px;
  border-bottom: 1px solid #2e2e4a;
  align-items: center;
}

.table-row:hover {
  background: rgba(0, 212, 255, 0.05);
}

.status-select {
  width: 100%;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid #2e2e4a;
  color: #00d4ff;
  cursor: pointer;
  transition: all 0.3s ease;
}

.status-select:hover {
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
}

.status-select:focus {
  outline: none;
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.15);
  box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.2);
}

.status-select option {
  background: rgba(26, 26, 46, 0.95);
  color: #00d4ff;
  padding: 8px;
}

.col-actions {
  display: flex;
  gap: 8px;
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

.action-btn.view:hover {
  border-color: #4ecdc4;
  background: rgba(78, 205, 196, 0.1);
}

.action-btn.update:hover {
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
}

.col-tracking {
  display: flex;
  align-items: center;
}

.tracking-input {
  width: 100%;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid #2e2e4a;
  color: #fff;
  transition: all 0.3s ease;
}

.tracking-input::placeholder {
  color: #666;
}

.tracking-input:focus {
  outline: none;
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
  box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.2);
}

.tracking-input:hover {
  border-color: #3e3e5a;
}

/* Modale */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  overflow-y: auto;
}

.modal-content {
  background: rgba(26, 26, 46, 0.95);
  border-radius: 12px;
  border: 1px solid #2e2e4a;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 25px 30px;
  border-bottom: 1px solid #2e2e4a;
  background: rgba(0, 212, 255, 0.1);
}

.modal-header h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 24px;
}

.close-btn {
  background: none;
  border: 1px solid #2e2e4a;
  border-radius: 6px;
  color: #00d4ff;
  font-size: 20px;
  width: 35px;
  height: 35px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  border-color: #ff4444;
  background: rgba(255, 68, 68, 0.1);
  color: #ff4444;
}

.modal-body {
  padding: 30px;
}

.loading-state,
.error-state {
  padding: 60px 30px;
  text-align: center;
  color: #00d4ff;
}

.spinner {
  border: 3px solid rgba(0, 212, 255, 0.2);
  border-top: 3px solid #00d4ff;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.detail-section {
  margin-bottom: 30px;
  padding-bottom: 25px;
  border-bottom: 1px solid #2e2e4a;
}

.detail-section:last-child {
  border-bottom: none;
}

.detail-section h3 {
  color: #00d4ff;
  margin: 0 0 20px 0;
  font-size: 18px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-label {
  color: #888;
  font-size: 13px;
  font-weight: 500;
}

.info-value {
  color: #fff;
  font-size: 15px;
}

.total-amount {
  color: #00d4ff;
  font-weight: 600;
  font-size: 18px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(0, 212, 255, 0.2);
  color: #00d4ff;
}

.address-box {
  background: rgba(0, 212, 255, 0.05);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 15px;
  color: #fff;
  line-height: 1.6;
}

.address-edit-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.address-input {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid #2e2e4a;
  color: #fff;
  font-family: inherit;
  resize: vertical;
  transition: all 0.3s ease;
  line-height: 1.6;
}

.address-input::placeholder {
  color: #666;
}

.address-input:focus {
  outline: none;
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
  box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.2);
}

.address-input:hover {
  border-color: #3e3e5a;
}

.address-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.save-address-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  background: rgba(0, 212, 255, 0.2);
  border: 1px solid #00d4ff;
  color: #00d4ff;
  cursor: pointer;
  transition: all 0.3s ease;
}

.save-address-btn:hover:not(:disabled) {
  background: rgba(0, 212, 255, 0.3);
  border-color: #00d4ff;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 212, 255, 0.3);
}

.save-address-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.address-status {
  font-size: 13px;
  padding: 4px 8px;
  border-radius: 4px;
}

.address-status.success {
  color: #4ecdc4;
}

.address-status.error {
  color: #ff4444;
}

.items-table {
  margin-top: 15px;
}

.items-header {
  display: grid;
  grid-template-columns: 80px 2fr 100px 120px 100px;
  gap: 15px;
  padding: 12px 15px;
  background: rgba(0, 212, 255, 0.1);
  border-radius: 6px;
  font-weight: 600;
  color: #00d4ff;
  font-size: 13px;
}

.items-row {
  display: grid;
  grid-template-columns: 80px 2fr 100px 120px 100px;
  gap: 15px;
  padding: 15px;
  border-bottom: 1px solid #2e2e4a;
  align-items: start;
}

.items-row:last-child {
  border-bottom: none;
}

.items-row:hover {
  background: rgba(0, 212, 255, 0.05);
}

.item-col-ref {
  color: #888;
  font-size: 13px;
}

.item-col-name {
  color: #fff;
}

.item-col-name strong {
  display: block;
  margin-bottom: 5px;
}

.customization-info {
  color: #888;
  font-size: 12px;
  margin-top: 5px;
}

.item-col-qty,
.item-col-price,
.item-col-total {
  color: #fff;
  text-align: right;
}

.item-col-total {
  font-weight: 600;
  color: #00d4ff;
}

.payments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-item {
  background: rgba(0, 212, 255, 0.05);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.payment-info {
  display: flex;
  gap: 15px;
  align-items: center;
}

.payment-amount {
  color: #00d4ff;
  font-weight: 600;
  font-size: 16px;
}

.payment-method {
  color: #fff;
}

.payment-status {
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(0, 212, 255, 0.2);
  color: #00d4ff;
}

.payment-date {
  color: #888;
  font-size: 13px;
}

/* Scrollbar personnalisée pour la modale */
.modal-content::-webkit-scrollbar {
  width: 8px;
}

.modal-content::-webkit-scrollbar-track {
  background: rgba(26, 26, 46, 0.5);
}

.modal-content::-webkit-scrollbar-thumb {
  background: rgba(0, 212, 255, 0.3);
  border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 212, 255, 0.5);
}

/* Styles pour les boutons d'email */
.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.preview-email,
.send-email {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #2e2e4a;
  background: rgba(26, 26, 46, 0.8);
  color: #00d4ff;
}

.preview-email:hover:not(:disabled) {
  border-color: #4ecdc4;
  background: rgba(78, 205, 196, 0.1);
  color: #4ecdc4;
}

.send-email:hover:not(:disabled) {
  border-color: #00d4ff;
  background: rgba(0, 212, 255, 0.2);
  color: #00d4ff;
}

.preview-email:disabled,
.send-email:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modale de prévisualisation d'email */
.email-preview-modal {
  max-width: 800px;
}

.email-preview-body {
  padding: 30px;
  max-height: 80vh;
  overflow-y: auto;
}

.email-info {
  background: rgba(0, 212, 255, 0.05);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.email-info .info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #2e2e4a;
}

.email-info .info-row:last-child {
  border-bottom: none;
}

.email-info .info-label {
  color: #888;
  font-weight: 500;
}

.email-info .info-value {
  color: #fff;
}

.warning-box {
  background: rgba(255, 193, 7, 0.1);
  border-left: 4px solid #ffc107;
  padding: 12px 15px;
  border-radius: 5px;
  margin-bottom: 20px;
  color: #ffc107;
}

.email-html-preview {
  background: #fff;
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 20px;
  max-height: 500px;
  overflow-y: auto;
}

.email-html-preview :deep(*) {
  color: #333 !important;
}

/* Responsive */
@media (max-width: 768px) {
  .orders-management h2 {
    font-size: 22px;
  }
  
  .orders-table {
    overflow-x: auto;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 50px 1.5fr 90px 100px 120px 80px 1fr;
    gap: 10px;
    padding: 15px;
    font-size: 13px;
  }
  
  .status-select {
    font-size: 12px;
    padding: 5px 8px;
  }
  
  .action-btn {
    padding: 6px;
    font-size: 12px;
  }
  
  .tracking-input {
    padding: 5px 8px;
    font-size: 11px;
  }
  
  .modal-content {
    max-width: 95%;
    margin: 10px;
  }
  
  .modal-header {
    padding: 20px;
  }
  
  .modal-header h2 {
    font-size: 20px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .items-header,
  .items-row {
    grid-template-columns: 60px 1fr 60px 80px 80px;
    gap: 10px;
    padding: 10px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .orders-management h2 {
    font-size: 20px;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 12px;
  }
  
  .table-header {
    display: none;
  }
  
  .table-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border: 1px solid #2e2e4a;
    border-radius: 8px;
    margin-bottom: 10px;
  }
  
  .col-id::before {
    content: "ID: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-client::before {
    content: "Client: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-total::before {
    content: "Total: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-status::before {
    content: "Statut: ";
    font-weight: 600;
    color: #00d4ff;
    display: block;
    margin-bottom: 5px;
  }
  
  .col-date::before {
    content: "Date: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-actions::before {
    content: "Actions: ";
    font-weight: 600;
    color: #00d4ff;
    display: block;
    margin-bottom: 5px;
  }
  
  .col-tracking::before {
    content: "Numéro de suivi: ";
    font-weight: 600;
    color: #00d4ff;
    display: block;
    margin-bottom: 5px;
  }
  
  .tracking-input {
    width: 100%;
  }
  
  .modal-content {
    max-width: 100%;
    margin: 5px;
    max-height: 95vh;
  }
  
  .modal-header {
    padding: 15px;
  }
  
  .modal-header h2 {
    font-size: 18px;
  }
  
  .modal-body {
    padding: 15px;
  }
  
  .items-header,
  .items-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .items-header {
    display: none;
  }
}
</style>
