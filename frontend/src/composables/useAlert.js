import { createApp, h } from 'vue'
import Alert from '../components/Alert.vue'

let alertApp = null
let alertContainer = null

const createAlertContainer = () => {
  if (!alertContainer) {
    alertContainer = document.createElement('div')
    document.body.appendChild(alertContainer)
  }
  return alertContainer
}

const showAlert = (options) => {
  return new Promise((resolve) => {
    // Créer le conteneur si nécessaire
    const container = createAlertContainer()
    
    // Nettoyer l'instance précédente si elle existe
    if (alertApp) {
      alertApp.unmount()
      alertApp = null
    }

    // Créer une nouvelle instance
    let visible = true
    
    const handleClose = () => {
      visible = false
      if (alertApp) {
        alertApp.unmount()
        alertApp = null
      }
      if (container && container.parentNode) {
        container.parentNode.removeChild(container)
        alertContainer = null
      }
      resolve(false)
    }

    const handleConfirm = () => {
      visible = false
      if (alertApp) {
        alertApp.unmount()
        alertApp = null
      }
      if (container && container.parentNode) {
        container.parentNode.removeChild(container)
        alertContainer = null
      }
      resolve(true)
    }

    const handleCancel = () => {
      visible = false
      if (alertApp) {
        alertApp.unmount()
        alertApp = null
      }
      if (container && container.parentNode) {
        container.parentNode.removeChild(container)
        alertContainer = null
      }
      resolve(false)
    }

    alertApp = createApp({
      render: () => h(Alert, {
        visible: visible,
        type: options.type || 'info',
        title: options.title || '',
        message: options.message || '',
        confirmText: options.confirmText || 'Confirmer',
        cancelText: options.cancelText || 'Annuler',
        closeOnOverlay: options.closeOnOverlay || false,
        autoClose: options.autoClose !== false,
        duration: options.duration || 3000,
        onClose: handleClose,
        onConfirm: handleConfirm,
        onCancel: handleCancel,
        'onUpdate:visible': (val) => {
          visible = val
        }
      })
    })

    alertApp.mount(container)
  })
}

export const useAlert = () => {
  const alert = (message, options = {}) => {
    return showAlert({
      type: 'info',
      message,
      autoClose: true,
      ...options
    })
  }

  const success = (message, options = {}) => {
    return showAlert({
      type: 'success',
      message,
      autoClose: true,
      ...options
    })
  }

  const error = (message, options = {}) => {
    return showAlert({
      type: 'error',
      message,
      autoClose: false,
      ...options
    })
  }

  const warning = (message, options = {}) => {
    return showAlert({
      type: 'warning',
      message,
      autoClose: false,
      ...options
    })
  }

  const info = (message, options = {}) => {
    return showAlert({
      type: 'info',
      message,
      autoClose: true,
      ...options
    })
  }

  const confirm = (message, options = {}) => {
    return showAlert({
      type: 'confirm',
      message,
      autoClose: false,
      ...options
    })
  }

  return {
    alert,
    success,
    error,
    warning,
    info,
    confirm
  }
}

// Export global pour utilisation simple
export const showAlertSuccess = (message, options = {}) => {
  const { success } = useAlert()
  return success(message, options)
}

export const showAlertError = (message, options = {}) => {
  const { error } = useAlert()
  return error(message, options)
}

export const showAlertWarning = (message, options = {}) => {
  const { warning } = useAlert()
  return warning(message, options)
}

export const showAlertInfo = (message, options = {}) => {
  const { info } = useAlert()
  return info(message, options)
}

export const showAlertConfirm = (message, options = {}) => {
  const { confirm } = useAlert()
  return confirm(message, options)
}

