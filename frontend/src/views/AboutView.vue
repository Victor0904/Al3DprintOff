<template>
  <div class="about-page">
    <div class="container">
      <!-- Hero Section -->
      <section class="hero-section">
        <div class="hero-content">
          <h1 class="hero-title">À propos d'Al3DPrint</h1>
          <p class="hero-description">
            Nous sommes passionnés par l'innovation et l'impression 3D. 
            Notre mission est de rendre cette technologie accessible à tous.
          </p>
        </div>
      </section>

      <!-- Notre Histoire -->
      <section class="story-section">
        <div class="story-content">
          <div class="story-text">
            <h2 class="section-title">Notre Histoire</h2>
            <p class="story-paragraph">
              Fondée en 2024, Al3DPrint est née de la passion pour l'impression 3D et 
              l'innovation technologique. Notre équipe de designers et d'ingénieurs 
              travaille sans relâche pour créer des produits uniques et personnalisables.
            </p>
            <p class="story-paragraph">
              Nous croyons que l'avenir de la fabrication réside dans la personnalisation 
              et l'innovation. Chaque produit que nous créons est pensé pour répondre aux 
              besoins spécifiques de nos clients tout en respectant l'environnement.
            </p>
          </div>
          <div class="story-visual">
            <div class="floating-elements">
              <div class="element element-1">🔮</div>
              <div class="element element-2">⚡</div>
              <div class="element element-3">🎨</div>
              <div class="element element-4">🌱</div>
            </div>
          </div>
        </div>
      </section>

    

      <!-- Nos Valeurs -->
      <section class="values-section">
        <h2 class="section-title">Nos Valeurs</h2>
        <div class="values-grid">
          <div class="value-card" v-for="value in values" :key="value.id">
            <div class="value-icon">{{ value.icon }}</div>
            <h3 class="value-title">{{ value.title }}</h3>
            <p class="value-description">{{ value.description }}</p>
          </div>
        </div>
      </section>

      <!-- Contact -->
      <section class="contact-section">
        <h2 class="section-title">Contactez-nous</h2>
        <div class="contact-content">
          <div class="contact-info">
            <div class="contact-item">
              <span class="contact-icon">📧</span>
              <div class="contact-details">
                <h4>Email</h4>
                <p>contact@al3dprint.com</p>
              </div>
            </div>
            <div class="contact-item">
              <span class="contact-icon">📞</span>
              <div class="contact-details">
                <h4>Téléphone</h4>
                <p>+33 1 23 45 67 89</p>
              </div>
            </div>
            <div class="contact-item">
              <span class="contact-icon">📍</span>
              <div class="contact-details">
                <h4>Adresse</h4>
                <p>123 Rue de l'Innovation<br>75001 Paris, France</p>
              </div>
            </div>
          </div>
          <div class="contact-form">
            <form @submit.prevent="submitContact">
              <div class="form-group">
                <input 
                  type="text" 
                  placeholder="Votre nom" 
                  v-model="contactForm.name"
                  class="form-input"
                  required
                >
              </div>
              <div class="form-group">
                <input 
                  type="email" 
                  placeholder="Votre email" 
                  v-model="contactForm.email"
                  class="form-input"
                  required
                >
              </div>
              <div class="form-group">
                <textarea 
                  placeholder="Votre message" 
                  v-model="contactForm.message"
                  class="form-textarea"
                  rows="5"
                  required
                ></textarea>
              </div>
              <button type="submit" class="btn btn-primary">
                Envoyer le message
              </button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useAlert } from '../composables/useAlert'

const { success: showSuccess, error: showError } = useAlert()

// État du formulaire de contact
const contactForm = reactive({
  name: '',
  email: '',
  message: ''
})

// Données de l'équipe
const team = ref([
  {
    id: 1,
    name: 'Alexandre Dubois',
    role: 'Fondateur & CEO',
    description: 'Passionné d\'innovation et d\'impression 3D depuis plus de 10 ans.',
    icon: '👨‍💼'
  },
  {
    id: 2,
    name: 'Marie Leroy',
    role: 'Designer Principal',
    description: 'Créatrice talentueuse spécialisée dans le design futuriste.',
    icon: '👩‍🎨'
  },
  {
    id: 3,
    name: 'Thomas Martin',
    role: 'Ingénieur 3D',
    description: 'Expert en technologies d\'impression et optimisation des processus.',
    icon: '👨‍🔬'
  },
  {
    id: 4,
    name: 'Sophie Chen',
    role: 'Responsable Qualité',
    description: 'Garantit l\'excellence de chaque produit qui sort de nos ateliers.',
    icon: '👩‍🔬'
  }
])

