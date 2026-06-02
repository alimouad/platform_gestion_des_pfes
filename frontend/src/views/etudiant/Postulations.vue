<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const items = ref([])
const loading = ref(false)
const filter = ref('')

async function fetchAll() {
  loading.value = true
  try {
    const res = await api.get('/postulations')
    items.value = res.data.data.filter(p => p.etudiant_id === etudiantId.value)
  } catch {}
  loading.value = false
}

const filtered = computed(() => {
  if (!filter.value) return items.value
  return items.value.filter(p => p.statut === filter.value)
})

const counts = computed(() => ({
  en_attente: items.value.filter(p => p.statut === 'en_attente').length,
  accepte:    items.value.filter(p => p.statut === 'accepte').length,
  rejete:     items.value.filter(p => p.statut === 'rejete').length,
}))

async function annuler(id) {
  if (!confirm('Annuler cette postulation ?')) return
  try {
    await api.delete(`/postulations/${id}`)
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

const statutLabel = { en_attente: 'En attente', accepte: 'Acceptée', rejete: 'Rejetée' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', accepte: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }
const statutIcon  = { en_attente: 'fa-clock', accepte: 'fa-circle-check', rejete: 'fa-circle-xmark' }

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6 pb-12">

    <!-- Hero banner -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-7 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Suivi de candidatures</p>
          <h1 class="mt-1 text-2xl font-black">Mes postulations</h1>
          <p class="mt-1 text-sm text-white/60">{{ items.length }} candidature{{ items.length !== 1 ? 's' : '' }} au total</p>
        </div>
        <!-- KPI chips -->
        <div class="flex items-center gap-3">
          <div class="rounded-2xl bg-white/10 px-5 py-3 text-center">
            <p class="text-2xl font-black text-white">{{ counts.en_attente }}</p>
            <p class="text-[10px] font-bold text-white/50 uppercase tracking-wide">En attente</p>
          </div>
          <div class="rounded-2xl bg-[#d6e87a]/15 px-5 py-3 text-center">
            <p class="text-2xl font-black text-[#d6e87a]">{{ counts.accepte }}</p>
            <p class="text-[10px] font-bold text-[#d6e87a]/60 uppercase tracking-wide">Acceptées</p>
          </div>
          <div class="rounded-2xl bg-red-400/10 px-5 py-3 text-center">
            <p class="text-2xl font-black text-red-300">{{ counts.rejete }}</p>
            <p class="text-[10px] font-bold text-red-300/60 uppercase tracking-wide">Rejetées</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter pills -->
    <div class="flex flex-wrap gap-2">
      <button @click="filter = ''"
        class="rounded-2xl px-5 py-2.5 text-sm font-bold transition"
        :class="!filter ? 'bg-[#1e4a49] text-[#d6e87a]' : 'bg-white/90 text-slate-600 hover:bg-slate-100 border border-white/70'">
        Toutes
      </button>
      <button v-for="(label, key) in statutLabel" :key="key"
        @click="filter = key"
        class="flex items-center gap-2 rounded-2xl px-5 py-2.5 text-sm font-bold transition border"
        :class="filter === key ? 'bg-[#1e4a49] text-[#d6e87a] border-transparent' : 'bg-white/90 text-slate-600 hover:bg-slate-100 border-white/70'">
        {{ label }}
        <span class="rounded-md px-1.5 py-0.5 text-[10px]" :class="filter === key ? 'bg-white/20' : 'bg-slate-100'">{{ counts[key] || 0 }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">
      <i class="fa-solid fa-circle-notch fa-spin text-2xl text-[#d6e87a] mb-3 block"></i>Chargement…
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
      <i class="fa-solid fa-file-signature text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucune postulation</p>
      <p class="mt-1 text-sm text-slate-400">Explorez les projets disponibles et postulez.</p>
      <router-link to="/etudiant/projets" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-6 py-2.5 text-sm font-black text-white hover:bg-[#163836] transition">
        <i class="fa-solid fa-compass"></i> Voir les projets
      </router-link>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <article v-for="p in filtered" :key="p.id"
        class="group flex flex-wrap items-center gap-5 rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg">

        <!-- Status icon -->
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl transition-transform group-hover:scale-105"
          :class="p.statut === 'accepte' ? 'bg-green-100 text-green-600'
            : p.statut === 'rejete'  ? 'bg-red-100 text-red-500'
            : 'bg-[#d6e87a]/30 text-[#1e4a49]'">
          <i :class="`fa-solid ${statutIcon[p.statut]} text-xl`"></i>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-50">
          <div class="flex flex-wrap items-center gap-2 mb-1">
            <span v-if="p.projet?.domaine" class="rounded-lg bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 uppercase tracking-wide">
              {{ p.projet.domaine }}
            </span>
            <span class="text-[10px] text-slate-300 font-mono">#{{ p.id }}</span>
          </div>
          <h3 class="text-sm font-black text-slate-900 leading-snug line-clamp-1 group-hover:text-[#1e4a49] transition">
            {{ p.projet?.titre || '—' }}
          </h3>
          <p class="mt-0.5 text-xs text-slate-400">
            <i class="fa-solid fa-chalkboard-user mr-1"></i>
            {{ p.projet?.professeur?.utilisateur?.prenom }} {{ p.projet?.professeur?.utilisateur?.nom }}
          </p>
        </div>

        <!-- Date -->
        <div class="hidden md:block text-right shrink-0">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Postulé le</p>
          <p class="text-sm font-bold text-slate-700">
            {{ new Date(p.date_candidature || p.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) }}
          </p>
        </div>

        <!-- Status badge -->
        <div class="flex items-center gap-2 rounded-2xl px-4 py-2.5 shrink-0"
          :class="p.statut === 'accepte' ? 'bg-green-50 border border-green-100'
            : p.statut === 'rejete'  ? 'bg-red-50 border border-red-100'
            : 'bg-slate-50 border border-slate-100'">
          <span class="h-2 w-2 rounded-full"
            :class="p.statut === 'accepte' ? 'bg-green-500' : p.statut === 'rejete' ? 'bg-red-400' : 'bg-amber-400'"></span>
          <span class="text-[11px] font-black uppercase tracking-widest"
            :class="p.statut === 'accepte' ? 'text-green-700' : p.statut === 'rejete' ? 'text-red-600' : 'text-amber-700'">
            {{ statutLabel[p.statut] }}
          </span>
        </div>

        <!-- Action -->
        <button v-if="p.statut === 'en_attente'" @click="annuler(p.id)"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-300 hover:bg-red-50 hover:text-red-500 transition"
          title="Annuler">
          <i class="fa-solid fa-trash-can"></i>
        </button>
        <router-link v-else-if="p.statut === 'accepte'" to="/etudiant/mon-projet"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a]/20 text-[#1e4a49] hover:bg-[#d6e87a]/40 transition">
          <i class="fa-solid fa-arrow-right text-sm"></i>
        </router-link>
        <div v-else class="h-10 w-10 shrink-0"></div>
      </article>
    </div>
  </div>
</template>

<style scoped>
/* Antialiasing for better "Semi-Bold" rendering */
* {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Entrance animation with a slightly smoother curve */
article {
  animation: slideUpFade 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

@keyframes slideUpFade {
  from { 
    opacity: 0; 
    transform: translateY(30px); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0); 
  }
}

/* Stagger delay for the cards */
article:nth-child(1) { animation-delay: 0.1s; }
article:nth-child(2) { animation-delay: 0.15s; }
article:nth-child(3) { animation-delay: 0.2s; }

/* Custom Scrollbar for a more integrated feel */
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: #fcfdfc;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>