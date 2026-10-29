<template>
  <div class="checkout-page">
    <div class="container">
      <h1 class="page-title">Finaliser la commande</h1>
      
      <div v-if="cartStore.items.length === 0 && currentStep !== 3" class="empty-cart">
        <div class="empty-cart-icon">🛒</div>
        <h2>Votre panier est vide</h2>
        <p>Ajoutez des produits à votre panier avant de passer commande.</p>
        <router-link to="/products" class="btn btn-primary">
          Découvrir les produits
        </router-link>
      </div>

      <div v-else class="checkout-content">
        <div class="checkout-steps">
          <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
            <div class="step-number">1</div>
            <div class="step-title">Informations</div>
          </div>
          <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
            <div class="step-number">2</div>
            <div class="step-title">Paiement</div>
          </div>
          <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
            <div class="step-number">3</div>
            <div class="step-title">Confirmation</div>
          </div>
        </div>

        <div class="checkout-main">
          <div class="checkout-form">
            <!-- Étape 1: Informations -->
            <div v-if="currentStep === 1" class="step-content">
              <h2 class="step-title">Informations de livraison</h2>
              <form @submit.prevent="nextStep">
                <!-- Sélection d'adresse pour utilisateurs connectés -->
                <div v-if="isLoggedIn && hasProfileAddresses" class="address-selection-section">
                  <h3 class="section-subtitle">Choisir une adresse de livraison</h3>
                  
                  <!-- Message informatif si maximum d'adresses atteint -->
                  <div v-if="hasMaxAddresses" class="max-addresses-info">
                    <div class="info-icon">ℹ️</div>
                    <div class="info-content">
                      <strong>Maximum d'adresses atteint</strong>
                      <p>Vous avez déjà 3 adresses enregistrées. Vous pouvez choisir parmi vos adresses existantes ou modifier/supprimer une adresse dans votre profil pour en ajouter une nouvelle.</p>
                    </div>
                  </div>
                  <div class="address-options">
                    <div class="address-option" :class="{ active: selectedAddressType === 'profile' }" @click="selectProfileAddress">
                      <div class="address-option-header">
                        <input type="radio" id="profile-address" v-model="selectedAddressType" value="profile" name="addressType">
                        <label for="profile-address" class="address-option-label">
                          <span class="address-option-title">Utiliser une adresse de mon profil</span>
                          <span class="address-option-subtitle">Sélectionnez parmi vos adresses enregistrées</span>
                        </label>
                      </div>
                      <div v-if="selectedAddressType === 'profile'" class="address-dropdown">
                        <select v-model="selectedProfileAddress" @change="loadSelectedAddress" class="address-select">
                          <option value="">Choisir une adresse...</option>
                          <option v-if="user?.adresse" value="1">Adresse principale - {{ user.adresse }}, {{ user.code_postal }} {{ user.ville }}</option>
                          <option v-if="user?.adresse2" value="2">Adresse secondaire - {{ user.adresse2 }}, {{ user.code_postal2 }} {{ user.ville2 }}</option>
                          <option v-if="user?.adresse3" value="3">Adresse tertiaire - {{ user.adresse3 }}, {{ user.code_postal3 }} {{ user.ville3 }}</option>
                        </select>
                      </div>
                    </div>
                    
                    <div 
                      class="address-option" 
                      :class="{ 
                        active: selectedAddressType === 'new',
                        disabled: hasMaxAddresses
                      }" 
                      @click="selectNewAddress"
                    >
                      <div class="address-option-header">
                        <input 
                          type="radio" 
                          id="new-address" 
                          v-model="selectedAddressType" 
                          value="new" 
                          name="addressType"
                          :disabled="hasMaxAddresses"
                        >
                        <label for="new-address" class="address-option-label">
                          <span class="address-option-title">
                            Nouvelle adresse
                            <span v-if="hasMaxAddresses" class="max-addresses-badge">(Maximum atteint)</span>
                          </span>
                          <span class="address-option-subtitle">
                            <span v-if="!hasMaxAddresses">Saisir une nouvelle adresse de livraison</span>
                            <span v-else class="disabled-text">Vous avez atteint le maximum de 3 adresses</span>
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Formulaire d'adresse -->
                <div v-if="(selectedAddressType === 'new' && canAddNewAddress) || !isLoggedIn || !hasProfileAddresses" class="address-form-section">
                  <h3 class="section-subtitle">Informations de livraison</h3>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label for="firstName">Prénom</label>
                      <input type="text" id="firstName" v-model="checkoutForm.firstName" required>
                    </div>
                    <div class="form-group">
                      <label for="lastName">Nom</label>
                      <input type="text" id="lastName" v-model="checkoutForm.lastName" required>
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" v-model="checkoutForm.email" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" v-model="checkoutForm.phone" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" v-model="checkoutForm.address" rows="3" required></textarea>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group postal-code-group">
                      <label for="postalCode">Code postal</label>
                      <input type="text" id="postalCode" v-model="checkoutForm.postalCode" required>
                    </div>
                    <div class="form-group city-group">
                      <label for="city">Ville</label>
                      <input type="text" id="city" v-model="checkoutForm.city" required>
                    </div>
                  </div>
                  
                  <!-- Option de sauvegarde pour les utilisateurs connectés -->
                  <div v-if="isLoggedIn" class="save-address-section">
                    <div class="checkbox-group">
                      <label class="checkbox-label">
                        <input type="checkbox" v-model="saveToProfile">
                        <span class="checkmark"></span>
                        💾 Sauvegarder cette adresse dans mon profil
                      </label>
                      <p class="checkbox-description">
                        Cette adresse sera ajoutée à vos adresses de livraison
                      </p>
                    </div>
                  </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Continuer</button>
              </form>
            </div>

            <!-- Étape 2: Paiement -->
            <div v-if="currentStep === 2" class="step-content">
              <h2 class="step-title">Méthode de paiement</h2>
              <div class="payment-methods">
                <div class="payment-method" :class="{ active: selectedPayment === 'card' }" @click="selectStripePayment">
                  <div class="payment-icon">💳</div>
                  <div class="payment-info">
                    <h3>Carte bancaire</h3>
                    <p>Visa, Mastercard, American Express</p>
                  </div>
                </div>
              </div>
              
              <!-- Formulaire Stripe Elements -->
              <div v-if="selectedPayment === 'card'" class="stripe-payment-form">
                <div id="stripe-card-element" class="stripe-element">
                  <!-- Stripe Elements sera injecté ici -->
                </div>
                <div id="stripe-card-errors" role="alert" class="stripe-errors"></div>
              </div>
              
              <div class="checkout-actions">
                <button class="btn btn-secondary" @click="prevStep">Retour</button>
                <button 
                  class="btn btn-primary" 
                  @click="handlePayment" 
                  :disabled="!selectedPayment || (selectedPayment === 'card' && !stripeReady) || processingPayment"
                >
                  <span v-if="processingPayment">Traitement en cours...</span>
                  <span v-else>Payer {{ formatPrice(displayTotal) }}€</span>
                </button>
              </div>
            </div>

            <!-- Étape 3: Confirmation -->
            <div v-if="currentStep === 3" class="step-content">
              <h2 class="step-title">Confirmation de commande</h2>
              <div class="confirmation-content">
                <div class="confirmation-icon">✅</div>
                <h3>Commande confirmée !</h3>
                <p>Votre commande a été enregistrée avec succès et votre panier a été vidé.</p>
                
                <div class="success-messages">
                  <div class="success-message">
                    <span class="success-icon">🛒</span>
                    <span>Panier vidé automatiquement</span>
                  </div>
                  <div class="success-message">
                    <span class="success-icon">📧</span>
                    <span>Email de confirmation envoyé</span>
                  </div>
                  <div class="success-message">
                    <span class="success-icon">📦</span>
                    <span>Préparation en cours</span>
                  </div>
                </div>
                
                <div class="order-summary">
                  <div class="summary-item">
                    <span>Numéro de commande</span>
                    <span>#{{ currentOrderId }}</span>
                  </div>
                  <div class="summary-item">
                    <span>Total TTC</span>
                    <span v-if="orderTotalSnapshot > 0">{{ formatPrice(orderTotalSnapshot) }}€</span>
                    <span v-else class="empty-cart-text">—</span>
                  </div>
                  <div class="summary-item">
                    <span>Méthode de paiement</span>
                    <span>{{ getPaymentMethodName(selectedPayment) }}</span>
                  </div>
                </div>
                <div class="confirmation-actions">
                  <router-link :to="'/order/' + currentOrderId" class="btn btn-primary">
                    Voir ma commande #{{ currentOrderId }}
                  </router-link>
                  <router-link to="/products" class="btn btn-secondary">Continuer mes achats</router-link>
                </div>
              </div>
            </div>
          </div>

          <div class="checkout-summary">
            <div class="summary-card">
              <h3 class="summary-title">Résumé de la commande</h3>
              
              <div class="order-items">
                <div
                  v-for="item in (currentStep === 3 && orderItemsSnapshot.length ? orderItemsSnapshot : cartStore.items)"
                  :key="item.id"
                  class="order-item"
                >
                  <div class="item-image">
                    <img v-if="item.image" :src="item.image" :alt="item.name" class="item-img">
                    <div v-else class="item-placeholder">🔮</div>
                  </div>
                  <div class="item-details">
                    <h4 class="item-name">{{ item.name }}</h4>
                    <p class="item-category">{{ item.category }}</p>
                    <div class="item-quantity">Quantité: {{ item.quantity }}</div>
                  </div>
                  <div class="item-price">{{ formatPrice(item.price * item.quantity) }}€</div>
                </div>
              </div>
              
              <div class="summary-totals">
                <div class="summary-line">
                  <span>Sous-total</span>
                  <span>
                    {{ formatPrice(currentStep === 3 ? orderItemsSnapshot.reduce((s, i) => s + i.price * i.quantity, 0) : cartStore.totalPrice) }}€
                  </span>
                </div>
                <div class="summary-line">
                  <span>Livraison</span>
                  <span :class="(currentStep === 3 ? shippingSnapshot : shippingCost) === 0 ? 'free' : ''">
                    {{ (currentStep === 3 ? shippingSnapshot : shippingCost) === 0 ? 'Gratuite' : formatPrice(currentStep === 3 ? shippingSnapshot : shippingCost) + '€' }}
                  </span>
                </div>
                <div v-if="currentStep !== 3 && cartStore.discountAmount > 0" class="summary-line">
                  <span>Code promo {{ cartStore.promoCode }}</span>
                  <span>-{{ formatPrice(cartStore.discountAmount) }}€</span>
                </div>
                <div class="summary-line">
                  <span>{{ tvaLabel }}</span>
                  <span>{{ formatPrice(displayTotal - displayTotal / tvaMultiplier) }}€</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-line total">
                  <span>Total TTC</span>
                  <span>
                    {{ formatPrice(displayTotal) }}€
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { loadStripe } from '@stripe/stripe-js'
import { useAlert } from '../composables/useAlert'
import { useAuthFetch } from '../composables/useAuthFetch'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { apiFetch } = useAuthFetch()
const { error: showError, warning: showWarning } = useAlert()

