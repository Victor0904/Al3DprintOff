<template>
  <div class="modal-overlay">
    <div class="modal" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Connexion</h2>
        <button class="close-btn" @click="closeModal">✕</button>
      </div>
      
      <form @submit.prevent="handleLogin" class="modal-form">
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            class="form-input"
            required
            placeholder="votre@email.com"
          >
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">Mot de passe</label>
          <input 
            type="password" 
            id="password" 
            v-model="form.password" 
            class="form-input"
            required
            placeholder="Votre mot de passe"
          >
        </div>
        
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" v-model="form.remember">
            <span class="checkmark"></span>
            Se souvenir de moi
          </label>
          <router-link to="/forgot-password" class="forgot-link" @click="closeModal">Mot de passe oublié ?</router-link>
        </div>
        
        <!-- Notification panier -->
        <div v-if="hasGuestCart" class="cart-notification">
          <div class="notification-icon">🛒</div>
          <div class="notification-text">
            <strong>Panier sauvegardé !</strong><br>
            Vos {{ guestCartItems }} articles seront ajoutés à votre compte.
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary modal-btn" :disabled="loading">
          <span v-if="loading">Connexion...</span>
          <span v-else>Se connecter</span>
        </button>
        
        <div class="modal-footer">
          <p>Pas encore de compte ? 
            <button type="button" class="link-btn" @click="switchToRegister">
              S'inscrire
            </button>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { useAlert } from '../composables/useAlert'

const emit = defineEmits(['close', 'login', 'switchToRegister'])
const authStore = useAuthStore()
const cartStore = useCartStore()
const { error: showError } = useAlert()

const loading = ref(false)

// Computed pour vérifier le panier d'invité
const hasGuestCart = computed(() => {
  return cartStore.isGuestCart && cartStore.totalItems > 0
})

const guestCartItems = computed(() => cartStore.totalItems)
const form = reactive({
  email: '',
  password: '',
  remember: false
})

const closeModal = () => {
  emit('close')
}

const switchToRegister = () => {
  emit('switchToRegister')
}

// Gestion de la touche Échap
const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

const handleLogin = async () => {
  loading.value = true
  
  try {
    console.log('LoginModal - Tentative de connexion avec:', form)
    const result = await authStore.login(form)
    
    if (result.success) {
      emit('login', authStore.user)
      // Réinitialiser le formulaire après connexion réussie
      form.email = ''
      form.password = ''
      form.remember = false
      closeModal()
    } else {
      showError('Erreur de connexion: ' + result.error)
    }
  } catch (error) {
    showError('Erreur de connexion: ' + error.message)
  } finally {
    loading.value = false
  }
}

// Ajouter et supprimer l'écouteur d'événements
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  box-sizing: border-box;
}

.modal {
  background: linear-gradient(135deg, var(--dark-secondary) 0%, var(--dark-tertiary) 100%);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 30px;
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: modalSlideIn 0.3s ease-out;
  overflow-y: auto;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.modal-title {
  font-size: 24px;
  font-weight: bold;
  color: var(--primary-color);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 20px;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: rgba(255, 107, 107, 0.1);
  color: var(--secondary-color);
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.form-input {
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 12px 15px;
  color: var(--text-primary);
  font-size: 16px;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.form-input::placeholder {
  color: var(--text-secondary);
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 10px 0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-secondary);
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

.forgot-link {
  color: var(--primary-color);
  text-decoration: none;
  font-size: 14px;
  transition: all 0.3s ease;
}

.forgot-link:hover {
  color: var(--accent-color);
}

/* Notification panier */
.cart-notification {
  background: linear-gradient(135deg, rgba(208, 255, 89, 0.1), rgba(204, 240, 212, 0.1));
  border: 1px solid var(--primary-color);
  border-radius: 8px;
  padding: 12px;
  margin: 15px 0;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.notification-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.notification-text {
  color: var(--text-primary);
  font-size: 14px;
  line-height: 1.4;
}

.notification-text strong {
  color: var(--primary-color);
}

.modal-btn {
  width: 100%;
  padding: 15px;
  font-size: 16px;
  font-weight: 600;
  margin-top: 10px;
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-footer {
  text-align: center;
  margin-top: 20px;
}

.modal-footer p {
  color: var(--text-secondary);
  font-size: 14px;
}

.link-btn {
  background: none;
  border: none;
  color: var(--primary-color);
  text-decoration: underline;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.3s ease;
}

.link-btn:hover {
  color: var(--accent-color);
}

@media (max-width: 480px) {
  .modal {
    padding: 20px;
    margin: 20px;
  }
  
  .modal-title {
    font-size: 20px;
  }
}
</style>
