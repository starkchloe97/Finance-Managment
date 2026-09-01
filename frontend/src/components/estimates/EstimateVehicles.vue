<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const vehicles = computed(() => {
  return props.items.flatMap((item) => {
    return (item.vehicles || []).map((vehicle) => ({
      ...vehicle,
      itemTitle: item.title,
    }))
  })
})

const companyVehicle = (vehicle) => {
  return vehicle.source === 'company' ? vehicle.asset : null
}

const display = (value) => {
  return value !== null && value !== undefined && value !== ''
    ? value
    : '—'
}
</script>

<template>
  <div class="vehicles-section">
    <div v-if="!vehicles.length" class="vehicles-empty">
      <div class="empty-icon" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M5 17h14" />
          <path d="M6 17l1-6h10l1 6" />
          <path d="M7 11l1.5-4h7L17 11" />
          <circle cx="8" cy="17" r="1.5" />
          <circle cx="16" cy="17" r="1.5" />
        </svg>
      </div>

      <strong>No vehicles assigned</strong>
      <p>
        This estimate does not currently have any vehicle requirements.
      </p>
    </div>

    <div v-else class="vehicle-list">
      <article
        v-for="(vehicle, index) in vehicles"
        :key="vehicle.id || `${vehicle.itemTitle}-${index}`"
        class="vehicle-card"
      >
        <div class="vehicle-card-head">
          <div class="vehicle-heading">
            <div class="vehicle-icon" aria-hidden="true">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M5 17h14" />
                <path d="M6 17l1-6h10l1 6" />
                <path d="M7 11l1.5-4h7L17 11" />
                <circle cx="8" cy="17" r="1.5" />
                <circle cx="16" cy="17" r="1.5" />
              </svg>
            </div>

            <div>
              <span class="vehicle-number">
                Vehicle {{ index + 1 }}
              </span>

              <h3>
                {{
                  vehicle.source === 'company'
                    ? display(companyVehicle(vehicle)?.name)
                    : display(vehicle.vehicle_name)
                }}
              </h3>

              <span class="vehicle-item">
                For: {{ vehicle.itemTitle }}
              </span>
            </div>
          </div>

          <span
            class="source-badge"
            :class="vehicle.source === 'company'
              ? 'source-company'
              : 'source-hired'"
          >
            {{ vehicle.source === 'company' ? 'Company' : 'Hired' }}
          </span>
        </div>

        <div class="vehicle-details">
          <div class="vehicle-detail">
            <span>Registration</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.registration_number)
                  : display(vehicle.registration_number)
              }}
            </strong>
          </div>

          <div
            v-if="vehicle.source === 'company'"
            class="vehicle-detail"
          >
            <span>Asset code</span>
            <strong>
              {{ display(companyVehicle(vehicle)?.asset_code) }}
            </strong>
          </div>

          <div class="vehicle-detail">
            <span>Make</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.make)
                  : display(vehicle.make)
              }}
            </strong>
          </div>

          <div class="vehicle-detail">
            <span>Model</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.model)
                  : display(vehicle.model)
              }}
            </strong>
          </div>

          <div class="vehicle-detail">
            <span>Year</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.model_year)
                  : display(vehicle.model_year)
              }}
            </strong>
          </div>

          <div class="vehicle-detail">
            <span>Vehicle type</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.vehicle_type)
                  : display(vehicle.vehicle_type)
              }}
            </strong>
          </div>

          <div class="vehicle-detail">
            <span>Color</span>
            <strong>
              {{
                vehicle.source === 'company'
                  ? display(companyVehicle(vehicle)?.color)
                  : display(vehicle.color)
              }}
            </strong>
          </div>

          <div
            v-if="vehicle.source === 'hired'"
            class="vehicle-detail"
          >
            <span>VIN</span>
            <strong>{{ display(vehicle.vin) }}</strong>
          </div>

          <div
            v-if="vehicle.source === 'hired'"
            class="vehicle-detail"
          >
            <span>Engine number</span>
            <strong>{{ display(vehicle.engine_number) }}</strong>
          </div>
        </div>

        <div
          v-if="vehicle.notes"
          class="vehicle-notes"
        >
          <span>Notes</span>
          <p>{{ vehicle.notes }}</p>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.vehicles-section {
  min-width: 0;
}

.vehicle-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vehicle-card {
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.vehicle-card-head {
  align-items: flex-start;
  display: flex;
  gap: 16px;
  justify-content: space-between;
  padding: 16px;
}

.vehicle-heading {
  align-items: center;
  display: flex;
  gap: 12px;
  min-width: 0;
}

.vehicle-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 10px;
  color: var(--accent);
  display: flex;
  flex: 0 0 40px;
  height: 40px;
  justify-content: center;
  width: 40px;
}

.vehicle-icon svg {
  height: 20px;
  width: 20px;
}

.vehicle-number {
  color: var(--text-muted);
  display: block;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.vehicle-heading h3 {
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 600;
  margin: 2px 0;
}

.vehicle-item {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
}

.source-badge {
  border-radius: 999px;
  flex: 0 0 auto;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  padding: 5px 9px;
  text-transform: uppercase;
}

.source-company {
  background: var(--accent-soft);
  color: var(--accent);
}

.source-hired {
  background: var(--warning-soft);
  color: var(--warning);
}

.vehicle-details {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.vehicle-detail {
  border-bottom: 1px solid var(--border);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  padding: 12px 16px;
}

.vehicle-detail:nth-child(4n) {
  border-right: 0;
}

.vehicle-detail span {
  color: var(--text-muted);
  font-size: 11px;
}

.vehicle-detail strong {
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.vehicle-notes {
  background: var(--surface-subtle, var(--background-secondary));
  border-top: 1px solid var(--border);
  padding: 12px 16px;
}

.vehicle-notes span {
  color: var(--text-muted);
  display: block;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 4px;
}

.vehicle-notes p {
  color: var(--text-secondary);
  font-size: 13px;
  margin: 0;
}

.vehicles-empty {
  align-items: center;
  display: flex;
  flex-direction: column;
  padding: 48px 20px;
  text-align: center;
}

.empty-icon {
  align-items: center;
  background: var(--surface-subtle, var(--background-secondary));
  border-radius: 12px;
  color: var(--text-muted);
  display: flex;
  height: 44px;
  justify-content: center;
  margin-bottom: 12px;
  width: 44px;
}

.empty-icon svg {
  height: 22px;
  width: 22px;
}

.vehicles-empty strong {
  color: var(--text-primary);
  font-size: 14px;
}

.vehicles-empty p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 5px 0 0;
}

@media (max-width: 800px) {
  .vehicle-details {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .vehicle-detail:nth-child(4n) {
    border-right: 1px solid var(--border);
  }

  .vehicle-detail:nth-child(2n) {
    border-right: 0;
  }
}

@media (max-width: 520px) {
  .vehicle-card-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .vehicle-details {
    grid-template-columns: 1fr;
  }

  .vehicle-detail,
  .vehicle-detail:nth-child(2n),
  .vehicle-detail:nth-child(4n) {
    border-right: 0;
  }
}
</style>