const currentStep = ref(1)
const selectedPayment = ref('')
const currentOrderId = ref(null)
const shippingSnapshot = ref(0)
const saveToProfile = ref(false)

// Variables pour la sélection d'adresse
const selectedAddressType = ref('new')
const selectedProfileAddress = ref('')

// Variables Stripe
const stripe = ref(null)
const stripeElements = ref(null)
const cardElement = ref(null)
const stripeReady = ref(false)
const processingPayment = ref(false)
const paymentIntentId = ref(null)

// Snapshot de la commande pour l'écran de confirmation
const orderItemsSnapshot = ref([])
const orderTotalSnapshot = ref(0)
const stripePublicKey = ref('pk_test_51RD2FtC65YdLoUEBf797QhF92k5EGptjRe8Ezgzyns2oLWazTUCL8nnDM65E3novlFKqVJhOicPcC0CKnNlJjmXp009QE36exo')

// Shipping constants
const SHIPPING_FREE_THRESHOLD = 50
const SHIPPING_COST = 10

// Computed
const isLoggedIn = computed(() => authStore.isAuthenticated)
const user = computed(() => authStore.user)
const isEntreprise = computed(() => user.value?.type_client === 'entreprise')
const tvaRate = computed(() => isEntreprise.value ? 0.17 : 0.2)
const tvaMultiplier = computed(() => 1 + tvaRate.value)
const tvaLabel = computed(() => isEntreprise.value ? 'dont TVA 17 %' : 'dont TVA 20 %')

