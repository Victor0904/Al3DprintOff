import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Charger les données persistantes
import { useCartStore } from './stores/cart'
import { useAuthStore } from './stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()

cartStore.loadCart()
authStore.loadUser()

app.mount('#app')
