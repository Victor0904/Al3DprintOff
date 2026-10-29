<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <!-- Section principale -->
        <div class="footer-main">
          <div class="footer-section">
            <h3 class="footer-title">Al3DPrint</h3>
            <p class="footer-description">
              L'avenir de l'impression 3D à votre portée. Découvrez nos produits innovants 
              et personnalisez vos créations selon vos envies.
            </p>
          </div>

          <div class="footer-section">
            <h4 class="footer-subtitle">Navigation</h4>
            <ul class="footer-links">
              <li><router-link to="/" class="footer-link">Accueil</router-link></li>
              <li><router-link to="/products" class="footer-link">Produits</router-link></li>
              <li><router-link to="/custom-products" class="footer-link">Personnalisés</router-link></li>
              <li><router-link to="/about" class="footer-link">À propos</router-link></li>
            </ul>
          </div>

          <div class="footer-section">
            <h4 class="footer-subtitle">Support</h4>
            <ul class="footer-links">
              <li><router-link to="/about" class="footer-link">Centre d'aide</router-link></li>
              <li><router-link to="/about" class="footer-link">Contact</router-link></li>
              <li><router-link to="/about" class="footer-link">FAQ</router-link></li>
              <li><router-link to="/cgv" class="footer-link">Retours</router-link></li>
            </ul>
          </div>

          <div class="footer-section">
            <h4 class="footer-subtitle">Newsletter</h4>
            <p class="newsletter-text">
              Restez informé des dernières nouveautés et offres spéciales.
            </p>
            <div class="newsletter-form">
              <input 
                type="email" 
                placeholder="Votre email" 
                class="newsletter-input"
                v-model="email"
              >
              <button class="newsletter-btn" @click="subscribeNewsletter">
                S'abonner
              </button>
            </div>
          </div>
        </div>

        <!-- Section inférieure -->
        <div class="footer-bottom">
          <div class="footer-bottom-content">
            <p class="copyright">
              © 2026 Al3DPrint. Tous droits réservés.
            </p>
            <div class="footer-bottom-links">
              <router-link to="/mentions-legales" class="footer-bottom-link">Mentions légales</router-link>
              <router-link to="/politique-confidentialite" class="footer-bottom-link">Politique de confidentialité</router-link>
              <router-link to="/cgv" class="footer-bottom-link">CGV</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Effet de particules -->
    <div class="particles">
      <div class="particle" v-for="i in 20" :key="i" :style="getParticleStyle(i)"></div>
    </div>
  </footer>
</template>

<script setup>
import { ref } from 'vue'
import { useAlert } from '../composables/useAlert'

const email = ref('')
const { success: showSuccess } = useAlert()

const subscribeNewsletter = () => {
  if (email.value) {
    // Ici on pourrait intégrer avec l'API pour l'inscription à la newsletter
    showSuccess('Merci pour votre inscription à la newsletter !')
    email.value = ''
  }
}

const getParticleStyle = (index) => {
  const delay = Math.random() * 5
  const duration = 3 + Math.random() * 4
  const size = 2 + Math.random() * 3
  const left = Math.random() * 100
  
  return {
    left: `${left}%`,
    animationDelay: `${delay}s`,
    animationDuration: `${duration}s`,
    width: `${size}px`,
    height: `${size}px`
  }
}
</script>

<style scoped>
.footer {
  background: linear-gradient(135deg, #0a0a0a 0%, var(--surface) 100%);
  border-top: 1px solid var(--border-color);
  margin-top: auto;
  position: relative;
  overflow: hidden;
}

.footer-content {
  padding: 60px 0 20px;
}

.footer-main {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 40px;
  margin-bottom: 40px;
}

.footer-section {
  display: flex;
  flex-direction: column;
}

.footer-title {
  font-size: 28px;
  font-weight: bold;
  color: var(--primary-color);
  margin-bottom: 15px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.footer-subtitle {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.footer-description {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 20px;
}

.social-links {
  display: flex;
  gap: 15px;
}

.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(208, 255, 89, 0.1);
  border: 1px solid var(--border-color);
  border-radius: 50%;
  text-decoration: none;
  font-size: 18px;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: var(--primary-color);
  transform: translateY(-3px);
  box-shadow: 0 5px 15px var(--glow-color);
}

.footer-links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-link {
  color: var(--text-secondary);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
}

.footer-link:hover {
  color: var(--primary-color);
  padding-left: 10px;
}

.footer-link::before {
  content: '→';
  position: absolute;
  left: -15px;
  opacity: 0;
  transition: all 0.3s ease;
}

.footer-link:hover::before {
  opacity: 1;
  left: -10px;
}

.newsletter-text {
  color: var(--text-secondary);
  margin-bottom: 15px;
  line-height: 1.5;
}

.newsletter-form {
  display: flex;
  gap: 10px;
}

.newsletter-input {
  flex: 1;
  background: rgba(20, 20, 20, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 12px 15px;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.newsletter-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.newsletter-input::placeholder {
  color: var(--text-secondary);
}

.newsletter-btn {
  background: var(--primary-color);
  color: #050505;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.newsletter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px var(--glow-color);
}

.footer-bottom {
  border-top: 1px solid var(--border-color);
  padding: 20px 0;
}

.footer-bottom-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.copyright {
  color: var(--text-secondary);
  font-size: 14px;
}

.footer-bottom-links {
  display: flex;
  gap: 20px;
}

.footer-bottom-link {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 14px;
  transition: all 0.3s ease;
}

.footer-bottom-link:hover {
  color: var(--primary-color);
}

/* Particules animées */
.particles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  overflow: hidden;
}

.particle {
  position: absolute;
  background: var(--primary-color);
  border-radius: 50%;
  opacity: 0.3;
  animation: float-particles linear infinite;
}

@keyframes float-particles {
  0% {
    transform: translateY(100vh) rotate(0deg);
    opacity: 0;
  }
  10% {
    opacity: 0.3;
  }
  90% {
    opacity: 0.3;
  }
  100% {
    transform: translateY(-100px) rotate(360deg);
    opacity: 0;
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .footer-main {
    grid-template-columns: 1fr 1fr;
    gap: 30px;
  }
}

@media (max-width: 768px) {
  .footer-content { padding: 40px 0 16px; }

  .footer-main {
    grid-template-columns: 1fr 1fr;
    gap: 28px;
  }

  .footer-bottom-content {
    flex-direction: column;
    gap: 12px;
    text-align: center;
  }

  .footer-bottom-links {
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px 20px;
  }

  .newsletter-form { flex-direction: column; }

  .social-links { justify-content: center; }
}

@media (max-width: 480px) {
  .footer-content { padding: 32px 0 12px; }

  .footer-main {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .footer-title   { font-size: 22px; }
  .footer-subtitle { font-size: 15px; margin-bottom: 12px; }
  .footer-description { font-size: 14px; }

  .footer-bottom-links {
    flex-direction: column;
    align-items: center;
    gap: 8px;
  }

  .footer-bottom-link,
  .copyright { font-size: 12px; }

  .social-link { width: 36px; height: 36px; font-size: 16px; }
}
</style>
