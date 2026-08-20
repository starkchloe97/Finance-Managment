<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCustomerStore } from '@/stores/customerStore'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import CustomerTable from '@/components/customers/CustomerTable.vue'
import CustomerCard from '@/components/customers/CustomerCard.vue'
import { money } from '@/utils/money'

const store = useCustomerStore()
const { customers, loading, error, search, pagination } = storeToRefs(store)

const deleting = ref(null)
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 820
}

const searchTimer = ref(null)
const onSearch = (value) => {
  search.value = value
  clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(() => store.setSearch(value), 300)
}

const sortOptions = [
  { value: 'latest', label: 'Newest' },
  { value: 'name', label: 'Name' },
]

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  store.setSearch(store.search)
})

onUnmounted(() => {
  clearTimeout(searchTimer.value)
  window.removeEventListener('resize', checkMobile)
})

const confirmDelete = (customer) => {
  deleting.value = customer
}

const doDelete = async () => {
  if (!deleting.value) return
  await store.deleteCustomer(deleting.value.id)
  deleting.value = null
}
</script>

<template>
  <div>
    <div class="page-head">
      <h1>Customers</h1>
      <RouterLink class="btn" to="/customers/create">+ New Customer</RouterLink>
    </div>

    <div class="card">
      <div class="toolbar">
        <SearchInput
          :model-value="search"
          placeholder="Search by name, phone or company…"
          @update:model-value="onSearch"
        />
        <FilterSelect
          :model-value="store.sort"
          :options="sortOptions"
          placeholder="Sort"
          @update:model-value="store.setSort"
        />
      </div>

      <StatePanel
        :loading="loading && !customers.length"
        :error="error"
        :empty="!loading && !error && !customers.length"
        empty-title="No customers yet — create your first customer to start quoting jobs."
        empty-action="Create customer"
        empty-to="/customers/create"
      >
        <!-- Desktop table -->
        <template v-if="!isMobile">
          <CustomerTable :customers="customers" @delete="confirmDelete" />
        </template>

        <!-- Mobile card grid -->
        <div v-else class="entity-card-grid">
          <CustomerCard
            v-for="customer in customers"
            :key="customer.id"
            :customer="customer"
            @delete="confirmDelete"
          />
        </div>

        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="store.setPage"
        />
      </StatePanel>
    </div>

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Delete customer?"
      :message="deleting ? `Delete ${deleting.name}? This cannot be undone.` : ''"
      confirm-label="Delete"
      variant="danger"
      @confirm="doDelete"
      @cancel="deleting = null"
    />
  </div>
</template>