const shippingCost = computed(() =>
  cartStore.totalPrice >= SHIPPING_FREE_THRESHOLD ? 0 : SHIPPING_COST
)

// Prix produits TTC : total = sous-total remisé + port (même calcul que le serveur)
const displayTotal = computed(() =>
  currentStep.value === 3 ? orderTotalSnapshot.value : cartStore.discountedTotal + shippingCost.value
)
// Commande déjà créée pour ce panier (réutilisée si le paiement échoue puis est retenté)
const pendingOrder = ref(null)

// Vérifier si l'utilisateur a des adresses dans son profil
const hasProfileAddresses = computed(() => {
  if (!user.value) return false
  return !!(user.value.adresse || user.value.adresse2 || user.value.adresse3)
})

// Vérifier si l'utilisateur a atteint le maximum d'adresses (3)
const hasMaxAddresses = computed(() => {
  if (!user.value) return false
  const addresses = [user.value.adresse, user.value.adresse2, user.value.adresse3]
  return addresses.filter(addr => addr && addr.trim() !== '').length >= 3
})

// Vérifier si l'utilisateur peut ajouter une nouvelle adresse
const canAddNewAddress = computed(() => {
  return !isLoggedIn.value || !hasMaxAddresses.value
})

const checkoutForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  postalCode: ''
})

