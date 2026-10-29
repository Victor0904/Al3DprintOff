<template>
  <div class="promo-management">
    <div class="management-header">
      <h2>🎟️ Codes Promotionnels</h2>
      <button @click="openAddModal" class="add-btn">➕ Ajouter un code</button>
    </div>

    <!-- Tableau des codes -->
    <div class="table-wrapper">
      <div v-if="loading" class="loading-state">Chargement…</div>
      <div v-else-if="codes.length === 0" class="empty-state">
        Aucun code promo. Créez-en un avec le bouton ci-dessus.
      </div>
      <table v-else class="promo-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Remise</th>
            <th>Statut</th>
            <th>Créé le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="code in codes" :key="code.id" :class="{ inactive: !code.actif }">
            <td><span class="code-chip">{{ code.code }}</span></td>
            <td class="remise-cell">-{{ code.remise }}%</td>
            <td>
              <span :class="['status-badge', code.actif ? 'active' : 'paused']">
                {{ code.actif ? '✅ Actif' : '⏸️ Pausé' }}
              </span>
            </td>
            <td class="date-cell">{{ formatDate(code.date_creation) }}</td>
            <td class="actions-cell">
              <button
                @click="toggleCode(code)"
                class="action-btn toggle"
                :title="code.actif ? 'Mettre en pause' : 'Activer'"
              >
                {{ code.actif ? '⏸️' : '▶️' }}
              </button>
              <button @click="editCode(code)" class="action-btn edit" title="Modifier">✏️</button>
              <button @click="deleteCode(code.id)" class="action-btn delete" title="Supprimer">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Ajout / Modification -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editingCode ? 'Modifier le code' : 'Nouveau code promo' }}</h3>
          <button @click="closeModal" class="close-btn">×</button>
        </div>

        <form @submit.prevent="saveCode" class="modal-form">
          <div class="form-group">
            <label>Code promo *</label>
            <input
              v-model="form.code"
              type="text"
              class="form-input"
              placeholder="EX: SUMMER20"
              required
              @input="form.code = form.code.toUpperCase()"
            >
            <span class="hint">Sera automatiquement mis en majuscules</span>
          </div>

          <div class="form-group">
            <label>Remise (%) *</label>
            <div class="input-suffix-wrapper">
              <input
                v-model.number="form.remise"
                type="number"
                class="form-input"
                min="1"
                max="100"
                step="0.01"
                placeholder="10"
                required
              >
              <span class="input-suffix">%</span>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input v-model="form.actif" type="checkbox" class="checkbox">
              Code actif
            </label>
          </div>

          <div v-if="formError" class="form-error">{{ formError }}</div>

          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Enregistrement…' : (editingCode ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthFetch } from '../../composables/useAuthFetch'
import { useAlert } from '../../composables/useAlert'

const { apiFetch } = useAuthFetch()
const { confirm: showConfirm } = useAlert()

const codes    = ref([])
const loading  = ref(true)
const showModal = ref(false)
const editingCode = ref(null)
const saving   = ref(false)
const formError = ref('')

const defaultForm = () => ({ code: '', remise: 10, actif: true })
const form = ref(defaultForm())

const loadCodes = async () => {
  loading.value = true
  try {
    const res = await apiFetch('/api/code-promo')
    if (res.ok) codes.value = await res.json()
  } catch { /* silently */ }
  finally { loading.value = false }
}

const openAddModal = () => {
  editingCode.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}

const editCode = (code) => {
  editingCode.value = code
  form.value = { code: code.code, remise: code.remise, actif: code.actif }
  formError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingCode.value = null
  form.value = defaultForm()
  formError.value = ''
}

const saveCode = async () => {
  saving.value = true
  formError.value = ''
  try {
    const payload = { ...form.value }
    const url    = editingCode.value ? `/api/code-promo/${editingCode.value.id}` : '/api/code-promo'
    const method = editingCode.value ? 'PUT' : 'POST'
    const res    = await apiFetch(url, { method, body: JSON.stringify(payload) })
    const data   = await res.json()
    if (!res.ok) {
      formError.value = data.error || 'Erreur lors de l\'enregistrement'
      return
    }
    if (editingCode.value) {
      const idx = codes.value.findIndex(c => c.id === editingCode.value.id)
      if (idx !== -1) codes.value[idx] = data
    } else {
      codes.value.unshift(data)
    }
    closeModal()
  } catch {
    formError.value = 'Erreur réseau'
  } finally {
    saving.value = false
  }
}

