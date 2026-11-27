import { ref } from 'vue'

// Module-level state: a single toast queue shared across every component
// that calls useToast(). Mount <AppToast /> once in the root layout.
const toast = ref(null)
let timer = null

export function useToast() {
  const show = (message, type = 'success', duration, title = '') => {
    clearTimeout(timer)
    const timeout = duration ?? (type === 'error' ? 7000 : 3400)
    toast.value = { message, type, title }
    timer = setTimeout(() => {
      toast.value = null
    }, timeout)
  }

  const dismiss = () => {
    clearTimeout(timer)
    toast.value = null
  }

  return { toast, show, dismiss }
}
