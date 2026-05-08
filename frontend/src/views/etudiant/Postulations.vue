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
  <div class="w-full min-h-screen  pb-20">
    <header class="sticky top-0 z-30 w-full backdrop-blur-md border-b border-gray-100 transition-all">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#1e4a49]/5 border border-[#1e4a49]/10 mb-3">
          <span class="text-[10px] font-bold uppercase tracking-widest text-[#1e4a49]">Suivi du mon PFE</span>
        </div>
          <h1 class="text-4xl font-semibold text-gray-900 tracking-tight">Mes postulations</h1>
          <p class="text-sm font-medium text-gray-500">Consultez l'historique et l'avancement de vos candidatures.</p>
        </div>
        
        <div class="flex items-center gap-6 bg-white px-6 py-4 rounded-[2rem] shadow-sm border border-gray-100">
          <div class="flex flex-col items-center">
            <p class="text-2xl font-semibold text-gray-900">{{ items.length }}</p>
            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Total</p>
          </div>
          <div class="w-px h-8 bg-gray-100"></div>
          <div class="flex flex-col items-center">
            <div class="flex items-center gap-1.5">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#D4E98D] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#D4E98D]"></span>
              </span>
              <p class="text-2xl font-semibold text-gray-900">{{ counts['en_attente'] || 0 }}</p>
            </div>
            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Actives</p>
          </div>
        </div>
      </div>
    </header>

    <div class="mt-10 space-y-10">
      <div class="flex flex-wrap items-center gap-3">
        <button @click="filter = ''"
          class="rounded-2xl px-6 py-3 text-xs font-bold uppercase tracking-widest transition-all"
          :class="!filter ? 'bg-gray-900 text-white shadow-xl shadow-gray-200' : 'bg-white text-gray-400 hover:text-gray-900 border border-gray-100'">
          Tous les dossiers
        </button>
        
        <button v-for="(label, key) in statutLabel" :key="key"
          @click="filter = key"
          class="group flex items-center gap-3 rounded-2xl px-6 py-3 text-xs font-bold uppercase tracking-widest transition-all border"
          :class="filter === key ? 'bg-gray-900 text-white shadow-xl shadow-gray-200 border-gray-900' : 'bg-white text-gray-400 hover:border-gray-300 border-gray-100'">
          {{ label }}
          <span class="text-[10px] px-2 py-0.5 rounded-full" :class="filter === key ? 'bg-white/20' : 'bg-gray-50 group-hover:bg-gray-100'">
            {{ counts[key] || 0 }}
          </span>
        </button>
      </div>

      <div class="w-full">
        <div v-if="loading" class="w-full py-32 flex flex-col items-center justify-center space-y-4">
          <div class="w-12 h-12 border-4 border-gray-100 border-t-[#D4E98D] rounded-full animate-spin"></div>
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">Chargement de vos données...</p>
        </div>

        <div v-else-if="filtered.length === 0" class="w-full py-40 rounded-[3rem] border-2 border-dashed border-gray-100 flex flex-col items-center justify-center bg-gray-50/30 text-center">
          <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-6">
             <i class="fa-solid fa-file-circle-exclamation text-3xl text-gray-200"></i>
          </div>
          <h2 class="text-xl font-semibold text-gray-800">Aucun dossier trouvé</h2>
          <p class="text-sm text-gray-500 mt-2 max-w-xs">Il semble qu'aucune candidature ne corresponde à votre sélection actuelle.</p>
          <button @click="router.push('/projets')" class="mt-8 px-8 py-3 bg-gray-900 text-white rounded-2xl text-xs font-bold uppercase tracking-widest hover:bg-[#1e4a49] transition-colors">
            Parcourir les projets
          </button>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
          <article v-for="p in filtered" :key="p.id"
            class="group w-full flex flex-col lg:flex-row lg:items-center gap-8 bg-white p-8 rounded-[2.5rem] border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:shadow-gray-200/40 hover:border-[#D4E98D]/50 hover:-translate-y-1">
            
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.5rem] shadow-inner transition-transform group-hover:rotate-6"
              :class="p.statut === 'accepte' ? 'bg-emerald-50 text-emerald-600'
                : p.statut === 'rejete' ? 'bg-rose-50 text-rose-600'
                : 'bg-[#D4E98D]/10 text-[#1e4a49]'">
              <i :class="`fa-solid ${statutIcon[p.statut]} text-2xl`"></i>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <span class="px-3 py-1 rounded-lg bg-gray-50 text-[10px] font-bold text-[#A3B18A] uppercase tracking-widest border border-gray-100">{{ p.projet?.domaine }}</span>
                <span class="text-[10px] font-semibold text-gray-300 uppercase tracking-widest font-mono">#ID-{{ p.id }}</span>
              </div>
              <h3 class="text-xl font-semibold text-gray-900 truncate group-hover:text-[#1e4a49] transition-colors leading-tight">
                {{ p.projet?.titre || 'Projet de Fin d\'Études' }}
              </h3>
              <div class="flex items-center gap-2 mt-2">
                <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500">
                  {{ p.projet?.professeur?.utilisateur?.nom?.charAt(0) }}
                </div>
                <p class="text-sm text-gray-400 font-medium">
                  Supervisé par <span class="text-gray-700 font-semibold">{{ p.projet?.professeur?.utilisateur?.prenom }} {{ p.projet?.professeur?.utilisateur?.nom }}</span>
                </p>
              </div>
            </div>

            <div class="lg:w-44 px-2 py-4 lg:py-0 border-t lg:border-t-0 lg:border-l border-gray-50">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Date d'envoi</p>
              <p class="text-sm font-semibold text-gray-700">
                {{ new Date(p.date_candidature || p.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}
              </p>
            </div>

            <div class="lg:w-52">
              <div class="flex items-center justify-center lg:justify-start gap-3 rounded-[1.25rem] py-4 px-6 transition-all"
                :class="p.statut === 'accepte' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                  : p.statut === 'rejete' ? 'bg-rose-50 text-rose-700 border border-rose-100'
                  : 'bg-gray-50 text-gray-600 border border-gray-100'">
                <div class="w-2 h-2 rounded-full" :class="p.statut === 'accepte' ? 'bg-emerald-500' : p.statut === 'rejete' ? 'bg-rose-500' : 'bg-gray-300'"></div>
                <span class="text-[11px] font-bold uppercase tracking-widest">{{ statutLabel[p.statut] || p.statut }}</span>
              </div>
            </div>

            <div class="flex justify-end lg:w-24">
              <button v-if="p.statut === 'en_attente'" @click="annuler(p.id)"
                class="h-12 w-12 rounded-[1.25rem] flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 border border-transparent hover:border-rose-100 transition-all group/btn"
                title="Annuler ma postulation">
                <i class="fa-solid fa-trash-can text-base transition-transform group-hover/btn:scale-110"></i>
              </button>
              <button v-else @click="viewDetails(p.id)" class="h-12 w-12 rounded-[1.25rem] flex items-center justify-center text-gray-400 hover:text-[#1e4a49] hover:bg-[#D4E98D]/10 border border-transparent hover:border-[#D4E98D]/20 transition-all">
                <i class="fa-solid fa-arrow-right text-base"></i>
              </button>
            </div>
          </article>
        </div>
      </div>
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