<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()

const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const submit = async () => {
  try {
    await auth.login(form)

    router.push('/')
  } catch (error) {
    alert(error.response?.data?.message || 'Login failed')
  }
}
</script>

<template>
  <div class="login">
    <form @submit.prevent="submit">
      <h2>Transport ERP</h2>

      <p class="sub">Sign in to continue</p>

      <div class="field">
        <label>Email</label>
        <input type="email" v-model="form.email" placeholder="you@company.com" />
      </div>

      <div class="field">
        <label>Password</label>
        <input type="password" v-model="form.password" placeholder="••••••••" />
      </div>

      <button type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login {
  align-items: center;
  display: flex;
  justify-content: center;
  min-height: 100vh;
  padding: var(--space-6);
}

.login form {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  max-width: var(--login-max-width);
  padding: var(--space-8);
  width: 100%;
}

.login h2,
.login .sub {
  text-align: center;
}

.login .sub {
  color: var(--text-muted);
  margin: var(--space-2) var(--space-0) var(--space-6);
}

.login button {
  width: 100%;
}
</style>
