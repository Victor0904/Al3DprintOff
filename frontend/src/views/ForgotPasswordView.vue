<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Mot de passe oublié</h1>
          <p class="auth-subtitle">Entrez votre email pour recevoir un lien de réinitialisation</p>
        </div>

        <div v-if="!emailSent" class="auth-form">
          <form @submit.prevent="handleForgotPassword">
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input 
                type="email" 
                id="email" 
                v-model="form.email" 
                class="form-input"
                placeholder="votre@email.com"
                required
                :disabled="loading"
              >
            </div>

            <button 
              type="submit" 
              class="btn btn-primary auth-btn"
              :disabled="loading"
            >
              <span v-if="loading">Envoi en cours...</span>
              <span v-else>Envoyer le lien de réinitialisation</span>
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
          <h2>Email envoyé !</h2>
          <p>
            Si cet email existe dans notre base de données, vous recevrez un email 
            contenant un lien pour réinitialiser votre mot de passe.
          </p>
          <p class="email-info">
            Vérifiez votre boîte de réception (et vos spams) à l'adresse :<br>
            <strong>{{ form.email }}</strong>
          </p>
          <router-link to="/login" class="btn btn-primary">
            Retour à la connexion
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAlert } from '../composables/useAlert'

const router = useRouter()
const { success: showSuccess, error: showError } = useAlert()

const loading = ref(false)
const emailSent = ref(false)
const form = reactive({
  email: ''
})

const handleForgotPassword = async () => {
  loading.value = true
  
  try {
    const response = await fetch('/api/clients/forgot-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: form.email
      })
    })

    const data = await response.json()

    if (response.ok) {
      emailSent.value = true
      showSuccess(data.message || 'Email envoyé avec succès')
    } else {
      showError(data.error || 'Erreur lors de l\'envoi de l\'email')
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

.auth-btn {
  width: 100%;
  padding: 14px;
  margin-top: 10px;
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
  margin-bottom: 15px;
}

.email-info {
  background: rgba(208, 255, 89, 0.1);
  padding: 15px;
  border-radius: 8px;
  margin: 20px 0;
}

.email-info strong {
  color: var(--primary-color);
}
</style>

