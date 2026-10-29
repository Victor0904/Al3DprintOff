<template>
  <div class="users-management">
    <h2>👥 Gestion des Utilisateurs</h2>
    <div class="users-table">
      <div class="table-header">
        <div class="col-id">ID</div>
        <div class="col-name">Nom</div>
        <div class="col-email">Email</div>
        <div class="col-date">Date d'inscription</div>
        <div class="col-actions">Actions</div>
      </div>
      <div v-for="user in users" :key="user.id" class="table-row">
        <div class="col-id">{{ user.id }}</div>
        <div class="col-name">{{ user.nom }} {{ user.prenom }}</div>
        <div class="col-email">{{ user.email }}</div>
        <div class="col-date">{{ formatDate(user.date_inscription) }}</div>
        <div class="col-actions">
          <button @click="viewUser(user)" class="action-btn view">👁️</button>
          <button @click="deleteUser(user.id)" class="action-btn delete">🗑️</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAlert } from '../../composables/useAlert'
import { useAuthFetch } from '../../composables/useAuthFetch'

const users = ref([])
const { info: showInfo, confirm: showConfirm } = useAlert()
const { apiFetch } = useAuthFetch()

const loadUsers = async () => {
  try {
    const response = await apiFetch('/api/clients')
    if (response.ok) {
      users.value = await response.json()
    }
  } catch (error) {
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const viewUser = async (user) => {
  await showInfo(`Utilisateur: ${user.nom} ${user.prenom}\nEmail: ${user.email}`)
}

const deleteUser = async (userId) => {
  const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')
  if (confirmed) {
    try {
      const response = await apiFetch(`/api/clients/${userId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        users.value = users.value.filter(u => u.id !== userId)
      }
    } catch (error) {
    }
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
.users-management {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.users-management h2 {
  color: #00d4ff;
  margin: 0;
  font-size: 28px;
}

.users-table {
  background: rgba(26, 26, 46, 0.5);
  border-radius: 10px;
  border: 1px solid #2e2e4a;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 60px 2fr 2fr 150px 120px;
  gap: 15px;
  padding: 20px;
  background: rgba(0, 212, 255, 0.1);
  border-bottom: 1px solid #2e2e4a;
  font-weight: 600;
  color: #00d4ff;
}

.table-row {
  display: grid;
  grid-template-columns: 60px 2fr 2fr 150px 120px;
  gap: 15px;
  padding: 20px;
  border-bottom: 1px solid #2e2e4a;
  align-items: center;
}

.table-row:hover {
  background: rgba(0, 212, 255, 0.05);
}

.col-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: none;
  border: 1px solid #2e2e4a;
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.action-btn.view:hover {
  border-color: #4ecdc4;
  background: rgba(78, 205, 196, 0.1);
}

.action-btn.delete:hover {
  border-color: #ff6b6b;
  background: rgba(255, 107, 107, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
  .users-management h2 {
    font-size: 22px;
  }
  
  .users-table {
    overflow-x: auto;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 50px 1.5fr 1.5fr 120px 100px;
    gap: 10px;
    padding: 15px;
    font-size: 13px;
  }
  
  .action-btn {
    padding: 6px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .users-management h2 {
    font-size: 20px;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 12px;
  }
  
  .table-header {
    display: none;
  }
  
  .table-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border: 1px solid #2e2e4a;
    border-radius: 8px;
    margin-bottom: 10px;
  }
  
  .col-id::before {
    content: "ID: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-name::before {
    content: "Nom: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-email::before {
    content: "Email: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-date::before {
    content: "Date d'inscription: ";
    font-weight: 600;
    color: #00d4ff;
  }
  
  .col-actions::before {
    content: "Actions: ";
    font-weight: 600;
    color: #00d4ff;
    display: block;
    margin-bottom: 5px;
  }
  
  .col-actions {
    flex-direction: row;
    justify-content: flex-start;
  }
}
</style>