const nextStep = async () => {
  if (currentStep.value === 1) {
    // Sauvegarder les informations d'adresse si demandé
    if (saveToProfile.value && isLoggedIn.value) {
      await saveAddressToProfile()
    }
  }
  
  if (currentStep.value < 2) {
    currentStep.value++
  }
}

// Vérifier si le panier est vide
const isCartEmpty = computed(() => cartStore.items.length === 0)

  const createOrder = async () => {
    try {
      // Vérifier que l'utilisateur est connecté
      if (!authStore.user?.id) {
        throw new Error('Vous devez être connecté pour passer une commande')
      }

      // Vérifier que l'ID utilisateur est valide (doit être un nombre)
      const userId = parseInt(authStore.user.id)
      if (isNaN(userId) || userId <= 0) {
        throw new Error('ID utilisateur invalide. Veuillez vous reconnecter.')
      }

      // Vérifier que le panier n'est pas vide
      if (cartStore.items.length === 0) {
        throw new Error('Votre panier est vide')
      }

      const orderData = {
        client_id: userId,
        statut: 'en attente',
        adresse_livraison: `${checkoutForm.address}, ${checkoutForm.postalCode} ${checkoutForm.city}`,
        mode_paiement: selectedPayment.value === 'card' ? 'stripe' : getPaymentMethodKey(selectedPayment.value),
        code_promo: cartStore.promoCode || null,
        details: cartStore.items.map(item => {
          const produitId = parseInt(item.id)
          const quantite = parseInt(item.quantity)
          
          // Validation
          if (isNaN(produitId) || produitId <= 0) {
            throw new Error(`ID de produit invalide: ${item.id}`)
          }
          if (isNaN(quantite) || quantite <= 0) {
            throw new Error(`Quantité invalide: ${item.quantity}`)
          }
          
          return {
            produit_id: produitId,
            quantite: quantite,
            personnalisation: item.customization || null
          }
        })
      }
      const response = await apiFetch('/api/commandes', {
        method: 'POST',
        body: JSON.stringify(orderData)
      })

      // Sécuriser le parse pour éviter l'erreur "Unexpected token '<'"
      const contentType = response.headers.get('content-type')
      if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text()
        throw new Error('Le serveur a retourné une erreur lors de la création de la commande. Vérifiez le backend.')
      }

      const responseData = await response.json()

      if (!response.ok) {
        const errorMessage = responseData.error || 'Erreur lors de la création de la commande'
        throw new Error(errorMessage)
      }
      return responseData
    } catch (error) {
      throw error
    }
  }

  const getPaymentMethodKey = (method) => {
    const methodMap = {
      'Carte bancaire': 'card'
    }
    return methodMap[method] || 'card'
  }

