<template>
  <div class="demandes-management">
    <div class="management-header">
      <h2>🎨 Demandes de Personnalisation</h2>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <span class="stat-value">{{ demandes.length }}</span>
        <span class="stat-label">Total</span>
      </div>
      <div class="stat-card pending">
        <span class="stat-value">{{ pendingCount }}</span>
        <span class="stat-label">En attente</span>
      </div>
      <div class="stat-card done">
        <span class="stat-value">{{ doneCount }}</span>
        <span class="stat-label">Traitées</span>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
      <button
        v-for="f in filters"
        :key="f.value"
        :class="['filter-btn', { active: activeFilter === f.value }]"
        @click="activeFilter = f.value"
      >
        {{ f.label }}
        <span v-if="f.value === 'En attente' && pendingCount > 0" class="filter-badge">{{ pendingCount }}</span>
      </button>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <div v-if="loading" class="state-msg">Chargement…</div>
      <div v-else-if="filtered.length === 0" class="state-msg">Aucune demande trouvée.</div>

      <table v-else class="demandes-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Client</th>
            <th>Produit</th>
            <th>Photos</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in filtered" :key="d.id" :class="{ 'row-done': d.statut === 'Traité' }">
            <td class="td-id">#{{ d.id }}</td>
            <td class="td-date">{{ formatDate(d.date_creation) }}</td>
            <td class="td-client">
              <span class="client-nom">{{ d.client_nom }}</span>
              <a :href="`mailto:${d.client_email}`" class="client-email">{{ d.client_email }}</a>
            </td>
            <td class="td-product">{{ d.product_name }}</td>
            <td class="td-photos">
              <div class="thumbnails">
                <img
                  v-for="(src, idx) in d.fichiers.slice(0, 3)"
                  :key="idx"
                  :src="src"
                  class="thumb"
                  @error="$event.target.style.display='none'"
                >
                <span v-if="d.fichiers.length > 3" class="more-badge">+{{ d.fichiers.length - 3 }}</span>
              </div>
            </td>
            <td>
              <span :class="['status-badge', d.statut === 'Traité' ? 'done' : 'pending']">
                {{ d.statut === 'Traité' ? '✅ Traité' : '⏳ En attente' }}
              </span>
            </td>
            <td class="td-actions">
              <button @click="openModal(d)" class="action-btn view" title="Voir">👁️</button>
              <button @click="deleteDemande(d.id)" class="action-btn delete" title="Supprimer">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal détail -->
    <div v-if="modal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>Demande #{{ modal.id }} — {{ modal.product_name }}</h3>
          <button @click="closeModal" class="close-btn">×</button>
        </div>

        <!-- Info client -->
        <div class="modal-section">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Client</span>
              <span class="info-value">{{ modal.client_nom }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email</span>
              <a :href="`mailto:${modal.client_email}`" class="info-link">{{ modal.client_email }}</a>
            </div>
            <div class="info-item">
              <span class="info-label">Produit</span>
              <span class="info-value">{{ modal.product_name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Date</span>
              <span class="info-value">{{ formatDate(modal.date_creation) }}</span>
            </div>
          </div>
        </div>

        <!-- Galerie images -->
        <div class="modal-section">
          <h4 class="section-title">Photos envoyées ({{ modal.fichiers.length }})</h4>
          <div class="gallery">
            <div
              v-for="(src, idx) in modal.fichiers"
              :key="idx"
              class="gallery-item"
              @click="lightboxSrc = src"
            >
              <img :src="src" class="gallery-img" @error="$event.target.parentElement.style.display='none'">
              <div class="gallery-overlay">🔍</div>
            </div>
          </div>
        </div>

        <!-- Statut + notes -->
        <div class="modal-section">
          <h4 class="section-title">Gestion</h4>
          <div class="form-row-modal">
            <div class="form-group">
              <label>Statut</label>
              <select v-model="editStatut" class="form-select">
                <option value="En attente">⏳ En attente</option>
                <option value="Traité">✅ Traité</option>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Notes internes</label>
            <textarea
              v-model="editNotes"
              class="form-textarea"
              rows="3"
              placeholder="Notes pour votre équipe…"
            ></textarea>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="closeModal" class="btn btn-secondary">Fermer</button>
          <button @click="saveModal" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <div v-if="lightboxSrc" class="lightbox" @click="lightboxSrc = null">
      <img :src="lightboxSrc" class="lightbox-img">
      <button class="lightbox-close" @click.stop="lightboxSrc = null">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthFetch } from '../../composables/useAuthFetch'
import { useAlert } from '../../composables/useAlert'

const { apiFetch } = useAuthFetch()
const { confirm: showConfirm } = useAlert()

const demandes     = ref([])
const loading      = ref(true)
const activeFilter = ref('Tous')
const modal        = ref(null)
const editStatut   = ref('')
const editNotes    = ref('')
const saving       = ref(false)
const lightboxSrc  = ref(null)

const filters = [
  { label: 'Toutes',     value: 'Tous' },
  { label: 'En attente', value: 'En attente' },
  { label: 'Traitées',   value: 'Traité' },
]

const pendingCount = computed(() => demandes.value.filter(d => d.statut === 'En attente').length)
const doneCount    = computed(() => demandes.value.filter(d => d.statut === 'Traité').length)

const filtered = computed(() => {
  if (activeFilter.value === 'Tous') return demandes.value
  return demandes.value.filter(d => d.statut === activeFilter.value)
})

const loadDemandes = async () => {
  loading.value = true
  try {
    const res = await apiFetch('/api/demandes-personnalisation')
    if (res.ok) demandes.value = await res.json()
  } catch { /* silently */ }
  finally { loading.value = false }
}

const openModal = (d) => {
  modal.value    = d
  editStatut.value = d.statut
  editNotes.value  = d.notes || ''
}

const closeModal = () => {
  modal.value   = null
  lightboxSrc.value = null
}

const saveModal = async () => {
  saving.value = true
  try {
    const res = await apiFetch(`/api/demandes-personnalisation/${modal.value.id}`, {
      method: 'PUT',
      body: JSON.stringify({ statut: editStatut.value, notes: editNotes.value })
    })
    if (res.ok) {
      const updated = await res.json()
      const idx = demandes.value.findIndex(d => d.id === modal.value.id)
      if (idx !== -1) demandes.value[idx] = updated
      modal.value = updated
    }
  } catch { /* silently */ }
  finally { saving.value = false }
}

const deleteDemande = async (id) => {
  const ok = await showConfirm('Supprimer cette demande et ses images ?')
  if (!ok) return
  const res = await apiFetch(`/api/demandes-personnalisation/${id}`, { method: 'DELETE' })
  if (res.ok) demandes.value = demandes.value.filter(d => d.id !== id)
}

const formatDate = (str) => {
  if (!str) return '—'
  return new Date(str).toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

onMounted(loadDemandes)
</script>

<style scoped>
.demandes-management {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.management-header h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 28px;
}

/* Stats */
.stats-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.stat-card {
  flex: 1;
  min-width: 100px;
  background: rgba(26,26,46,0.6);
  border: 1px solid #2e2e4a;
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.stat-card.pending { border-color: rgba(251,191,36,0.5); }
.stat-card.done    { border-color: rgba(16,185,129,0.5); }

.stat-value {
  font-size: 28px;
  font-weight: 900;
  color: #00d4ff;
}
.stat-card.pending .stat-value { color: #fbbf24; }
.stat-card.done    .stat-value { color: #10b981; }

.stat-label {
  font-size: 12px;
  color: #a0a0a0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

/* Filtres */
.filter-bar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(26,26,46,0.6);
  border: 1px solid #2e2e4a;
  color: #a0a0a0;
  padding: 8px 18px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}

.filter-btn:hover  { border-color: #00d4ff; color: #00d4ff; }
.filter-btn.active { background: rgba(0,212,255,0.15); border-color: #00d4ff; color: #00d4ff; }

.filter-badge {
  background: #fbbf24;
  color: #000;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
}

/* Table */
.table-wrapper {
  background: rgba(26,26,46,0.5);
  border: 1px solid #2e2e4a;
  border-radius: 12px;
  overflow-x: auto;
}

.state-msg {
  padding: 40px;
  text-align: center;
  color: #a0a0a0;
}

.demandes-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 700px;
}

.demandes-table th {
  background: rgba(0,212,255,0.08);
  color: #00d4ff;
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #2e2e4a;
}

.demandes-table td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(46,46,74,0.5);
  color: #e0e0e0;
  font-size: 13px;
  vertical-align: middle;
}

.demandes-table tr:last-child td { border-bottom: none; }
.demandes-table tr.row-done td   { opacity: 0.55; }

.td-id    { color: #a0a0a0; font-weight: 700; font-family: monospace; }
.td-date  { color: #a0a0a0; white-space: nowrap; }

.td-client { display: flex; flex-direction: column; gap: 2px; }
.client-nom   { font-weight: 600; color: #e0e0e0; }
.client-email { font-size: 12px; color: #00d4ff; text-decoration: none; }
.client-email:hover { text-decoration: underline; }

.td-product { font-weight: 600; }

/* Thumbnails */
.thumbnails {
  display: flex;
  gap: 4px;
  align-items: center;
}

.thumb {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #2e2e4a;
}

.more-badge {
  font-size: 11px;
  font-weight: 700;
  color: #a0a0a0;
  background: rgba(46,46,74,0.8);
  padding: 2px 6px;
  border-radius: 10px;
}

/* Status badge */
.status-badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.status-badge.pending { background: rgba(251,191,36,0.15); color: #fbbf24; }
.status-badge.done    { background: rgba(16,185,129,0.15);  color: #10b981; }

/* Actions */
.td-actions { display: flex; gap: 6px; }

.action-btn {
  background: none;
  border: 1px solid #2e2e4a;
  border-radius: 6px;
  padding: 6px 9px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}
.action-btn.view:hover   { border-color: #00d4ff; background: rgba(0,212,255,0.1); }
.action-btn.delete:hover { border-color: #ff6b6b; background: rgba(255,107,107,0.1); }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.modal {
  background: linear-gradient(135deg,#1a1a2e,#16213e);
  border: 2px solid #00d4ff;
  border-radius: 14px;
  padding: 28px;
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 { color: #00d4ff; margin: 0; font-size: 18px; }

.close-btn {
  background: none; border: none; color: #e0e0e0;
  font-size: 26px; cursor: pointer; line-height: 1;
}

.modal-section { display: flex; flex-direction: column; gap: 10px; }

.section-title {
  color: #00d4ff;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0;
}

/* Info grid */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.info-item {
  background: rgba(0,0,0,0.2);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.info-label { font-size: 11px; color: #a0a0a0; font-weight: 600; text-transform: uppercase; }
.info-value { font-size: 14px; color: #e0e0e0; font-weight: 500; }
.info-link  { font-size: 14px; color: #00d4ff; text-decoration: none; }
.info-link:hover { text-decoration: underline; }

/* Gallery */
.gallery {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.gallery-item {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #2e2e4a;
  flex-shrink: 0;
}

.gallery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.gallery-item:hover .gallery-img { transform: scale(1.08); }

.gallery-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  opacity: 0;
  transition: opacity 0.2s;
}

.gallery-item:hover .gallery-overlay { opacity: 1; }

/* Form */
.form-row-modal { display: flex; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; flex: 1; }
.form-group label { color: #e0e0e0; font-size: 13px; font-weight: 600; }

.form-select, .form-textarea {
  background: rgba(26,26,46,0.8);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 10px 12px;
  color: #e0e0e0;
  width: 100%;
  box-sizing: border-box;
  font-size: 13px;
  font-family: inherit;
}

.form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #00d4ff;
}

.form-textarea { resize: vertical; }

/* Modal actions */
.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn {
  padding: 10px 22px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  border: none;
}
.btn:hover { transform: translateY(-1px); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-secondary { background: #2e2e4a; color: #e0e0e0; }
.btn-primary   { background: linear-gradient(45deg,#00d4ff,#4ecdc4); color: #000; }

/* Lightbox */
.lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 20000;
  cursor: zoom-out;
  padding: 20px;
}

.lightbox-img {
  max-width: 100%;
  max-height: 90vh;
  object-fit: contain;
  border-radius: 8px;
}

.lightbox-close {
  position: absolute;
  top: 20px;
  right: 28px;
  background: none;
  border: none;
  color: white;
  font-size: 36px;
  cursor: pointer;
  line-height: 1;
}

/* Responsive */
@media (max-width: 768px) {
  .info-grid { grid-template-columns: 1fr; }
  .stats-row { gap: 10px; }
  .stat-card { padding: 14px; }
  .stat-value { font-size: 22px; }
  .modal { padding: 18px; }
  .gallery-item { width: 80px; height: 80px; }
}

@media (max-width: 480px) {
  .management-header h2 { font-size: 22px; }
  .modal { padding: 14px; }
  .modal-actions { flex-direction: column; }
  .btn { width: 100%; }
}
</style>
