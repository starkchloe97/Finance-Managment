<script setup>
import InfoTip from '@/components/ui/InfoTip.vue'

defineProps({
  form: { type: Object, required: true },
  fieldError: { type: Function, default: () => '' },
  submitting: Boolean,
})
</script>

<template>
  <section class="form-section">
    <div class="section-heading">
      <h3>Profile details</h3>
      <p>Identity and contact information your team will recognize.</p>
    </div>

    <div class="grid">
      <div class="field">
        <label for="investor-name">Name</label>
        <input
          id="investor-name"
          v-model="form.name"
          type="text"
          autocomplete="name"
          required
          :disabled="submitting"
          :aria-invalid="fieldError('name') ? 'true' : undefined"
          aria-describedby="investor-name-error"
        />
        <small v-if="fieldError('name')" id="investor-name-error" class="error">
          {{ fieldError('name') }}
        </small>
      </div>

      <div class="field">
        <label for="investor-email">Email</label>
        <input
          id="investor-email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          :disabled="submitting"
          :aria-invalid="fieldError('email') ? 'true' : undefined"
          aria-describedby="investor-email-error"
        />
        <small v-if="fieldError('email')" id="investor-email-error" class="error">
          {{ fieldError('email') }}
        </small>
      </div>

      <div class="field">
        <label for="investor-phone">Phone</label>
        <input
          id="investor-phone"
          v-model="form.phone"
          type="tel"
          autocomplete="tel"
          :disabled="submitting"
          :aria-invalid="fieldError('phone') ? 'true' : undefined"
          aria-describedby="investor-phone-error"
        />
        <small v-if="fieldError('phone')" id="investor-phone-error" class="error">
          {{ fieldError('phone') }}
        </small>
      </div>

      <div class="field">
        <label for="investor-status">
          Status
          <InfoTip label="Active investors can receive new investments and loans. Inactive pauses new activity — existing records are never touched." />
        </label>
        <select id="investor-status" v-model="form.status" :disabled="submitting">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
  </section>

  <section class="form-section">
    <div class="section-heading">
      <h3>Additional information</h3>
      <p>Context that helps during future investment conversations.</p>
    </div>

    <div class="field">
      <label for="investor-address">Address</label>
      <textarea
        id="investor-address"
        v-model="form.address"
        rows="3"
        autocomplete="street-address"
        :disabled="submitting"
      ></textarea>
    </div>

    <div class="field">
      <label for="investor-notes">
        Notes
        <InfoTip label="Internal only — agreements, preferences, anything worth remembering. Never shown to the investor." />
      </label>
      <textarea
        id="investor-notes"
        v-model="form.notes"
        rows="3"
        :disabled="submitting"
      ></textarea>
    </div>
  </section>
</template>

<style scoped>
.form-section + .form-section {
  border-top: 1px solid var(--border);
  margin-top: var(--space-5);
  padding-top: var(--space-5);
}

.section-heading { margin-bottom: var(--space-4); }
.section-heading h3 { font-size: 15px; font-weight: 600; margin: 0; }
.section-heading p { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.field label {
  align-items: center;
  display: flex;
  gap: 5px;
}
</style>