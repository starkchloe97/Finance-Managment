import { defineStore } from 'pinia'
import * as service from '@/services/customerService'

// Enough to fill the estimate form's customer dropdown in one request. The API
// caps per_page at 100; past that the dropdown needs to become searchable.
const OPTIONS_PAGE_SIZE = 100

export const useCustomerStore = defineStore('customers', {
  state: () => ({
    customers: [],
    loading: false,
    error: '',
    search: '',
    sort: 'latest',
    // Laravel paginator meta
    pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    // Search runs on every keystroke, so responses can land out of order.
    // Only the newest request is allowed to write to the list.
    latestRequest: 0,
  }),

  actions: {
    async fetchCustomers() {
      const request = ++this.latestRequest

      this.loading = true
      this.error = ''

      try {
        const response = await service.getCustomers({
          search: this.search,
          sort: this.sort,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
        })

        if (request === this.latestRequest) {
          this.customers = response.data.data
          this.pagination = response.data.meta
        }
      } catch (error) {
        if (request === this.latestRequest) {
          this.error = error.response?.data?.message || 'Could not load customers.'
        }
      } finally {
        if (request === this.latestRequest) {
          this.loading = false
        }
      }
    },

    async setSearch(value) {
      this.search = value
      this.pagination.current_page = 1
      await this.fetchCustomers()
    },

    async setSort(value) {
      this.sort = value
      this.pagination.current_page = 1
      await this.fetchCustomers()
    },

    async setPage(page) {
      this.pagination.current_page = page
      await this.fetchCustomers()
    },

    async deleteCustomer(id) {
      await service.deleteCustomer(id)
      await this.fetchCustomers()
    },

    /**
     * Every customer, for the estimate form's dropdown — unfiltered and
     * unpaginated, unlike the list above.
     */
    async loadOptions() {
      const request = ++this.latestRequest

      const response = await service.getCustomers({ per_page: OPTIONS_PAGE_SIZE })

      if (request === this.latestRequest) {
        this.customers = response.data.data
      }
    },
  },
})
