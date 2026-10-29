<template>
  <div class="modal-overlay">
    <div class="modal" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Inscription</h2>
        <button class="close-btn" @click="closeModal">✕</button>
      </div>
      
      <form @submit.prevent="handleRegister" class="modal-form">
        <div class="form-group">
          <label for="name" class="form-label">Nom complet *</label>
          <input 
            type="text" 
            id="name" 
            v-model="form.name" 
            class="form-input"
            :class="{ 'error': errors.name }"
            required
            placeholder="Votre nom complet"
          >
          <div v-if="errors.name" class="error-message">{{ errors.name }}</div>
        </div>
        
        <div class="form-group">
          <label for="email" class="form-label">Email *</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            class="form-input"
            :class="{ 'error': errors.email }"
            required
            placeholder="votre@email.com"
          >
          <div v-if="errors.email" class="error-message">{{ errors.email }}</div>
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">Mot de passe *</label>
          <input 
            type="password" 
            id="password" 
            v-model="form.password" 
            class="form-input"
            :class="{ 'error': errors.password }"
            required
            placeholder="Mot de passe sécurisé"
            minlength="8"
          >
          <div v-if="errors.password" class="error-message">{{ errors.password }}</div>
          
          <!-- Indicateur de force du mot de passe -->
          <div v-if="form.password" class="password-strength">
            <div class="strength-label">Force du mot de passe : {{ passwordStrength.label }}</div>
            <div class="strength-bar">
              <div 
                class="strength-fill" 
                :style="{ 
                  width: `${passwordStrength.percent}%`,
                  backgroundColor: passwordStrength.color 
                }"
              ></div>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="confirmPassword" class="form-label">Confirmer le mot de passe *</label>
          <input 
            type="password" 
            id="confirmPassword" 
            v-model="form.confirmPassword" 
            class="form-input"
            :class="{ 'error': errors.confirmPassword }"
            required
            placeholder="Confirmez votre mot de passe"
          >
          <div v-if="errors.confirmPassword" class="error-message">{{ errors.confirmPassword }}</div>
        </div>

        <div class="form-group">
          <span class="form-label">Type de compte *</span>
          <div class="client-type-options">
            <label class="client-type-option">
              <input type="radio" v-model="form.clientType" value="particulier" name="clientType">
              <span class="option-label">Particulier</span>
              <span class="option-hint">Prix TTC affichés</span>
            </label>
            <label class="client-type-option">
              <input type="radio" v-model="form.clientType" value="entreprise" name="clientType">
              <span class="option-label">Entreprise</span>
              <span class="option-hint">TVA 17 % sur facture</span>
            </label>
          </div>
        </div>
        
        <!-- Section adresse (optionnelle) -->
        <div class="address-section">
          <h4 class="section-subtitle">📍 Informations d'adresse (optionnel)</h4>
          <p class="section-description">Ces informations faciliteront vos futures commandes</p>
          
          <div class="form-group">
            <label for="address" class="form-label">Adresse</label>
            <textarea 
              id="address" 
              v-model="form.address" 
              class="form-input"
              placeholder="123 rue de la Paix"
              rows="3"
            ></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group postal-code-group">
              <label for="postalCode" class="form-label">Code postal</label>
              <input 
                type="text" 
                id="postalCode" 
                v-model="form.postalCode" 
                class="form-input"
                placeholder="75001"
                maxlength="10"
              >
            </div>
            
            <div class="form-group city-group">
              <label for="city" class="form-label">Ville</label>
              <input 
                type="text" 
                id="city" 
                v-model="form.city" 
                class="form-input"
                placeholder="Paris"
              >
            </div>
          </div>
          
          <div class="form-group">
            <label for="phone" class="form-label">Téléphone</label>
            <input 
              type="tel" 
              id="phone" 
              v-model="form.phone" 
              class="form-input"
              placeholder="+33 1 23 45 67 89"
            >
          </div>
        </div>
        
        <div class="form-group">
          <label class="checkbox-label" :class="{ 'error': errors.terms }">
            <input type="checkbox" v-model="form.terms" required>
            <span class="checkmark"></span>
            J'accepte les <a href="#" class="terms-link">conditions d'utilisation</a> *
          </label>
          <div v-if="errors.terms" class="error-message">{{ errors.terms }}</div>
        </div>
        
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" v-model="form.newsletter">
            <span class="checkmark"></span>
            S'abonner à la newsletter
          </label>
        </div>
        
        <button type="submit" class="btn btn-primary modal-btn" :disabled="loading || !isFormValid">
          <span v-if="loading">Inscription...</span>
          <span v-else>S'inscrire</span>
        </button>
        
        <div class="modal-footer">
          <p>Déjà un compte ? 
            <button type="button" class="link-btn" @click="switchToLogin">
              Se connecter
            </button>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'

