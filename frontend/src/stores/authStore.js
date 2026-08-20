import { defineStore } from 'pinia'
import { login, logout, me } from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
  },

  actions: {
    async login(credentials) {
      this.loading = true

      try {
        const response = await login(credentials)

        this.user = response.data.user
        this.token = response.data.token

        localStorage.setItem('token', this.token)

        return true
      } finally {
        this.loading = false
      }
    },

    async getUser() {
      if (!this.token) return

      try {
        const response = await me()
        this.user = response.data
      } catch (e) {
        this.logout()
      }
    },

    async logout() {
      try {
        await logout()
      } catch (e) {
        // Handle error
      }

      this.clearSession()
    },

    clearSession() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
    },
  },
})
