<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Connexion</h1>
          <p class="auth-subtitle">Connectez-vous à votre compte</p>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
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
              placeholder="Votre mot de passe"
              required
            >
          </div>

          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.remember">
              <span class="checkmark"></span>
              Se souvenir de moi
            </label>
            <router-link to="/forgot-password" class="forgot-link">Mot de passe oublié ?</router-link>
          </div>

          <button 
            type="submit" 
            class="btn btn-primary auth-btn"
            :disabled="loading"
          >
            <span v-if="loading">Connexion...</span>
            <span v-else>Se connecter</span>
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
          <p>Pas encore de compte ? 
            <router-link to="/register" class="auth-link">
              Créer un compte
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
          </div>
          <h2 class="visual-title">Bienvenue chez Al3DPrint</h2>
          <p class="visual-description">
            Accédez à votre espace personnel et découvrez des produits 
            personnalisés selon vos envies.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAlert } from '../composables/useAlert'

const router = useRouter()
const authStore = useAuthStore()
const { error: showError } = useAlert()

const loading = ref(false)
const form = reactive({
  email: '',
  password: '',
  remember: false
})

const handleLogin = async () => {
  loading.value = true
  
  try {
    const result = await authStore.login(form)
    if (result.success) {
      // Rediriger vers la page d'origine ou l'accueil
      const returnTo = router.currentRoute.value.query.returnTo || '/'
      router.push(returnTo)
    } else {
      showError('Erreur de connexion: ' + result.error)
    }
  } catch (error) {
    showError('Erreur de connexion: ' + error.message)
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
  background: rgba(26, 26, 46, 0.8);
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
}

.auth-header {
  text-align: center;
  margin-bottom: 40px;
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
  background: rgba(26, 26, 46, 0.8);
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

.auth-btn {
  width: 100%;
  padding: 15px;
  font-size: 16px;
  font-weight: 600;
  margin-top: 10px;
}

.auth-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.auth-divider {
  text-align: center;
  margin: 20px 0;
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
  background: rgba(26, 26, 46, 0.8);
  color: var(--text-secondary);
  padding: 0 20px;
  font-size: 14px;
}

.social-login {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  background: rgba(26, 26, 46, 0.8);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.social-btn:hover {
  border-color: var(--primary-color);
  background: rgba(0, 212, 255, 0.1);
}

.social-icon {
  font-size: 16px;
}

.auth-footer {
  text-align: center;
  margin-top: 30px;
}

.auth-footer p {
  color: var(--text-secondary);
  font-size: 14px;
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
  animation: float-shapes 6s ease-in-out infinite;
}

.shape-1 {
  width: 100px;
  height: 100px;
  top: 20%;
  left: 20%;
  animation-delay: 0s;
}

.shape-2 {
  width: 150px;
  height: 150px;
  top: 60%;
  right: 20%;
  animation-delay: 2s;
}

.shape-3 {
  width: 80px;
  height: 80px;
  bottom: 20%;
  left: 50%;
  animation-delay: 4s;
}

@keyframes float-shapes {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-30px) rotate(180deg); }
}

.visual-title {
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 20px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.visual-description {
  font-size: 16px;
  line-height: 1.6;
  color: var(--text-secondary);
  max-width: 300px;
  margin: 0 auto;
}

/* Responsive */
@media (max-width: 768px) {
  .auth-container {
    grid-template-columns: 1fr;
    max-width: 400px;
  }
  
  .auth-card {
    padding: 40px 30px;
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