const emit = defineEmits(['close', 'register', 'switchToLogin'])
const authStore = useAuthStore()
const { error: showError, warning: showWarning } = useAlert()

const loading = ref(false)
const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  clientType: 'particulier',
  phone: '',
  address: '',
  postalCode: '',
  city: '',
  terms: false,
  newsletter: false
})

// État des erreurs de validation
const errors = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  terms: ''
})

// Validation en temps réel
const validateName = (name) => {
  if (!name.trim()) {
    return 'Le nom est requis'
  }
  if (name.trim().length < 2) {
    return 'Le nom doit contenir au moins 2 caractères'
  }
  if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(name.trim())) {
    return 'Le nom ne peut contenir que des lettres et espaces'
  }
  return ''
}

const validateEmail = (email) => {
  if (!email.trim()) {
    return 'L\'email est requis'
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.trim())) {
    return 'Format d\'email invalide'
  }
  return ''
}

const validatePassword = (password) => {
  if (!password) {
    return 'Le mot de passe est requis'
  }
  if (password.length < 8) {
    return 'Le mot de passe doit contenir au moins 8 caractères'
  }
  if (!/(?=.*[a-z])/.test(password)) {
    return 'Le mot de passe doit contenir au moins une minuscule'
  }
  if (!/(?=.*[A-Z])/.test(password)) {
    return 'Le mot de passe doit contenir au moins une majuscule'
  }
  if (!/(?=.*\d)/.test(password)) {
    return 'Le mot de passe doit contenir au moins un chiffre'
  }
  return ''
}

const validateConfirmPassword = (confirmPassword, password) => {
  if (!confirmPassword) {
    return 'La confirmation du mot de passe est requise'
  }
  if (confirmPassword !== password) {
    return 'Les mots de passe ne correspondent pas'
  }
  return ''
}

const validateTerms = (terms) => {
  if (!terms) {
    return 'Vous devez accepter les conditions d\'utilisation'
  }
  return ''
}

// Watchers pour la validation en temps réel
watch(() => form.name, (newName) => {
  errors.name = validateName(newName)
})

watch(() => form.email, (newEmail) => {
  errors.email = validateEmail(newEmail)
})

watch(() => form.password, (newPassword) => {
  errors.password = validatePassword(newPassword)
  // Re-valider la confirmation si le mot de passe change
  if (form.confirmPassword) {
    errors.confirmPassword = validateConfirmPassword(form.confirmPassword, newPassword)
  }
})

watch(() => form.confirmPassword, (newConfirmPassword) => {
  errors.confirmPassword = validateConfirmPassword(newConfirmPassword, form.password)
})

watch(() => form.terms, (newTerms) => {
  errors.terms = validateTerms(newTerms)
})

// Validation globale du formulaire
const isFormValid = computed(() => {
  return form.name && 
         form.email && 
         form.password && 
         form.password === form.confirmPassword && 
         form.terms &&
         !errors.name &&
         !errors.email &&
         !errors.password &&
         !errors.confirmPassword &&
         !errors.terms
})