const toggleCode = async (code) => {
  const res  = await apiFetch(`/api/code-promo/${code.id}`, {
    method: 'PUT',
    body: JSON.stringify({ actif: !code.actif })
  })
  if (res.ok) {
    const data = await res.json()
    const idx  = codes.value.findIndex(c => c.id === code.id)
    if (idx !== -1) codes.value[idx] = data
  }
}

const deleteCode = async (id) => {
  const confirmed = await showConfirm('Supprimer ce code promo ?')
  if (!confirmed) return
  const res = await apiFetch(`/api/code-promo/${id}`, { method: 'DELETE' })
  if (res.ok) codes.value = codes.value.filter(c => c.id !== id)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(loadCodes)
</script>

<style scoped>
.promo-management {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.management-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.management-header h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 28px;
}

.add-btn {
  background: linear-gradient(45deg, #00d4ff, #4ecdc4);
  color: #000;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
  transition: all 0.3s;
}
.add-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,212,255,0.4); }

/* Table */
.table-wrapper {
  background: rgba(26,26,46,0.6);
  border: 1px solid #2e2e4a;
  border-radius: 12px;
  overflow: hidden;
}

.loading-state, .empty-state {
  padding: 40px;
  text-align: center;
  color: #a0a0a0;
}

.promo-table {
  width: 100%;
  border-collapse: collapse;
}

.promo-table th {
  background: rgba(0,212,255,0.08);
  color: #00d4ff;
  padding: 14px 20px;
  text-align: left;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #2e2e4a;
}

.promo-table td {
  padding: 14px 20px;
  color: #e0e0e0;
  border-bottom: 1px solid rgba(46,46,74,0.5);
  font-size: 14px;
}

.promo-table tr:last-child td { border-bottom: none; }
.promo-table tr.inactive td  { opacity: 0.5; }

.code-chip {
  background: rgba(0,212,255,0.12);
  color: #00d4ff;
  font-family: monospace;
  font-size: 14px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
  letter-spacing: 1px;
}

.remise-cell {
  color: #4ecdc4;
  font-weight: 700;
  font-size: 16px;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge.active { background: rgba(78,205,196,0.15); color: #4ecdc4; }
.status-badge.paused { background: rgba(255,193,7,0.15);  color: #ffc107; }

.date-cell { color: #a0a0a0; font-size: 13px; }

.actions-cell { display: flex; gap: 8px; }

.action-btn {
  background: none;
  border: 1px solid #2e2e4a;
  border-radius: 6px;
  padding: 7px 10px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}
.action-btn.edit:hover   { border-color: #00d4ff; background: rgba(0,212,255,0.1); }
.action-btn.toggle:hover { border-color: #4ecdc4; background: rgba(78,205,196,0.1); }
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
  padding: 30px;
  width: 100%;
  max-width: 460px;
  box-sizing: border-box;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.modal-header h3 { color: #00d4ff; margin: 0; }

.close-btn {
  background: none; border: none; color: #e0e0e0;
  font-size: 24px; cursor: pointer;
}

.modal-form { display: flex; flex-direction: column; gap: 18px; }

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { color: #e0e0e0; font-weight: 600; font-size: 14px; }

.hint { color: #a0a0a0; font-size: 11px; }

.form-input {
  background: rgba(26,26,46,0.8);
  border: 1px solid #2e2e4a;
  border-radius: 8px;
  padding: 12px;
  color: #e0e0e0;
  width: 100%;
  box-sizing: border-box;
}
.form-input:focus { outline: none; border-color: #00d4ff; }

.input-suffix-wrapper { position: relative; }
.input-suffix-wrapper .form-input { padding-right: 36px; }
.input-suffix {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0a0a0;
  font-size: 14px;
  pointer-events: none;
}

.checkbox-label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.checkbox { width: 17px; height: 17px; }

.form-error {
  color: #ff6b6b;
  font-size: 13px;
  background: rgba(255,107,107,0.1);
  border: 1px solid rgba(255,107,107,0.3);
  border-radius: 6px;
  padding: 10px 14px;
}

.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 4px; }

.btn {
  padding: 11px 22px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn:hover { transform: translateY(-1px); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-secondary { background: #2e2e4a; color: #e0e0e0; border: 1px solid #2e2e4a; }
.btn-primary   { background: linear-gradient(45deg,#00d4ff,#4ecdc4); color: #000; border: none; }
</style>
