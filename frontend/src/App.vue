<template>
  <div id="app">
    <NavBar />
    <router-view />
    <Footer />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import NavBar from './components/NavBar.vue'
import Footer from './components/Footer.vue'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'

// Initialiser les stores
const authStore = useAuthStore()
const cartStore = useCartStore()

// Configurer la synchronisation entre les stores
authStore.setCartStore(cartStore)

onMounted(() => {
  // Charger l'utilisateur et le panier au démarrage
  authStore.loadUser()
  cartStore.loadCart()
})
</script>

<style>
/* Reset et styles de base */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI',
    sans-serif;
  background: radial-gradient(ellipse at center, var(--secondary-color) 0%, var(--dark-bg) 70%);
  color: var(--text-primary);
  min-height: 100vh;
  overflow-x: hidden;
  /* évite le double-tap zoom sur iOS pour les boutons */
  touch-action: manipulation;
  -webkit-text-size-adjust: 100%;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Variables CSS pour le thème futuriste */
:root {
  --primary-color: #d0ff59;
  --secondary-color: #181818;
  --accent-color: #ccf0d4;
  --dark-bg: #0b0b0b;
  --dark-secondary: #101010;
  --dark-tertiary: #050505;
  --text-primary: #ffffff;
  --text-secondary: #8b8b8b;
  --border-color: rgba(255, 255, 255, 0.12);
  --glow-color: rgba(208, 255, 89, 0.45);
  --shadow-color: rgba(0, 0, 0, 0.7);
  --surface: #141414;
  --surface-2: #2a2a2a;
  --radius: 14px;
}

/* Animations et effets */
@keyframes glow {
  0%, 100% { box-shadow: 0 0 5px var(--glow-color); }
  50% { box-shadow: 0 0 20px var(--glow-color), 0 0 30px var(--glow-color); }
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Styles globaux */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.btn {
  padding: 12px 24px;
  border-radius: 999px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid transparent;
  position: relative;
  overflow: hidden;
}

.btn-primary {
  background: #050505;
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.25);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.7);
  position: relative;
}

.btn-primary::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--primary-color);
  transform: translateX(-100%);
  transition: transform 0.3s ease;
  z-index: -1;
}

.btn-primary:hover {
  transform: translateY(-2px);
  color: #050505 !important;
  border-color: var(--primary-color);
  box-shadow: 0 10px 24px rgba(208, 255, 89, 0.25);
}

:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}

.btn-primary:hover::before {
  transform: translateX(0);
}

.btn-secondary {
  background: transparent;
  color: var(--text-primary);
  border-color: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(12px);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.06);
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.card {
  background: var(--surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 20px;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px var(--shadow-color);
  border-color: var(--primary-color);
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: var(--dark-bg);
}

::-webkit-scrollbar-thumb {
  background: var(--primary-color);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--accent-color);
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 0 15px;
  }
  
  .btn {
    padding: 10px 20px;
    font-size: 14px;
  }
}
</style>