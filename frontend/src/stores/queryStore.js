import { defineStore } from 'pinia'
import * as service from '@/services/queryService'

export const QUERY_STATUSES = [
  { value: 'new', label: 'New' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'resolved', label: 'Resolved' },
]

export const useQueryStore = defineStore('queries', {
  state: () => ({
    queries: [],
    loading: false,
    error: '',
    search: '',
    status: '',
    pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    latestRequest: 0,
  }),

  actions: {
    async fetchQueries() {
      const request = ++this.latestRequest
      this.loading = true
      this.error = ''

      try {
        const response = await service.getQueries({
          search: this.search,
          status: this.status,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
        })

        if (request === this.latestRequest) {
          this.queries = response.data.data
          this.pagination = response.data.meta
        }
      } catch (error) {
        if (request === this.latestRequest) {
          this.error = error.response?.data?.message || 'Could not load queries.'
        }
      } finally {
        if (request === this.latestRequest) this.loading = false
      }
    },

    async setSearch(value) {
      this.search = value
      this.pagination.current_page = 1
      await this.fetchQueries()
    },

    async setStatus(value) {
      this.status = value
      this.pagination.current_page = 1
      await this.fetchQueries()
    },

    async setPage(page) {
      this.pagination.current_page = page
      await this.fetchQueries()
    },

    async deleteQuery(id) {
      await service.deleteQuery(id)
      await this.fetchQueries()
    },
  },
})
