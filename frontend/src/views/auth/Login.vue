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
  <div class="min-h-screen w-full font-sans antialiased flex" style="background:#f5f5f0">

    <!-- LEFT — illustration panel -->
    <div class="hidden lg:flex flex-col w-[45%] min-h-screen relative overflow-hidden" style="background:#f0f5e0">

      <!-- decorative shapes -->
      <div class="absolute top-0 left-0 w-full h-full">
        <div class="absolute top-16 right-12 w-64 h-64 rounded-full border-[40px] border-[#d6e87a]/30"></div>
        <div class="absolute bottom-24 left-8 w-40 h-40 rounded-full border-[28px] border-[#1e4a49]/10"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-[#1e4a49]/5"></div>
        <div class="absolute top-1/3 left-8 w-4 h-4 rounded-full bg-[#d6e87a]"></div>
        <div class="absolute bottom-1/3 right-16 w-3 h-3 rounded-full bg-[#1e4a49]/20"></div>
        <div class="absolute top-2/3 left-1/3 w-2 h-2 rounded-full bg-[#d6e87a]/60"></div>
      </div>

      <!-- content -->
      <div class="relative flex-1 flex flex-col justify-between px-14 py-14">

        <!-- top logo -->
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#1e4a49]">
            <i class="fa-solid fa-seedling text-[#d6e87a] text-sm"></i>
          </div>
          <div>
            <p class="font-black text-[#1e4a49] text-sm leading-none">FSBM</p>
            <p class="text-[10px] font-bold text-[#1e4a49]/40 uppercase tracking-widest">Hassan II · Casablanca</p>
          </div>
        </div>

        <!-- center illustration area -->
        <div class="space-y-8">
          <!-- big number + label -->
          <div>
            <div class="text-[120px] font-black leading-none tracking-tighter" style="color:#1e4a49; opacity:0.07">PFE</div>
            <div class="-mt-6 space-y-2">
              <h2 class="text-4xl font-black text-[#1e4a49] leading-tight tracking-tight">
                Plateforme de<br/>gestion PFE
              </h2>
              <p class="text-sm text-[#1e4a49]/50 font-medium max-w-xs leading-relaxed">
                Master Géomatique Appliquée aux Géosciences et Environnement · 2024–2026
              </p>
            </div>
          </div>

          <!-- feature cards -->
          <div class="space-y-3">
            <div v-for="f in [
              { icon: 'fa-folder-open',     text: 'Gestion complète du cycle PFE', sub: 'De la soumission à la soutenance' },
              { icon: 'fa-map-location-dot',text: 'Carte SIG interactive',          sub: 'Visualisation des zones d\'étude' },
              { icon: 'fa-comments',         text: 'Messagerie intégrée',            sub: 'Communication étudiant–encadrant' },
            ]" :key="f.text"
              class="flex items-center gap-4 rounded-2xl bg-white/60 px-5 py-3.5 backdrop-blur-sm border border-white">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1e4a49]">
                <i :class="`fa-solid ${f.icon} text-[#d6e87a] text-sm`"></i>
              </div>
              <div>
                <p class="text-sm font-black text-[#1e4a49]">{{ f.text }}</p>
                <p class="text-[11px] text-[#1e4a49]/40 font-medium">{{ f.sub }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- bottom stats -->
        <div class="flex gap-6">
          <div v-for="s in [{ n:'2635', l:'Étudiants' },{ n:'29', l:'Professeurs' },{ n:'84', l:'Projets' }]" :key="s.l">
            <p class="text-2xl font-black text-[#1e4a49]">{{ s.n }}</p>
            <p class="text-[11px] font-bold text-[#1e4a49]/40 uppercase tracking-widest">{{ s.l }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT — form -->
    <div class="flex flex-1 items-center justify-center px-6 py-12 lg:px-16">
      <div class="w-full max-w-sm">

        <!-- mobile logo -->
        <div class="mb-10 flex items-center gap-3 lg:hidden">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
            <i class="fa-solid fa-seedling text-[#d6e87a] text-sm"></i>
          </div>
          <span class="font-black text-[#1e4a49] text-base">FSBM · Plateforme PFE</span>
        </div>

        <!-- heading -->
        <div class="mb-10">
          <div class="inline-flex items-center gap-2 rounded-full bg-[#d6e87a]/30 px-3 py-1.5 mb-5">
            <span class="h-1.5 w-1.5 rounded-full bg-[#4a7a30]"></span>
            <span class="text-[11px] font-black uppercase tracking-widest text-[#4a7a30]">Accès sécurisé</span>
          </div>
          <h1 class="text-3xl font-black text-[#1e4a49] tracking-tight">Connexion</h1>
          <p class="mt-2 text-sm text-slate-400 font-medium">Identifiants institutionnels requis.</p>
        </div>

        <!-- form -->
        <form @submit.prevent="submit" novalidate class="space-y-4">

          <!-- email -->
          <div>
            <label class="mb-2 block text-[11px] font-black uppercase tracking-widest text-slate-400">E-mail</label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input
                v-model="email"
                type="email"
                placeholder="prenom.nom@fsbm.ac.ma"
                required
                class="w-full rounded-2xl border-2 border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-800 placeholder:text-slate-300 outline-none transition-all focus:border-[#1e4a49] focus:shadow-[0_0_0_4px_rgba(30,74,73,0.08)]"
              />
            </div>
          </div>

          <!-- password -->
          <div>
            <label class="mb-2 block text-[11px] font-black uppercase tracking-widest text-slate-400">Mot de passe</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                required
                class="w-full rounded-2xl border-2 border-slate-200 bg-white py-3.5 pl-11 pr-12 text-sm font-semibold text-slate-800 placeholder:text-slate-300 outline-none transition-all focus:border-[#1e4a49] focus:shadow-[0_0_0_4px_rgba(30,74,73,0.08)]"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 transition-colors">
                <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
              </button>
            </div>
          </div>

          <!-- error -->
          <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1"
            leave-active-class="transition-all duration-200" leave-to-class="opacity-0">
            <div v-if="loginError" class="flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3">
              <i class="fa-solid fa-circle-exclamation text-red-400 text-sm shrink-0"></i>
              <p class="text-sm font-semibold text-red-600">{{ loginError }}</p>
            </div>
          </Transition>

          <!-- submit -->
          <button type="submit" :disabled="loading || !email || !password"
            class="group w-full rounded-2xl py-4 text-sm font-black text-white shadow-lg transition-all hover:shadow-xl hover:shadow-[#1e4a49]/20 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            style="background: #1e4a49">
            <span class="flex items-center justify-center gap-3">
              <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
              <i v-else class="fa-solid fa-arrow-right-to-bracket transition-transform group-hover:translate-x-0.5"></i>
              {{ loading ? 'Connexion…' : 'Se connecter' }}
            </span>
          </button>
        </form>

        <!-- roles -->
        <div class="mt-10">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-4">Espaces disponibles</p>
          <div class="grid grid-cols-2 gap-2">
            <div v-for="r in [
              { icon: 'fa-user-shield',    label: 'Administrateur', bg: 'bg-violet-50', text: 'text-violet-600', dot: 'bg-violet-400' },
              { icon: 'fa-user-tie',        label: 'Coordinateur',  bg: 'bg-sky-50',    text: 'text-sky-600',    dot: 'bg-sky-400' },
              { icon: 'fa-chalkboard-user', label: 'Professeur',    bg: 'bg-[#f0f5e0]', text: 'text-[#4a7a30]',  dot: 'bg-[#d6e87a]' },
              { icon: 'fa-user-graduate',   label: 'Étudiant',      bg: 'bg-orange-50', text: 'text-orange-600', dot: 'bg-orange-400' },
            ]" :key="r.label"
              class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white px-3.5 py-3">
              <span class="h-2 w-2 rounded-full shrink-0" :class="r.dot"></span>
              <span class="text-xs font-bold text-slate-700 truncate">{{ r.label }}</span>
            </div>
          </div>
        </div>

        <p class="mt-8 text-center text-[10px] text-slate-300">
          Faculté des Sciences Ben M'Sick · Université Hassan II de Casablanca
        </p>
      </div>
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