const saveAddressToProfile = async () => {
  try {
    const updateData = {
      adresse: checkoutForm.address,
      code_postal: checkoutForm.postalCode,
      ville: checkoutForm.city,
      telephone: checkoutForm.phone
    }

    const response = await apiFetch(`/api/clients/profile/${authStore.user.id}`, {
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

const loadUserData = () => {
  if (isLoggedIn.value && authStore.user) {
    const user = authStore.user
    checkoutForm.firstName = user.nom?.split(' ')[0] || ''
    checkoutForm.lastName = user.nom?.split(' ').slice(1).join(' ') || ''
    checkoutForm.email = user.email || ''
    checkoutForm.phone = user.telephone || ''
    
    // Si l'utilisateur a des adresses, proposer de les utiliser par défaut
    if (hasProfileAddresses.value) {
      selectedAddressType.value = 'profile'
      // Charger la première adresse disponible par défaut
      if (user.adresse) {
        selectedProfileAddress.value = '1'
        loadSelectedAddress()
      } else if (user.adresse2) {
        selectedProfileAddress.value = '2'
        loadSelectedAddress()
      } else if (user.adresse3) {
        selectedProfileAddress.value = '3'
        loadSelectedAddress()
      }
    } else {
      // Sinon, charger les données de base
      checkoutForm.address = user.adresse || ''
      checkoutForm.city = user.ville || ''
      checkoutForm.postalCode = user.code_postal || ''
    }
    
    // Si l'utilisateur a atteint le maximum d'adresses, forcer la sélection d'une adresse existante
    if (hasMaxAddresses.value) {
      selectedAddressType.value = 'profile'
      // S'assurer qu'une adresse est sélectionnée
      if (!selectedProfileAddress.value) {
        if (user.adresse) {
          selectedProfileAddress.value = '1'
          loadSelectedAddress()
        } else if (user.adresse2) {
          selectedProfileAddress.value = '2'
          loadSelectedAddress()
        } else if (user.adresse3) {
          selectedProfileAddress.value = '3'
          loadSelectedAddress()
        }
      }
    }
  }
}

// Fonctions pour la sélection d'adresse
const selectProfileAddress = () => {
  selectedAddressType.value = 'profile'
}

const selectNewAddress = () => {
  // Empêcher la sélection d'une nouvelle adresse si l'utilisateur a déjà 3 adresses
  if (hasMaxAddresses.value) {
    return
  }
  
  selectedAddressType.value = 'new'
  // Vider le formulaire pour permettre la saisie d'une nouvelle adresse
  checkoutForm.address = ''
  checkoutForm.city = ''
  checkoutForm.postalCode = ''
}

const loadSelectedAddress = () => {
  if (!selectedProfileAddress.value || !user.value) return
  
  const addressNumber = selectedProfileAddress.value
  const userData = user.value
  
  if (addressNumber === '1') {
    checkoutForm.address = userData.adresse || ''
    checkoutForm.city = userData.ville || ''
    checkoutForm.postalCode = userData.code_postal || ''
  } else if (addressNumber === '2') {
    checkoutForm.address = userData.adresse2 || ''
    checkoutForm.city = userData.ville2 || ''
    checkoutForm.postalCode = userData.code_postal2 || ''
  } else if (addressNumber === '3') {
    checkoutForm.address = userData.adresse3 || ''
    checkoutForm.city = userData.ville3 || ''
    checkoutForm.postalCode = userData.code_postal3 || ''
  }
}

// Fonctions Stripe
const initializeStripe = async () => {
  try {
    stripe.value = await loadStripe(stripePublicKey.value)
    if (stripe.value) {
      stripeElements.value = stripe.value.elements()
      stripeReady.value = true
    }
  } catch (error) {
  }
}

const selectStripePayment = async () => {
  selectedPayment.value = 'card'
  
  // Initialiser Stripe si ce n'est pas déjà fait
  if (!stripe.value) {
    await initializeStripe()
  }
  
  // Attendre que le DOM soit mis à jour
  await nextTick()
  
  // Créer l'élément de carte si ce n'est pas déjà fait
  if (stripeElements.value && !cardElement.value) {
    const cardElementContainer = document.getElementById('stripe-card-element')
    if (cardElementContainer && cardElementContainer.children.length === 0) {
      cardElement.value = stripeElements.value.create('card', {
        hidePostalCode: true,
        style: {
          base: {
            fontSize: '16px',
            color: '#ffffff',
            '::placeholder': {
              color: '#aab7c4',
            },
          },
          invalid: {
            color: '#fa755a',
            iconColor: '#fa755a',
          },
        },
      })
      
      cardElement.value.mount('#stripe-card-element')
      
      // Gérer les erreurs
      cardElement.value.on('change', (event) => {
        const displayError = document.getElementById('stripe-card-errors')
        if (event.error) {
          displayError.textContent = event.error.message
        } else {
          displayError.textContent = ''
        }
      })
    }
  }
}

const createPaymentIntent = async (commandeId) => {
  try {
    const response = await apiFetch('/api/stripe/create-payment-intent', {
      method: 'POST',
      body: JSON.stringify({
        commande_id: commandeId
      })
    })
    
    // Vérifier le Content-Type de la réponse
    const contentType = response.headers.get('content-type')
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text()
      throw new Error('Le serveur a retourné une erreur. Vérifiez que Stripe PHP est installé sur le backend.')
    }
    
    const data = await response.json()
    
    if (!response.ok) {
      const errorMessage = data.error || 'Erreur lors de la création du PaymentIntent'
      throw new Error(errorMessage)
    }
    
    return data
  } catch (error) {
    throw error
  }
}

const confirmPayment = async (paymentIntentId, commandeId = null) => {
  try {
    const response = await apiFetch('/api/stripe/confirm-payment', {
      method: 'POST',
      body: JSON.stringify({
        payment_intent_id: paymentIntentId,
        commande_id: commandeId
      })
    })
    
    // Vérifier le Content-Type de la réponse
    const contentType = response.headers.get('content-type')
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text()
      throw new Error('Le serveur a retourné une erreur. Vérifiez que Stripe PHP est installé sur le backend.')
    }
    
    const data = await response.json()
    
    if (!response.ok) {
      const errorMessage = data.error || 'Erreur lors de la confirmation du paiement'
      throw new Error(errorMessage)
    }
    
    return data
  } catch (error) {
    throw error
  }
}

const handlePayment = async () => {
  if (selectedPayment.value === 'card') {
    await handleStripePayment()
  } else {
    showWarning('Veuillez sélectionner une méthode de paiement')
  }
}

const handleStripePayment = async () => {
  if (!stripe.value || !cardElement.value) {
    showError('Stripe n\'est pas initialisé. Veuillez réessayer.')
    return
  }
  
  processingPayment.value = true
  
  try {
    // 1. Créer la commande : le serveur calcule le total (prix BDD, promo, port)
    if (!pendingOrder.value) {
      pendingOrder.value = await createOrder()
    }
    const orderId = pendingOrder.value?.id
    if (!orderId) {
      throw new Error('Erreur lors de la création de la commande')
    }

    // 2. PaymentIntent du montant de la commande (fixé côté serveur)
    const paymentIntentData = await createPaymentIntent(orderId)
    paymentIntentId.value = paymentIntentData.paymentIntentId
    
    // Confirmer le paiement avec la carte
    const { error, paymentIntent } = await stripe.value.confirmCardPayment(
      paymentIntentData.clientSecret,
      {
        payment_method: {
          card: cardElement.value,
          billing_details: {
            name: `${checkoutForm.firstName} ${checkoutForm.lastName}`,
            email: checkoutForm.email,
            phone: checkoutForm.phone,
            address: {
              line1: checkoutForm.address,
              city: checkoutForm.city,
              postal_code: checkoutForm.postalCode,
              country: 'FR',
            },
          },
        },
      }
    )
    
    if (error) {
      // Afficher l'erreur à l'utilisateur
      const displayError = document.getElementById('stripe-card-errors')
      if (displayError) {
        displayError.textContent = error.message
      }
      
      // Message d'erreur spécial pour Stripe non installé
      if (error.message && error.message.includes('Stripe PHP n\'est pas installé')) {
        showWarning('⚠️ Configuration requise\n\nStripe PHP n\'est pas installé sur le serveur.\n\nVeuillez installer la dépendance en exécutant dans le dossier backend:\n\ncomposer require stripe/stripe-php\n\nConsultez le fichier backend/INSTALL_STRIPE.md pour plus d\'informations.')
      } else {
        showError(`Erreur de paiement: ${error.message}`)
      }
      
      processingPayment.value = false
      return
    }
    
    if (paymentIntent.status === 'succeeded') {
      // Confirmer le paiement côté backend avec l'ID de la commande créée
      await confirmPayment(paymentIntent.id, orderId)
      
      // Sauvegarder un snapshot de la commande pour l'écran de confirmation
      currentOrderId.value = orderId
      shippingSnapshot.value = shippingCost.value
      orderItemsSnapshot.value = cartStore.items.map(item => ({ ...item }))
      orderTotalSnapshot.value = parseFloat(pendingOrder.value.total)
      pendingOrder.value = null

      // Vider le panier dans le store
      cartStore.clearCart()
      
      // Passer à l'étape de confirmation
      currentStep.value = 3
    } else {
      throw new Error(`Le paiement n'a pas abouti. Statut: ${paymentIntent.status}`)
    }
  } catch (error) {
    const errorMessage = error.message || 'Une erreur est survenue lors du paiement'
    
    // Message d'erreur spécial pour Stripe non installé
    if (errorMessage.includes('Stripe PHP n\'est pas installé')) {
      showWarning('⚠️ Configuration requise\n\nStripe PHP n\'est pas installé sur le serveur.\n\nVeuillez installer la dépendance en exécutant dans le dossier backend:\n\ncomposer require stripe/stripe-php\n\nConsultez le fichier backend/INSTALL_STRIPE.md pour plus d\'informations.')
    } else {
      showError(`Erreur: ${errorMessage}`)
    }
    
    processingPayment.value = false
  } finally {
    processingPayment.value = false
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2)
}

const getPaymentMethodName = (method) => {
  const methods = {
    card: 'Carte bancaire',
    stripe: 'Carte bancaire'
  }
  return methods[method] || 'Carte bancaire'
}

// Watcher pour initialiser Stripe quand on arrive à l'étape de paiement
// La carte est la seule méthode disponible : on la sélectionne automatiquement
watch(currentStep, async (newStep) => {
  if (newStep === 2) {
    await selectStripePayment()
  }
})

onMounted(() => {
  loadUserData()
})
</script>

<style scoped>
.checkout-page {
  min-height: 100vh;
  padding: 40px 0;
}

.page-title {
  font-size: 36px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 40px;
}

.empty-cart {
  text-align: center;
  padding: 80px 20px;
}

.empty-cart-icon {
  font-size: 80px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.checkout-steps {
  display: flex;
  justify-content: center;
  margin-bottom: 40px;
  gap: 40px;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  opacity: 0.5;
  transition: all 0.3s ease;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--border-color);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: var(--primary-color);
  color: #050505;
}

.step.completed .step-number {
  background: var(--accent-color);
  color: white;
}

.step-title {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.checkout-main {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
}

.checkout-form {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.step-content h2 {
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 30px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 20px;
}

/* Ajustements spécifiques pour code postal et ville */
.postal-code-group {
  min-width: 120px;
}

.city-group {
  flex: 1;
}

/* Responsive pour mobile */
@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  
  .postal-code-group,
  .city-group {
    min-width: auto;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

/* Section de sélection d'adresse */
.address-selection-section {
  margin-bottom: 30px;
}

.max-addresses-info {
  display: flex;
  gap: 15px;
  background: rgba(255, 193, 7, 0.1);
  border: 1px solid rgba(255, 193, 7, 0.3);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.info-icon {
  font-size: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-content {
  flex: 1;
}

.info-content strong {
  color: var(--text-primary);
  font-size: 16px;
  display: block;
  margin-bottom: 8px;
}

.info-content p {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 0;
  line-height: 1.5;
}

.section-subtitle {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.address-options {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.address-option {
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: rgba(20, 20, 20, 0.3);
}

.address-option:hover {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.05);
}

.address-option.active {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
  box-shadow: 0 0 0 2px rgba(208, 255, 89, 0.2);
}

.address-option.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: rgba(20, 20, 20, 0.2);
  border-color: var(--border-color);
}

.address-option.disabled:hover {
  border-color: var(--border-color);
  background: rgba(20, 20, 20, 0.2);
}

.address-option-header {
  display: flex;
  align-items: flex-start;
  gap: 15px;
}

.address-option-header input[type="radio"] {
  margin: 0;
  margin-top: 2px;
}

.address-option-label {
  flex: 1;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.address-option-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.address-option-subtitle {
  font-size: 14px;
  color: var(--text-secondary);
}

.max-addresses-badge {
  font-size: 12px;
  color: #ff6b6b;
  font-weight: 600;
  margin-left: 8px;
}

.disabled-text {
  color: #999;
  font-style: italic;
}

.address-dropdown {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid var(--border-color);
}

.address-select {
  width: 100%;
  padding: 12px 15px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.3s ease;
}

.address-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(208, 255, 89, 0.2);
}

.address-form-section {
  margin-top: 20px;
}

/* Section de sauvegarde d'adresse */
.save-address-section {
  background: rgba(208, 255, 89, 0.05);
  border: 1px solid rgba(208, 255, 89, 0.2);
  border-radius: 12px;
  padding: 20px;
  margin: 20px 0;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  font-weight: 500;
  color: var(--text-primary);
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid var(--primary-color);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  position: relative;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
  background: var(--primary-color);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
  content: '✓';
  color: white;
  font-weight: bold;
  font-size: 14px;
}

.checkbox-description {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 0;
  margin-left: 32px;
  font-style: italic;
}

.form-group label {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group textarea {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 12px 15px;
  color: var(--text-primary);
  font-size: 16px;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 30px;
}

.payment-method {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-method:hover,
.payment-method.active {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
}

.payment-icon {
  font-size: 24px;
}

.payment-info h3 {
  color: var(--text-primary);
  font-size: 16px;
  margin-bottom: 5px;
}

.payment-info p {
  color: var(--text-secondary);
  font-size: 14px;
}

/* Styles Stripe */
.stripe-payment-form {
  margin-top: 30px;
  padding: 20px;
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--border-color);
  border-radius: 12px;
}

.stripe-element {
  padding: 15px;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  margin-bottom: 15px;
}

.stripe-errors {
  color: #fa755a;
  font-size: 14px;
  margin-top: 10px;
  min-height: 20px;
}

.checkout-actions {
  display: flex;
  gap: 15px;
  justify-content: space-between;
}

.confirmation-content {
  text-align: center;
}

.confirmation-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.confirmation-content h3 {
  font-size: 24px;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.confirmation-content p {
  color: var(--text-secondary);
  margin-bottom: 30px;
}

.success-messages {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 30px 0;
  padding: 20px;
  background: rgba(208, 255, 89, 0.05);
  border: 1px solid rgba(208, 255, 89, 0.2);
  border-radius: 12px;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
  color: var(--text-primary);
  font-weight: 500;
}

.success-icon {
  font-size: 18px;
  width: 24px;
  text-align: center;
}

.empty-cart-text {
  color: var(--accent-color);
  font-style: italic;
  font-weight: 500;
}

.order-summary {
  background: rgba(20, 20, 20, 0.5);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 30px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.summary-item:last-child {
  margin-bottom: 0;
}

.confirmation-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.checkout-summary {
  position: sticky;
  top: 100px;
}

.summary-card {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.summary-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.order-items {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.order-item {
  display: flex;
  gap: 15px;
  align-items: center;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
}

.item-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--dark-secondary), var(--dark-tertiary));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  opacity: 0.5;
}

.item-details {
  flex: 1;
}

.item-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.item-category {
  font-size: 12px;
  color: var(--primary-color);
  margin-bottom: 5px;
}

.item-quantity {
  font-size: 12px;
  color: var(--text-secondary);
}

.item-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--primary-color);
}

.summary-totals {
  border-top: 1px solid var(--border-color);
  padding-top: 20px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  color: var(--text-secondary);
}

.summary-line.total {
  color: var(--text-primary);
  font-weight: bold;
  font-size: 18px;
}

.summary-line .free {
  color: var(--accent-color);
}

.summary-divider {
  height: 1px;
  background: var(--border-color);
  margin: 15px 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .checkout-main {
    grid-template-columns: 1fr;
  }
  
  .checkout-summary {
    position: static;
  }
}

@media (max-width: 768px) {
  .checkout-steps {
    gap: 20px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .checkout-actions,
  .confirmation-actions {
    flex-direction: column;
  }
  
  .page-title {
    font-size: 28px;
  }
  
  .address-options {
    gap: 10px;
  }
  
  .address-option {
    padding: 15px;
  }
  
  .address-option-header {
    gap: 10px;
  }
  
  .address-option-title {
    font-size: 14px;
  }
  
  .address-option-subtitle {
    font-size: 12px;
  }
}
</style>