// Valeurs de l'entreprise
const values = ref([
  {
    id: 1,
    icon: '🎨',
    title: 'Innovation',
    description: 'Nous repoussons constamment les limites de l\'impression 3D pour créer des produits révolutionnaires.'
  },
  {
    id: 2,
    icon: '🌱',
    title: 'Durabilité',
    description: 'Nous utilisons des matériaux écologiques et des processus respectueux de l\'environnement.'
  },
  {
    id: 3,
    icon: '👥',
    title: 'Collaboration',
    description: 'Nous travaillons en étroite collaboration avec nos clients pour réaliser leurs visions.'
  },
  {
    id: 4,
    icon: '⭐',
    title: 'Excellence',
    description: 'Chaque produit est conçu et fabriqué avec le plus grand soin et attention aux détails.'
  }
])

// Méthodes
const submitContact = async () => {
  try {
    const response = await fetch('/api/contact', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nom: contactForm.name,
        email: contactForm.email,
        message: contactForm.message
      })
    })

    if (response.ok) {
      showSuccess('Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.')
      contactForm.name = ''
      contactForm.email = ''
      contactForm.message = ''
    } else {
      const data = await response.json()
      showError(data.error || 'Erreur lors de l\'envoi du message.')
    }
  } catch {
    showError('Impossible d\'envoyer le message. Veuillez réessayer.')
  }
}
</script>

<style scoped>
.about-page {
  min-height: 100vh;
  padding: 40px 0;
}

.hero-section {
  text-align: center;
  padding: 80px 0;
  background: linear-gradient(135deg, rgba(26, 26, 46, 0.5), rgba(22, 33, 62, 0.5));
  border-radius: 20px;
  margin-bottom: 60px;
}

.hero-title {
  font-size: 48px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 20px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-description {
  font-size: 20px;
  color: var(--text-secondary);
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

.section-title {
  font-size: 36px;
  font-weight: bold;
  text-align: center;
  margin-bottom: 50px;
  color: var(--text-primary);
}

.story-section {
  margin-bottom: 80px;
}

.story-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.story-paragraph {
  font-size: 16px;
  color: var(--text-secondary);
  line-height: 1.8;
  margin-bottom: 20px;
}

.story-visual {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  height: 400px;
}

.floating-elements {
  position: relative;
  width: 300px;
  height: 300px;
}

.element {
  position: absolute;
  font-size: 48px;
  animation: float 3s ease-in-out infinite;
}

.element-1 {
  top: 20%;
  left: 20%;
  animation-delay: 0s;
}

.element-2 {
  top: 20%;
  right: 20%;
  animation-delay: 0.5s;
}

.element-3 {
  bottom: 20%;
  left: 20%;
  animation-delay: 1s;
}

.element-4 {
  bottom: 20%;
  right: 20%;
  animation-delay: 1.5s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.team-section {
  margin-bottom: 80px;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
}

.team-member {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px 20px;
  text-align: center;
  transition: all 0.3s ease;
}

.team-member:hover {
  transform: translateY(-10px);
  border-color: var(--primary-color);
  box-shadow: 0 15px 40px var(--shadow-color);
}

.member-avatar {
  width: 80px;
  height: 80px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.avatar-icon {
  font-size: 32px;
}

.member-name {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.member-role {
  color: var(--primary-color);
  font-weight: 600;
  margin-bottom: 15px;
}

.member-description {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.values-section {
  margin-bottom: 80px;
}

.values-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
}

.value-card {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px 20px;
  text-align: center;
  transition: all 0.3s ease;
}

.value-card:hover {
  transform: translateY(-10px);
  border-color: var(--primary-color);
  box-shadow: 0 15px 40px var(--shadow-color);
}

.value-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.value-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 15px;
}

.value-description {
  color: var(--text-secondary);
  line-height: 1.6;
}

.contact-section {
  background: rgba(26, 26, 46, 0.5);
  border-radius: 20px;
  padding: 60px 40px;
}

.contact-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.contact-icon {
  font-size: 24px;
  width: 50px;
  height: 50px;
  background: rgba(0, 212, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.contact-details h4 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.contact-details p {
  color: var(--text-secondary);
  line-height: 1.5;
}

.contact-form {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  padding: 30px;
}

.form-group {
  margin-bottom: 20px;
}

.form-input,
.form-textarea {
  width: 100%;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 12px 15px;
  color: var(--text-primary);
  font-size: 16px;
  transition: all 0.3s ease;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: var(--text-secondary);
}

.form-textarea {
  resize: vertical;
  min-height: 120px;
}

/* Responsive */
@media (max-width: 1024px) {
  .story-content,
  .contact-content {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  
  .hero-title {
    font-size: 36px;
  }
  
  .section-title {
    font-size: 28px;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 60px 20px;
  }
  
  .hero-title {
    font-size: 28px;
  }
  
  .hero-description {
    font-size: 16px;
  }
  
  .story-visual {
    height: 250px;
  }
  
  .floating-elements {
    width: 200px;
    height: 200px;
  }
  
  .element {
    font-size: 32px;
  }
  
  .contact-section {
    padding: 40px 20px;
  }
  
  .contact-form {
    padding: 20px;
  }
}
</style>
