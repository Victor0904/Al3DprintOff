<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Réinitialiser le mot de passe</h1>
          <p class="auth-subtitle">Entrez votre nouveau mot de passe</p>
        </div>

        <div v-if="!success" class="auth-form">
          <form @submit.prevent="handleResetPassword">
            <div class="form-group">
              <label for="password" class="form-label">Nouveau mot de passe</label>
              <input 
                type="password" 
                id="password" 
                v-model="form.password" 
                class="form-input"
                placeholder="Minimum 8 caractères"
                required
                :disabled="loading"
                minlength="8"
              >
              <small class="form-hint">Le mot de passe doit contenir au moins 8 caractères</small>
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
                :disabled="loading"
                minlength="8"
              >
              <small v-if="form.confirmPassword && form.password !== form.confirmPassword" class="form-error">
                Les mots de passe ne correspondent pas
              </small>
            </div>

            <button 
              type="submit" 
              class="btn btn-primary auth-btn"
              :disabled="loading || form.password !== form.confirmPassword || form.password.length < 8"
            >
              <span v-if="loading">Réinitialisation...</span>
              <span v-else>Réinitialiser le mot de passe</span>
            </button>
          </form>

          <div class="auth-footer">
            <p>
              <router-link to="/login" class="auth-link">
                ← Retour à la connexion
              </router-link>
            </p>
          </div>
        </div>

        <div v-else class="success-message">
          <div class="success-icon">✅</div>
          <h2>Mot de passe réinitialisé !</h2>
          <p>Votre mot de passe a été modifié avec succès.</p>
          <router-link to="/login" class="btn btn-primary">
            Se connecter
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlert } from '../composables/useAlert'

const route = useRoute()
const router = useRouter()
const { success: showSuccess, error: showError } = useAlert()

const loading = ref(false)
const success = ref(false)
const form = reactive({
  password: '',
  confirmPassword: ''
})

const token = ref(null)

onMounted(() => {
  // Récupérer le token depuis l'URL
  token.value = route.query.token
  
  if (!token.value) {
    showError('Token de réinitialisation manquant')
    router.push('/forgot-password')
  }
})

const handleResetPassword = async () => {
  if (form.password !== form.confirmPassword) {
    showError('Les mots de passe ne correspondent pas')
    return
  }

  if (form.password.length < 8) {
    showError('Le mot de passe doit contenir au moins 8 caractères')
    return
  }

  loading.value = true
  
  try {
    const response = await fetch('/api/clients/reset-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        token: token.value,
        new_password: form.password
      })
    })

    const data = await response.json()

    if (response.ok) {
      success.value = true
      showSuccess(data.message || 'Mot de passe réinitialisé avec succès')
    } else {
      showError(data.error || 'Erreur lors de la réinitialisation')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showError('Erreur de connexion. Veuillez réessayer plus tard.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 100%);
}

.auth-container {
  width: 100%;
  max-width: 500px;
}

.auth-card {
  background: rgba(20, 20, 20, 0.9);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--border-color);
}

.auth-header {
  text-align: center;
  margin-bottom: 30px;
}

.auth-title {
  color: var(--primary-color);
  font-size: 28px;
  margin-bottom: 10px;
}

.auth-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  color: var(--text-primary);
  margin-bottom: 8px;
  font-weight: 500;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: rgba(20, 20, 20, 0.8);
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(208, 255, 89, 0.1);
}

.form-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-hint {
  display: block;
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 5px;
}

.form-error {
  display: block;
  color: #ff4444;
  font-size: 12px;
  margin-top: 5px;
}

.auth-btn {
  width: 100%;
  padding: 14px;
  margin-top: 10px;
}

.auth-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auth-footer {
  margin-top: 20px;
  text-align: center;
}

.auth-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: all 0.3s ease;
}

.auth-link:hover {
  color: var(--accent-color);
  text-decoration: underline;
}

.success-message {
  text-align: center;
  padding: 20px 0;
}

.success-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.success-message h2 {
  color: var(--primary-color);
  margin-bottom: 15px;
}

.success-message p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 20px;
}
</style>

