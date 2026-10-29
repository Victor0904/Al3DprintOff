<template>
  <Teleport to="body">
    <Transition name="alert">
      <div v-if="visible" class="alert-overlay" @click.self="handleOverlayClick">
        <div class="alert-container" :class="alertTypeClass">
          <div class="alert-icon">
            <span v-if="type === 'success'">✓</span>
            <span v-else-if="type === 'error'">✕</span>
            <span v-else-if="type === 'warning'">⚠</span>
            <span v-else-if="type === 'info'">ℹ</span>
            <span v-else-if="type === 'confirm'">?</span>
          </div>
          <div class="alert-content">
            <h3 v-if="title" class="alert-title">{{ title }}</h3>
            <div class="alert-message" v-html="formattedMessage"></div>
          </div>
          <div v-if="type === 'confirm'" class="alert-actions">
            <button class="alert-btn alert-btn-cancel" @click="handleCancel">
              {{ cancelText }}
            </button>
            <button class="alert-btn alert-btn-confirm" @click="handleConfirm">
              {{ confirmText }}
            </button>
          </div>
          <button v-else class="alert-close" @click="handleClose">✕</button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info', 'confirm'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Confirmer'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  closeOnOverlay: {
    type: Boolean,
    default: false
  },
  autoClose: {
    type: Boolean,
    default: false
  },
  duration: {
    type: Number,
    default: 3000
  }
})

const emit = defineEmits(['confirm', 'cancel', 'close', 'update:visible'])

const alertTypeClass = computed(() => `alert-${props.type}`)

const formattedMessage = computed(() => {
  return props.message.replace(/\n/g, '<br>')
})

let autoCloseTimer = null

const handleClose = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }
  emit('update:visible', false)
  emit('close')
}

const handleConfirm = () => {
  emit('confirm')
  handleClose()
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    handleClose()
  }
}

watch(() => props.visible, (newVal) => {
  if (newVal && props.autoClose && props.type !== 'confirm') {
    autoCloseTimer = setTimeout(() => {
      handleClose()
    }, props.duration)
  } else if (!newVal && autoCloseTimer) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }
})

onMounted(() => {
  if (props.visible && props.autoClose && props.type !== 'confirm') {
    autoCloseTimer = setTimeout(() => {
      handleClose()
    }, props.duration)
  }
})
</script>

<style scoped>
.alert-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.alert-container {
  background: linear-gradient(135deg, var(--dark-secondary) 0%, var(--dark-tertiary) 100%);
  border: 2px solid var(--border-color);
  border-radius: 16px;
  padding: 30px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  position: relative;
  animation: alertSlideIn 0.3s ease-out;
}

@keyframes alertSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.alert-success {
  border-color: var(--accent-color);
  box-shadow: 0 10px 40px rgba(78, 205, 196, 0.3);
}

.alert-error {
  border-color: var(--secondary-color);
  box-shadow: 0 10px 40px rgba(255, 107, 107, 0.3);
}

.alert-warning {
  border-color: #ffa500;
  box-shadow: 0 10px 40px rgba(255, 165, 0, 0.3);
}

.alert-info {
  border-color: var(--primary-color);
  box-shadow: 0 10px 40px var(--glow-color);
}

.alert-confirm {
  border-color: var(--primary-color);
  box-shadow: 0 10px 40px var(--glow-color);
}

.alert-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  font-weight: bold;
  margin: 0 auto 20px;
  animation: pulse 2s infinite;
}

.alert-success .alert-icon {
  background: rgba(78, 205, 196, 0.2);
  color: var(--accent-color);
  border: 2px solid var(--accent-color);
}

.alert-error .alert-icon {
  background: rgba(255, 107, 107, 0.2);
  color: var(--secondary-color);
  border: 2px solid var(--secondary-color);
}

.alert-warning .alert-icon {
  background: rgba(255, 165, 0, 0.2);
  color: #ffa500;
  border: 2px solid #ffa500;
}

.alert-info .alert-icon,
.alert-confirm .alert-icon {
  background: rgba(0, 212, 255, 0.2);
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
}

.alert-content {
  text-align: center;
  margin-bottom: 20px;
}

.alert-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 15px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.alert-message {
  font-size: 16px;
  line-height: 1.6;
  color: var(--text-primary);
  white-space: pre-wrap;
}

.alert-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 25px;
}

.alert-btn {
  padding: 12px 30px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 120px;
}

.alert-btn-cancel {
  background: transparent;
  color: var(--text-secondary);
  border: 2px solid var(--border-color);
}

.alert-btn-cancel:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--text-secondary);
  color: var(--text-primary);
  transform: translateY(-2px);
}

.alert-btn-confirm {
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  color: white;
  box-shadow: 0 4px 15px var(--glow-color);
}

.alert-btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px var(--glow-color);
}

.alert-close {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 35px;
  height: 35px;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: var(--text-secondary);
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.alert-close:hover {
  background: rgba(255, 107, 107, 0.2);
  color: var(--secondary-color);
  transform: rotate(90deg);
}

/* Animations */
.alert-enter-active,
.alert-leave-active {
  transition: opacity 0.3s ease;
}

.alert-enter-from,
.alert-leave-to {
  opacity: 0;
}

.alert-enter-active .alert-container,
.alert-leave-active .alert-container {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.alert-enter-from .alert-container,
.alert-leave-to .alert-container {
  transform: translateY(-20px) scale(0.95);
  opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .alert-container {
    padding: 25px 20px;
    max-width: 90%;
  }

  .alert-icon {
    width: 50px;
    height: 50px;
    font-size: 28px;
  }

  .alert-title {
    font-size: 20px;
  }

  .alert-message {
    font-size: 14px;
  }

  .alert-actions {
    flex-direction: column;
  }

  .alert-btn {
    width: 100%;
  }
}
</style>

