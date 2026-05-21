<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import apiClient, { setAuthToken } from '@/services/api'

const router = useRouter()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const loginError = ref('')

async function submit() {
  loginError.value = ''
  if (!email.value || !password.value) return
  loading.value = true
  try {
    const res = await apiClient.post('/login', {
      courriel: email.value,
      mot_de_passe: password.value,
    })
    if (res.data?.token) {
      setAuthToken(res.data.token)
      const me = await apiClient.get('/me')
      const user = me.data?.data
      localStorage.setItem('admin_user', JSON.stringify(user || {}))
      const routes = {
        superadmin: '/admin/dashboard',
        coordinateur: '/coordinateur/dashboard',
        professeur: '/professeur/dashboard',
        etudiant: '/etudiant/dashboard',
      }
      router.push(routes[user?.role] || '/')
    }
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Email ou mot de passe incorrect.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen w-full font-sans antialiased relative overflow-hidden flex items-center justify-center" style="background: linear-gradient(135deg, #0f2a29 0%, #1e4a49 50%, #2d6b5e 100%)">

    <!-- animated background blobs -->
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
      <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full opacity-10" style="background: radial-gradient(circle, #d6e87a 0%, transparent 70%)"></div>
      <div class="absolute bottom-[-15%] left-[-8%] w-[500px] h-[500px] rounded-full opacity-8" style="background: radial-gradient(circle, #d6e87a 0%, transparent 70%)"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full opacity-5" style="background: radial-gradient(circle, #ffffff 0%, transparent 70%)"></div>
    </div>

    <!-- grid overlay -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.03]"
      style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px"></div>

    <!-- card -->
    <div class="relative z-10 w-full max-w-[420px] mx-4">

      <!-- top badge -->
      <div class="flex justify-center mb-8">
        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 backdrop-blur-sm">
          <div class="h-2 w-2 rounded-full bg-[#d6e87a] animate-pulse"></div>
          <span class="text-[11px] font-bold uppercase tracking-widest text-white/60">Plateforme PFE · GAGE 2024-2026</span>
        </div>
      </div>

      <!-- logo + title -->
      <div class="text-center mb-10">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-[#d6e87a] text-[#1e4a49] text-2xl shadow-2xl shadow-[#d6e87a]/20 mb-5">
          <i class="fa-solid fa-seedling"></i>
        </div>
        <h1 class="text-3xl font-black text-white tracking-tight">Bienvenue</h1>
        <p class="mt-2 text-sm text-white/40 font-medium">FSBM · Université Hassan II de Casablanca</p>
      </div>

      <!-- glass form card -->
      <div class="rounded-3xl border border-white/10 bg-white/8 p-8 shadow-2xl backdrop-blur-xl">

        <form @submit.prevent="submit" novalidate class="space-y-5">

          <!-- email -->
          <div>
            <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-white/40">
              Adresse e-mail
            </label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                v-model="email"
                type="email"
                placeholder="prenom.nom@fsbm.ac.ma"
                required
                class="w-full rounded-2xl border border-white/10 bg-white/5 py-3.5 pl-11 pr-4 text-sm font-semibold text-white placeholder:text-white/20 outline-none transition-all focus:border-[#d6e87a]/60 focus:bg-white/10 focus:shadow-[0_0_0_3px_rgba(214,232,122,0.1)]"
              />
            </div>
          </div>

          <!-- password -->
          <div>
            <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-white/40">
              Mot de passe
            </label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                required
                class="w-full rounded-2xl border border-white/10 bg-white/5 py-3.5 pl-11 pr-12 text-sm font-semibold text-white placeholder:text-white/20 outline-none transition-all focus:border-[#d6e87a]/60 focus:bg-white/10 focus:shadow-[0_0_0_3px_rgba(214,232,122,0.1)]"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/20 hover:text-white/60 transition-colors">
                <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
              </button>
            </div>
          </div>

          <!-- error -->
          <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 scale-95"
            leave-active-class="transition-all duration-200"
            leave-to-class="opacity-0 scale-95"
          >
            <div v-if="loginError"
              class="flex items-center gap-3 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3">
              <i class="fa-solid fa-circle-exclamation text-red-400 text-sm shrink-0"></i>
              <p class="text-sm font-semibold text-red-300">{{ loginError }}</p>
            </div>
          </Transition>

          <!-- submit -->
          <button
            type="submit"
            :disabled="loading || !email || !password"
            class="group relative w-full overflow-hidden rounded-2xl py-4 text-sm font-black text-[#1e4a49] shadow-lg transition-all hover:shadow-[#d6e87a]/30 hover:shadow-xl active:scale-[0.99] disabled:opacity-40 disabled:cursor-not-allowed"
            style="background: #d6e87a"
          >
            <span class="flex items-center justify-center gap-3">
              <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
              <i v-else class="fa-solid fa-right-to-bracket transition-transform group-hover:translate-x-0.5"></i>
              {{ loading ? 'Connexion en cours…' : 'Se connecter' }}
            </span>
          </button>
        </form>

        <!-- divider -->
        <div class="my-7 flex items-center gap-3">
          <div class="h-px flex-1 bg-white/10"></div>
          <span class="text-[10px] font-bold uppercase tracking-widest text-white/20">Espaces</span>
          <div class="h-px flex-1 bg-white/10"></div>
        </div>

        <!-- roles -->
        <div class="grid grid-cols-4 gap-2">
          <div v-for="r in [
            { icon: 'fa-user-shield',    label: 'Admin',    color: '#a78bfa' },
            { icon: 'fa-user-tie',        label: 'Coord.',   color: '#60a5fa' },
            { icon: 'fa-chalkboard-user', label: 'Prof.',    color: '#d6e87a' },
            { icon: 'fa-user-graduate',   label: 'Étud.',    color: '#fb923c' },
          ]" :key="r.label"
            class="flex flex-col items-center gap-1.5 rounded-2xl border border-white/8 bg-white/5 py-3 px-1">
            <i :class="`fa-solid ${r.icon} text-base`" :style="`color: ${r.color}`"></i>
            <span class="text-[9px] font-bold text-white/40">{{ r.label }}</span>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-[10px] text-white/25 font-medium">
        Faculté des Sciences Ben M'Sick · © 2025
      </p>
    </div>
  </div>
</template>

<style scoped>
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px white inset;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
