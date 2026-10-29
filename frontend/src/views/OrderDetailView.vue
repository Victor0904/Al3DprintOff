<template>
  <div class="order-detail">
    <!-- Header -->
    <div class="order-header">
      <div class="container">
        <div class="header-content">
          <div class="back-section">
            <router-link to="/profile" class="back-btn">
              <i class="fas fa-arrow-left"></i>
              Retour au profil
            </router-link>
          </div>
          <div class="order-info">
            <h1>Détail de la commande</h1>
            <div class="order-number">Commande {{ order?.numero_commande || ('#' + order?.id) }}</div>
          </div>
          <div v-if="order" class="header-actions">
            <button class="btn btn-invoice" @click="downloadInvoice">
              ⬇ Télécharger la facture
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Chargement des détails de la commande...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">⚠️</div>
      <h3>Erreur de chargement</h3>
      <p>{{ error }}</p>
      <button @click="loadOrder" class="btn btn-primary">Réessayer</button>
    </div>

    <!-- Order Content -->
    <div v-else-if="order" class="order-content">
      <div class="container">
        <div class="order-grid">
          <!-- Order Summary -->
          <div class="order-summary-card">
            <div class="card-header">
              <h2>Résumé de la commande</h2>
              <div class="order-status" :class="getStatusClass(order.statut)">
                {{ getStatusText(order.statut) }}
              </div>
            </div>
            
            <div class="summary-details">
              <div class="summary-row">
                <span>Numéro de commande</span>
                <span class="order-ref">{{ order.numero_commande || ('#' + order.id) }}</span>
              </div>
              <div class="summary-row">
                <span>Date de commande</span>
                <span>{{ formatDate(order.date_commande) }}</span>
              </div>
              <!-- Professionnel (entreprise) : détail HT + TVA 17 % + TTC -->
              <template v-if="isEntreprise">
                <div class="summary-row">
                  <span>Sous-total HT</span>
                  <span>{{ formatPrice(orderTotalHT) }}€</span>
                </div>
                <div class="summary-row">
                  <span>TVA 17 %</span>
                  <span>{{ formatPrice(orderTVA17) }}€</span>
                </div>
                <div v-if="order.frais_livraison && parseFloat(order.frais_livraison) > 0" class="summary-row">
                  <span>Frais de livraison</span>
                  <span>{{ formatPrice(order.frais_livraison) }}€</span>
                </div>
                <div v-else class="summary-row">
                  <span>Frais de livraison</span>
                  <span class="free-shipping">Gratuite</span>
                </div>
                <div class="summary-row summary-row-total">
                  <span>Total TTC</span>
                  <span class="total-price total-ttc">{{ formatPrice(orderTotalTTC) }}€</span>
                </div>
              </template>
              <!-- Particulier : total TTC -->
              <template v-else>
                <div v-if="order.frais_livraison && parseFloat(order.frais_livraison) > 0" class="summary-row">
                  <span>Frais de livraison</span>
                  <span>{{ formatPrice(order.frais_livraison) }}€</span>
                </div>
                <div v-else class="summary-row">
                  <span>Frais de livraison</span>
                  <span class="free-shipping">Gratuite</span>
                </div>
                <div class="summary-row summary-row-total">
                  <span>Total TTC</span>
                  <span class="total-price">{{ formatPrice(orderTotalTTC) }}€</span>
                </div>
              </template>
              <div class="summary-row">
                <span>Mode de paiement</span>
                <span>{{ order.mode_paiement }}</span>
              </div>
              <div v-if="order.adresse_livraison" class="summary-row">
                <span>Adresse de livraison</span>
                <span style="white-space: pre-line">{{ order.adresse_livraison }}</span>
              </div>
            </div>
          </div>

          <!-- Client Information -->
          <div class="client-info-card">
            <div class="card-header">
              <h2>Informations client</h2>
            </div>
            
            <div class="client-details">
              <div class="client-row">
                <span class="label">Nom</span>
                <span class="value">{{ order.client.nom }}</span>
              </div>
              <div class="client-row">
                <span class="label">Email</span>
                <span class="value">{{ order.client.email }}</span>
              </div>
              <div v-if="order.client.telephone" class="client-row">
                <span class="label">Téléphone</span>
                <span class="value">{{ order.client.telephone }}</span>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="order-items-card">
            <div class="card-header">
              <h2>Articles commandés</h2>
              <span class="items-count">{{ order.details?.length || 0 }} article(s)</span>
            </div>
            
            <div class="items-list">
              <div v-for="item in order.details" :key="item.id" class="order-item">
                <div class="item-image">
                  <img :src="getProductImage(item.produit)" :alt="item.produit.nom" />
                </div>
                
                <div class="item-details">
                  <h3 class="item-name">{{ item.produit.nom }}</h3>
                  <div class="item-price">{{ formatPrice(item.prix_unitaire) }}€</div>
                  
                  <p v-if="item.personnalisation?.variante_nom" class="item-customization">
                    <strong>Coloris :</strong> {{ item.personnalisation.variante_nom }}
                  </p>
                  <div v-else-if="item.personnalisation" class="item-customization">
                    <h4>Personnalisation :</h4>
                    <ul>
                      <li v-for="(value, key) in item.personnalisation" :key="key">
                        <strong>{{ formatCustomizationKey(key) }}:</strong> {{ value }}
                      </li>
                    </ul>
                  </div>
                </div>
                
                <div class="item-quantity">
                  <span class="quantity-label">Quantité</span>
                  <span class="quantity-value">{{ item.quantite }}</span>
                </div>
                
                <div class="item-total">
                  <span class="total-label">Total</span>
                  <span class="total-value">{{ formatPrice(item.prix_unitaire * item.quantite) }}€</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Information -->
          <div v-if="order.paiements && order.paiements.length > 0" class="payment-info-card">
            <div class="card-header">
              <h2>Informations de paiement</h2>
            </div>
            
            <div class="payments-list">
              <div v-for="payment in order.paiements" :key="payment.id" class="payment-item">
                <div class="payment-details">
                  <div class="payment-method">{{ getPaymentMethodName(payment.methode) }}</div>
                  <div class="payment-amount">{{ formatPrice(order.total) }}€</div>
                </div>
                <div class="payment-status" :class="getPaymentStatusClass(payment.statut)">
                  {{ getPaymentStatusText(payment.statut) }}
                </div>
                <div class="payment-date">{{ formatDate(payment.date_paiement) }}</div>
              </div>
            </div>
          </div>

          <!-- Printing Information -->
          <div v-if="order.impressions && order.impressions.length > 0" class="printing-info-card">
            <div class="card-header">
              <h2>Informations d'impression</h2>
            </div>
            
            <div class="impressions-list">
              <div v-for="impression in order.impressions" :key="impression.id" class="impression-item">
                <div class="impression-product">
                  <h4>{{ impression.produit.nom }}</h4>
                  <div class="impression-material">{{ impression.materiau }}</div>
                </div>
                
                <div class="impression-status" :class="getImpressionStatusClass(impression.statut)">
                  {{ getImpressionStatusText(impression.statut) }}
                </div>
                
                <div class="impression-details">
                  <div v-if="impression.temps_estime" class="impression-time">
                    <i class="fas fa-clock"></i>
                    {{ impression.temps_estime }}h
                  </div>
                  <div v-if="impression.date_debut" class="impression-date">
                    <i class="fas fa-play"></i>
                    Début: {{ formatDate(impression.date_debut) }}
                  </div>
                  <div v-if="impression.date_fin" class="impression-date">
                    <i class="fas fa-check"></i>
                    Fin: {{ formatDate(impression.date_fin) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAuthFetch } from '../composables/useAuthFetch'

export default {
  name: 'OrderDetailView',
  setup() {
    const route = useRoute()
    const authStore = useAuthStore()
    const { apiFetch } = useAuthFetch()
    const order = ref(null)
    const loading = ref(true)
    const error = ref(null)

    const isEntreprise = computed(() => authStore.user?.type_client === 'entreprise')

    // order.total en base = TTC (prix produits déjà TTC)
    const orderTotalTTC = computed(() => {
      if (!order.value?.total) return 0
      return order.value.total
    })

    const orderTotalHT = computed(() => {
      if (!order.value?.total) return 0
      return order.value.total / 1.17
    })

    const orderTVA17 = computed(() => {
      if (!order.value?.total) return 0
      return order.value.total - orderTotalHT.value
    })

    const loadOrder = async () => {
      try {
        loading.value = true
        error.value = null

        const orderId = route.params.id
        const response = await apiFetch(`/api/commandes/${orderId}`)

        if (!response.ok) {
          throw new Error('Commande non trouvée')
        }

        order.value = await response.json()
      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatPrice = (price) => {
      return parseFloat(price).toFixed(2)
    }

    const getStatusClass = (status) => {
      const statusClasses = {
        'en attente': 'status-pending',
        'payée': 'status-processing',
        'en cours': 'status-processing',
        'expédiée': 'status-shipped',
        'livrée': 'status-delivered',
        'annulée': 'status-cancelled'
      }
      return statusClasses[status] || 'status-unknown'
    }

    const getStatusText = (status) => {
      const statusTexts = {
        'en attente': 'En attente de paiement',
        'payée': 'Payée',
        'en cours': 'En cours de traitement',
        'expédiée': 'Expédiée',
        'livrée': 'Livrée',
        'annulée': 'Annulée'
      }
      return statusTexts[status] || status
    }

    const getPaymentMethodName = (method) => {
      const methods = {
        'card': 'Carte bancaire',
        'stripe': 'Carte bancaire'
      }
      return methods[method] || 'Carte bancaire'
    }

    const getPaymentStatusClass = (status) => {
      const statusClasses = {
        'pending': 'payment-pending',
        'completed': 'payment-completed',
        'failed': 'payment-failed',
        'refunded': 'payment-refunded'
      }
      return statusClasses[status] || 'payment-unknown'
    }

    const getPaymentStatusText = (status) => {
      const statusTexts = {
        'pending': 'En attente',
        'completed': 'Payé',
        'failed': 'Échec',
        'refunded': 'Remboursé'
      }
      return statusTexts[status] || status
    }

    const getImpressionStatusClass = (status) => {
      const statusClasses = {
        'en attente': 'impression-pending',
        'en cours': 'impression-processing',
        'terminée': 'impression-completed',
        'échouée': 'impression-failed'
      }
      return statusClasses[status] || 'impression-unknown'
    }

    const getImpressionStatusText = (status) => {
      const statusTexts = {
        'en attente': 'En attente',
        'en cours': 'En cours',
        'terminée': 'Terminée',
        'échouée': 'Échouée'
      }
      return statusTexts[status] || status
    }

    const getProductImage = (product) => {
      // Créer une image placeholder SVG locale
      const svgContent = `
        <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#f8f9fa;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#e9ecef;stop-opacity:1" />
            </linearGradient>
          </defs>
          <rect width="150" height="150" fill="url(#grad1)" stroke="#dee2e6" stroke-width="2" rx="8"/>
          <circle cx="75" cy="60" r="20" fill="#d0ff59" opacity="0.3"/>
          <text x="75" y="100" text-anchor="middle" font-family="Arial, sans-serif" font-size="11" font-weight="600" fill="#495057">
            ${product.nom.length > 18 ? product.nom.substring(0, 18) + '...' : product.nom}
          </text>
          <text x="75" y="120" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" fill="#6c757d">
            Produit
          </text>
        </svg>
      `
      return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svgContent)}`
    }

    const downloadInvoice = () => {
      if (!order.value) return
      const o = order.value
      const dateCommande = o.date_commande
        ? new Date(o.date_commande).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
        : ''
      const dateFacture = new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
      const numeroCommande = o.numero_commande || ('#' + o.id)
      const frais = parseFloat(o.frais_livraison || 0)
      const totalTTC = parseFloat(o.total || 0)
      const client = o.client || {}

      let itemsHtml = ''
      for (const item of o.details || []) {
        const pu = parseFloat(item.prix_unitaire || 0).toFixed(2)
        const qty = item.quantite || 1
        const tot = (parseFloat(item.prix_unitaire || 0) * qty).toFixed(2)
        itemsHtml += `<tr>
          <td style="padding:10px 8px;border-bottom:1px solid #e8e8e8;color:#333;">${item.produit?.nom || 'Article'}</td>
          <td style="padding:10px 8px;border-bottom:1px solid #e8e8e8;text-align:center;color:#555;">${qty}</td>
          <td style="padding:10px 8px;border-bottom:1px solid #e8e8e8;text-align:right;color:#555;">${pu} €</td>
          <td style="padding:10px 8px;border-bottom:1px solid #e8e8e8;text-align:right;font-weight:700;color:#141414;">${tot} €</td>
        </tr>`
      }

      const fraisStr = frais > 0 ? frais.toFixed(2) + ' €' : 'Gratuite'
      const totalStr = totalTTC.toFixed(2)
      const modePaiement = o.mode_paiement || 'Carte bancaire'
      const statut = o.statut || ''
      const adresse = o.adresse_livraison || ''
      const currentYear = new Date().getFullYear()

      const html = `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture ${numeroCommande} — Al3DPrint</title>
<style>
  @page { margin: 15mm 18mm; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; background: #fff; }
  .page { padding: 0; }
  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 3px solid #d0ff59; margin-bottom: 24px; }
  .logo { font-size: 28px; font-weight: 900; color: #d0ff59; letter-spacing: -1px; }
  .logo-sub { font-size: 10px; color: #888; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; }
  .invoice-meta { text-align: right; }
  .invoice-title { font-size: 22px; font-weight: 700; color: #141414; letter-spacing: 2px; text-transform: uppercase; }
  .invoice-num { font-size: 14px; color: #d0ff59; font-weight: 700; margin-top: 4px; font-family: monospace; }
  .invoice-date { font-size: 11px; color: #888; margin-top: 3px; }
  /* Two columns */
  .two-col { display: flex; gap: 24px; margin-bottom: 24px; }
  .col { flex: 1; }
  .section-label { font-size: 10px; font-weight: 700; color: #d0ff59; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
  .info-box { background: #f8f9fc; border: 1px solid #e8eaf0; border-radius: 6px; padding: 14px 16px; font-size: 12px; line-height: 1.8; color: #444; }
  .info-box strong { color: #141414; font-size: 13px; display: block; margin-bottom: 2px; }
  /* Table */
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  table.items thead tr { background: #141414; }
  table.items thead th { padding: 10px 8px; color: #d0ff59; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
  table.items thead th:first-child { text-align: left; }
  table.items thead th:nth-child(2) { text-align: center; }
  table.items thead th:nth-child(3), table.items thead th:nth-child(4) { text-align: right; }
  /* Totals */
  .totals { width: 300px; margin-left: auto; }
  .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #555; border-bottom: 1px solid #f0f0f0; }
  .totals-row.total { padding-top: 10px; border-top: 2px solid #d0ff59; border-bottom: none; margin-top: 6px; }
  .totals-row.total .label { font-size: 15px; font-weight: 700; color: #141414; }
  .totals-row.total .val { font-size: 18px; font-weight: 900; color: #d0ff59; }
  /* Paid stamp */
  .paid-area { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
  .statut-badge { padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #141414; color: #d0ff59; letter-spacing: 0.5px; text-transform: uppercase; }
  .paid-stamp { border: 3px solid #059669; border-radius: 6px; color: #059669; font-size: 20px; font-weight: 900; padding: 6px 18px; letter-spacing: 3px; transform: rotate(-4deg); display: inline-block; }
  /* Footer */
  .footer { margin-top: 32px; padding-top: 14px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 10px; color: #aaa; line-height: 1.7; }
  /* Print button — hidden on print */
  .print-btn { position: fixed; top: 20px; right: 20px; background: #d0ff59; color: #000; border: none; padding: 12px 24px; border-radius: 50px; font-size: 14px; font-weight: 700; cursor: pointer; z-index: 999; box-shadow: 0 4px 15px rgba(208, 255, 89,0.4); }
  @media print { .print-btn { display: none !important; } @page { margin: 15mm 18mm; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">⬇ Enregistrer en PDF</button>
<div class="page">

  <div class="header">
    <div>
      <div class="logo">Al3DPrint</div>
      <div class="logo-sub">Impression 3D Professionnelle</div>
    </div>
    <div class="invoice-meta">
      <div class="invoice-title">Facture</div>
      <div class="invoice-num">${numeroCommande}</div>
      <div class="invoice-date">Émise le ${dateFacture}</div>
    </div>
  </div>

  <div class="two-col">
    <div class="col">
      <div class="section-label">Vendeur</div>
      <div class="info-box">
        <strong>Al3DPrint</strong>
        contact@al3dprint.fr<br>
        al3dprint.fr<br>
        &copy; ${currentYear} Al3DPrint
      </div>
    </div>
    <div class="col">
      <div class="section-label">Client</div>
      <div class="info-box">
        <strong>${client.nom || ''}</strong>
        ${client.email || ''}<br>
        ${client.telephone || ''}
      </div>
    </div>
  </div>

  <div class="two-col">
    <div class="col">
      <div class="section-label">Détails commande</div>
      <div class="info-box">
        <strong>${numeroCommande}</strong>
        Date : ${dateCommande}<br>
        Paiement : ${modePaiement}
      </div>
    </div>
    <div class="col">
      <div class="section-label">Livraison</div>
      <div class="info-box">
        <strong>Adresse</strong>
        ${adresse}
      </div>
    </div>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th>Article</th>
        <th style="text-align:center">Qté</th>
        <th style="text-align:right">Prix unit. TTC</th>
        <th style="text-align:right">Total TTC</th>
      </tr>
    </thead>
    <tbody>${itemsHtml}</tbody>
  </table>

  <div class="totals">
    <div class="totals-row">
      <span class="label">Frais de livraison</span>
      <span class="val">${fraisStr}</span>
    </div>
    <div class="totals-row total">
      <span class="label">TOTAL TTC</span>
      <span class="val">${totalStr} €</span>
    </div>
  </div>

  <div class="paid-area">
    <span class="statut-badge">${statut}</span>
    <span class="paid-stamp">PAYÉ</span>
  </div>

  <div class="footer">
    <p>Al3DPrint — contact@al3dprint.fr — al3dprint.fr</p>
    <p>Document généré automatiquement. Merci pour votre confiance.</p>
    <p>&copy; ${currentYear} Al3DPrint. Tous droits réservés.</p>
  </div>

</div>
</body>
</html>`

      const win = window.open('', '_blank', 'width=860,height=900')
      if (win) {
        win.document.write(html)
        win.document.close()
        win.focus()
      }
    }

    const formatCustomizationKey = (key) => {
      const keyMap = {
        'color': 'Couleur',
        'size': 'Taille',
        'material': 'Matériau',
        'text': 'Texte personnalisé',
        'logo': 'Logo'
      }
      return keyMap[key] || key
    }

    onMounted(() => {
      loadOrder()
    })

    return {
      order,
      loading,
      error,
      isEntreprise,
      orderTotalHT,
      orderTVA17,
      orderTotalTTC,
      loadOrder,
      downloadInvoice,
      formatDate,
      formatPrice,
      getStatusClass,
      getStatusText,
      getPaymentMethodName,
      getPaymentStatusClass,
      getPaymentStatusText,
      getImpressionStatusClass,
      getImpressionStatusText,
      getProductImage,
      formatCustomizationKey
    }
  }
}
</script>

<style scoped>
.order-detail {
  min-height: 100vh;
  background: radial-gradient(ellipse at center, var(--secondary-color) 0%, var(--dark-bg) 70%);
}

.order-header {
  background: rgba(5, 5, 5, 0.9);
  backdrop-filter: blur(18px);
  border-bottom: 1px solid var(--border-color);
  padding: 2rem 0;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.header-actions {
  flex-shrink: 0;
}

.btn-invoice {
  background: #d0ff59;
  color: #000;
  font-weight: 700;
  padding: 0.65rem 1.4rem;
  border-radius: 50px;
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn-invoice:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(208, 255, 89, 0.4);
}

.btn-invoice:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: white;
  text-decoration: none;
  padding: 0.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50px;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(-5px);
}

.order-info h1 {
  color: white;
  margin: 0;
  font-size: 2.5rem;
  font-weight: 700;
}

.order-number {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 50vh;
  text-align: center;
  color: white;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.order-content {
  padding: 3rem 0;
}

.order-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.order-summary-card,
.client-info-card,
.order-items-card,
.payment-info-card,
.printing-info-card {
  background: #181818;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
  border: 1px solid var(--border-color);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid rgba(255, 255, 255, 0.12);
}

.card-header h2 {
  margin: 0;
  color: #e0e0e0;
  font-size: 1.5rem;
  font-weight: 600;
}

.order-status {
  padding: 0.5rem 1rem;
  border-radius: 25px;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status-processing {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status-shipped {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-delivered {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status-cancelled {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  color: #e0e0e0;
}

.summary-row:last-child {
  border-bottom: none;
}

.order-ref {
  font-family: monospace;
  font-weight: 700;
  color: var(--primary-color);
  font-size: 1rem;
}

.free-shipping {
  color: #ccf0d4;
  font-weight: 600;
}

.summary-row-total {
  border-top: 2px solid rgba(208, 255, 89, 0.3);
  margin-top: 6px;
  padding-top: 10px;
}

.total-price {
  font-weight: 700;
  font-size: 1.2rem;
  color: var(--primary-color);
}

.client-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.client-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  color: #e0e0e0;
}

.client-row:last-child {
  border-bottom: none;
}

.label {
  font-weight: 600;
  color: #b0b0b0;
}

.value {
  color: #e0e0e0;
}

.items-count {
  background: var(--primary-color);
  color: #000;
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.order-item {
  display: grid;
  grid-template-columns: 100px 1fr auto auto;
  gap: 1.5rem;
  align-items: center;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  border: 1px solid var(--border-color);
  color: #e0e0e0;
}

.item-image img {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 10px;
}

.item-details {
  flex: 1;
}

.item-name {
  margin: 0 0 0.5rem 0;
  color: #e0e0e0;
  font-size: 1.1rem;
  font-weight: 600;
}

.item-price {
  color: var(--primary-color);
  font-weight: 600;
  font-size: 1.1rem;
}

.item-customization {
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  border: 1px solid var(--border-color);
}

.item-customization h4 {
  margin: 0 0 0.5rem 0;
  color: #e0e0e0;
  font-size: 0.9rem;
  font-weight: 600;
}

.item-customization ul {
  margin: 0;
  padding-left: 1rem;
  list-style: none;
}

.item-customization li {
  margin: 0.25rem 0;
  color: #b0b0b0;
  font-size: 0.9rem;
}

.item-quantity,
.item-total {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.quantity-label,
.total-label {
  font-size: 0.8rem;
  color: #b0b0b0;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.quantity-value,
.total-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #e0e0e0;
}

.total-value {
  color: var(--primary-color);
}

.payments-list,
.impressions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-item,
.impression-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  border: 1px solid var(--border-color);
  color: #e0e0e0;
}

.payment-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.payment-method {
  font-weight: 600;
  color: #e0e0e0;
}

.payment-amount {
  color: var(--primary-color);
  font-weight: 600;
}

.payment-status {
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.payment-pending {
  background: #fff3cd;
  color: #856404;
}

.payment-completed {
  background: #d4edda;
  color: #155724;
}

.payment-failed {
  background: #f8d7da;
  color: #721c24;
}

.payment-refunded {
  background: #d1ecf1;
  color: #0c5460;
}

.payment-date {
  color: #b0b0b0;
  font-size: 0.9rem;
}

.impression-product h4 {
  margin: 0 0 0.25rem 0;
  color: #e0e0e0;
  font-size: 1rem;
  font-weight: 600;
}

.impression-material {
  color: #b0b0b0;
  font-size: 0.9rem;
}

.impression-status {
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.impression-pending {
  background: #fff3cd;
  color: #856404;
}

.impression-processing {
  background: #d1ecf1;
  color: #0c5460;
}

.impression-completed {
  background: #d4edda;
  color: #155724;
}

.impression-failed {
  background: #f8d7da;
  color: #721c24;
}

.impression-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: #b0b0b0;
}

.impression-time,
.impression-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 50px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-primary {
  background: var(--primary-color);
  color: #000;
}

.btn-primary:hover {
  background: var(--primary-color);
  transform: translateY(-2px);
  color: #000;
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
    align-items: center;
  }

  .btn-invoice {
    font-size: 0.85rem;
    padding: 0.6rem 1.2rem;
  }

  .order-info h1 {
    font-size: 2rem;
  }

  .order-grid {
    padding: 0 1rem;
  }

  .order-item {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 1rem;
  }

  .item-quantity,
  .item-total {
    flex-direction: row;
    justify-content: space-between;
  }

  .payment-item,
  .impression-item {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}
</style>