// Force de validation du mot de passe
const passwordStrength = computed(() => {
  const pwd = form.password
  if (!pwd) return { score: 0, label: '', color: '', percent: 0 }

  const hasLower = /[a-z]/.test(pwd)
  const hasUpper = /[A-Z]/.test(pwd)
  const hasDigit = /\d/.test(pwd)
  const hasSpecial = /[!@#$%^&*()\-_=+[\]{};:'",.<>/?\\|`~]/.test(pwd)
  const len = pwd.length
  const typesCount = [hasLower, hasUpper, hasDigit, hasSpecial].filter(Boolean).length

  if (len < 8) {
    return { score: 1, label: 'Très faible', color: '#ff6b6b', percent: 20 }
  }
  if (len >= 12 && typesCount === 4) {
    return { score: 5, label: 'Très fort', color: '#4caf50', percent: 100 }
  }
  if ((len >= 10 && typesCount >= 3) || (len >= 12 && typesCount >= 2)) {
    return { score: 4, label: 'Fort', color: '#66bb6a', percent: 80 }
  }
  if ((len >= 8 && typesCount >= 3) || (len >= 10 && typesCount >= 2)) {
    return { score: 3, label: 'Moyen', color: '#ffeb3b', percent: 60 }
  }
  if (len >= 8 && typesCount >= 2) {
    return { score: 2, label: 'Faible', color: '#ffa726', percent: 40 }
  }
  return { score: 1, label: 'Très faible', color: '#ff6b6b', percent: 20 }
})

const closeModal = () => {
  emit('close')
}

const switchToLogin = () => {
  emit('switchToLogin')
}

// Gestion de la touche Échap
const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

const handleRegister = async () => {
  // Validation finale avant soumission
  errors.name = validateName(form.name)
  errors.email = validateEmail(form.email)
  errors.password = validatePassword(form.password)
  errors.confirmPassword = validateConfirmPassword(form.confirmPassword, form.password)
  errors.terms = validateTerms(form.terms)
  
  if (!isFormValid.value) {
    showWarning('Veuillez corriger les erreurs avant de continuer.')
    return
  }
  
  loading.value = true
  
  try {
    console.log('RegisterModal - Tentative d\'inscription avec:', form)
    const result = await authStore.register(form)
    
    if (result.success) {
      emit('register', authStore.user)
      // Réinitialiser le formulaire après inscription réussie
      Object.keys(form).forEach(key => {
        if (key === 'clientType') {
          form[key] = 'particulier'
        } else if (typeof form[key] === 'boolean') {
          form[key] = false
        } else {
          form[key] = ''
        }
      })
      Object.keys(errors).forEach(key => {
        errors[key] = ''
      })
      closeModal()
    } else {
      showError('Erreur d\'inscription: ' + result.error)
    }
  } catch (error) {
    showError('Erreur d\'inscription: ' + error.message)
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
  max-width: 450px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 15px;
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

/* Section adresse optionnelle */
.address-section {
  background: rgba(0, 212, 255, 0.05);
  border: 1px solid rgba(0, 212, 255, 0.2);
  border-radius: 12px;
  padding: 20px;
  margin: 20px 0;
}

.section-subtitle {
  color: var(--primary-color);
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-description {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 20px;
  font-style: italic;
}

.form-label {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.client-type-options {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.client-type-option {
  flex: 1;
  min-width: 140px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  padding: 12px 16px;
  background: rgba(26, 26, 46, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.client-type-option:has(input:checked) {
  border-color: var(--primary-color);
  box-shadow: 0 0 12px var(--glow-color);
}

.client-type-option input {
  margin-right: 8px;
}

.option-label {
  font-weight: 600;
  color: var(--text-primary);
}

.option-hint {
  font-size: 12px;
  color: var(--text-secondary);
}

.form-input,
.form-textarea {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 12px 15px;
  color: var(--text-primary);
  font-size: 16px;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.form-input.error,
.form-textarea.error {
  border-color: var(--secondary-color);
  box-shadow: 0 0 10px rgba(255, 107, 107, 0.3);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: var(--text-secondary);
}

/* Messages d'erreur */
.error-message {
  color: var(--secondary-color);
  font-size: 12px;
  margin-top: 5px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.error-message::before {
  content: '⚠️';
  font-size: 10px;
}

/* Indicateur de force du mot de passe */
.password-strength {
  margin-top: 10px;
}

.strength-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 5px;
}

.strength-bar {
  width: 100%;
  height: 4px;
  background: rgba(26, 26, 46, 0.8);
  border-radius: 2px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s ease;
  border-radius: 2px;
}

/* Checkbox avec erreur */
.checkbox-label.error {
  color: var(--secondary-color);
}

.checkbox-label.error .checkmark {
  border-color: var(--secondary-color);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: var(--text-secondary);
  font-size: 14px;
  cursor: pointer;
  line-height: 1.4;
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
  flex-shrink: 0;
  margin-top: 2px;
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

.terms-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: all 0.3s ease;
}

.terms-link:hover {
  color: var(--accent-color);
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
  
  .modal-overlay {
    padding: 10px 0;
  }
}
</style>
