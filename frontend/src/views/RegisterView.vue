<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Inscription</h1>
          <p class="auth-subtitle">Créez votre compte Al3DPrint</p>
        </div>

        <form @submit.prevent="handleRegister" class="auth-form">
          <div class="form-group">
            <label for="name" class="form-label">Nom complet</label>
            <input 
              type="text" 
              id="name" 
              v-model="form.name" 
              class="form-input"
              placeholder="Votre nom complet"
              required
            >
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input 
              type="email" 
              id="email" 
              v-model="form.email" 
              class="form-input"
              placeholder="votre@email.com"
              required
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Mot de passe</label>
            <input 
              type="password" 
              id="password" 
              v-model="form.password" 
              class="form-input"
              placeholder="Mot de passe sécurisé"
              minlength="6"
              required
            >
          </div>

          <div class="form-group">
            <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
            <input 
              type="password" 
              id="confirmPassword" 
              v-model="form.confirmPassword" 
              class="form-input"
              placeholder="Confirmez votre mot de passe"
              required
            >
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

          <div class="form-group">
            <label for="phone" class="form-label">Téléphone (optionnel)</label>
            <input 
              type="tel" 
              id="phone" 
              v-model="form.phone" 
              class="form-input"
              placeholder="0123456789"
            >
          </div>

          <div class="form-group">
            <label for="address" class="form-label">Adresse (optionnel)</label>
            <textarea 
              id="address" 
              v-model="form.address" 
              class="form-textarea"
              placeholder="Votre adresse complète"
              rows="3"
            ></textarea>
          </div>

          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.terms" required>
              <span class="checkmark"></span>
              J'accepte les <a href="#" class="terms-link">conditions d'utilisation</a>
            </label>
          </div>

          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.newsletter">
              <span class="checkmark"></span>
              S'abonner à la newsletter
            </label>
          </div>

          <button 
            type="submit" 
            class="btn btn-primary auth-btn"
            :disabled="loading || !isFormValid"
          >
            <span v-if="loading">Inscription...</span>
            <span v-else>Créer mon compte</span>
          </button>

          <div class="auth-divider">
            <span>ou</span>
          </div>

          <div class="social-login">
            <button type="button" class="social-btn google-btn">
              <span class="social-icon">🔍</span>
              Continuer avec Google
            </button>
            <button type="button" class="social-btn facebook-btn">
              <span class="social-icon">📘</span>
              Continuer avec Facebook
            </button>
          </div>
        </form>

        <div class="auth-footer">
          <p>Déjà un compte ? 
            <router-link to="/login" class="auth-link">
              Se connecter
            </router-link>
          </p>
        </div>
      </div>

      <div class="auth-visual">
        <div class="visual-content">
          <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
          </div>
          <h2 class="visual-title">Rejoignez-nous</h2>
          <p class="visual-description">
            Créez votre compte et accédez à des produits personnalisés, 
            des offres exclusives et une expérience d'achat unique.
          </p>
          <div class="benefits">
            <div class="benefit-item">
              <span class="benefit-icon">🎁</span>
              <span>Offres exclusives</span>
            </div>
            <div class="benefit-item">
              <span class="benefit-icon">🚚</span>
              <span>Livraison gratuite</span>
            </div>
            <div class="benefit-item">
              <span class="benefit-icon">🎨</span>
              <span>Personnalisation</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'

const router = useRouter()
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
  terms: false,
  newsletter: false
})

const isFormValid = computed(() => {
  return form.name && 
         form.email && 
         form.password && 
         form.password === form.confirmPassword && 
         form.terms
})

const handleRegister = async () => {
  if (!isFormValid.value) {
    showWarning('Veuillez remplir tous les champs obligatoires et accepter les conditions.')
    return
  }
  
  loading.value = true
  
  try {
    const result = await authStore.register(form)
    if (result.success) {
      // Rediriger vers la page d'origine ou l'accueil
      const returnTo = router.currentRoute.value.query.returnTo || '/'
      router.push(returnTo)
    } else {
      showError('Erreur d\'inscription: ' + result.error)
    }
  } catch (error) {
    showError('Erreur d\'inscription: ' + error.message)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Si l'utilisateur est déjà connecté, rediriger
  if (authStore.isAuthenticated) {
    router.push('/')
  }
})
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.auth-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  max-width: 1000px;
  width: 100%;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.auth-card {
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  max-height: 100vh;
  overflow-y: auto;
}

.auth-header {
  text-align: center;
  margin-bottom: 30px;
}

.auth-title {
  font-size: 32px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.auth-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
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
  background: rgba(20, 20, 20, 0.6);
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
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 12px;
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 8px var(--glow-color);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: var(--text-secondary);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-options {
  margin: 5px 0;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: var(--text-secondary);
  font-size: 13px;
  cursor: pointer;
  line-height: 1.4;
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkmark {
  width: 16px;
  height: 16px;
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
  font-size: 10px;
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

.auth-btn {
  width: 100%;
  padding: 12px;
  font-size: 14px;
  font-weight: 600;
  margin-top: 10px;
}

.auth-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.auth-divider {
  text-align: center;
  margin: 15px 0;
  position: relative;
}

.auth-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--border-color);
}

.auth-divider span {
  background: rgba(20, 20, 20, 0.8);
  color: var(--text-secondary);
  padding: 0 15px;
  font-size: 12px;
}

.social-login {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: rgba(20, 20, 20, 0.8);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
}

.social-btn:hover {
  border-color: var(--primary-color);
  background: rgba(208, 255, 89, 0.1);
}

.social-icon {
  font-size: 14px;
}

.auth-footer {
  text-align: center;
  margin-top: 20px;
}

.auth-footer p {
  color: var(--text-secondary);
  font-size: 13px;
}

.auth-link {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.auth-link:hover {
  color: var(--accent-color);
}

.auth-visual {
  background: linear-gradient(135deg, var(--dark-secondary), var(--dark-tertiary));
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.visual-content {
  text-align: center;
  color: var(--text-primary);
  z-index: 2;
  position: relative;
}

.floating-shapes {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.shape {
  position: absolute;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  border-radius: 50%;
  opacity: 0.1;
  animation: float-shapes 8s ease-in-out infinite;
}

.shape-1 {
  width: 80px;
  height: 80px;
  top: 15%;
  left: 15%;
  animation-delay: 0s;
}

.shape-2 {
  width: 120px;
  height: 120px;
  top: 50%;
  right: 15%;
  animation-delay: 2s;
}

.shape-3 {
  width: 100px;
  height: 100px;
  bottom: 20%;
  left: 30%;
  animation-delay: 4s;
}

.shape-4 {
  width: 60px;
  height: 60px;
  top: 30%;
  right: 40%;
  animation-delay: 6s;
}

@keyframes float-shapes {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.visual-title {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 15px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.visual-description {
  font-size: 14px;
  line-height: 1.6;
  color: var(--text-secondary);
  max-width: 280px;
  margin: 0 auto 20px;
}

.benefits {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: 13px;
}

.benefit-icon {
  font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
  .auth-container {
    grid-template-columns: 1fr;
    max-width: 400px;
  }
  
  .auth-card {
    padding: 40px 30px;
    max-height: none;
  }
  
  .auth-visual {
    display: none;
  }
  
  .auth-title {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .auth-page {
    padding: 10px;
  }
  
  .auth-card {
    padding: 30px 20px;
  }
}
</style>
