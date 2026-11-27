<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import InlineNotice from '@/components/ui/InlineNotice.vue'
import { getErrorMessage } from '@/utils/errors'

const router = useRouter()

const auth = useAuthStore()
const loginError = ref('')

const form = reactive({
  email: '',
  password: '',
})

const submit = async () => {
  loginError.value = ''
  try {
    await auth.login(form)

    router.push('/')
  } catch (error) {
    loginError.value = getErrorMessage(error, 'Check your email and password, then try again.')
  }
}
</script>

<template>
  <div class="login">
    <aside class="login-aside" aria-hidden="true">
      <div class="aside-brand">
        <span class="aside-mark">T</span>
        <span>Transport ERP</span>
      </div>

      <div class="aside-body">
        <h2>Run estimates, jobs and finances from one place.</h2>
        <p>Quotes, transport jobs, invoices, investors and loans — all connected.</p>
      </div>

      <div class="aside-foot">Finance Management Suite</div>
    </aside>

    <main class="login-main">
      <form @submit.prevent="submit">
        <span class="form-mark" aria-hidden="true">T</span>
        <h2>Welcome back</h2>

        <p class="sub">Sign in to your Transport ERP account</p>

        <InlineNotice
          v-if="loginError"
          class="login-error"
          title="We couldn't sign you in."
          :message="loginError"
        />

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
    </main>
  </div>
</template>

<style scoped>
.login {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
  min-height: 100vh;
}

/* ---------- brand panel ---------- */
.login-aside {
  background:
    radial-gradient(900px 500px at 0% 0%, rgb(59 130 246 / 38%), transparent 60%),
    radial-gradient(700px 500px at 100% 100%, rgb(124 58 237 / 32%), transparent 60%), #0f172a;
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  padding: var(--space-10) 56px;
  position: relative;
}

.login-aside > * {
  position: relative;
  z-index: 1;
}

.aside-brand {
  align-items: center;
  display: flex;
  font-size: var(--text-lg);
  font-weight: var(--font-weight-semibold);
  gap: 12px;
  letter-spacing: -0.01em;
}

.aside-mark,
.form-mark {
  align-items: center;
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  border-radius: 10px;
  box-shadow:
    0 6px 18px rgb(37 99 235 / 45%),
    inset 0 1px 0 rgb(255 255 255 / 25%);
  color: #fff;
  display: inline-flex;
  font-weight: 700;
  height: 38px;
  justify-content: center;
  width: 38px;
}

.aside-body {
  max-width: 460px;
}

.aside-body h2 {
  color: #fff;
  font-size: 34px;
  letter-spacing: -0.03em;
  line-height: 1.15;
}

.aside-body p {
  color: #aab6cc;
  font-size: var(--text-lg);
  line-height: 1.6;
  margin-top: var(--space-4);
}

.aside-foot {
  color: #6b7a96;
  font-size: var(--text-xs);
}

/* ---------- form ---------- */
.login-main {
  align-items: center;
  background: var(--surface);
  display: flex;
  justify-content: center;
  padding: var(--space-6);
}

.login form {
  max-width: var(--login-max-width);
  width: 100%;
}

.form-mark {
  display: none;
  margin-bottom: var(--space-5);
}

.login form h2 {
  font-size: var(--text-2xl);
  letter-spacing: -0.025em;
}

.login .sub {
  color: var(--text-muted);
  margin: var(--space-2) var(--space-0) var(--space-8);
}

.login-error {
  margin-bottom: var(--space-5);
}

.login .field {
  margin-bottom: var(--space-5);
}

.login input {
  min-height: 42px;
}

.login button {
  margin-top: var(--space-2);
  min-height: 42px;
  width: 100%;
}

@media (max-width: 900px) {
  .login {
    grid-template-columns: 1fr;
  }

  .login-aside {
    display: none;
  }

  .login-main {
    background: var(--bg);
  }

  .login form {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    padding: var(--space-8);
  }

  .form-mark {
    display: inline-flex;
  }
}
</style>
