import { ref } from 'vue'

// Module-level state: a single toast queue shared across every component
// that calls useToast(). Mount <AppToast /> once in the root layout.
const toast = ref(null)
let timer = null

export function useToast() {
  const show = (message, type = 'success', duration = 3400) => {
    clearTimeout(timer)
    toast.value = { message, type }
    timer = setTimeout(() => {
      toast.value = null
    }, duration)
  }

  const dismiss = () => {
    clearTimeout(timer)
    toast.value = null
  }

  return { toast, show, dismiss }
}