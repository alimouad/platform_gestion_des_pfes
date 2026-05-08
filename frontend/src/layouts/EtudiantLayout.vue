<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api, { clearAuthToken } from '@/services/api'

const router = useRouter()
const route = useRoute()
const expanded = ref(false)
const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))

const nav = [
  { icon: 'fa-house',          label: 'Accueil',         to: '/etudiant/dashboard' },
  { icon: 'fa-compass',        label: 'Projets',         to: '/etudiant/projets' },
  { icon: 'fa-file-signature', label: 'Mes postulations',to: '/etudiant/postulations' },
  { icon: 'fa-cloud-arrow-up', label: 'Mes dépôts',      to: '/etudiant/depots' },
  { icon: 'fa-graduation-cap', label: 'Ma soutenance',   to: '/etudiant/soutenance' },
  { icon: 'fa-user',           label: 'Mon profil',      to: '/etudiant/profil' },
]

const isActive = (to) => route.path.startsWith(to)

async function logout() {
  try { await api.post('/logout') } catch {}
  clearAuthToken()
  localStorage.removeItem('admin_user')
  router.push('/login')
}
</script>

<template>
  <div class="flex min-h-screen" style="background:#f0f3eb">

    <aside
      class="fixed left-0 top-0 z-40 flex h-full flex-col items-center justify-between border-r border-white/60 bg-white/90 py-5 shadow-[4px_0_30px_rgba(100,120,80,0.10)] backdrop-blur-xl transition-all duration-300"
      :class="expanded ? 'w-56' : 'w-[72px]'"
      @mouseenter="expanded = true"
      @mouseleave="expanded = false"
    >
      <div class="flex w-full flex-col items-center gap-1 px-3">
        <div class="flex items-center gap-3 overflow-hidden" :class="expanded ? 'w-full' : 'justify-center'">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-base font-black text-[#4a5e20]">
            <i class="fa-solid fa-seedling"></i>
          </div>
          <div v-show="expanded" class="leading-tight">
            <p class="whitespace-nowrap text-sm font-extrabold text-slate-800">FSBM Univ.</p>
            <p class="whitespace-nowrap text-[10px] font-bold uppercase tracking-widest text-[#6a8a40]">Étudiant</p>
          </div>
        </div>

        <nav class="mt-8 flex w-full flex-col gap-1">
          <router-link
            v-for="item in nav"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 overflow-hidden rounded-2xl px-3 py-3 text-sm font-semibold transition-all duration-150"
            :class="isActive(item.to)
              ? 'bg-[#d6e87a] text-slate-900 shadow-sm'
              : 'text-slate-400 hover:bg-slate-100 hover:text-slate-800'"
          >
            <i :class="`fa-solid ${item.icon} w-5 text-center text-base shrink-0`"></i>
            <span v-show="expanded" class="whitespace-nowrap transition-opacity duration-150">{{ item.label }}</span>
          </router-link>
        </nav>
      </div>

      <div class="w-full px-3">
        <button
          @click="logout"
          class="flex w-full items-center gap-3 overflow-hidden rounded-2xl px-3 py-3 text-sm font-semibold text-slate-400 transition hover:bg-red-50 hover:text-red-500"
        >
          <i class="fa-solid fa-right-from-bracket w-5 shrink-0 text-center text-base"></i>
          <span v-show="expanded" class="whitespace-nowrap">Déconnexion</span>
        </button>
      </div>
    </aside>

    <div class="flex flex-1 flex-col transition-all duration-300" :class="expanded ? 'ml-56' : 'ml-[72px]'">

      <header class="sticky top-0 z-30 flex items-center justify-between border-b border-white/60 bg-white/80 px-6 py-3 backdrop-blur-xl">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm">
            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
            <input class="w-52 bg-transparent text-slate-700 placeholder:text-slate-400 outline-none" placeholder="Rechercher un projet…" />
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50">
            <i class="fa-regular fa-bell text-sm"></i>
          </button>
          <button @click="router.push('/etudiant/profil')"
            class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#d6e87a] text-xs font-black text-slate-700">
              {{ (user.prenom || 'E')[0] }}{{ (user.nom || 'T')[0] }}
            </div>
            <div class="text-right leading-tight">
              <p class="text-xs font-bold text-slate-800">{{ user.prenom }} {{ user.nom }}</p>
              <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                {{ user.etudiant?.niveau || 'Étudiant' }}
              </p>
            </div>
          </button>
        </div>
      </header>

      <main class="flex-1 p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
