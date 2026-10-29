import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const isGuestCart = ref(true) // Indique si c'est un panier d'invité
  const isLoading = ref(false)
  const showNotification = ref(false)
  const notificationTimeout = ref(null)

  // ── Code promo ────────────────────────────────────────────────────────────
  const promoCode    = ref(localStorage.getItem('promo_code') || '')
  const promoPercent = ref(parseFloat(localStorage.getItem('promo_percent') || '0'))
  const promoError   = ref('')

  const discountAmount = computed(() => {
    if (!promoCode.value || promoPercent.value <= 0) return 0
    return totalPrice.value * (promoPercent.value / 100)
  })

  const discountedTotal = computed(() => Math.max(0, totalPrice.value - discountAmount.value))

  const applyPromoCode = async (code) => {
    promoError.value = ''
    const trimmed = (code || '').trim().toUpperCase()
    if (!trimmed) {
      promoError.value = 'Veuillez saisir un code promo'
      return false
    }
    try {
      const res = await fetch('/api/code-promo/validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: trimmed })
      })
      const data = await res.json()
      if (data.valid) {
        promoCode.value    = data.code
        promoPercent.value = parseFloat(data.remise)
        localStorage.setItem('promo_code', data.code)
        localStorage.setItem('promo_percent', String(data.remise))
        return true
      } else {
        promoError.value = data.message || 'Code promo invalide'
        return false
      }
    } catch {
      promoError.value = 'Erreur lors de la validation du code'
      return false
    }
  }

  const removePromoCode = () => {
    promoCode.value    = ''
    promoPercent.value = 0
    promoError.value   = ''
    localStorage.removeItem('promo_code')
    localStorage.removeItem('promo_percent')
  }
  // ─────────────────────────────────────────────────────────────────────────

  const totalItems = computed(() => {
    return items.value.reduce((total, item) => total + item.quantity, 0)
  })

  const totalPrice = computed(() => {
    return items.value.reduce((total, item) => total + (item.price * item.quantity), 0)
  })

  // Fonction utilitaire pour obtenir l'utilisateur connecté
  const getCurrentUser = () => {
    const savedUser = localStorage.getItem('user')
    const savedToken = localStorage.getItem('token')
    if (savedUser && savedToken) {
      return JSON.parse(savedUser)
    }
    return null
  }

  // En-têtes avec le token d'authentification (requis par l'API panier)
  const authHeaders = () => {
    const token = localStorage.getItem('token')
    return {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {})
    }
  }

  // Fonction pour sauvegarder en base de données
  const saveToDatabase = async (action, data) => {
    const user = getCurrentUser()
    if (!user || isGuestCart.value) {
      return { success: false }
    }

    try {
      const response = await fetch(`/api/cart/${user.id}${action}`, {
        method: data.method || 'POST',
        headers: authHeaders(),
        body: JSON.stringify(data.body || {})
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur inconnue' }))
        throw new Error(errorData.error || 'Erreur lors de la sauvegarde')
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la sauvegarde en base:', error)
      return { success: false, error: error.message }
    }
  }

  // Charger le panier depuis la base de données
  const loadCartFromDatabase = async () => {
    const user = getCurrentUser()
    if (!user) {
      return null
    }

    try {
      const response = await fetch(`/api/cart/${user.id}`, {
        method: 'GET',
        headers: authHeaders()
      })

      if (!response.ok) {
        throw new Error('Erreur lors du chargement du panier')
      }

      const data = await response.json()
      return data.items || []
    } catch (error) {
      console.error('Erreur lors du chargement du panier depuis la base:', error)
      return null
    }
  }

  const addToCart = async (product, quantity = 1) => {
    const varianteId = product.variante_selectionnee?.id || null
    const varianteNom = product.variante_selectionnee?.nom_couleur || null

    const existingItem = items.value.find(
      item => item.id === product.id && item.variante_id === varianteId
    )

    if (existingItem) {
      existingItem.quantity += quantity
    } else {
      items.value.push({
        id: product.id,
        variante_id: varianteId,
        variante_nom: varianteNom,
        name: product.nom,
        price: parseFloat(product.prix),
        image: product.image_principale,
        quantity: quantity,
        category: product.categorie?.nom || 'Sans catégorie',
        item_id: null
      })
    }

    saveCart()
    showCartNotification()

    const user = getCurrentUser()
    if (user && !isGuestCart.value) {
      const result = await saveToDatabase('/add', {
        method: 'POST',
        body: {
          produit_id: product.id,
          quantite: quantity,
          personnalisation: varianteId ? { variante_id: varianteId, variante_nom: varianteNom } : null
        }
      })

      if (result.success) {
        await syncCartFromDatabase()
      }
    }
  }

  // Fonction pour afficher la notification
  const showCartNotification = () => {
    // Annuler le timeout précédent si une notification est déjà affichée
    if (notificationTimeout.value) {
      clearTimeout(notificationTimeout.value)
    }
    
    // Afficher la notification
    showNotification.value = true
    
    // Masquer la notification après 4 secondes
    notificationTimeout.value = setTimeout(() => {
      showNotification.value = false
      notificationTimeout.value = null
    }, 4000)
  }

  const removeFromCart = async (productId, varianteId = null) => {
    const item = items.value.find(
      item => item.id === productId && item.variante_id === varianteId
    )

    const user = getCurrentUser()
    if (user && !isGuestCart.value && item?.item_id) {
      await saveToDatabase(`/item/${item.item_id}`, {
        method: 'DELETE',
        body: {}
      })
    }

    items.value = items.value.filter(
      item => !(item.id === productId && item.variante_id === varianteId)
    )
    saveCart()
  }

  const updateQuantity = async (productId, quantity, varianteId = null) => {
    const item = items.value.find(
      item => item.id === productId && item.variante_id === varianteId
    )
    if (item) {
      if (quantity <= 0) {
        await removeFromCart(productId, varianteId)
      } else {
        item.quantity = quantity
        saveCart()

        const user = getCurrentUser()
        if (user && !isGuestCart.value && item.item_id) {
          await saveToDatabase('/update', {
            method: 'PUT',
            body: {
              item_id: item.item_id,
              quantite: quantity
            }
          })
        }
      }
    }
  }

  const clearCart = async () => {
    // Si l'utilisateur est connecté, vider en base de données
    const user = getCurrentUser()
    if (user && !isGuestCart.value) {
      try {
        await fetch(`/api/cart/${user.id}/clear`, {
          method: 'POST',
          headers: authHeaders()
        })
      } catch (error) {
        console.error('Erreur lors du vidage du panier en base:', error)
      }
    }
    
    items.value = []
    removePromoCode()

    // Marquer le panier comme vidé pour éviter le rechargement
    localStorage.setItem('cart_cleared', 'true')
    
    // Vider le panier selon le type (invité ou utilisateur)
    if (isGuestCart.value) {
      const guestId = localStorage.getItem('guest_id')
      if (guestId) {
        localStorage.removeItem(`guest_cart_${guestId}`)
      }
    } else {
      if (user) {
        localStorage.removeItem(`user_cart_${user.id}`)
      }
    }
  }

  // Sauvegarder le panier localement (pour les invités ou en cache)
  const saveCart = () => {
    if (isGuestCart.value) {
      // Sauvegarder comme panier d'invité
      const guestId = localStorage.getItem('guest_id') || `guest_${Date.now()}`
      localStorage.setItem('guest_id', guestId)
      localStorage.setItem(`guest_cart_${guestId}`, JSON.stringify(items.value))
    } else {
      // Sauvegarder comme panier utilisateur connecté (cache local)
      const user = getCurrentUser()
      if (user) {
        localStorage.setItem(`user_cart_${user.id}`, JSON.stringify(items.value))
      }
    }
  }

  // Synchroniser le panier depuis la base de données
  const syncCartFromDatabase = async () => {
    const user = getCurrentUser()
    if (!user) {
      return
    }

    try {
      const dbItems = await loadCartFromDatabase()
      if (dbItems) {
        // Convertir le format de la base de données au format du store
        items.value = dbItems.map(item => ({
          id: item.produit_id,
          variante_id: item.personnalisation?.variante_id || null,
          variante_nom: item.personnalisation?.variante_nom || null,
          item_id: item.id,
          name: item.name,
          price: item.price,
          image: item.image,
          quantity: item.quantity,
          category: item.category,
          personnalisation: item.personnalisation
        }))
        
        // Sauvegarder en cache local
        saveCart()
      }
    } catch (error) {
      console.error('Erreur lors de la synchronisation:', error)
    }
  }

  // Charger le panier au démarrage
  const loadCart = async () => {
    isLoading.value = true
    
    // Vérifier si le panier a été explicitement vidé
    const cartCleared = localStorage.getItem('cart_cleared')
    if (cartCleared === 'true') {
      localStorage.removeItem('cart_cleared')
      isLoading.value = false
      return
    }
    
    const user = getCurrentUser()
    
    if (user) {
      // Utilisateur connecté - charger depuis la base de données
      isGuestCart.value = false
      await syncCartFromDatabase()
    } else {
      // Invité - charger depuis le localStorage
      isGuestCart.value = true
      const guestId = localStorage.getItem('guest_id') || `guest_${Date.now()}`
      localStorage.setItem('guest_id', guestId)
      const guestCart = localStorage.getItem(`guest_cart_${guestId}`)
      if (guestCart) {
        items.value = JSON.parse(guestCart)
      } else {
        items.value = []
      }
    }
    
    isLoading.value = false
  }

  // Synchroniser le panier lors de la connexion
  const syncCartOnLogin = async () => {
    const user = getCurrentUser()
    if (!user) return
    
    // Récupérer le panier d'invité actuel (si existant)
    const guestId = localStorage.getItem('guest_id')
    const guestCartKey = guestId ? `guest_cart_${guestId}` : null
    const guestCart = guestCartKey ? localStorage.getItem(guestCartKey) : null
    
    // Charger le panier depuis la base de données
    await syncCartFromDatabase()
    
    // Si il y a un panier d'invité, fusionner avec le panier de la base
    if (guestCart) {
      const guestItems = JSON.parse(guestCart)
      
      for (const guestItem of guestItems) {
        const existingItem = items.value.find(
          item => item.id === guestItem.id && item.variante_id === (guestItem.variante_id || null)
        )

        if (existingItem) {
          await updateQuantity(guestItem.id, existingItem.quantity + guestItem.quantity, guestItem.variante_id || null)
        } else {
          // Ajouter l'article
          // On doit récupérer les détails du produit depuis l'API
          try {
            const response = await fetch(`/api/produits/${guestItem.id}`)
            if (response.ok) {
              const product = await response.json()
              await addToCart(product, guestItem.quantity)
            }
          } catch (error) {
            console.error('Erreur lors de la récupération du produit:', error)
          }
        }
      }
      
      // Nettoyer le panier d'invité
      if (guestCartKey) {
        localStorage.removeItem(guestCartKey)
      }
    }
    
    // Marquer comme panier utilisateur
    isGuestCart.value = false
  }

  // Synchroniser le panier lors de la déconnexion
  const syncCartOnLogout = () => {
    // Récupérer l'utilisateur avant qu'il soit supprimé du localStorage
    const user = getCurrentUser()
    
    // Vérifier si le panier a été vidé
    const cartCleared = localStorage.getItem('cart_cleared')
    if (cartCleared === 'true') {
      if (user) {
        localStorage.removeItem(`user_cart_${user.id}`)
      }
      localStorage.removeItem('cart_cleared')
    }
    
    // Nettoyer le panier utilisateur du localStorage
    if (user) {
      localStorage.removeItem(`user_cart_${user.id}`)
    }
    
    // Nettoyer TOUS les paniers d'invité existants dans le localStorage
    // On parcourt toutes les clés du localStorage pour trouver les paniers d'invité
    const keysToRemove = []
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      if (key && key.startsWith('guest_cart_')) {
        keysToRemove.push(key)
      }
    }
    keysToRemove.forEach(key => localStorage.removeItem(key))
    
    // Supprimer aussi l'ID invité actuel
    localStorage.removeItem('guest_id')
    
    // Vider complètement le panier actuel
    items.value = []
    removePromoCode()

    // Créer un nouvel ID invité vide (panier vide pour le nouvel invité)
    const newGuestId = `guest_${Date.now()}`
    localStorage.setItem('guest_id', newGuestId)
    
    // Marquer comme panier invité (vide)
    isGuestCart.value = true
    
    // Le panier de l'utilisateur reste en base de données pour qu'il puisse le récupérer à la prochaine connexion
    // Mais il n'est plus visible localement
  }

  return {
    items,
    totalItems,
    totalPrice,
    isGuestCart,
    isLoading,
    showNotification,
    promoCode,
    promoPercent,
    promoError,
    discountAmount,
    discountedTotal,
    applyPromoCode,
    removePromoCode,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    loadCart,
    syncCartOnLogin,
    syncCartOnLogout,
    syncCartFromDatabase
  }
})
